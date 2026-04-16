<?php

namespace App\Services;

use App\Models\Memorial;
use App\Models\User;
use App\Support\MemorialSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchService
{
    /**
     * Search memorials by name with optional public-only filter
     *
     * @param string $query Search term
     * @param User|null $user Authenticated user when available
     * @param int $perPage Number of results per page
     * @param string|null $locale Locale used for translated content search
     * @return LengthAwarePaginator
     */
    public function searchMemorials(string $query, ?User $user = null, int $perPage = 15, ?string $locale = null): LengthAwarePaginator
    {
        $searchQuery = Memorial::query();
        MemorialSearch::applyKeywordFilter($searchQuery, $query, $locale);

        if (!$user) {
            $searchQuery->where('is_public', true);
        } elseif ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin') {
            // Admin users can search across all memorials.
        } else {
            $searchQuery->where(static function ($visibilityQuery) use ($user): void {
                $visibilityQuery
                    ->where('is_public', true)
                    ->orWhere('user_id', $user->id);
            });
        }

        // Sort by created_at DESC (newest first)
        $searchQuery->orderBy('created_at', 'desc');

        return $searchQuery->paginate($perPage);
    }
}
