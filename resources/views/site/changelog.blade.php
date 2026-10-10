<x-site.layout
    title="تغییرات نسخه‌ها"
    description="فهرست تغییرات و امکانات تازه‌ی هزار ریال، از قدیمی تا جدید."
    path="/changelog"
    :breadcrumbs="[['label' => 'خانه', 'url' => '/'], ['label' => 'تغییرات نسخه‌ها', 'url' => '/changelog']]">
    <article class="mx-auto max-w-3xl px-4 pt-10">
        <h1 class="text-4xl leading-[1.4] font-extrabold">تغییرات نسخه‌ها</h1>
        <div class="prose-site mt-8">{!! $page['html'] !!}</div>
    </article>
</x-site.layout>
