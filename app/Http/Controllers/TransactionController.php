<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Reseller;
use App\Models\Product;
use App\Models\ProductVariant;
use mishagp\OCRmyPDF\OCRmyPDF;
use App\Models\User;
use App\Helpers\RoleHelper;

class TransactionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $resellers = is_distributor_or_admin(auth()->user())
            ? User::whereHas('role', function($query) {
                $query->where('name', 'reseller');
            })->get()
            : [];
        $transactions = Transaction::with('user')
            ->orderByDesc('created_at')
            ->paginate(15);
        // Hitung jumlah transaksi per status
        $statusCounts = [
            'pending' => Transaction::where('status', 'pending')->count(),
            'processed' => Transaction::where('status', 'processed')->count(),
            'packed' => Transaction::where('status', 'packed')->count(),
            'shipped' => Transaction::where('status', 'shipped')->count(),
        ];
        return view('pages.transactions.index', compact('transactions', 'resellers', 'statusCounts'));
    }

    public function create()
    {
        $resellers = User::whereHas('role', function($query) {
            $query->where('name', 'reseller');
            if (is_distributor(auth()->user())) {
                $query->where('parent_id', auth()->user()->id);
            }
        })->get()->map(function($reseller) {
            return [
                'id' => $reseller->id,
                'name' => $reseller->name,
                'email' => $reseller->email,
                'profile_photo_url' => str_replace('\\', '/', $reseller->profile_photo_url)
            ];
        });
        $products = Product::with('variants.attributeValues')->get();
        return view('pages.transactions.create', compact('resellers', 'products'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $rules = [
            'shipments' => 'required|array|min:1',
            'shipments.*.shipping_number' => 'required|string|max:255',
            'shipments.*.description' => 'required|string',
            'shipments.*.items' => 'required|array|min:1',
            'shipments.*.items.*.variant_id' => 'required|exists:product_variants,id',
            'shipments.*.items.*.quantity' => 'required|integer|min:1',
            'shipping_pdf_path' => 'required|string',
        ];
        if (is_distributor_or_admin($user)) {
            $rules['user_id'] = 'required|exists:users,id';
        }
        $validated = $request->validate($rules);

        foreach ($validated['shipments'] as $shipment) {
            // Skip if shipping_number already exists
            if (Transaction::where('shipping_number', $shipment['shipping_number'])->exists()) {
                continue;
            }
            $transaction = Transaction::create([
                'transaction_code' => 'TRX-' . strtoupper(uniqid()),
                'user_id' => $validated['user_id'] ?? $user->id,
                'shipping_number' => $shipment['shipping_number'],
                'total_paid' => 0,
                'total_price' => 0,
                'description' => $shipment['description'],
                'shipping_pdf_path' => $validated['shipping_pdf_path'],
            ]);
            foreach ($shipment['items'] as $item) {
                $variant = ProductVariant::with('product')->findOrFail($item['variant_id']);
                $transaction->items()->create([
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'factory_price' => $variant->product->factory_price,
                    'distributor_price' => $variant->product->distributor_price,
                    'reseller_price' => $variant->product->reseller_price,
                    'retail_price' => $variant->retail_price,
                ]);
            }
            $totalPrice = $transaction->items->sum(function ($item) {
                return $item->quantity * $item->retail_price;
            });
            $transaction->update(['total_price' => $totalPrice]);
        }

        return response()->json(['success' => true, 'message' => __('common.transaction.created_successfully')]);
    }

    public function downloadPdf(Transaction $transaction)
    {
        return response()->download(storage_path('app/' . $transaction->shipping_pdf_path));
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return back()->with('success', __('common.transaction.deleted_successfully'));
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }
        if (is_array($ids) && count($ids)) {
            Transaction::whereIn('id', $ids)->delete();
            return back()->with('success', __('common.transaction.deleted_successfully'));
        }
        return back()->with('error', __('No transactions selected.'));
    }

    public function edit(Transaction $transaction)
    {
        $resellers = User::whereHas('role', function($query) {
            $query->where('name', 'reseller');
        })->get()->map(function($reseller) {
            return [
                'id' => $reseller->id,
                'name' => $reseller->name,
                'email' => $reseller->email,
                'profile_photo_url' => str_replace('\\', '/', $reseller->profile_photo_url)
            ];
        });
        $products = Product::with('variants.attributeValues')->get();
        $transaction->load('items.variant');
        return view('pages.transactions.edit', compact('transaction', 'resellers', 'products'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $user = Auth::user();
        $rules = [
            'shipping_number' => 'required|string|max:255',
            'description' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
        if (is_distributor_or_admin($user)) {
            $rules['user_id'] = 'required|exists:users,id';
        }
        $validated = $request->validate($rules);
        $transaction->update([
            'user_id' => is_distributor_or_admin($user) ? $request->user_id : $transaction->user_id,
            'shipping_number' => $request->shipping_number,
            'description' => $request->description,
        ]);
        // Remove old items
        $transaction->items()->delete();
        // Add new items
        foreach ($request->items as $item) {
            $variant = ProductVariant::findOrFail($item['variant_id']);
            $transaction->items()->create([
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'factory_price' => $variant->product->factory_price,
                'distributor_price' => $variant->product->distributor_price,
                'reseller_price' => $variant->product->reseller_price,
                'retail_price' => $variant->retail_price,
            ]);
        }
        // Recalculate total price
        $totalPrice = $transaction->items->sum(function ($item) {
            return $item->quantity * $item->retail_price;
        });
        $transaction->update(['total_price' => $totalPrice]);
        return redirect()->route('transactions.index')
            ->with('success', __('common.form.edit_title', ['item' => __('common.transaction.title')]));
    }

    public function parsePdf(Request $request)
    {
        $request->validate(['shippingPdf' => 'required|file|mimes:pdf']);
        $path = $request->file('shippingPdf')->store('shipping_pdfs');
        $inputPath = storage_path('app/' . $path);

        $text = \Spatie\PdfToText\Pdf::getText($inputPath);
        $pages = preg_split('/\f/', $text);
        $allText = implode("\n", $pages);

        $blocks = preg_split('/(?=No\.\s*Resi:|Resi:)/i', $allText);

        $shipments = [];
        $variants = \App\Models\ProductVariant::with(['product', 'attributeValues'])->get();

        foreach ($blocks as $block) {
            if (!trim($block)) continue;

            // Ambil nomor resi
            if (preg_match('/Resi:\s*([A-Z0-9]+)/i', $block, $m)) {
                $shippingNumber = $m[1];
            } elseif (preg_match('/No\.\s*Resi:\s*([A-Z0-9]+)/i', $block, $m)) {
                $shippingNumber = $m[1];
            } else {
                continue;
            }

            if (!isset($shipments[$shippingNumber])) {
                $shipments[$shippingNumber] = [
                    'shipping_number' => $shippingNumber,
                    'recipient' => null,
                    'sender' => null,
                    'items' => [],
                    'description' => $block,
                    'reseller' => null,
                ];
            } else {
                $shipments[$shippingNumber]['description'] .= "\n" . $block;
            }

            // Penerima & Pengirim
            $recipient = null;
            $senderName = null;
            if (preg_match('/Penerima:([^\n]+)/i', $block, $m)) {
                $raw = trim($m[1]);
                if (strpos($raw, 'Pengirim:') !== false) {
                    $parts = explode('Pengirim:', $raw);
                    $recipient = trim($parts[0]);
                    $senderName = trim($parts[1]);
                } else {
                    $recipient = $raw;
                }
            }
            if (!$senderName && preg_match('/Pengirim:([^\n]+)/i', $block, $m)) {
                $senderName = trim($m[1]);
            }
            if (!$shipments[$shippingNumber]['recipient'] && $recipient) {
                $shipments[$shippingNumber]['recipient'] = $recipient;
            }
            if (!$shipments[$shippingNumber]['sender'] && $senderName) {
                $shipments[$shippingNumber]['sender'] = $senderName;
            }

            // Cari reseller jika belum ada
            if (!$shipments[$shippingNumber]['reseller'] && $senderName) {
                $reseller = \App\Models\User::whereHas('role', function($query) {
                    $query->where('name', 'reseller');
                })->where('name', 'like', '%' . $senderName . '%')->first();
                if ($reseller) {
                    $shipments[$shippingNumber]['reseller'] = [
                        'id' => $reseller->id,
                        'name' => $reseller->name,
                        'email' => $reseller->email,
                        'profile_photo_url' => str_replace('\\', '/', $reseller->profile_photo_url)
                    ];
                }
            }

            // --- PARSING ITEM ---
            $lines = preg_split('/\r\n|\r|\n/', $block);
            $foundItem = false;

            // 1. Parsing berdasarkan label (SKU, Variasi, Qty) dengan blok produk multi-baris
            for ($i = 0; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if (stripos($line, 'SKU') === 0) {
                    $sku = '';
                    $pdfVariation = '';
                    $qty = 1;
                    // Jika baris berikutnya label Variasi, ambil beberapa baris setelahnya sebagai blok produk
                    if (isset($lines[$i + 1]) && stripos($lines[$i + 1], 'Variasi') === 0) {
                        $productBlock = [];
                        for ($j = $i + 2; $j < count($lines); $j++) {
                            $val = trim($lines[$j]);
                            if ($val === '' || stripos($val, 'Qty') === 0) break;
                            $productBlock[] = $val;
                        }
                        $productText = implode(' ', $productBlock);
                        // Coba deteksi SKU di dalam productText
                        if (preg_match('/([A-Z]_)?(BDMSBR|GUNUNG|GNG-\d+|AD-\d+)[^,]*,?([0-9]{2})?/i', $productText, $m)) {
                            $sku = isset($m[2]) ? trim($m[2]) : '';
                            $pdfVariation = $productText;
                            // Normalisasi SKU
                            $sku = preg_replace('/[^A-Z0-9-]/i', '', $sku);
                        }
                        // Cari qty setelah blok produk
                        for ($j = $i + 2 + count($productBlock); $j < min($i + 10, count($lines)); $j++) {
                            if (stripos($lines[$j], 'Qty') === 0 && isset($lines[$j + 1]) && is_numeric(trim($lines[$j + 1]))) {
                                $qty = (int) trim($lines[$j + 1]);
                                break;
                            }
                        }
                    }
                    // Jika baris berikutnya bukan label Variasi, gunakan logika lama
                    elseif (isset($lines[$i + 1]) && preg_match('/([A-Z]_)?(BDMSBR|GUNUNG|GNG-\d+|AD-\d+)/i', $lines[$i + 1], $mSku)) {
                        $sku = isset($mSku[2]) ? trim($mSku[2]) : '';
                        $pdfVariation = '';
                        $qty = 1;
                        // Normalisasi SKU
                        $sku = preg_replace('/[^A-Z0-9-]/i', '', $sku);
                        // Cari variasi dan qty seperti sebelumnya
                        for ($j = $i + 2; $j < min($i + 6, count($lines)); $j++) {
                            if (stripos($lines[$j], 'Variasi') === 0 && isset($lines[$j + 1])) {
                                $pdfVariation = trim($lines[$j + 1]);
                            }
                            if (stripos($lines[$j], 'Qty') === 0 && isset($lines[$j + 1]) && is_numeric(trim($lines[$j + 1]))) {
                                $qty = (int) trim($lines[$j + 1]);
                            }
                        }
                    }
                    // Query variant dari DB
                    if ($sku) {
                        $matchedVariant = $variants->first(function($variant) use ($sku) {
                            return stripos($variant->sku, $sku) !== false;
                        });
                        if ($matchedVariant) {
                            $shipments[$shippingNumber]['items'][] = [
                                'variant_id' => $matchedVariant->id,
                                'sku' => $matchedVariant->sku,
                                'product' => $matchedVariant->product->name,
                                'variation' => $matchedVariant->attributeValues->pluck('value')->implode(', '),
                                'image_url' => $matchedVariant->product->image_url,
                                'qty' => $qty ?: 1,
                                'dropdownOpen' => false,
                            ];
                            $foundItem = true;
                        }
                    }
                }
            }

            // 2. Fallback: regex di seluruh blok jika belum ketemu
            if (!$foundItem) {
                foreach ($lines as $i => $line) {
                    $line = trim($line);
                    // Pola: [SKU] ... [variasi],[size]
                    if (preg_match('/([A-Z]_)?(BDMSBR|GUNUNG|GNG-\d+|AD-\d+)\s*([a-zA-Z ]*),?(\d{2})?/i', $line, $m)) {
                        $sku = isset($m[2]) ? trim($m[2]) : '';
                        $pdfVariation = isset($m[3]) ? trim($m[3]) : '';
                        $size = isset($m[4]) ? trim($m[4]) : '';
                        $qty = 1;
                        // Normalisasi SKU
                        $sku = preg_replace('/[^A-Z0-9-]/i', '', $sku);
                        // Cari qty di baris berikutnya
                        for ($j = $i + 1; $j < min($i + 4, count($lines)); $j++) {
                            if (preg_match('/Qty/i', $lines[$j]) && isset($lines[$j + 1]) && is_numeric(trim($lines[$j + 1]))) {
                                $qty = (int) trim($lines[$j + 1]);
                                break;
                            }
                            if (is_numeric(trim($lines[$j]))) {
                                $qty = (int) trim($lines[$j]);
                                break;
                            }
                        }
                        // Query variant dari DB
                        $matchedVariant = $variants->first(function($variant) use ($sku) {
                            return stripos($variant->sku, $sku) !== false;
                        });
                        if ($matchedVariant) {
                            $shipments[$shippingNumber]['items'][] = [
                                'variant_id' => $matchedVariant->id,
                                'sku' => $matchedVariant->sku,
                                'product' => $matchedVariant->product->name,
                                'variation' => $matchedVariant->attributeValues->pluck('value')->implode(', '),
                                'image_url' => $matchedVariant->product->image_url,
                                'qty' => $qty ?: 1,
                                'dropdownOpen' => false,
                            ];
                            $foundItem = true;
                        }
                    }
                }
            }

            if (!$foundItem) {
                $shipments[$shippingNumber]['items'] = [];
            }
        }
        $shipments = array_values($shipments);
        return response()->json([
            'shipments' => $shipments,
            'pdf_path' => $path
        ]);
    }

    /**
     * Update the status of one or more transactions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:transactions,id',
            'status' => 'required|in:pending,processed,packed,shipped'
        ]);

        try {
            Transaction::whereIn('id', $request->transaction_ids)
                ->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => __('common.status.update_success')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('common.status.update_error')
            ], 500);
        }
    }
}
