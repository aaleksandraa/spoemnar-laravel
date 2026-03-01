<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Display the contact form.
     */
    public function index()
    {
        return view('contact');
    }

    /**
     * Handle the contact form submission.
     */
    public function store(ContactFormRequest $request)
    {
        // Data is already validated and sanitized by ContactFormRequest
        $validated = $request->validated();

        try {
            Log::info('Contact form submitted', [
                'ip_hash' => hash('sha256', $request->ip()),
                'timestamp' => now()->toIso8601String(),
                'success' => true,
            ]);

            return redirect()->back()->with('success', __('ui.contact.success'));
        } catch (\Exception $e) {
            Log::error('Contact form error: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('ui.contact.error'))
                ->withInput();
        }
    }
}
