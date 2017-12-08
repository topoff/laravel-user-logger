<?php

namespace Topoff\Tracker\Repositories;

use Topoff\Tracker\Models\Language;

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