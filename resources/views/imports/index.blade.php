@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <h1 class="text-2xl font-black">وارد کردن داده</h1>
    <form method="post" enctype="multipart/form-data" action="{{ route('imports.csv') }}" class="glass space-y-3 rounded-3xl p-4">@csrf<h2 class="font-black">CSV / Excel بانک</h2><input name="statement" type="file" class="tap w-full"><button class="tap rounded-2xl bg-slate-950 px-4 font-bold text-white">ارسال فایل</button></form>
    <form method="post" action="{{ route('imports.sms-preview') }}" class="glass space-y-3 rounded-3xl p-4">@csrf<h2 class="font-black">پیش‌نمایش پیامک بانکی</h2><textarea name="sms_text" class="min-h-32 w-full rounded-2xl border-0 bg-white/90 px-4 py-3 dark:bg-slate-800" placeholder="متن پیامک را اینجا بچسبانید"></textarea><button class="tap rounded-2xl bg-slate-950 px-4 font-bold text-white">تحلیل پیامک</button></form>
</section>
@endsection
