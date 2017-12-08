<?php

namespace Topoff\Tracker\Parsers;

class JenssegerUserAgentParser extends UserAgentParser
{
    protected $parsedAgent;

    /**
     * UserAgentParser constructor.
     *
     * @param string $userAgent
     */
    public function __construct(string $userAgent)
    {
        $this->userAgent = $userAgent;

        $this->parsedAgent = new \Jenssegers\Agent\Agent();
    }

    /**
     * Delivers the agent attributes from the agent of the current request
     *
     * @return array|null
     */
    public function getAgentAttributes(): ?array
    {
        try {
            return [
                'name'            => $this->userAgent,
                'browser'         => $this->parsedAgent->browser(),
                'browser_version' => $this->parsedAgent->browser(),
            ];
        } catch (\Exception $e) {
            return NULL;
        }
    }

    /**
     * Delivers the device attributes from the agent of the current request
     *
     * @return array|null
     */
    public function getDeviceAttributes():?array
    {
        try {
            $device = $this->parseResult->getDevice();

            return [
                'kind'             => $device->getType(),
                'model'            => $device->getModel() ?? $agent->device(),
                'platform'         => $this->parseResult->getOperatingSystem()->getName(),
                'platform_version' => $this->parseResult->getOperatingSystem()->getVersion()->getComplete(),
                'is_mobile'        => $this->parseResult->isMobile(),
                'is_robot'         => $this->parseResult->isBot(),
            ];
        } catch (\Exception $e) {
            return NULL;
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
                'preference' => 'de',
                'range'      => 'de,en,fr',
            ];
        } catch (\Exception $e) {
            return NULL;
        }
    }
}
