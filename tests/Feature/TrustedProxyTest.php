<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_trusted_proxy_preserves_https_redirects_without_trusting_forwarded_host(): void
    {
        config(['trustedproxy.proxies' => '192.0.2.1']);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1']);

        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'attacker.example',
        ])->get('http://shop.example/admin/finance')->assertRedirect('https://shop.example/admin/login');
    }

    public function test_untrusted_client_cannot_change_redirect_scheme(): void
    {
        config(['trustedproxy.proxies' => '192.0.2.1']);
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1']);

        $this->withHeader('X-Forwarded-Proto', 'https')->get('http://shop.example/admin/finance')
            ->assertRedirect('http://shop.example/admin/login');
    }

    #[TestWith(['192.0.2.1', 422])]
    #[TestWith(['', 429])]
    public function test_forwarded_client_ip_changes_login_quota_only_for_a_trusted_proxy(string $proxies, int $status): void
    {
        $this->freezeTime();
        config(['trustedproxy.proxies' => $proxies]);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1']);
        $this->withHeader('X-Forwarded-For', '198.51.100.1');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/admin/login')->assertUnprocessable();
        }
        $this->postJson('/admin/login')->assertTooManyRequests();

        $this->withHeader('X-Forwarded-For', '198.51.100.2')->postJson('/admin/login')->assertStatus($status);
    }
}
