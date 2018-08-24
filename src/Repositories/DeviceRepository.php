<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Topoff\LaravelUserLogger\Models\Device;

/**
 * Class DeviceRepository
 * @package Topoff\LaravelUserLogger\Repositories
 */
class DeviceRepository
{
    /**
     * Finds an existing Device or creates a new DB Record
     *
     * @param array $attributes
     *
     * @return Device
     */
    public function findOrCreate(Array $attributes): Device
    {
        return Device::firstOrCreate($attributes);
    }

    /**
     * Finds / creats the record for a not detected device
     *
     * @return Device
     */
    public function findOrCreateNotDetected(): Device
    {
        return Device::firstOrCreate([
                                          'kind'             => NULL,
                                          'model'            => NULL,
                                          'platform'         => NULL,
                                          'platform_version' => NULL,
                                          'is_mobile'        => NULL,
                                          'is_robot'         => NULL,
                                      ]);
    }
}