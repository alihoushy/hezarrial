@props(['items'])
<nav aria-label="مسیر صفحه" class="mx-auto max-w-6xl px-4 pt-6 text-sm text-muted-foreground">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        @foreach ($items as $item)
            <li class="flex items-center gap-2">
                @if (! $loop->last)
                    <a href="{{ $item['url'] }}" class="hover:text-foreground">{{ $item['label'] }}</a>
                    <span aria-hidden="true">›</span>
                @else
                    <span aria-current="page" class="text-foreground">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
