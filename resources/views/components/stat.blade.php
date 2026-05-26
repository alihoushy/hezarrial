@props(['label', 'value', 'tone' => 'slate'])
<div class="rounded-2xl bg-white/80 p-4 dark:bg-slate-800">
    <p class="text-xs font-bold text-slate-500">{{ $label }}</p>
    <p class="num mt-2 text-lg font-black">{{ number_format($value) }}</p>
    <p class="text-[11px] text-slate-400">ریال</p>
</div>
