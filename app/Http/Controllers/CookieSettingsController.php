<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CookieSettingsController extends Controller
{
    /**
     * Display the cookie settings page.
     */
    public function index(): View
    {
        return view('pages.cookie-settings');
    }
}
