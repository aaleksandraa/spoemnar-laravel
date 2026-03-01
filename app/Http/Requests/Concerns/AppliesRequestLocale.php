<?php

namespace App\Http\Requests\Concerns;

use App\Support\LocaleResolver;

trait AppliesRequestLocale
{
    protected function applyRequestLocale(): void
    {
        $candidate = $this->input('locale');

        if (!is_string($candidate) || trim($candidate) === '') {
            $candidate = $this->localeFromAcceptLanguage();
        }

        if (!is_string($candidate)) {
            return;
        }

        $normalized = LocaleResolver::normalizeLocale($candidate);
        if (!LocaleResolver::isSupported($normalized)) {
            return;
        }

        app()->setLocale($normalized);

        if ($this->has('locale')) {
            $this->merge(['locale' => $normalized]);
        }
    }

    private function localeFromAcceptLanguage(): ?string
    {
        foreach ($this->getLanguages() as $language) {
            $primary = substr(strtolower((string) $language), 0, 2);
            if (LocaleResolver::isSupported($primary)) {
                return $primary;
            }
        }

        return null;
    }
}
