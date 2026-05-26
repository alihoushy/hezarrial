<?php

namespace App\Services\Backup;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    public function create(User $user): Backup
    {
        $payload = [
            'schema_version' => 1,
            'created_at' => now()->toISOString(),
            'user' => $user->only(['name', 'email', 'mobile', 'settings']),
            'accounts' => $user->accounts()->withTrashed()->get(),
            'categories' => $user->categories()->withTrashed()->get(),
            'people' => $user->people()->withTrashed()->get(),
            'transactions' => $user->transactions()->withTrashed()->get(),
        ];

        $fileName = 'hezarrial-backup-'.$user->id.'-'.now()->format('Ymd-His').'.json';
        $path = 'backups/'.$user->id.'/'.$fileName;
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        Storage::disk('local')->put($path, $json);

        return Backup::create([
            'user_id' => $user->id,
            'file_path' => $path,
            'file_name' => $fileName,
            'file_size' => strlen($json),
            'is_encrypted' => false,
            'created_at' => now(),
        ]);
    }
}
