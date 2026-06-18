<?php

namespace App\Services;

use App\Models\User;
use App\Models\Setting;
use App\Models\PasswordHistory;
use Illuminate\Support\Facades\Hash;

class PasswordHistoryService
{
    /**
     * Check if plain password matches any of the last N password hashes.
     */
    public function check(User $user, string $plainPassword): bool
    {
        $limit = (int) Setting::get('password_history_count', 5);

        $history = PasswordHistory::where('user_id', $user->id)
            ->latest('id')
            ->limit($limit)
            ->get();

        foreach ($history as $record) {
            if (Hash::check($plainPassword, $record->password)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Store new password hash and prune oldest hashes to keep only last N.
     */
    public function record(User $user, string $hashedPassword): void
    {
        PasswordHistory::create([
            'user_id' => $user->id,
            'password' => $hashedPassword,
        ]);

        $limit = (int) Setting::get('password_history_count', 5);

        // Keep only the last N records
        $keepIds = PasswordHistory::where('user_id', $user->id)
            ->latest('id')
            ->limit($limit)
            ->pluck('id')
            ->toArray();

        PasswordHistory::where('user_id', $user->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
