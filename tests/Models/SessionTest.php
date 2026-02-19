<?php

namespace Topoff\LaravelUserLogger\Tests\Models;

use Topoff\LaravelUserLogger\Models\Session;
use Topoff\LaravelUserLogger\Tests\TestCase;

require_once __DIR__.'/../TestCase.php';

class SessionTest extends TestCase
{
    public function test_robot_and_suspicious_helpers(): void
    {
        $session = new Session([
            'is_robot' => true,
            'is_suspicious' => false,
        ]);

        $this->assertTrue($session->isRobot());
        $this->assertFalse($session->isNoRobot());
        $this->assertFalse($session->isSuspicious());
        $this->assertTrue($session->isNotSuspicious());
    }
}
