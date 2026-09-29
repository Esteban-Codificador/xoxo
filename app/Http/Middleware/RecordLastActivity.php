<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps users.last_active_at (shown in the admin) within a few minutes of
 * the user's last request: at most one write per user per window, without
 * touching updated_at or firing model events.
 */
class RecordLastActivity
{
    public const int WINDOW_MINUTES = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $this->isStale($user)) {
            User::withoutTimestamps(fn () => $user->forceFill(['last_active_at' => now()])->saveQuietly());
        }

        return $next($request);
    }

    private function isStale(User $user): bool
    {
        // A user instance built in code (not read from the database) may not
        // carry the column, and strict models throw on missing attributes.
        if (! array_key_exists('last_active_at', $user->getAttributes())) {
            return true;
        }

        return $user->last_active_at === null || $user->last_active_at->lt(now()->subMinutes(self::WINDOW_MINUTES));
    }
}
