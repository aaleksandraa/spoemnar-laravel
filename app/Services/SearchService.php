<?php

namespace App\Services;

use App\Models\Memorial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Search memorials by name with optional public-only filter
     *
     * @param string $query Search term
     * @param bool $publicOnly Filter to only public memorials
     * @param int $perPage Number of results per page
     * @return LengthAwarePaginator
     */
    public function searchMemorials(string $query, bool $publicOnly = true, int $perPage = 15): LengthAwarePaginator
    {
        $searchQuery = Memorial::query()
            ->where(function ($q) use ($query) {
                $q->where(DB::raw('LOWER(first_name)'), 'LIKE', '%' . strtolower($query) . '%')
                  ->orWhere(DB::raw('LOWER(last_name)'), 'LIKE', '%' . strtolower($query) . '%');
            });

        // Apply public-only filter if needed
        if ($publicOnly) {
            $searchQuery->where('is_public', true);
        }

        // Sort by created_at DESC (newest first)
        $searchQuery->orderBy('created_at', 'desc');

        return $searchQuery->paginate($perPage);
    }
}
