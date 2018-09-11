<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Topoff\LaravelUserLogger\Models\Agent;
use Topoff\LaravelUserLogger\Models\Device;
use Topoff\LaravelUserLogger\Models\Language;
use Topoff\LaravelUserLogger\Models\Referer;
use Topoff\LaravelUserLogger\Models\Session;

/**
 * Class SessionRepository
 *
 * @package Topoff\LaravelUserLogger\Repositories
 */
class SessionRepository
{
    /**
     * Get or Create a session
     * If there was no user in the session but now there is one, it gets updated.
     *
     * @param string        $uuid
     * @param User|null     $user
     * @param Device|null   $device
     * @param Agent|null    $agent
     * @param Referer|null  $referer
     * @param Language|null $language
     * @param string|null   $clientIp
     * @param bool          $suspicious
     * @param bool          $isRobot
     *
     * @return Session
     */
    public function findOrCreate(string $uuid,
                                 User $user = NULL,
                                 Device $device = NULL,
                                 Agent $agent = NULL,
                                 Referer $referer = NULL,
                                 Language $language = NULL,
                                 string $clientIp = NULL,
                                 bool $suspicious = false,
                                 ?bool $isRobot = false): Session
    {
        if (config('user-logger.log_ip') !== true) {
            $clientIp = NULL;
        }

        $session = Session::firstOrCreate(['id' => $uuid], [
            'user_id'      => $user->id ?? NULL,
            'device_id'    => $device->id ?? NULL,
            'agent_id'     => $agent->id ?? NULL,
            'referer_id'   => $referer->id ?? NULL,
            'language_id'  => $language->id ?? NULL,
            'client_ip'    => !empty($clientIp) ? $this->hashIp($clientIp) : NULL,
            'is_suspicous' => $suspicious,
            'is_robot'     => $isRobot,
        ]);

        $this->updateUser($session, $user);

        return $session;
    }

    /**
     * Hash the ip and change it a bit that it don't fits with lookup tables
     * a little bit security through obscurity
     *
     * @param string $clientIp
     *
     * @return string
     */
    private function hashIp(string $clientIp): string
    {
        $clientIp = md5($clientIp);
        return substr($clientIp, 0, 10) . substr($clientIp, 20, 12) . substr($clientIp, 11, 10);
    }

    /**
     * Updates the user of the session, if not present yet
     *
     * @param Session $session
     * @param User    $user
     *
     * @return Session
     */
    public function updateUser(Session $session, User $user = NULL): Session
    {
        if (empty($session->user_id) && isset($user)) {
            $session->updated_at = Carbon::now();
            $session->user_id = $user->id;
            $session->save();
        }

        return $session;
    }

    /**
     * Get an existing session
     *
     * @param string $uuid
     *
     * @return null|Session
     */
    public function find(string $uuid): ?Session
    {
        return Session::find($uuid);
    }
}