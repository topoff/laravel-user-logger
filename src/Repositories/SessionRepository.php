<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Topoff\LaravelUserLogger\Models\Agent;
use Topoff\LaravelUserLogger\Models\Device;
use Topoff\LaravelUserLogger\Models\Domain;
use Topoff\LaravelUserLogger\Models\Language;
use Topoff\LaravelUserLogger\Models\Referer;
use Topoff\LaravelUserLogger\Models\Session;

class SessionRepository
{
    /**
     * Finds an existing Uri or creates a new DB Record
     * If there was no user in the session but now there is one, it gets updated.
     * updates field updated_at on every access
     *
     * @param string        $key
     * @param User|null     $user
     * @param Device|null   $device
     * @param Agent|null    $agent
     * @param Referer|null  $referer
     * @param Language|null $language
     * @param string|null   $clientIp
     * @param bool          $isRobot
     *
     * @return Session
     */
    public function findOrCreate(string $key, User $user = NULL, Device $device = NULL, Agent $agent = NULL, Referer $referer = NULL, Language $language = NULL, string $clientIp = NULL, ?bool $isRobot = false): Session
    {
        $session = Session::firstOrCreate(['session_key' => $key], [
            'user_id'     => $user->id ?? NULL,
            'device_id'   => $device->id ?? NULL,
            'agent_id'    => $agent->id ?? NULL,
            'referer_id'  => $referer->id ?? NULL,
            'language_id' => $language->id ?? NULL,
            'client_ip'   => $clientIp ?? NULL,
            'is_robot'    => $isRobot,
        ]);

        if ($session->exists === true) {
            $session->updated_at = Carbon::now();
            $session->user_id = $session->user_id ?? $user->id;
            $session->save();
        }

        return $session;
    }
}