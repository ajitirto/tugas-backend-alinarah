<?php

namespace App\Console\Commands;

use App\Jobs\ImportCommentsJob;
use App\Jobs\ImportPostsJob;
use App\Jobs\ImportUsersJob;
use App\Services\DummyJsonService;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportDummyJsonCommand extends Command
{
    protected $signature = 'import:dummyjson';

    protected $description = 'Import users, posts, and comments from DummyJSON';

    private const CHUNK_SIZE = 500;

    private const PROGRESS_TIMEOUT = 60;

    public function handle(DummyJsonService $service): int
    {
        $this->info('Fetching data from DummyJSON...');

        $users = $service->getUsers();
        $posts = $service->getPosts();
        $comments = $service->getComments();

        $this->info('Users: ' . count($users));
        $this->info('Posts: ' . count($posts));
        $this->info('Comments: ' . count($comments));

        /*
         * ============================================================
         * 1. IMPORT USERS
         * ============================================================
         */
        $this->newLine();
        $this->info('Importing users...');

        $userBatch = $this->dispatchBatch(
            $users,
            ImportUsersJob::class,
            'DummyJSON - Import Users'
        );

        if (! $userBatch || ! $this->showProgress($userBatch)) {
            return self::FAILURE;
        }

        /*
         * ============================================================
         * 2. IMPORT POSTS
         * ============================================================
         */
        $this->newLine();
        $this->info('Importing posts...');

        $postBatch = $this->dispatchBatch(
            $posts,
            ImportPostsJob::class,
            'DummyJSON - Import Posts'
        );

        if (! $postBatch || ! $this->showProgress($postBatch)) {
            return self::FAILURE;
        }

        /*
         * ============================================================
         * SYNC POST AUTO INCREMENT
         * ============================================================
         */
        $this->syncPostSequence();

        /*
         * ============================================================
         * 3. IMPORT COMMENTS
         * ============================================================
         */
        $this->newLine();
        $this->info('Importing comments...');

        $commentBatch = $this->dispatchBatch(
            $comments,
            ImportCommentsJob::class,
            'DummyJSON - Import Comments'
        );

        if (! $commentBatch || ! $this->showProgress($commentBatch)) {
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

    /**
     * Dispatch jobs dalam batch.
     */
    private function dispatchBatch(
        array $data,
        string $jobClass,
        string $name
    ): ?Batch {
        if (empty($data)) {
            $this->warn("No data available for {$name}.");

            return null;
        }

        $jobs = [];

        foreach (array_chunk($data, self::CHUNK_SIZE) as $chunk) {
            $jobs[] = new $jobClass($chunk);
        }

        if (empty($jobs)) {
            $this->error("No jobs created for {$name}.");

            return null;
        }

        try {
            $batch = Bus::batch($jobs)
                ->name($name)
                ->dispatch();

            $this->info("Batch ID: {$batch->id}");

            return $batch;
        } catch (Throwable $e) {
            $this->error('Failed to dispatch batch.');
            $this->error($e->getMessage());

            return null;
        }
    }

    /**
     * Menampilkan progress batch dengan timeout.
     */
    private function showProgress(Batch $batch): bool
    {
        $startTime = time();
        $lastProgress = -1;

        while (true) {
            $currentBatch = Bus::findBatch($batch->id);

            if (! $currentBatch) {
                $this->newLine();
                $this->error('Batch not found.');

                return false;
            }

            $progress = $currentBatch->progress();

            if ($progress !== $lastProgress) {
                $this->output->write("\rProgress: {$progress}%");

                $lastProgress = $progress;
            }

            if ($currentBatch->failedJobs > 0) {
                $this->newLine();

                $this->error(
                    "Batch has {$currentBatch->failedJobs} failed job(s)."
                );

                return false;
            }

            if ($currentBatch->finished()) {
                break;
            }

            if ($currentBatch->cancelled()) {
                $this->newLine();
                $this->error('Batch was cancelled.');

                return false;
            }

            if ((time() - $startTime) >= self::PROGRESS_TIMEOUT) {
                $this->newLine();

                $this->error(
                    'Batch progress timeout after '
                    . self::PROGRESS_TIMEOUT
                    . ' seconds.'
                );

                $this->error(
                    "Batch ID: {$currentBatch->id}"
                );

                $this->warn(
                    'Make sure the queue worker is running:'
                );

                $this->line('php artisan queue:work');

                return false;
            }

            sleep(1);
        }

        $this->newLine();
        $this->info('Batch completed successfully.');

        return true;
    }

    /**
     * Sinkronisasi sequence PostgreSQL setelah import posts.
     *
     * Import menggunakan ID dari DummyJSON secara manual,
     * sehingga sequence PostgreSQL perlu disesuaikan
     * dengan ID terbesar yang sudah ada.
     */
    private function syncPostSequence(): void
    {
        $this->info('Synchronizing posts ID sequence...');

        try {
            DB::statement("
                SELECT setval(
                    pg_get_serial_sequence('posts', 'id'),
                    COALESCE((SELECT MAX(id) FROM posts), 1)
                )
            ");

            $this->info('Posts ID sequence synchronized.');
        } catch (Throwable $e) {
            $this->error('Failed to synchronize posts ID sequence.');
            $this->error($e->getMessage());

            throw $e;
        }
    }
}
