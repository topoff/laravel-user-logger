<?php

namespace Topoff\LaravelUserLogger\Parsers;

use DeviceDetector\Cache\LaravelCache;
use DeviceDetector\DeviceDetector;
use Illuminate\Http\Request;
use Throwable;

/**
 * Class UserAgentParser
 */
class UserAgentParser
{
    protected ?DeviceDetector $detector = null;

    public function __construct(protected Request $request)
    {
        $this->parse();
    }

    protected function parse(): void
    {
        if ($this->request->userAgent() === null) {
            return;
        }

        $detector = new DeviceDetector($this->request->userAgent());

        if (config('user-logger.user_agent.cache', true) === true) {
            try {
                $detector->setCache(new LaravelCache);
            } catch (Throwable) {
                // Ignore cache adapter failures and continue with plain parsing.
            }
        }

        if (config('user-logger.user_agent.skip_bot_detection', false) === true) {
            $detector->skipBotDetection();
        }

        $detector->parse();
        $this->detector = $detector;
    }

    /**
     * Delivers the agent attributes from the agent of the current request
     */
    public function getAgentAttributes(): ?array
    {
        if (! $this->detector instanceof DeviceDetector) {
            return null;
        }

        $client = $this->detector->getClient();

        return [
            'name' => $this->request->userAgent(),
            'browser' => $client['name'] ?? '',
            'browser_version' => $client['version'] ?? '',
        ];
    }

    /**
     * Delivers the device attributes from the agent of the current request
     */
    public function getDeviceAttributes(): ?array
    {
        if (! $this->detector instanceof DeviceDetector) {
            return null;
        }

        $os = $this->detector->getOs();

        return [
            'kind' => mb_strtolower($this->detector->getDeviceName()),
            'model' => mb_strtolower($this->detector->getModel()),
            'platform' => mb_strtolower($os['name'] ?? ''),
            'platform_version' => mb_strtolower($os['version'] ?? ''),
            'is_mobile' => $this->detector->isMobile(),
            'is_robot' => $this->detector->isBot(),
        ];
    }

    /**
     * Whether the user agent was detected
     */
    public function hasResult(): bool
    {
        if (! $this->detector instanceof DeviceDetector) {
            return false;
        }

        if ($this->detector->isBot()) {
            return true;
        }

        $client = $this->detector->getClient();
        $os = $this->detector->getOs();

        return ! empty($client['name']) || ! empty($os['name']) || $this->detector->getDevice() !== null;
    }
}
