<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Device;

/**
 * Class DeviceRepository
 */
class DeviceRepository
{
    /**
     * Finds an existing Device or creates a new DB Record
     */
    public function findOrCreate(array $attributes): Device
    {
        return Device::firstOrCreate($attributes);
    }

    /**
     * Finds / creats the record for a not detected device
     */
    public function findOrCreateNotDetected(): Device
    {
        return Device::firstOrCreate([
            'kind' => null,
            'model' => null,
            'platform' => null,
            'platform_version' => null,
            'is_mobile' => null,
            'is_robot' => null,
        ]);
    }
}
