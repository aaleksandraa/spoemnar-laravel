<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class MemorialSearch
{
    public static function applyKeywordFilter(Builder $query, string $searchTerm, ?string $locale = null): Builder
    {
        $normalizedSearchTerm = trim($searchTerm);
        if ($normalizedSearchTerm === '') {
            return $query;
        }

        $activeLocale = self::resolveLocale($locale);
        $likePattern = '%'.$normalizedSearchTerm.'%';
        $normalizedLikePattern = '%'.self::normalizeNameToken($normalizedSearchTerm).'%';

        return $query->where(function (Builder $innerQuery) use ($activeLocale, $likePattern, $normalizedLikePattern): void {
            $innerQuery->where('first_name', 'like', $likePattern)
                ->orWhere('last_name', 'like', $likePattern)
                ->orWhere('birth_place', 'like', $likePattern)
                ->orWhere('death_place', 'like', $likePattern)
                ->orWhere('biography', 'like', $likePattern)
                ->orWhereRaw(FullNameSearch::expression().' like ?', [$likePattern])
                ->orWhereRaw(self::normalizedExpression('first_name').' like ?', [$normalizedLikePattern])
                ->orWhereRaw(self::normalizedExpression('last_name').' like ?', [$normalizedLikePattern])
                ->orWhereRaw(self::normalizedExpression(FullNameSearch::expression(), true).' like ?', [$normalizedLikePattern])
                ->orWhereHas('translations', static function (Builder $translationQuery) use ($activeLocale, $likePattern): void {
                    $translationQuery
                        ->where('locale', $activeLocale)
                        ->where(static function (Builder $localizedQuery) use ($likePattern): void {
                            $localizedQuery
                                ->where('birth_place', 'like', $likePattern)
                                ->orWhere('death_place', 'like', $likePattern)
                                ->orWhere('biography', 'like', $likePattern);
                        });
                });
        });
    }

    private static function resolveLocale(?string $locale): string
    {
        if (is_string($locale) && LocaleResolver::isSupported($locale)) {
            return LocaleResolver::normalizeLocale($locale);
        }

        return app()->getLocale();
    }

    private static function normalizedExpression(string $expression, bool $isRawExpression = false): string
    {
        $subject = $isRawExpression
            ? $expression
            : "COALESCE({$expression}, '')";

        return "LOWER(REPLACE({$subject}, 'h', ''))";
    }

    private static function normalizeNameToken(string $value): string
    {
        $normalizedValue = mb_strtolower($value, 'UTF-8');

        return str_replace('h', '', $normalizedValue);
    }
}
