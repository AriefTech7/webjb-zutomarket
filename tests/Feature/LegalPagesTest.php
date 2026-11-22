<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Terms of service and refund policy pages (required by the payment gateway review). */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_and_refund_pages_are_public_and_linked_from_the_footer(): void
    {
        Setting::create(['key' => 'store_name', 'value' => 'zutomarket.id']);

        $this->get('/ketentuan-layanan')->assertOk()->assertSee('Ketentuan Layanan')->assertSee('Berlaku sejak 22 November 2026')
            ->assertSee('zutomarket.id adalah toko online')->assertSee('Pembayaran dan serah terima akun')->assertSee('DP 30%')
            ->assertSee('href="'.route('refund').'"', false)->assertSee('href="'.route('installment').'"', false);
        $this->get('/kebijakan-refund')->assertOk()->assertSee('Kebijakan Refund')->assertSee('Refund penuh')->assertSee('Tidak dapat di-refund')
            ->assertSee('Cara mengajukan refund')->assertSee('1–3 hari kerja')->assertSee('href="'.route('terms').'"', false);

        $this->get('/')->assertOk()->assertSee('href="'.route('terms').'"', false)->assertSee('href="'.route('refund').'"', false)
            ->assertSee('Ketentuan Layanan')->assertSee('Kebijakan Refund');
    }
}
