<?php

namespace Topoff\Tracker\Repositories;

use Topoff\Tracker\Models\Device;

class DeviceRepository
{
    /**
     * Finds an existing Device or creates a new DB Record
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function findOrCreate(Array $attributes)
    {
        return Device::firstOrCreate($attributes);
    }
}