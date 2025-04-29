<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function change(Request $request)
    {
        $locale = $request->input('locale');

        if (in_array($locale, ['en', 'id'])) { // Only allow certain locales
            Session::put('locale', $locale);
            App::setLocale($locale);
        }

        return back(); // Redirect back to previous page
    }
}
