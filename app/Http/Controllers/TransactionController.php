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

        // Ambil semua variant beserta produk dan atribut
        $variants = \App\Models\ProductVariant::with(['product', 'attributeValues'])->get();

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

            // Parsing item dari PDF
            $lines = preg_split('/\r\n|\r|\n/', $block);
            for ($i = 0; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                // --- SKU di baris berikutnya setelah 'SKU' ---
                if (stripos($line, 'SKU') === 0 && isset($lines[$i + 1])) {
                    $nextLine = trim($lines[$i + 1]);
                    $sku = '';
                    $pdfVariation = '';
                    $qty = 1;

                    // CASE 1: Baris berikutnya label Variasi, value SKU+variasi di bawahnya
                    if (preg_match('/Variasi/i', $nextLine) && isset($lines[$i + 2])) {
                        $skuVarLine = trim($lines[$i + 2]);
                        // Coba deteksi SKU di awal, sisanya variasi
                        if (preg_match('/^(BDMSBR|GUNUNG|GNG-\d+)\s+(.+)/i', $skuVarLine, $m)) {
                            $sku = trim($m[1]);
                            $pdfVariation = trim($m[2]);
                        } else {
                            // fallback: jika tidak ada SKU di awal, anggap seluruhnya variasi
                            $pdfVariation = $skuVarLine;
                        }
                        // Cari qty
                        for ($j = $i + 3; $j < min($i + 8, count($lines)); $j++) {
                            if (preg_match('/Qty/i', $lines[$j]) && isset($lines[$j + 1]) && is_numeric(trim($lines[$j + 1]))) {
                                $qty = (int) trim($lines[$j + 1]);
                                break;
                            }
                        }
                    }
                    // CASE 2: Baris berikutnya langsung value SKU
                    elseif (preg_match('/(BDMSBR|GUNUNG|GNG-\d+)/i', $nextLine, $mSku)) {
                        $sku = trim($mSku[1]);
                        // Cari variasi dan qty seperti sebelumnya
                        for ($j = $i + 2; $j < min($i + 8, count($lines)); $j++) {
                            $next = trim($lines[$j]);
                            if (preg_match('/Variasi/i', $next) && isset($lines[$j + 1])) {
                                $pdfVariation = trim($lines[$j + 1]);
                                continue;
                            }
                            if (!$pdfVariation && preg_match('/[a-zA-Z]+,\d+/', $next) && !preg_match('/Qty|Nama Produk|SKU/i', $next)) {
                                $pdfVariation = $next;
                                continue;
                            }
                            if (preg_match('/Qty/i', $next) && isset($lines[$j + 1]) && is_numeric(trim($lines[$j + 1]))) {
                                $qty = (int) trim($lines[$j + 1]);
                                continue;
                            }
                            if (!$pdfVariation && preg_match('/([a-zA-Z ]+,[0-9]+)\s+(\d+)/', $next, $m2)) {
                                $pdfVariation = trim($m2[1]);
                                $qty = (int) $m2[2];
                                continue;
                            }
                        }
                    }

                    // Query variant dari DB pakai LIKE SKU dan filter variasi jika ada
                    if ($sku) {
                        $matchedVariant = $variants->first(function($variant) use ($sku, $pdfVariation) {
                            if (stripos($variant->sku, $sku) === false) return false;
                            if ($pdfVariation) {
                                $pdfVars = array_map('trim', explode(',', strtolower($pdfVariation)));
                                $attrString = strtolower($variant->attributeValues->pluck('value')->implode(','));
                                foreach ($pdfVars as $pv) {
                                    if ($pv && stripos($attrString, $pv) === false) return false;
                                }
                            }
                            return true;
                        });
                        if (!$matchedVariant) {
                            $matchedVariant = $variants->first(function($variant) use ($sku) {
                                return stripos($variant->sku, $sku) !== false;
                            });
                        }
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
                        } else {
                            $shipments[$shippingNumber]['items'][] = [
                                'variant_id' => '',
                                'sku' => $sku,
                                'product' => '',
                                'variation' => $pdfVariation,
                                'image_url' => '',
                                'qty' => $qty ?: 1,
                                'dropdownOpen' => false,
                            ];
                        }
                    }
                }
            }
            // Loop ulang jika items masih kosong, cari pola di seluruh lines
            if (empty($shipments[$shippingNumber]['items'])) {
                foreach ($lines as $idx => $line) {
                    $line = trim($line);
                    // Pola: [size] ... BDMSBR Putih Biru,37
                    if (preg_match('/(BDMSBR|GUNUNG|GNG-\d+)\s+([a-zA-Z ]+,[0-9]+)/i', $line, $m)) {
                        $sku = trim($m[1]);
                        $pdfVariation = trim($m[2]);
                        $qty = 1;
                        // Cari qty di baris berikutnya
                        for ($j = $idx + 1; $j < min($idx + 4, count($lines)); $j++) {
                            if (preg_match('/Qty/i', $lines[$j]) && isset($lines[$j + 1]) && is_numeric(trim($lines[$j + 1]))) {
                                $qty = (int) trim($lines[$j + 1]);
                                break;
                            }
                            // Atau langsung angka di baris berikutnya
                            if (is_numeric(trim($lines[$j]))) {
                                $qty = (int) trim($lines[$j]);
                                break;
                            }
                        }
                        // Query variant dari DB pakai LIKE SKU dan filter variasi jika ada
                        $matchedVariant = $variants->first(function($variant) use ($sku, $pdfVariation) {
                            if (stripos($variant->sku, $sku) === false) return false;
                            if ($pdfVariation) {
                                $pdfVars = array_map('trim', explode(',', strtolower($pdfVariation)));
                                $attrString = strtolower($variant->attributeValues->pluck('value')->implode(','));
                                foreach ($pdfVars as $pv) {
                                    if ($pv && stripos($attrString, $pv) === false) return false;
                                }
                            }
                            return true;
                        });
                        if (!$matchedVariant) {
                            $matchedVariant = $variants->first(function($variant) use ($sku) {
                                return stripos($variant->sku, $sku) !== false;
                            });
                        }
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
                        } else {
                            $shipments[$shippingNumber]['items'][] = [
                                'variant_id' => '',
                                'sku' => $sku,
                                'product' => '',
                                'variation' => $pdfVariation,
                                'image_url' => '',
                                'qty' => $qty ?: 1,
                                'dropdownOpen' => false,
                            ];
                        }
                        break; // hanya ambil satu produk per blok
                    }
                }
            }
        }

        // Ubah ke array numerik
        $shipments = array_values($shipments);

        return response()->json($shipments);
    }
}
