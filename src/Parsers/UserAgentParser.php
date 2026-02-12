<?php

namespace Topoff\LaravelUserLogger\Parsers;

use DeviceDetector\DeviceDetector;
use Illuminate\Http\Request;

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

        $this->detector = new DeviceDetector($this->request->userAgent());
        $this->detector->parse();
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
