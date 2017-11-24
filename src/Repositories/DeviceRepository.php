<?php

namespace Topoff\Tracker\Repositories;

use Jenssegers\Agent\Agent;
use Topoff\Tracker\Models\Device;

class DeviceRepository
{
    /**
     * @param Agent $agent
     *
     * @return mixed
     */
    public function findOrCreateDevice(Agent $agent)
    {
        return Device::firstOrCreate(['kind' => 1, 'model' => 'osx', 'platform' => 'test', 'platform_version' => 'xy', 'is_mobile' => true]);
    }
}