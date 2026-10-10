<x-mail::message>
# پیام تازه از فرم تماس

**نام:** {{ $contact->name }}

**ایمیل:** {{ $contact->email }}

<x-mail::panel>
{{ $contact->message }}
</x-mail::panel>

برای پاسخ، همین ایمیل را Reply کنید.
</x-mail::message>
