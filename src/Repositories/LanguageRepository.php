<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Language;

class LanguageRepository
{
    /**
     * Finds an existing Language or creates a new DB Record
     *
     * @param array $attributes
     *
     * @return Language
     */
    public function findOrCreate(Array $attributes): Language
    {
        if (empty($attributes['preference'])) {
            $attributes['preference'] = 'unknown';
        }

        return Language::firstOrCreate($attributes);
    }
}