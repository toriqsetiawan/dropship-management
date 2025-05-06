<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function destroy($id)
    {
        $product = Product::with(['variants'])->findOrFail($id);

        $product->variants()->delete();
        $product->delete();

        return redirect()->route('products.index')->with('success', __('product.messages.deleted'));
    }
}
