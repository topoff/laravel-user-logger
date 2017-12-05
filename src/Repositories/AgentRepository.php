<?php

namespace Topoff\Tracker\Repositories;

use Jenssegers\Agent\Agent;
use Topoff\Tracker\Models\Device;
use Topoff\Tracker\Support\MobileDetect;
use Topoff\Tracker\Support\UserAgentParser;
use UAParser\Parser;

class AgentRepository
{
    /**
     * @param Agent $agent
     *
     * @return mixed
     */
    public function findOrCreateAgent(Agent $agent)
    {
        $mobileDetect = new MobileDetect();
        $device = Device::firstOrCreate(['kind' => $mobileDetect->getDeviceKind(), 'model' => $agent->device(), 'platform' => $agent->platform(), 'platform_version' => $agent->version($agent->platform()), 'is_mobile' => $agent->isMobile()]);

//        $agent = Agent::findOrCreate(['name' => $agent->])
    }
}