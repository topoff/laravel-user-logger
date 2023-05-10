<?php

namespace Topoff\LaravelUserLogger\Parsers;

use Illuminate\Http\Request;

/**
 * Class LanguageParser
 *
 * @package Topoff\LaravelUserLogger\Parsers
 */
class LanguageParser
{
    /**
     * @var Request
     */
    protected $request;

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
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

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

            reset($languages);
            $this->defaultLanguage = key($languages);
            $this->acceptedLanguages = implode(', ', array_keys($languages));
        }
    }

    /**
     * Delivers the language attributes from the agent of the current request
     *
     * @return array|null
     */
    public function getLanguageAttributes(): ?array
    {
        try {
            return [
                'preference' => $this->defaultLanguage,
                'range'      => $this->acceptedLanguages,
            ];
        } catch (\Exception $e) {
            return NULL;
        }
    }
}
