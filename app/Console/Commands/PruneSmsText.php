<?php

namespace App\Console\Commands;

use App\Models\IncomingSms;
use Illuminate\Console\Command;

class PruneSmsText extends Command
{
    protected $signature = 'app:prune-sms-text {--days=30 : Keep the text of handled messages this long}';
    protected $description = 'Clear the text of bank messages that were already handled, keeping the parsed fields.';

    public function handle(): int
    {
        $cleared = IncomingSms::query()
            ->whereNotIn('status', ['pending', 'unparsed'])
            ->whereNotNull('raw_message')
            ->where('created_at', '<', now()->subDays((int) $this->option('days')))
            ->update(['raw_message' => null]);

        $this->info("Cleared the text of {$cleared} handled messages.");

        return self::SUCCESS;
    }
}
