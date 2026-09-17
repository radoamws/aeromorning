<?php

namespace App\Console\Commands;

use App\Models\News;
use App\Services\WordPressPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LinkHreflangCommand extends Command
{
    protected $signature = 'news:link-hreflang
        {--limit=200 : Max number of pairs to link in this run}
        {--dry-run : Print candidate pairs without pushing or persisting anything}';

    protected $description = 'Backfill hreflang links for already-published FR/EN news pairs (one-off catch-up; new pairs link automatically during news:publish).';

    public function handle(): int
    {
        $limit  = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $this->backfillMissingPermalinks($dryRun);

        $candidates = News::where('status', News::STATUS_SYNCED)
            ->where('hreflang_linked', false)
            ->whereNotNull('wp_post_id')
            ->whereNotNull('wp_link')
            ->orderBy('id')
            ->get()
            ->groupBy('email_message_id');

        $linked     = 0;
        $incomplete = 0;

        foreach ($candidates as $group) {
            if ($linked >= $limit) {
                break;
            }

            $fr = $group->firstWhere('lang', 'FR');
            $en = $group->firstWhere('lang', 'EN');
            if (!$fr || !$en) {
                $incomplete++;
                continue; // sibling not published (or not yet resolved) — nothing to do here
            }

            if ($dryRun) {
                $this->line("[DRY] would link #{$fr->id} [FR] {$fr->wp_link}  <->  #{$en->id} [EN] {$en->wp_link}");
                $linked++;
                continue;
            }

            WordPressPostingService::linkHreflangWithSiblingIfReady($fr);
            $fr->refresh();

            if ($fr->hreflang_linked) {
                $linked++;
                $this->info("Linked #{$fr->id} [FR] <-> #{$en->id} [EN]");
            } else {
                $incomplete++;
                $this->warn("Failed to link #{$fr->id} <-> #{$en->id} — see storage/logs/laravel.log");
            }
        }

        $this->newLine();
        $this->line("Linked pairs:       {$linked}");
        $this->line("Incomplete/failed:  {$incomplete}");

        return Command::SUCCESS;
    }

    /**
     * Rows synced before the wp_link column existed have wp_post_id but no
     * stored permalink. Resolve it once via the WP REST API so the linking
     * pass above has something to push.
     */
    private function backfillMissingPermalinks(bool $dryRun): void
    {
        $missing = News::where('status', News::STATUS_SYNCED)
            ->whereNotNull('wp_post_id')
            ->where('wp_post_id', '>', 0)
            ->whereNull('wp_link')
            ->get();

        if ($missing->isEmpty()) {
            return;
        }

        $this->info("Resolving permalink for {$missing->count()} already-synced item(s) missing wp_link...");

        foreach ($missing as $news) {
            if ($dryRun) {
                $this->line("[DRY] would resolve permalink for #{$news->id} [{$news->lang}] wp_post_id={$news->wp_post_id}");
                continue;
            }

            try {
                $link = (new WordPressPostingService($news->lang))->fetchPermalink((int) $news->wp_post_id);
            } catch (\Throwable $e) {
                Log::warning("Permalink resolution failed for news #{$news->id}: " . $e->getMessage());
                $link = null;
            }

            if ($link === null) {
                $this->warn("  #{$news->id} — could not resolve permalink");
                continue;
            }

            $news->wp_link = $link;
            $news->save();
            $this->info("  #{$news->id} -> {$link}");
        }
    }
}
