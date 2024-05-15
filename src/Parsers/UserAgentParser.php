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

    protected Request $request;

    /**
     * UserAgentParser constructor.
     *
     *
     * @throws \UserAgentParser\Exception\NoResultFoundException
     * @throws \UserAgentParser\Exception\PackageNotLoadedException
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
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
            $this->parseResult = new UserAgent();

            return;
        }

        $chain = new Provider\Chain([
            new Provider\JenssegersAgent(), // Ist viel schneller, ca. 15ms
            new Provider\MatomoDeviceDetector(), // braucht ca. 600ms
        ]);

        /* @var $result \UserAgentParser\Model\UserAgent */
        $this->parseResult = $chain->parse($this->request->userAgent(), $this->request->headers->all());
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * nginx function to add the missing function getallheaders()
     */
    private function addFunctionGetAllHeaders(): void
    {
        if (! function_exists('getallheaders')) {
            function getallheaders()
            {
                $headers = [];
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }

                return $headers;
            }
        }
    }
}
