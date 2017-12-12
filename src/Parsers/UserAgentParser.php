<?php

namespace Topoff\Tracker\Parsers;

use BrowscapPHP\Browscap;
use UserAgentParser\Provider;

/**
 * Class MultiUserAgentParser
 *
 * @package Topoff\Tracker\Parsers
 */
class UserAgentParser
{
    /**
     * @var \UserAgentParser\Model\UserAgent
     */
    protected $parseResult;

    /**
     * @var
     */
    private $userAgent;

    /**
     * UserAgentParser constructor.
     *
     * @param string $userAgent
     */
    public function __construct(string $userAgent)
    {
        $this->userAgent = $userAgent;

        $browscapParser = new Browscap();
        $chain = new Provider\Chain([
                                        new Provider\JenssegersAgent(),
                                        new Provider\BrowscapPhp($browscapParser),
                                        new Provider\PiwikDeviceDetector(),
                                        new Provider\UAParser(),
                                    ]);

        /* @var $result \UserAgentParser\Model\UserAgent */
        $this->parseResult = $chain->parse($this->userAgent);
        // optional add all headers, to improve the result further (used currently only by WhichBrowser)
        //        $test = $this->addFunctionGetAllHeaders();
//        $this->parseResult = $chain->parse($this->userAgent, getallheaders());

        //        $agent = new \Jenssegers\Agent\Agent();
        //        $df = $agent->platform();
        //        $asd = $agent->device();
        //
        //        $uaparse = Parser::create()->parse($userAgent);
        //        $sa = $uaparse->device;
        //        $op = $uaparse->os;
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
                'browser'         => $this->parseResult->getBrowser()->getName(),
                'browser_version' => $this->parseResult->getBrowser()->getVersion()->getComplete(),
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
            $agent = new \Jenssegers\Agent\Agent();

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
     * nginx funktion to add the missing function getallheaders()
     */
    private function addFunctionGetAllHeaders()
    {
        if (!function_exists('getallheaders')) {
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
