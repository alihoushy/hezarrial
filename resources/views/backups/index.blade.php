@extends('layouts.app')
@section('content')
<section class="space-y-4">
    <div class="flex items-center justify-between"><h1 class="text-2xl font-black">پشتیبان‌گیری</h1><form method="post" action="{{ route('backups.store') }}">@csrf<button class="tap rounded-2xl bg-slate-950 px-4 font-bold text-white">پشتیبان جدید</button></form></div>
    <div class="space-y-2">@foreach($backups as $backup)<div class="rounded-3xl bg-white/85 p-4 dark:bg-slate-900"><strong>{{ $backup->file_name }}</strong><p class="text-xs text-slate-500">{{ $backup->created_at }}</p><form method="post" action="{{ route('backups.download', $backup) }}" class="mt-3 flex gap-2">@csrf<input name="password" type="password" placeholder="رمز عبور" class="tap min-w-0 flex-1 rounded-2xl border-0 bg-slate-100 px-3 dark:bg-slate-800"><button class="tap rounded-2xl bg-slate-950 px-4 text-sm font-bold text-white">دانلود</button></form></div>@endforeach</div>
    <form method="post" enctype="multipart/form-data" action="{{ route('backups.restore') }}" class="glass space-y-3 rounded-3xl p-4">@csrf<h2 class="font-black">بازیابی</h2><input name="backup" type="file" class="tap w-full"><input name="password" type="password" placeholder="رمز عبور" class="tap w-full rounded-2xl border-0 bg-white/90 px-4 dark:bg-slate-800"><button class="tap rounded-2xl bg-rose-600 px-4 font-bold text-white">اعتبارسنجی بازیابی</button></form>
</section>
@endsection
