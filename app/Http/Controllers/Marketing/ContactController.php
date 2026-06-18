<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        ContactMessage::create([
            ...$validated,
            'locale' => app()->getLocale(),
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('marketing.contact')
            ->with('success', __('marketing.contact_success'));
    }
}
