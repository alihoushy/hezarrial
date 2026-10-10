<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Support\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('site.contact', ['formToken' => Registration::token()]);
    }

    public function store(Request $request): RedirectResponse
    {
        // The same cheap bot checks as sign-up: an empty honeypot and a form that was open a few seconds.
        if (! Registration::looksHuman($request->all())) {
            throw ValidationException::withMessages(['message' => 'ارسال انجام نشد. صفحه را دوباره باز کنید و دوباره تلاش کنید.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'name.required' => 'نام را بنویسید.',
            'email.required' => 'ایمیل را بنویسید تا بتوانیم پاسخ بدهیم.',
            'email.email' => 'ایمیل درست نیست.',
            'message.required' => 'پیام را بنویسید.',
            'message.min' => 'پیام خیلی کوتاه است.',
            'message.max' => 'پیام خیلی طولانی است (حداکثر ۳۰۰۰ نویسه).',
        ]);

        $contact = ContactMessage::create($data);

        // The message is saved first; a mail problem must not lose it or fail the visitor's request.
        try {
            $to = config('services.contact.to');
            if ($to) {
                Mail::to($to)->send(new ContactMessageReceived($contact));
            }
        } catch (\Throwable $exception) {
            Log::error('Contact mail failed: '.$exception->getMessage());
        }

        return redirect()->route('contact')->with('sent', true);
    }
}
