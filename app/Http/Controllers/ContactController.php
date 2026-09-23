<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $ok = TelegramNotifier::sendContactRequest($data);

        if ($ok) {
            $successPayload = [
                'success' => true,
                'message' => 'Дякуємо! Ми зв’яжемося з вами найближчим часом.',
            ];

            if ($request->expectsJson()) {
                return response()->json($successPayload);
            }

            return back()->with('contact_success', $successPayload['message']);
        }

        Log::error('Contact form telegram delivery failed', ['data' => $data]);

        $errorPayload = [
            'success' => false,
            'message' => 'Сталася помилка. Спробуйте, будь ласка, пізніше.',
        ];

        if ($request->expectsJson()) {
            return response()->json($errorPayload, 500);
        }

        return back()
            ->with('contact_error', $errorPayload['message'])
            ->withInput();
    }
}
