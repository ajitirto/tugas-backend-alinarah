<?php

namespace App\Console\Commands;

use App\Jobs\ImportCommentsJob;
use App\Jobs\ImportPostsJob;
use App\Jobs\ImportUsersJob;
use App\Services\DummyJsonService;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class ImportDummyJsonCommand extends Command
{
    protected $signature = 'import:dummyjson';

    protected $description = 'Import users, posts, and comments from DummyJSON';

    public function handle(DummyJsonService $service): int
    {
        $this->info('Fetching data from DummyJSON...');

        $users = $service->getUsers();
        $posts = $service->getPosts();
        $comments = $service->getComments();

        $this->info('Users: '.count($users));
        $this->info('Posts: '.count($posts));
        $this->info('Comments: '.count($comments));

        /*
         * ============================================================
         * 1. IMPORT USERS
         * ============================================================
         */
        $this->newLine();
        $this->info('Importing users...');

        $userJobs = [];

        foreach (array_chunk($users, 500) as $chunk) {
            $userJobs[] = new ImportUsersJob($chunk);
        }

        $userBatch = Bus::batch($userJobs)
            ->name('DummyJSON - Import Users')
            ->dispatch();

        $this->info("Users Batch ID: {$userBatch->id}");

        if (! $this->showProgress($userBatch)) {
            return self::FAILURE;
        }

        /*
         * ============================================================
         * 2. IMPORT POSTS
         * ============================================================
         */
        $this->newLine();
        $this->info('Importing posts...');

        $postJobs = [];

        foreach (array_chunk($posts, 500) as $chunk) {
            $postJobs[] = new ImportPostsJob($chunk);
        }

        $postBatch = Bus::batch($postJobs)
            ->name('DummyJSON - Import Posts')
            ->dispatch();

        $this->info("Posts Batch ID: {$postBatch->id}");

        if (! $this->showProgress($postBatch)) {
            return self::FAILURE;
        }

        /*
         * ============================================================
         * 3. IMPORT COMMENTS
         * ============================================================
         */
        $this->newLine();
        $this->info('Importing comments...');

        $commentJobs = [];

        foreach (array_chunk($comments, 500) as $chunk) {
            $commentJobs[] = new ImportCommentsJob($chunk);
        }

        $commentBatch = Bus::batch($commentJobs)
            ->name('DummyJSON - Import Comments')
            ->dispatch();

        $this->info("Comments Batch ID: {$commentBatch->id}");

        if (! $this->showProgress($commentBatch)) {
            return self::FAILURE;
        }

        /*
         * ============================================================
         * DONE
         * ============================================================
         */
        $this->newLine();
        $this->info('DummyJSON import completed successfully.');

        return self::SUCCESS;
    }

    private function showProgress(Batch $batch): bool
    {
        while (true) {
            $batch = Bus::findBatch($batch->id);

            if (! $batch) {
                $this->error('Batch not found.');

                return false;
            }

            $progress = $batch->progress();

            $this->output->write(
                "\rProgress: {$progress}%"
            );

            /*
             * Batch selesai
             */
            if ($batch->finished()) {
                break;
            }

            /*
             * Batch dibatalkan
             */
            if ($batch->cancelled()) {
                $this->newLine();

                $this->error('Batch was cancelled.');

                return false;
            }

            sleep(1);
        }

        $this->newLine();

        /*
         * Ada job yang gagal
         */
        if ($batch->failedJobs > 0) {
            $this->error(
                "Batch completed with {$batch->failedJobs} failed job(s)."
            );

            return false;
        }

        $this->info('Batch completed successfully.');

        return true;
    }
}
