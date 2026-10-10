<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_the_manifest_is_installable(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach (['name', 'short_name', 'start_url', 'display', 'icons', 'background_color', 'theme_color', 'scope', 'lang', 'dir'] as $key) {
            $this->assertArrayHasKey($key, $manifest, $key);
        }

        $this->assertSame('/app', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);

        $sizes = collect($manifest['icons'])->pluck('sizes')->all();
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        $this->assertContains('maskable', collect($manifest['icons'])->pluck('purpose')->all());

        foreach ([...$manifest['icons'], ...collect($manifest['shortcuts'])->pluck('icons')->flatten(1)->all()] as $icon) {
            $this->assertFileExists(public_path($icon['src']), $icon['src']);
        }

        foreach ($manifest['shortcuts'] as $shortcut) {
            $this->assertStringStartsWith('/app', $shortcut['url']);
        }
    }

    public function test_icon_files_have_the_sizes_they_claim(): void
    {
        foreach (['icons/icon-192.png' => 192, 'icons/icon-512.png' => 512, 'icons/icon-maskable-512.png' => 512, 'icons/apple-touch-icon.png' => 180] as $file => $size) {
            [$width, $height] = getimagesize(public_path($file));
            $this->assertSame([$size, $size], [$width, $height], $file);
        }

        $this->assertSame([1200, 630], array_slice(getimagesize(public_path('images/og-default.png')), 0, 2));
    }

    public function test_the_service_worker_only_serves_the_offline_page_and_never_stores_data(): void
    {
        $worker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("request.mode !== 'navigate'", $worker, 'only page navigations are handled');
        $this->assertStringContainsString('/offline', $worker);
        // Pages and data under /app must always come from the server.
        $this->assertStringNotContainsString('cache.put', $worker);
        $this->assertStringNotContainsString('addAll', $worker);
        $this->assertStringNotContainsString("'/app", $worker);
    }

    public function test_the_offline_page_is_self_contained_and_not_indexed(): void
    {
        $response = $this->get('/offline')->assertOk();

        $response->assertSee('به اینترنت وصل نیستید');
        $response->assertSee('noindex', false);
        $this->assertStringNotContainsString('/build/', $response->getContent(), 'it must work without any other file');
        $this->assertEmpty($response->headers->getCookies());
    }

    public function test_the_apple_touch_icon_is_a_png(): void
    {
        $this->get('/login')->assertSee('/icons/apple-touch-icon.png', false);
        $this->get('/')->assertSee('/icons/apple-touch-icon.png', false);
    }
}
