<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Support\LoanCalculator;
use App\Support\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        '/', '/features', '/features/transactions', '/features/accounts', '/features/debts', '/features/checks', '/features/loans', '/features/budgets', '/features/reports',
        '/pricing', '/download', '/about', '/security', '/privacy', '/terms', '/faq', '/changelog', '/tools/loan-calculator', '/contact',
        '/blog', '/help', '/learn', '/blog/monthly-budgeting-guide', '/help/getting-started', '/learn/personal-finance-glossary',
    ];

    public function test_every_public_page_is_complete_for_search_engines(): void
    {
        foreach (self::PAGES as $path) {
            $response = $this->get($path)->assertOk();
            $html = $response->getContent();

            $this->assertMatchesRegularExpression('#<title>[^<]{5,}</title>#u', $html, $path);
            $this->assertMatchesRegularExpression('#<meta name="description" content="[^"]{40,}"#u', $html, $path.' description');
            $canonical = rtrim(config('app.url'), '/').($path === '/' ? '' : $path);
            $this->assertStringContainsString('<link rel="canonical" href="'.$canonical.'">', $html, $path);
            $this->assertStringContainsString('property="og:title"', $html, $path);
            $this->assertStringContainsString('application/ld+json', $html, $path);
            $this->assertStringContainsString('<html lang="fa" dir="rtl">', $html, $path);
            $this->assertSame(1, substr_count($html, '<h1'), $path.' needs exactly one h1');
            $this->assertStringNotContainsString('noindex', $html, $path);
        }
    }

    public function test_the_json_ld_is_valid_json_with_a_graph(): void
    {
        foreach (['/', '/faq', '/features/loans', '/blog/monthly-budgeting-guide'] as $path) {
            preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $this->get($path)->getContent(), $m);
            $data = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);

            $this->assertSame('https://schema.org', $data['@context']);
            $this->assertContains('Organization', array_column($data['@graph'], '@type'), $path);
        }

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $this->get('/faq')->getContent(), $m);
        $this->assertContains('FAQPage', array_column(json_decode($m[1], true)['@graph'], '@type'));
    }

    public function test_public_pages_set_no_cookie_and_create_no_session(): void
    {
        foreach (['/', '/blog', '/pricing', '/features/loans'] as $path) {
            $response = $this->get($path)->assertOk();
            $this->assertEmpty($response->headers->getCookies(), $path.' must not set cookies');
        }

        $this->assertSame(0, DB::table('sessions')->count());
    }

    public function test_the_content_security_policy_forbids_framing_unless_a_video_needs_it(): void
    {
        $csp = $this->get('/blog')->headers->get('Content-Security-Policy');
        $this->assertStringNotContainsString('frame-src', $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);

        $path = base_path('content/learn/zz-video-test.md');
        file_put_contents($path, "---\ntitle: \"ویدئو\"\ndescription: \"یک ویدئوی آزمایشی برای بررسی مجوز قاب آپارات در سیاست امنیت محتوا.\"\ndate: 2026-10-10\nvideo: \"abc123\"\n---\n\nمتن.\n");

        try {
            $response = $this->get('/learn/zz-video-test')->assertOk();
            $this->assertStringContainsString('frame-src https://www.aparat.com', $response->headers->get('Content-Security-Policy'));
            $this->assertStringContainsString('VideoObject', $response->getContent());
        } finally {
            unlink($path);
        }
    }

    public function test_sitemap_lists_pages_and_articles_with_absolute_urls(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $base = rtrim(config('app.url'), '/');

        foreach (['/', '/features/loans', '/pricing', '/blog/monthly-budgeting-guide', '/help/getting-started', '/learn/personal-finance-glossary', '/tools/loan-calculator'] as $path) {
            $this->assertStringContainsString('<loc>'.$base.($path === '/' ? '/' : $path).'</loc>', $xml, $path);
        }

        $this->assertStringNotContainsString('/app', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'the sitemap is well-formed XML');
    }

    public function test_robots_txt_hides_the_app_and_points_to_the_sitemap(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /app/', $robots);
        $this->assertStringContainsString('Disallow: /api/', $robots);
        $this->assertStringContainsString('Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml', $robots);
    }

    public function test_the_blog_has_categories_a_feed_and_not_found_pages(): void
    {
        $this->get('/blog/category/'.rawurlencode('بودجه'))->assertOk()->assertSee('قانون ۵۰/۳۰/۲۰');
        $this->get('/blog/category/nothing')->assertNotFound();
        $this->get('/blog/no-such-post')->assertNotFound();
        $this->get('/help/no-such-page')->assertNotFound();
        $this->get('/features/no-such-feature')->assertNotFound();

        $feed = $this->get('/blog/feed.xml')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')->getContent();
        $this->assertNotFalse(simplexml_load_string($feed));
        $this->assertStringContainsString('monthly-budgeting-guide', $feed);
    }

    public function test_an_article_gets_a_table_of_contents_anchors_and_persian_dates(): void
    {
        $html = $this->get('/blog/monthly-budgeting-guide')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="فهرست مطالب"', $html);
        $this->assertMatchesRegularExpression('#<h2 id="[^"]+">#u', $html);
        $this->assertStringContainsString('مهر ۱۴۰۵', $html);
        $this->assertStringContainsString('"@type":"BlogPosting"', $html);
        $this->assertStringContainsString('<time datetime="2026-10-03">', $html);
    }

    public function test_raw_html_in_markdown_is_stripped(): void
    {
        $path = base_path('content/blog/zz-html-test.md');
        file_put_contents($path, "---\ntitle: \"آزمایش\"\ndescription: \"آزمایش حذف HTML خام از مارک‌داون برای جلوگیری از اسکریپت تزریقی در صفحه.\"\ndate: 2026-10-10\n---\n\nسلام <script>alert(1)</script> [بد](javascript:alert(1))\n");

        try {
            $html = $this->get('/blog/zz-html-test')->assertOk()->getContent();
            $this->assertStringNotContainsString('<script>alert', $html);
            $this->assertStringNotContainsString('javascript:alert', $html);
        } finally {
            unlink($path);
        }
    }

    public function test_the_loan_calculator_computes_on_the_server(): void
    {
        $this->get('/tools/loan-calculator')->assertOk()->assertDontSee('aria-live="polite"', false);

        $response = $this->get('/tools/loan-calculator?amount=500000000&rate=23&months=36')->assertOk();
        $response->assertSee('aria-live="polite"', false)->assertSee('۱۹٬۳۵۴٬۸۶۱', false);

        $this->get('/tools/loan-calculator?amount=-5&rate=23&months=36')->assertOk()->assertSee('مبلغ وام باید بیشتر از صفر باشد.')->assertDontSee('aria-live="polite"', false);
        $this->get('/tools/loan-calculator?amount=100&rate=10&months=9999')->assertOk()->assertSee('تعداد اقساط حداکثر ۳۶۰ ماه است.');
    }

    public function test_the_annuity_maths(): void
    {
        $r = LoanCalculator::annuity(1_200_000, 0, 12);
        $this->assertEqualsWithDelta(100_000, $r['instalment'], 0.01);
        $this->assertEqualsWithDelta(0, $r['interest'], 0.01);

        $r = LoanCalculator::annuity(500_000_000, 23, 36);
        $this->assertEqualsWithDelta(19_354_861, $r['instalment'], 1);
        $this->assertEqualsWithDelta(0, end($r['schedule'])['balance'], 1);
        $this->assertCount(36, $r['schedule']);
        $this->assertEqualsWithDelta($r['total'] - 500_000_000, $r['interest'], 0.01);
    }

    public function test_the_contact_form_saves_the_message_and_mails_the_owner(): void
    {
        Mail::fake();
        config(['services.contact.to' => 'owner@example.com']);

        $this->get('/contact')->assertOk()->assertSee('name="form_token"', false);
        $token = Registration::token();
        $this->travel(10)->seconds();

        $this->post('/contact', ['name' => 'سارا', 'email' => 'sara@example.com', 'message' => 'سلام، یک پرسش دارم درباره‌ی چک.', 'website' => '', 'form_token' => $token])
            ->assertRedirect('/contact')
            ->assertSessionHas('sent');

        $this->assertDatabaseHas('contact_messages', ['email' => 'sara@example.com']);
        Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->hasReplyTo('sara@example.com'));
    }

    public function test_the_contact_form_turns_away_bots_and_bad_input(): void
    {
        Mail::fake();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $token = Registration::token();
        $this->travel(10)->seconds();
        $ok = ['name' => 'سارا', 'email' => 'sara@example.com', 'message' => 'سلام، یک پرسش دارم درباره‌ی چک.', 'website' => '', 'form_token' => $token];

        $this->post('/contact', [...$ok, 'website' => 'http://spam'])->assertSessionHasErrors('message');
        $this->post('/contact', [...$ok, 'form_token' => 'garbage'])->assertSessionHasErrors('message');
        $this->post('/contact', [...$ok, 'email' => 'not-an-email'])->assertSessionHasErrors('email');
        $this->post('/contact', [...$ok, 'message' => 'کوتاه'])->assertSessionHasErrors('message');

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_a_mail_failure_does_not_lose_the_message_or_fail_the_visitor(): void
    {
        config(['services.contact.to' => 'owner@example.com']);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $token = Registration::token();
        $this->travel(10)->seconds();

        $this->post('/contact', ['name' => 'سارا', 'email' => 'sara@example.com', 'message' => 'سلام، یک پرسش دارم درباره‌ی چک.', 'website' => '', 'form_token' => $token])->assertRedirect('/contact');

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_production_serves_one_canonical_host(): void
    {
        config(['app.url' => 'https://www.hezarrial.test']);
        $this->app['env'] = 'production';

        $this->get('https://hezarrial.test/pricing?x=1')->assertStatus(301)->assertRedirect('https://www.hezarrial.test/pricing?x=1');
        $this->get('http://www.hezarrial.test/blog')->assertRedirect('https://www.hezarrial.test/blog');
        $this->get('https://www.hezarrial.test/pricing')->assertOk();
        // Only safe requests are redirected: a form post must never be turned into a GET.
        $this->assertNotSame(301, $this->post('https://hezarrial.test/contact')->getStatusCode());
    }

    public function test_the_app_link_in_the_header_does_not_depend_on_a_session(): void
    {
        $this->get('/')->assertSee('href="/login"', false)->assertDontSee('ورود به برنامه');
    }
}
