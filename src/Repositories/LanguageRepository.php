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
     * @return mixed
     */
    public function findOrCreate(Array $attributes)
    {
        return Language::firstOrCreate($attributes);
    }
}