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

        // Only use the first page
        $pages = preg_split('/\f/', $text);
        $firstPageText = $pages[0];

        // Extract shipping number, recipient and sender name
        $shippingNumber = null;
        $recipient = null;
        $senderName = null;

        if (preg_match('/Resi:\s*([A-Z0-9]+)/i', $firstPageText, $m)) {
            $shippingNumber = $m[1];
        }

        // First try to find recipient and sender in the same line
        if (preg_match('/Penerima:([^\n]+)/i', $firstPageText, $m)) {
            $raw = trim($m[1]);
            if (strpos($raw, 'Pengirim:') !== false) {
                $parts = explode('Pengirim:', $raw);
                $recipient = trim($parts[0]);
                $senderName = trim($parts[1]);
            } else {
                $recipient = $raw;
            }
        }

        // If sender not found yet, try to find it separately
        if (!$senderName && preg_match('/Pengirim:([^\n]+)/i', $firstPageText, $m)) {
            $senderName = trim($m[1]);
        }

        // Find matching reseller by name
        $reseller = null;
        if ($senderName) {
            $reseller = User::whereHas('role', function($query) {
                $query->where('name', 'reseller');
            })->where('name', 'like', '%' . $senderName . '%')->first();
        }

        // Extract items/products (multi-line block parsing, improved for multi-line product names)
        $items = [];
        $lines = preg_split('/\r\n|\r|\n/', $firstPageText);

        for ($i = 0; $i < count($lines); $i++) {
            if (stripos($lines[$i], 'Nama Produk') !== false) {
                $productNameLines = [];
                $sku = '';
                $variation = '';
                $qty = 1;
                $foundSku = false;

                // Collect all lines after 'Nama Produk' up to 'Variasi' or 'Qty' or until SKU is found
                for ($j = $i + 1; $j < min($i + 12, count($lines)); $j++) {
                    if (stripos($lines[$j], 'Variasi') !== false || stripos($lines[$j], 'Qty') !== false) {
                        break;
                    }
                    // SKU line
                    if (preg_match('/SBR-\d+|BDMSBR|GUNUNG|GNG-\d+/i', $lines[$j], $m)) {
                        $sku = trim($m[0]);
                        $foundSku = true;
                        // Remove SKU and '|' from the line, add the rest to product name
                        $lineWithoutSku = trim(str_replace([$sku, '|'], '', $lines[$j]));
                        if ($lineWithoutSku !== '') {
                            $productNameLines[] = $lineWithoutSku;
                        }
                    } else {
                        // Collect all lines as part of the product name
                        $productNameLines[] = trim($lines[$j]);
                    }
                }

                // Now, look for variation/qty after 'Variasi'
                for ($j = $i + 1; $j < min($i + 12, count($lines)); $j++) {
                    if (stripos($lines[$j], 'Variasi') !== false && isset($lines[$j + 1])) {
                        $variationLine = trim($lines[$j + 1]);
                        if (preg_match('/(.+),(\d+)\s+(\d+)/', $variationLine, $vm)) {
                            $variation = trim($vm[1]) . ',' . trim($vm[2]);
                            $qty = (int) $vm[3];
                        }
                    }
                }

                $productName = trim(implode(' ', $productNameLines));

                if ($sku && $productName) {
                    $items[] = [
                        'product' => $productName,
                        'sku' => $sku,
                        'variation' => $variation,
                        'qty' => $qty,
                    ];
                }
            }
        }

        return response()->json([
            'shipping_number' => $shippingNumber,
            'description' => $firstPageText,
            'items' => $items,
            'recipient' => $recipient,
            'sender' => $senderName,
            'reseller' => $reseller ? [
                'id' => $reseller->id,
                'name' => $reseller->name,
                'email' => $reseller->email,
                'profile_photo_url' => str_replace('\\', '/', $reseller->profile_photo_url)
            ] : null
        ]);
    }
}
