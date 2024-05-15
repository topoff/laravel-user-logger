<?php

namespace Topoff\LaravelUserLogger\Repositories;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Cache;
use Topoff\LaravelUserLogger\Models\Agent;
use Topoff\LaravelUserLogger\Models\Device;
use Topoff\LaravelUserLogger\Models\Language;
use Topoff\LaravelUserLogger\Models\Referer;
use Topoff\LaravelUserLogger\Models\Session;

class SessionRepository
{
    /**
     * Get or Create a session
     * If there was no user in the session but now there is one, it gets updated.
     */
    public function findOrCreate(string $uuid,
        ?User $user = null,
        ?Device $device = null,
        ?Agent $agent = null,
        ?Referer $referer = null,
        ?Language $language = null,
        ?string $clientIp = null,
        bool $suspicious = false,
        ?bool $isRobot = false): Session
    {
        if (config('user-logger.log_ip') !== true) {
            $clientIp = null;
        }

        $session = Session::firstOrCreate(['id' => $uuid], [
            'user_id' => $user->id ?? null,
            'device_id' => $device->id ?? null,
            'agent_id' => $agent->id ?? null,
            'referer_id' => $referer->id ?? null,
            'language_id' => $language->id ?? null,
            'client_ip' => ! empty($clientIp) ? $this->hashIp($clientIp) : null,
            'is_suspicious' => $suspicious,
            'is_robot' => $isRobot,
        ]);

        $this->updateUser($session, $user);

        return $session;
    }

    public function setRobotAndSuspicious(Session $session): Session
    {
        $session->is_robot = true;
        $session->is_suspicious = true;
        $session->save();

        return $session;
    }

    /**
     * Hash the ip and change it a bit that it don't fits with lookup tables
     * a little bit security through obscurity
     */
    protected function hashIp(string $clientIp): string
    {
        $clientIp = md5($clientIp);

        return substr($clientIp, 0, 10).substr($clientIp, 20, 12).substr($clientIp, 11, 10);
    }

    /**
     * Updates the user of the session, if not present yet
     */
    public function updateUser(Session $session, ?User $user = null): Session
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
     */
    public function find(string $uuid): ?Session
    {
        return Cache::remember("Session_{$uuid}", 3600, fn () => Session::find($uuid));
    }
}
