<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Language;

class LanguageRepository
{
    /**
     * Finds an existing Language or creates a new DB Record
     */
    public function findOrCreate(array $attributes): Language
    {
        return Language::firstOrCreate($attributes);
    }
}
