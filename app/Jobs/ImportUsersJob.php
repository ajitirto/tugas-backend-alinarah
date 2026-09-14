<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportUsersJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public function __construct(public array $users)
    {
        //
    }

    public function handle(): void
    {
        $now = now();

        $data = collect($this->users)
            ->map(fn ($user) => [
                'id' => $user['id'],
                'first_name' => $user['firstName'],
                'last_name' => $user['lastName'],
                'username' => $user['username'],
                'email' => $user['email'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        User::upsert(
            $data,
            ['id'],
            [
                'first_name',
                'last_name',
                'username',
                'email',
                'updated_at',
            ]
        );
    }
}
