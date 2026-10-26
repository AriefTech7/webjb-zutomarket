<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['GET', '/wishlist/accounts', 60, 200])]
    #[TestWith(['POST', '/admin/login', 5, 422])]
    #[TestWith(['POST', '/tukar-tambah', 8, 422])]
    public function test_exhausted_feature_returns_429_without_blocking_other_features(string $method, string $uri, int $limit, int $status): void
    {
        $this->freezeTime();
        for ($attempt = 0; $attempt < $limit; $attempt++) {
            $this->json($method, $uri)->assertStatus($status);
        }

        $this->json($method, $uri)->assertTooManyRequests()->assertHeader('Retry-After', '60');

        if ($uri !== '/wishlist/accounts') {
            $this->getJson('/wishlist/accounts')->assertOk()->assertJsonPath('ids', []);
        }
        if ($uri !== '/admin/login') {
            $this->postJson('/admin/login')->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        }
        if ($uri !== '/tukar-tambah') {
            $this->postJson('/tukar-tambah')->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->assertDatabaseCount('trade_requests', 0);
        $this->assertGuest();
    }

    #[TestWith(['GET', '/wishlist/accounts', 60, 200])]
    #[TestWith(['POST', '/admin/login', 5, 422])]
    #[TestWith(['POST', '/tukar-tambah', 8, 422])]
    public function test_exhausted_quota_is_isolated_by_ip_and_resets_after_one_minute(string $method, string $uri, int $limit, int $status): void
    {
        $this->freezeTime();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1']);
        for ($attempt = 0; $attempt < $limit; $attempt++) {
            $this->json($method, $uri)->assertStatus($status);
        }
        $this->json($method, $uri)->assertTooManyRequests();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2']);
        $this->json($method, $uri)->assertStatus($status);

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1']);
        $this->json($method, $uri)->assertTooManyRequests();
        $this->travel(61)->seconds();
        $this->json($method, $uri)->assertStatus($status);
    }
}
