<?php

namespace Topoff\Tracker\Repositories;

use Illuminate\Foundation\Auth\User;
use Topoff\Tracker\Models\Agent;
use Topoff\Tracker\Models\Device;
use Topoff\Tracker\Models\Language;
use Topoff\Tracker\Models\Referer;
use Topoff\Tracker\Models\Session;

class SessionRepository
{
    /**
     * Finds an existing Uri or creates a new DB Record
     *
     *
     * @param string   $key
     * @param User     $user
     * @param Device   $device
     * @param Agent    $agent
     * @param Referer  $referer
     * @param Language $language
     * @param string   $clientIp
     * @param bool     $isRobot
     *
     * @return mixed
     */
    public function findOrCreate(string $key,
                                 User $user = NULL,
                                 Device $device = NULL,
                                 Agent $agent = NULL,
                                 Referer $referer = NULL,
                                 Language $language = NULL,
                                 string $clientIp = NULL,
                                 bool $isRobot = false)
    {
        return Session::firstOrCreate(['session_key' => $key], [
            'user_id'     => $user->id ?? NULL,
            'device_id'   => $device->id ?? NULL,
            'agent_id'    => $agent->id ?? NULL,
            'referer_id'  => $referer->id ?? NULL,
            'language_id' => $language->id ?? NULL,
            'client_ip'   => $clientIp ?? NULL,
            'is_robot'    => $isRobot,
        ]);
    }
}