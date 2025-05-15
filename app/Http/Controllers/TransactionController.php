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
        return view('pages.transactions.index', compact('transactions', 'resellers'));
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

        $transaction = Transaction::create([
            'transaction_code' => 'TRX-' . strtoupper(uniqid()),
            'user_id' => !is_distributor_or_admin($user)
                ? $request->user_id
                : $user->id,
            'shipping_number' => $request->shipping_number,
            'total_paid' => 0,
            'total_price' => 0,
            'description' => $request->description,
        ]);

        // Create transaction items
        foreach ($request->items as $item) {
            $variant = ProductVariant::findOrFail($item['variant_id']);
            $transaction->items()->create([
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'factory_price' => $variant->factory_price,
                'distributor_price' => $variant->distributor_price,
                'reseller_price' => $variant->reseller_price,
                'retail_price' => $variant->retail_price,
            ]);
        }

        // Calculate total price
        $totalPrice = $transaction->items->sum(function ($item) {
            return $item->quantity * $item->retail_price;
        });

        $transaction->update(['total_price' => $totalPrice]);

        return redirect()->route('transactions.index')
            ->with('success', __('common.transaction.created_successfully'));
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
                'factory_price' => $variant->factory_price,
                'distributor_price' => $variant->distributor_price,
                'reseller_price' => $variant->reseller_price,
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

        // Extract text from the PDF (no OCR)
        $text = \Spatie\PdfToText\Pdf::getText($inputPath);

        // Pisahkan berdasarkan halaman
        $pages = preg_split('/\f/', $text);
        $allText = implode("\n", $pages);

        // Pisahkan blok-blok berdasarkan No. Resi/Resi
        $blocks = preg_split('/(?=No\.\s*Resi:|Resi:)/i', $allText);

        $shipments = [];

        foreach ($blocks as $block) {
            if (!trim($block)) continue;

            // Ambil nomor resi
            if (preg_match('/Resi:\s*([A-Z0-9]+)/i', $block, $m)) {
                $shippingNumber = $m[1];
            } elseif (preg_match('/No\.\s*Resi:\s*([A-Z0-9]+)/i', $block, $m)) {
                $shippingNumber = $m[1];
            } else {
                continue; // skip jika tidak ada nomor resi
            }

            // Jika sudah ada shipment dengan nomor resi ini, gunakan referensinya
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
                // Gabungkan deskripsi jika multi halaman
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

            // Set recipient/sender jika belum ada
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

            // Produk (gunakan logika multi-line seperti sebelumnya)
            $lines = preg_split('/\r\n|\r|\n/', $block);
            for ($i = 0; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                // --- NEW: SKU before Nama Produk ---
                if (stripos($line, 'SKU') === 0 && isset($lines[$i + 1]) && preg_match('/(BDMSBR|GUNUNG|GNG-\d+)/i', $lines[$i + 1], $mSku)) {
                    $sku = trim($mSku[1]);
                    $productNameLines = [];
                    $variation = '';
                    $qty = 1;
                    $foundProduct = false;
                    $foundVariation = false;
                    $foundQty = false;

                    // Scan ke depan untuk Nama Produk, Variasi, Qty
                    for ($j = $i + 2; $j < min($i + 20, count($lines)); $j++) {
                        $nextLine = trim($lines[$j]);
                        if ($nextLine === '') continue;

                        if (!$foundProduct && stripos($nextLine, 'Nama Produk') === 0) {
                            // Kumpulkan nama produk
                            for ($k = $j + 1; $k < min($j + 10, count($lines)); $k++) {
                                $prodLine = trim($lines[$k]);
                                if ($prodLine === '' || stripos($prodLine, 'Variasi') === 0 || stripos($prodLine, 'Qty') === 0) break;
                                $productNameLines[] = $prodLine;
                            }
                            $foundProduct = true;
                            continue;
                        }
                        if (!$foundVariation && stripos($nextLine, 'Variasi') === 0 && isset($lines[$j + 1])) {
                            $variation = trim($lines[$j + 1]);
                            $foundVariation = true;
                            continue;
                        }
                        if (!$foundQty && stripos($nextLine, 'Qty') === 0 && isset($lines[$j + 1]) && is_numeric(trim($lines[$j + 1]))) {
                            $qty = (int) trim($lines[$j + 1]);
                            $foundQty = true;
                            continue;
                        }
                    }
                    $productName = trim(implode(' ', $productNameLines));
                    if ($sku && $productName) {
                        $shipments[$shippingNumber]['items'][] = [
                            'product' => $productName,
                            'sku' => $sku,
                            'variation' => $variation,
                            'qty' => $qty,
                        ];
                    }
                }

                // --- Tetap jalankan logika lama untuk Nama Produk -> SKU ---
                if (stripos($line, 'Nama Produk') === 0) {
                    $productNameLines = [];
                    $sku = '';
                    $variation = '';
                    $qty = 1;
                    $foundSku = false;
                    $foundVariation = false;
                    $foundQty = false;

                    // Scan hingga 20 baris ke depan
                    for ($j = $i + 1; $j < min($i + 20, count($lines)); $j++) {
                        $subLine = trim($lines[$j]);
                        if ($subLine === '') continue;

                        // Jika menemukan baris SKU, ambil dari baris berikutnya atau baris itu sendiri
                        if (!$foundSku && stripos($subLine, 'SKU') === 0) {
                            if (isset($lines[$j + 1]) && preg_match('/(BDMSBR|GUNUNG|GNG-\d+)/i', $lines[$j + 1], $m)) {
                                $sku = trim($m[1]);
                                $foundSku = true;
                            }
                            continue;
                        }
                        if (!$foundSku && preg_match('/(BDMSBR|GUNUNG|GNG-\d+)/i', $subLine, $m)) {
                            $sku = trim($m[1]);
                            $foundSku = true;
                        }
                        if (!$foundVariation && stripos($subLine, 'Variasi') === 0) {
                            if (isset($lines[$j + 1])) {
                                $variation = trim($lines[$j + 1]);
                                $foundVariation = true;
                            }
                            continue;
                        }
                        if (!$foundQty && stripos($subLine, 'Qty') === 0) {
                            if (isset($lines[$j + 1]) && is_numeric(trim($lines[$j + 1]))) {
                                $qty = (int) trim($lines[$j + 1]);
                                $foundQty = true;
                            }
                            continue;
                        }
                        if (
                            stripos($subLine, 'SKU') !== 0 &&
                            stripos($subLine, 'Variasi') !== 0 &&
                            stripos($subLine, 'Qty') !== 0
                        ) {
                            $productNameLines[] = $subLine;
                        }
                    }
                    $productName = trim(implode(' ', $productNameLines));
                    if ($sku && $productName) {
                        $shipments[$shippingNumber]['items'][] = [
                            'product' => $productName,
                            'sku' => $sku,
                            'variation' => $variation,
                            'qty' => $qty,
                        ];
                    }
                }
            }
        }

        // Ubah ke array numerik
        $shipments = array_values($shipments);

        return response()->json($shipments);
    }
}
