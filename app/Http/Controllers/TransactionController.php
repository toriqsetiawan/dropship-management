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
                    // Cari SKU di 1-5 baris setelah label SKU (bisa di tengah kalimat)
                    $skuFound = false;
                    for ($j = 1; $j <= 5; $j++) {
                        if (!isset($lines[$i + $j])) break;
                        $nextLine = $lines[$i + $j];
                        if (preg_match_all('/([a-zA-Z0-9]+-[a-zA-Z0-9]+-[a-zA-Z0-9]+)/i', $nextLine, $matches)) {
                            // Ambil SKU pertama yang ditemukan
                            $sku = strtolower($matches[1][0]);
                            $skuFound = true;
                            break;
                        }
                    }
                    // Jika tidak ketemu, coba scan blok produk seperti sebelumnya
                    if (!$skuFound) {
                        // Jika baris berikutnya label Variasi, ambil beberapa baris setelahnya sebagai blok produk
                        if (isset($lines[$i + 1]) && stripos($lines[$i + 1], 'Variasi') === 0) {
                            $productBlock = [];
                            for ($j = $i + 2; $j < count($lines); $j++) {
                                $val = trim($lines[$j]);
                                if ($val === '' || stripos($val, 'Qty') === 0) break;
                                $productBlock[] = $val;
                            }
                            $productText = implode(' ', $productBlock);
                            if (preg_match_all('/([a-zA-Z0-9]+-[a-zA-Z0-9]+-[a-zA-Z0-9]+)/i', $productText, $matches)) {
                                $sku = strtolower($matches[1][0]);
                            }
                            // Cari qty setelah blok produk
                            for ($j = $i + 2 + count($productBlock); $j < min($i + 10, count($lines)); $j++) {
                                if (stripos($lines[$j], 'Qty') === 0 && isset($lines[$j + 1]) && is_numeric(trim($lines[$j + 1]))) {
                                    $qty = (int) trim($lines[$j + 1]);
                                    break;
                                }
                            }
                        }
                    }
                    // Cari di baris variasi
                    for ($j = $i + 1; $j < min($i + 6, count($lines)); $j++) {
                        // 1. Cek jika label Variasi di baris
                        if (stripos($lines[$j], 'Variasi') === 0) {
                            // Cek di baris yang sama setelah ':'
                            $afterLabel = trim(substr($lines[$j], strlen('Variasi')));
                            if ($afterLabel) {
                                $variationLine = ltrim($afterLabel, ': ');
                            } elseif (isset($lines[$j + 1])) {
                                $variationLine = trim($lines[$j + 1]);
                            } else {
                                continue;
                            }
                            // Regex fleksibel
                            if (preg_match('/^(.+?)[,\s]+(\d{2,3})$/i', $variationLine, $matches)) {
                                $color = strtolower(trim($matches[1]));
                                $size = trim($matches[2]);
                            } else {
                                $parts = array_map('trim', explode(',', $variationLine));
                                if (count($parts) == 2) {
                                    $color = strtolower($parts[0]);
                                    $size = $parts[1];
                                }
                            }
                            break;
                        }
                    }

                    // Query variant dari DB
                    if ($sku) {
                        // Ambil bagian-bagian dari SKU (sebelum dash pertama)
                        $skuParts = explode('-', $sku);
                        $mainSku = strtolower($skuParts[0]);

                        // Inisialisasi color dan size
                        $color = null;
                        $size = null;

                        // Normalisasi SKU untuk pencocokan
                        $normalizedSku = strtolower($mainSku);
                        // Hapus prefix jika ada (misal A_BDMSBR -> bdmsbr)
                        $normalizedSku = preg_replace('/^[a-z]_/', '', $normalizedSku);
                        // Hapus karakter non-alphanumeric
                        $normalizedSku = preg_replace('/[^a-z0-9]/', '', $normalizedSku);

                        // Cari variant yang cocok dengan SKU utama
                        $matchedVariants = $variants->filter(function($variant) use ($normalizedSku) {
                            $variantSku = strtolower($variant->sku);
                            // Hapus karakter non-alphanumeric dari SKU variant
                            $variantSku = preg_replace('/[^a-z0-9]/', '', $variantSku);
                            return stripos($variantSku, $normalizedSku) !== false;
                        });

                        if ((!$color || !$size) && preg_match('/^[a-z0-9]+-([a-z]+)-(\d{2,3})$/i', $sku, $matches)) {
                            $color = strtolower($matches[1]);
                            $size = $matches[2];
                        }

                        // Jika ada warna dan ukuran, coba cari yang paling cocok
                        if ($color && $size && $matchedVariants->count() > 0) {
                            // Filter variant yang cocok warna dan ukuran
                            $colorSizeMatchedVariants = $matchedVariants->filter(function($variant) use ($color, $size) {
                                $variantValues = $variant->attributeValues->pluck('value')->map('strtolower');
                                // Cek warna - harus contains match
                                $hasColor = $variantValues->contains(function($value) use ($color) {
                                    return strpos($value, $color) !== false || strpos($color, $value) !== false;
                                });

                                // Cek ukuran - exact match
                                $hasSize = $variantValues->contains(function($value) use ($size) {
                                    return $value === $size;
                                });

                                return $hasColor && $hasSize;
                            });

                            // Jika ada yang cocok warna dan ukuran, ambil yang pertama
                            if ($colorSizeMatchedVariants->count() > 0) {
                                $matchedVariant = $colorSizeMatchedVariants->first();
                            }
                        }

                        // Jika tidak ketemu yang cocok warna dan ukuran, coba cari yang cocok warnanya saja
                        if (!isset($matchedVariant) && $color && $matchedVariants->count() > 0) {
                            // Filter variant yang cocok warnanya saja
                            $colorMatchedVariants = $matchedVariants->filter(function($variant) use ($color) {
                                $variantValues = $variant->attributeValues->pluck('value')->map('strtolower');
                                return $variantValues->contains(function($value) use ($color) {
                                    return strpos($value, $color) !== false || strpos($color, $value) !== false;
                                });
                            });

                            // Jika ada yang cocok warnanya saja, ambil yang pertama
                            if ($colorMatchedVariants->count() > 0) {
                                $matchedVariant = $colorMatchedVariants->first();
                            }
                        }

                        // Jika masih tidak ketemu, ambil yang pertama dari semua variant yang cocok SKU
                        if (!isset($matchedVariant) && $matchedVariants->count() > 0) {
                            $matchedVariant = $matchedVariants->first();
                        }

                        if (isset($matchedVariant)) {
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
                // Inisialisasi color dan size agar tidak undefined
                $color = $color ?? null;
                $size = $size ?? null;

                // Jika color/size masih null, coba scan seluruh blok untuk pola warna dan ukuran
                if (!$color || !$size) {
                    foreach ($lines as $line) {
                        $line = trim($line);
                        // Format: "Warna,Ukuran" atau "Warna Ukuran"
                        if (preg_match('/([a-zA-Z]+)[,\s]+(\d{2,3})/', $line, $matches)) {
                            $color = $color ?: strtolower($matches[1]);
                            $size = $size ?: $matches[2];
                            break;
                        }
                    }
                }

                foreach ($lines as $i => $line) {
                    $line = trim($line);
                    // Pola: [SKU] ... [variasi],[size]
                    if (preg_match('/([A-Z]_)?(BDMSBR|GUNUNG|GNG-\d+|AD-\d+)\s*([a-zA-Z ]*),?(\d{2})?/i', $line, $m)) {
                        $sku = isset($m[2]) ? trim($m[2]) : '';
                        $pdfVariation = isset($m[3]) ? trim($m[3]) : '';
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
                        // Ambil semua kandidat varian yang SKU-nya cocok
                        $filteredVariants = $variants->filter(function($variant) use ($sku) {
                            return stripos($variant->sku, $sku) !== false;
                        });

                        // 1. Cari yang cocok color dan size
                        $colorSizeMatched = $filteredVariants->filter(function($variant) use ($color, $size) {
                            $variantValues = $variant->attributeValues->pluck('value')->map('strtolower');

                            // Pecah color menjadi array kata (split camel case dan spasi)
                            $colorWords = $color ? preg_split('/(?=[A-Z])|\s+/', ucwords($color)) : $color;
                            $colorWords = array_filter(array_map('strtolower', $colorWords));

                            $hasColor = false;
                            foreach ($colorWords as $word) {
                                if ($variantValues->contains(function($value) use ($word) {
                                    return strpos($value, $word) !== false || strpos($word, $value) !== false;
                                })) {
                                    $hasColor = true;
                                    break;
                                }
                            }
                            $hasSize = $size ? $variantValues->contains(function($value) use ($size) {
                                return $value === $size;
                            }) : false;
                            return $hasColor && $hasSize;
                        });

                        if ($colorSizeMatched->count() > 0) {
                            $matchedVariant = $colorSizeMatched->first();
                        } else {
                            // 2. Cari yang cocok color saja
                            $colorMatched = $filteredVariants->filter(function($variant) use ($color) {
                                $variantValues = $variant->attributeValues->pluck('value')->map('strtolower');
                                $colorWords = $color ? preg_split('/(?=[A-Z])|\s+/', ucwords($color)) : [];
                                $colorWords = array_filter(array_map('strtolower', $colorWords));
                                $hasColor = false;
                                foreach ($colorWords as $word) {
                                    if ($variantValues->contains(function($value) use ($word) {
                                        return strpos($value, $word) !== false || strpos($word, $value) !== false;
                                    })) {
                                        $hasColor = true;
                                        break;
                                    }
                                }
                                return $hasColor;
                            });
                            if ($colorMatched->count() > 0) {
                                $matchedVariant = $colorMatched->first();
                            } else {
                                // 3. Cari yang cocok size saja
                                $sizeMatched = $filteredVariants->filter(function($variant) use ($size) {
                                    $variantValues = $variant->attributeValues->pluck('value')->map('strtolower');
                                    return $size ? $variantValues->contains(function($value) use ($size) {
                                        return $value === $size;
                                    }) : false;
                                });
                                if ($sizeMatched->count() > 0) {
                                    $matchedVariant = $sizeMatched->first();
                                } else {
                                    // 4. Fallback ke kandidat pertama
                                    $matchedVariant = $filteredVariants->first();
                                }
                            }
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
