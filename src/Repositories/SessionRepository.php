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
use Topoff\LaravelUserLogger\Support\IpHasher;

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
            'client_ip' => $this->prepareIp($clientIp),
            'is_suspicious' => $suspicious,
            'is_robot' => $isRobot,
        ]);

        $this->updateUser($session, $user);

        return $session;
    }

    protected function prepareIp(?string $clientIp): ?string
    {
        if (in_array($clientIp, [null, '', '0'], true)) {
            return null;
        }

        if (config('user-logger.hash_ip', true) === true) {
            return IpHasher::hash($clientIp);
        }

        return $clientIp;
    }

    public function setRobotAndSuspicious(Session $session): Session
    {
        $session->is_robot = true;
        $session->is_suspicious = true;
        $session->save();
        $this->forgetCached($session);

        return $session;
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
            $this->forgetCached($session);
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

    protected function forgetCached(Session $session): void
    {
        Cache::forget("Session_{$session->id}");
    }
}
