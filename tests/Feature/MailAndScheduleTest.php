<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class MailAndScheduleTest extends TestCase
{
    private function mail(): string
    {
        return (string) (new MailMessage)->subject('x')->line('سلام')->action('ورود', 'https://example.com')->render();
    }

    public function test_mail_follows_the_language_direction(): void
    {
        app()->setLocale('fa');
        $this->assertStringContainsString('dir="rtl"', $this->mail());

        app()->setLocale('en');
        $this->assertStringContainsString('dir="ltr"', $this->mail());
    }

    private function event(string $name): ?Event
    {
        return collect(app(Schedule::class)->events())->first(fn (Event $event) => $event->description === $name);
    }

    public function test_scheduled_jobs_run_in_process(): void
    {
        foreach (['recurring-transactions', 'prune-sms-text', 'queue-work'] as $name) {
            $event = $this->event($name);

            $this->assertNotNull($event, $name);
            $this->assertStringNotContainsString('artisan', (string) $event->command, "{$name} must not need a shell process");
        }
    }

    public function test_the_queue_is_drained_only_when_it_is_not_synchronous(): void
    {
        config(['queue.default' => 'sync']);
        $this->assertFalse($this->event('queue-work')->filtersPass($this->app));

        config(['queue.default' => 'database']);
        $this->assertTrue($this->event('queue-work')->filtersPass($this->app));
    }
}
