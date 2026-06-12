<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Console\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates resources/data/referers.json (snowplow referer-parser format)
 * from the actively maintained matomo/searchengine-and-social-list
 * definitions. Email provider entries are carried over from the original
 * snowplow database, which matomo does not cover.
 *
 * Maintenance-time command: run it in the package repository after a
 * `composer update matomo/searchengine-and-social-list` and commit the
 * regenerated json. Host apps only consume the committed file.
 */
class UpdateReferers extends Command
{
    /**
     * Country-code expansions for matomo's `{}` tld placeholder
     * (e.g. `google.{}`), curated for our markets plus the usual suspects.
     */
    private const array TLDS = [
        'com', 'ch', 'de', 'at', 'li', 'lu', 'fr', 'it', 'nl', 'be', 'es', 'pt', 'pl',
        'co.uk', 'ie', 'dk', 'se', 'no', 'fi', 'cz', 'sk', 'hu', 'ro', 'gr', 'com.tr',
        'ru', 'com.au', 'co.nz', 'ca', 'com.br', 'com.mx', 'co.in', 'co.jp', 'com.hk', 'com.sg',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-logger:update-referers {--output= : Override the output path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerates the bundled referers.json from matomo/searchengine-and-social-list.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! class_exists(Yaml::class) || ! InstalledVersions::isInstalled('matomo/searchengine-and-social-list')) {
            $this->error('Requires the dev dependencies symfony/yaml and matomo/searchengine-and-social-list - run this in the package repository.');

            return self::FAILURE;
        }

        $sourceDir = (string) InstalledVersions::getInstallPath('matomo/searchengine-and-social-list');
        $output = (string) ($this->option('output') ?? dirname(__DIR__, 3).'/resources/data/referers.json');

        // Order matters: later mediums overwrite earlier ones per domain,
        // so the maintained matomo data wins over carried-over email entries.
        $referers = [
            'email' => $this->snowplowEmailReferers(),
            'search' => $this->convertSearchEngines($sourceDir.'/SearchEngines.yml'),
            'social' => $this->convertNameUrlList($sourceDir.'/Socials.yml'),
            'ai' => $this->convertNameUrlList($sourceDir.'/AIAssistants.yml'),
        ];

        if (! is_dir(dirname($output))) {
            mkdir(dirname($output), 0755, true);
        }

        file_put_contents($output, json_encode($referers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        foreach ($referers as $medium => $sources) {
            $domains = array_sum(array_map(static fn (array $source): int => count($source['domains']), $sources));
            $this->info(sprintf('%-7s %4d sources, %5d domains', $medium, count($sources), $domains));
        }
        $this->info("Written to {$output}");

        return self::SUCCESS;
    }

    /**
     * SearchEngines.yml: name => list of {urls, params, backlink, charsets}.
     *
     * @return array<string, array{domains: list<string>, parameters?: list<string>}>
     */
    protected function convertSearchEngines(string $file): array
    {
        $sources = [];

        foreach ($this->parseYaml($file) as $name => $definitions) {
            $domains = [];
            $parameters = [];

            foreach ((array) $definitions as $definition) {
                foreach ((array) ($definition['urls'] ?? []) as $url) {
                    $domains = array_merge($domains, $this->expandTldPlaceholder((string) $url));
                }
                foreach ((array) ($definition['params'] ?? []) as $param) {
                    // Skip matomo's regex-based params - snowplow only supports plain query parameter names.
                    if (is_string($param) && $param !== '' && ! str_starts_with($param, '/')) {
                        $parameters[] = $param;
                    }
                }
            }

            if ($domains !== []) {
                $sources[(string) $name] = [
                    'domains' => array_values(array_unique($domains)),
                    'parameters' => array_values(array_unique($parameters)),
                ];
            }
        }

        return $sources;
    }

    /**
     * Socials.yml / AIAssistants.yml: name => list of urls.
     *
     * @return array<string, array{domains: list<string>}>
     */
    protected function convertNameUrlList(string $file): array
    {
        $sources = [];

        foreach ($this->parseYaml($file) as $name => $urls) {
            $domains = [];
            foreach ((array) $urls as $url) {
                $domains = array_merge($domains, $this->expandTldPlaceholder((string) $url));
            }

            if ($domains !== []) {
                $sources[(string) $name] = ['domains' => array_values(array_unique($domains))];
            }
        }

        return $sources;
    }

    /**
     * Carries over the email provider entries from the bundled snowplow
     * database, which the matomo list does not provide.
     *
     * @return array<string, array{domains: list<string>, parameters?: list<string>}>
     */
    protected function snowplowEmailReferers(): array
    {
        $snowplowFile = InstalledVersions::getInstallPath('snowplow/referer-parser').'/php/data/referers.json';
        if (! is_file($snowplowFile)) {
            $this->warn('snowplow referers.json not found - skipping email provider entries.');

            return [];
        }

        $data = json_decode((string) file_get_contents($snowplowFile), true, flags: JSON_THROW_ON_ERROR);

        return $data['email'] ?? [];
    }

    /**
     * @return list<string>
     */
    protected function expandTldPlaceholder(string $url): array
    {
        if (! str_contains($url, '{}')) {
            return [$url];
        }

        return array_map(static fn (string $tld): string => str_replace('{}', $tld, $url), self::TLDS);
    }

    protected function parseYaml(string $file): array
    {
        if (! is_file($file)) {
            throw new RuntimeException("Definition file not found: {$file}");
        }

        return (array) Yaml::parseFile($file);
    }
}
