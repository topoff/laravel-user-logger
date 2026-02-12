<?php

namespace Topoff\LaravelUserLogger\Parsers;

use Illuminate\Http\Request;

/**
 * Class LanguageParser
 */
class LanguageParser
{
    /**
     * @var string
     */
    protected $defaultLanguage;

    /**
     * @var array
     */
    protected $acceptedLanguages;

    /**
     * LanguageParser constructor.
     */
    public function __construct(protected \Illuminate\Http\Request $request)
    {
        $this->parseLanguages();
    }

    /**
     * Get accept languages.
     */
    protected function parseLanguages(): void
    {
        $acceptLanguage = $this->request->header('accept-language');

        if ($acceptLanguage) {
            $languages = [];

            // Parse accept language string.
            foreach (explode(',', $acceptLanguage) as $piece) {
                $parts = explode(';', $piece);
                $language = strtolower($parts[0]);
                $priority = empty($parts[1]) ? 1. : floatval(str_replace('q=', '', $parts[1]));

                $languages[$language] = $priority;
            }

            // Sort languages by priority.
            arsort($languages);
            $this->defaultLanguage = array_key_first($languages);
            $this->acceptedLanguages = implode(', ', array_keys($languages));
        }
    }

    /**
     * Delivers the language attributes from the agent of the current request
     */
    public function getLanguageAttributes(): ?array
    {
        try {
            return [
                'preference' => $this->defaultLanguage,
                'range' => $this->acceptedLanguages,
            ];
        } catch (\Exception) {
            return null;
        }
    }
}
