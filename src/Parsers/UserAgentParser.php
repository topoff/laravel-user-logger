<?php

namespace Topoff\LaravelUserLogger\Parsers;

use Illuminate\Http\Request;
use UserAgentParser\Model\UserAgent;
use UserAgentParser\Provider;

/**
 * Class MultiUserAgentParser
 */
class UserAgentParser
{
    protected UserAgent $parseResult;

    /**
     * UserAgentParser constructor.
     *
     *
     * @throws \UserAgentParser\Exception\NoResultFoundException
     * @throws \UserAgentParser\Exception\PackageNotLoadedException
     */
    public function __construct(protected Request $request)
    {
        $this->parse();
    }

    /**
     * chained parsing until one provider detects the agent
     *
     * @throws \UserAgentParser\Exception\NoResultFoundException
     */
    protected function parse(): void
    {
        if ($this->request->userAgent() === null) {
            $this->parseResult = new UserAgent;

            return;
        }

        // If you want to use more then one provider, you can use the @see Provider\Chain::class() instead of a single provider itself
        $provider = new Provider\MatomoDeviceDetector;
        $this->parseResult = $provider->parse($this->request->userAgent(), $this->request->headers->all());
    }

    /**
     * Delivers the agent attributes from the agent of the current request
     */
    public function getAgentAttributes(): ?array
    {
        try {
            return [
                'name' => $this->request->userAgent(),
                'browser' => $this->parseResult->getBrowser()->getName(),
                'browser_version' => $this->parseResult->getBrowser()->getVersion()->getComplete(),
            ];
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Delivers the device attributes from the agent of the current request
     */
    public function getDeviceAttributes(): ?array
    {
        try {
            $device = $this->parseResult->getDevice();

            return [
                'kind' => mb_strtolower($device->getType()),
                'model' => mb_strtolower($device->getModel()),
                'platform' => mb_strtolower($this->parseResult->getOperatingSystem()->getName()),
                'platform_version' => mb_strtolower($this->parseResult->getOperatingSystem()->getVersion()->getComplete()),
                'is_mobile' => $this->parseResult->isMobile(),
                'is_robot' => $this->parseResult->isBot(),
            ];
        } catch (\Exception) {
            return null;
        }
    }
}
