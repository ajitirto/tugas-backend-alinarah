<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MultiplyPostsCommand extends Command
{
    protected $signature = 'posts:multiply {jumlah}';

    protected $description = 'Memperbanyak data artikel berdasarkan jumlah salinan';

    public function handle(): int
    {
        $jumlah = (int) $this->argument('jumlah');

        if ($jumlah < 1) {
            $this->error('Jumlah harus lebih besar dari 0.');

            return self::FAILURE;
        }

        $posts = Post::query()
            ->select([
                'user_id',
                'title',
                'body',
                'tags',
                'views',
                'likes',
            ])
            ->get();

        $total = $posts->count() * $jumlah;

        $this->info("Artikel awal: {$posts->count()}");
        $this->info("Jumlah salinan: {$jumlah}");
        $this->info("Total artikel yang akan ditambahkan: {$total}");

        $bar = $this->output->createProgressBar($jumlah);
        $bar->start();

        foreach (range(1, $jumlah) as $copy) {
            $data = $posts->map(fn ($post) => [
                'user_id' => $post->user_id,
                'title' => $post->title,
                'body' => $post->body,
                'tags' => json_encode($post->tags),
                'views' => $post->views,
                'likes' => $post->likes,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            DB::table('posts')->insert($data);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Berhasil menambahkan {$total} artikel.");

        $count = Post::count();
        $this->info("Total artikel sekarang: {$count}");

        return self::SUCCESS;
    }
}
