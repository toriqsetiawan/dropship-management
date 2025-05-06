<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Reseller;
use App\Models\Product;
use App\Models\ProductVariant;

class TransactionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isDistributorOrSuperadmin = $user->hasRole(['distributor', 'superadmin']);
        $resellers = $isDistributorOrSuperadmin ? Reseller::all() : [];
        $transactions = Transaction::with('user')->orderByDesc('created_at')->paginate(15);
        return view('transactions.index', compact('transactions', 'isDistributorOrSuperadmin', 'resellers'));
    }

    public function downloadPdf(Transaction $transaction)
    {
        return response()->download(storage_path('app/' . $transaction->shipping_pdf_path));
    }

    public function upload(Request $request)
    {
        $user = Auth::user();
        $isDistributorOrSuperadmin = $user->hasRole(['distributor', 'superadmin']);

        $rules = [
            'shippingPdf' => 'required|file|mimes:pdf',
        ];
        if ($isDistributorOrSuperadmin) {
            $rules['selectedReseller'] = 'required|exists:resellers,id';
        }

        $validated = $request->validate($rules);

        $path = $request->file('shippingPdf')->store('shipping_pdfs');

        // Use Spatie PdfToText to extract text
        $text = \Spatie\PdfToText\Pdf::getText(storage_path('app/' . $path));

        // Load all product variants with their product and attribute values
        $variants = \App\Models\ProductVariant::with('product', 'attributeValues.attribute')->get();
        $created = 0;
        // If you want to handle multi-page PDFs, you can split by form feed (\f)
        $pages = preg_split('/\f/', $text);
        foreach ($pages as $text) {
            \Log::info('PDF Page Text:', ['text' => $text]);

            // Initialize variables
            $shippingNumber = $orderNumber = $recipient = $sender = $productName = $sku = $variation = $qty = $weight = $cod = $deadline = null;

            // Recipient
            if (preg_match('/Penerima:\s*([^\n]+)/i', $text, $m)) $recipient = trim($m[1]);

            // Shipping Number (handles both 'No. Resi:' and 'Resi:')
            if (preg_match('/(No\.\s*)?Resi:\s*([A-Z0-9]+)/i', $text, $m)) $shippingNumber = $m[2];

            // Sender
            if (preg_match('/Pengirim:\s*([^\n]+)/i', $text, $m)) $sender = trim($m[1]);

            // Weight
            if (preg_match('/Berat:\s*([0-9]+)\s*gr/i', $text, $m)) $weight = $m[1];

            // Deadline
            if (preg_match('/Batas Kirim\s*:\s*([0-9\-]+)/i', $text, $m)) $deadline = $m[1];

            // Order Number (handles both 'No.Pesanan:' and 'No. Pesanan:')
            if (preg_match('/No\.\s*Pesanan:\s*([A-Z0-9]+)/i', $text, $m)) $orderNumber = $m[1];

            // Product Name and SKU (robust for both cases)
            $productName = $sku = null;
            if (preg_match('/Nama Produk\s*([\s\S]+?)Pesan:/i', $text, $m)) {
                $productBlock = trim($m[1]);
                // Try to extract SKU from the product block
                if (preg_match('/(\[.*?\][^\[]*?)\s+([A-Z0-9 \-_]+)\s*$/i', $productBlock, $pm)) {
                    $productName = trim($pm[1]);
                    $sku = trim($pm[2]);
                } else {
                    $productName = trim(preg_replace('/\s+/', ' ', $productBlock));
                }
            }
            // If SKU not found, try to extract from a separate line
            if (!$sku && preg_match('/SKU\s*([A-Z0-9 \-_]+)/i', $text, $m)) $sku = trim($m[1]);

            // Variasi and Qty (robust for all cases)
            $variation = $qty = null;
            // Try: Variasi followed by value, then Qty followed by value
            if (preg_match('/Variasi\s*([\w\s,]+)\s*Qty\s*(\d+)/i', $text, $m)) {
                $variation = trim($m[1]);
                $qty = trim($m[2]);
            }
            // Try: Variasi label, value on next line, then Qty label, value on next line
            else if (preg_match('/Variasi\s*\n([\w\s,]+)\n\s*Qty\s*\n(\d+)/i', $text, $m)) {
                $variation = trim($m[1]);
                $qty = trim($m[2]);
            }
            // Try: Variasi and Qty on the same line (e.g. Putih Hitam,33 1)
            else if (preg_match('/Variasi\s*\n?([\w\s,]+)\s+(\d+)\s*$/im', $text, $m)) {
                $variation = trim($m[1]);
                $qty = trim($m[2]);
            }
            // Try: Just Qty label and value
            else if (preg_match('/Qty\s*(\d+)/i', $text, $m)) {
                $qty = trim($m[1]);
            }

            // --- Match SKU from PDF with database ---
            $matchedVariant = null;
            foreach ($variants as $variant) {
                if ($sku && strcasecmp($sku, $variant->sku) === 0) {
                    $matchedVariant = $variant;
                    break;
                }
            }
            if ($matchedVariant) {
                $productName = $matchedVariant->product->name ?? $productName;
                $sku = $matchedVariant->sku;
            }

            // --- Match Variant String (Color/Size) ---
            $color = $size = null;
            if ($variation && strpos($variation, ',') !== false) {
                [$color, $size] = array_map('trim', explode(',', $variation, 2));
            }
            $variantColor = $variantSize = null;
            if ($matchedVariant) {
                foreach ($matchedVariant->attributeValues as $attrValue) {
                    if ($color && stripos($color, $attrValue->value) !== false) {
                        $variantColor = $attrValue->value;
                    }
                    if ($size && stripos($size, $attrValue->value) !== false) {
                        $variantSize = $attrValue->value;
                    }
                }
            }
            // Optionally, you can update $variation to be more accurate:
            if ($variantColor && $variantSize) {
                $variation = $variantColor . ', ' . $variantSize;
            } elseif ($variantColor) {
                $variation = $variantColor;
            } elseif ($variantSize) {
                $variation = $variantSize;
            }

            // Compose description
            $description =
                __('common.transaction.shipping_number') . ': ' . $shippingNumber . "\n" .
                __('common.transaction.order_number') . ': ' . $orderNumber . "\n" .
                __('common.transaction.recipient') . ': ' . $recipient . "\n" .
                __('common.transaction.sender') . ': ' . $sender . "\n" .
                __('common.transaction.product') . ': ' . $productName . "\n" .
                __('common.transaction.sku') . ': ' . $sku . "\n" .
                __('common.transaction.variation') . ': ' . $variation . "\n" .
                __('common.transaction.qty') . ': ' . $qty . "\n" .
                __('common.weight') . ': ' . $weight . " gr\n" .
                __('common.cod') . ': ' . $cod . "\n" .
                __('common.shipping_deadline') . ': ' . $deadline;

            Transaction::create([
                'transaction_code' => 'TRX-' . strtoupper(uniqid()),
                'user_id' => $isDistributorOrSuperadmin ? $request->selectedReseller : $user->id,
                'shipping_pdf_path' => $path,
                'shipping_number' => $shippingNumber,
                'total_paid' => 0,
                'total_price' => 0,
                'description' => $description,
            ]);
            $created++;
        }

        return back()->with('success', "Shipping PDF processed. $created transaction(s) created!");
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
}
