<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Language;
use Topoff\LaravelUserLogger\Support\AttributeLimiter;

class LanguageRepository
{
    /**
     * Finds an existing Language or creates a new DB Record
     */
    public function findOrCreate(array $attributes): Language
    {
        return Language::firstOrCreate(AttributeLimiter::apply($attributes, [
            'preference' => 30,
            'range' => 255,
        ]));
    }
}
