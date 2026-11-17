<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneReportDownloads extends Command
{
    protected $signature = 'reports:prune-downloads';

    protected $description = 'Remove expired temporary report downloads and their parts';

    public function handle(): int
    {
        if (! Schema::hasTable('report_downloads')) {
            return self::SUCCESS;
        }
        $count = DB::table('report_downloads')->where('expires_at', '<=', now())->delete();
        $this->info("Removed {$count} expired report downloads.");

        return self::SUCCESS;
    }
}
