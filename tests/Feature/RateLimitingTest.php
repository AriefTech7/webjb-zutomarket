<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_returns_429_after_five_failed_attempts(): void
    {
        $this->freezeTime();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/admin/login')->assertUnprocessable()->assertJsonValidationErrors(['username', 'password']);
        }

        $this->postJson('/admin/login')->assertTooManyRequests()->assertHeader('Retry-After', '60');
        $this->assertGuest();
    }

    public function test_admin_login_quota_is_isolated_by_ip_and_resets_after_one_minute(): void
    {
        $this->freezeTime();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/admin/login')->assertUnprocessable();
        }
        $this->postJson('/admin/login')->assertTooManyRequests();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2']);
        $this->postJson('/admin/login')->assertUnprocessable();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1']);
        $this->postJson('/admin/login')->assertTooManyRequests();
        $this->travel(61)->seconds();
        $this->postJson('/admin/login')->assertUnprocessable();
    }
}
