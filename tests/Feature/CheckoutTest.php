<?php

namespace Tests\Feature;

use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Website checkout through Tripay for the super admin's accounts. */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private mixed $createResponse = null;

    private string $detailStatus = 'UNPAID';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.tripay' => ['mode' => 'sandbox', 'api_key' => 'test-api', 'private_key' => 'test-private', 'merchant_code' => 'T1234', 'expiry_minutes' => 60]]);
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);
        $this->superAdmin = User::factory()->create(['name' => 'Razan']);
        // Tripay's sandbox API. Tests change $createResponse / $detailStatus to steer it.
        Http::fake(fn (HttpRequest $request) => match (true) {
            str_contains($request->url(), 'merchant/payment-channel') => Http::response(['success' => true, 'data' => [
                ['group' => 'E-Wallet', 'code' => 'QRIS', 'name' => 'QRIS', 'icon_url' => 'https://tripay.co.id/qris.png', 'active' => true, 'fee_customer' => ['flat' => 750, 'percent' => 0.7], 'minimum_amount' => 1000, 'maximum_amount' => 5000000],
                ['group' => 'Virtual Account', 'code' => 'BRIVA', 'name' => 'BRI Virtual Account', 'icon_url' => '', 'active' => true, 'fee_customer' => ['flat' => 4250, 'percent' => 0], 'minimum_amount' => 10000, 'maximum_amount' => 0],
                ['group' => 'Virtual Account', 'code' => 'OFF', 'name' => 'Nonaktif', 'icon_url' => '', 'active' => false, 'fee_customer' => ['flat' => 0, 'percent' => 0]],
            ]]),
            str_contains($request->url(), 'transaction/create') => $this->createResponse ?? Http::response(['success' => true, 'data' => [
                'reference' => 'DEV-T1234ABC', 'pay_code' => null, 'checkout_url' => 'https://tripay.co.id/checkout/DEV-T1234ABC', 'qr_url' => 'https://tripay.co.id/qr/DEV-T1234ABC',
                'amount' => 353200, 'fee_customer' => 3200, 'expired_time' => now()->addHour()->getTimestamp(), 'status' => 'UNPAID',
                'instructions' => [['title' => 'Pembayaran via QRIS', 'steps' => ['Buka aplikasi <b>e-wallet</b>', 'Scan kode QR']]],
            ]]),
            str_contains($request->url(), 'transaction/detail') => Http::response(['success' => true, 'data' => ['status' => $this->detailStatus]]),
            default => Http::response([], 404),
        });
    }

    private function storeAccount(array $attributes = []): GameAccount
    {
        return GameAccount::factory()->create(['owner_id' => $this->superAdmin->id, ...$attributes]);
    }

    private function checkout(User $buyer, GameAccount $account, array $data = [])
    {
        return $this->actingAs($buyer)->post('/checkout', ['akun' => [$account->id], 'method' => 'QRIS', 'customer_name' => 'Budi', 'customer_phone' => '0812-3456-7890', 'agree' => '1', ...$data]);
    }

    public function test_only_the_super_admins_own_accounts_use_the_website_checkout(): void
    {
        $store = $this->storeAccount();
        // The super admin's accounts use the checkout even when a seller number is filled in.
        $withSellerNumber = $this->storeAccount(['seller_whatsapp' => '6289999999999']);
        $regular = GameAccount::factory()->create(['owner_id' => User::factory()->regularAdmin()->create()->id, 'seller_whatsapp' => '6288888888888']);
        $rental = $this->storeAccount(['is_rental' => true]);

        foreach ([$store, $withSellerNumber] as $account) {
            $this->get('/akun/'.$account->slug)->assertOk()->assertSee(route('checkout', ['akun' => [$account->id]]), false)->assertSee('Bayar aman di website');
        }
        $this->get('/akun/'.$regular->slug)->assertOk()->assertDontSee('data-checkout-button', false)->assertSee(e($regular->buyWhatsappUrl()), false);
        $this->assertFalse($rental->usesPaymentGateway());

        // Without Tripay keys everything stays on WhatsApp.
        config(['services.tripay.api_key' => null]);
        $this->get('/akun/'.$store->slug)->assertOk()->assertDontSee('data-checkout-button', false);
    }

    public function test_guests_sign_in_first_and_admins_cannot_check_out(): void
    {
        $account = $this->storeAccount();
        $this->get('/checkout?akun[]='.$account->id)->assertRedirect(route('account.login'));
        $this->actingAs($this->superAdmin)->get('/checkout?akun[]='.$account->id)->assertForbidden();
    }

    public function test_the_checkout_page_lists_active_channels_with_the_buyers_fee(): void
    {
        $account = $this->storeAccount(['price' => 350000]);
        $buyer = User::factory()->buyer()->create(['whatsapp' => '081234567890']);

        $this->actingAs($buyer)->get('/checkout?akun[]='.$account->id)->assertOk()
            ->assertSee('QRIS')->assertSee('data-fee="3200"', false)->assertSee('BRI Virtual Account')->assertSee('Biaya Rp 4.250')
            ->assertDontSee('Nonaktif')->assertSee('081234567890')->assertSee('Ketentuan Layanan')->assertSee('mode uji coba');
    }

    public function test_paying_creates_the_order_holds_the_account_and_shows_the_instructions(): void
    {
        $account = $this->storeAccount(['price' => 350000]);
        $buyer = User::factory()->buyer()->create();

        $this->checkout($buyer, $account, ['agree' => null])->assertSessionHasErrors('agree');
        $response = $this->checkout($buyer, $account);
        $order = Order::sole();
        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame(['unpaid', 350000, 3200, 353200, 'DEV-T1234ABC', '6281234567890'], [$order->status, $order->subtotal, $order->fee, $order->total, $order->tripay_reference, preg_replace('/^0/', '62', $order->customer_phone)]);
        Http::assertSent(fn (HttpRequest $request): bool => str_ends_with($request->url(), 'transaction/create')
            && $request['signature'] === hash_hmac('sha256', 'T1234'.$order->merchant_ref.'350000', 'test-private')
            && $request['amount'] === 350000 && $request['order_items'][0]['name'] === $account->title
            && $request->hasHeader('Authorization', 'Bearer test-api'));

        $this->get(route('orders.show', $order))->assertOk()->assertSee('Rp 353.200')->assertSee('https://tripay.co.id/qr/DEV-T1234ABC')
            ->assertSee('Buka aplikasi e-wallet')->assertDontSee('<b>e-wallet', false)->assertSee('data-order-poll', false);
        $this->get('/akun/'.$account->slug)->assertSee('Lanjutkan pembayaran');

        // Another buyer cannot pay for the held account.
        $other = User::factory()->buyer()->create();
        $this->actingAs($other)->get('/akun/'.$account->slug)->assertSee('Sedang dibayar pembeli lain');
        $this->checkout($other, $account)->assertRedirect(route('cart'))->assertSessionHas('error');
        $this->assertSame(1, Order::count());
        // Nor can they see the first buyer's order.
        $this->get(route('orders.show', $order))->assertNotFound();
    }

    public function test_a_failed_tripay_request_leaves_no_order_behind(): void
    {
        $this->createResponse = Http::response(['success' => false, 'message' => 'Invalid method'], 400);
        $account = $this->storeAccount();
        $this->checkout(User::factory()->buyer()->create(), $account)->assertSessionHas('error');
        $this->assertSame(0, Order::count());
    }

    public function test_the_signed_callback_marks_the_order_paid_and_the_account_sold(): void
    {
        $account = $this->storeAccount();
        $buyer = User::factory()->buyer()->create();
        $this->checkout($buyer, $account);
        $order = Order::sole();
        $body = json_encode(['reference' => 'DEV-T1234ABC', 'merchant_ref' => $order->merchant_ref, 'status' => 'PAID', 'total_amount' => 353200]);
        $post = fn (string $signature, string $event = 'payment_status') => $this->call('POST', '/payment/tripay/callback', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CALLBACK_SIGNATURE' => $signature, 'HTTP_X_CALLBACK_EVENT' => $event], $body);

        $post('salah')->assertForbidden();
        $this->assertSame('unpaid', $order->fresh()->status);

        $signature = hash_hmac('sha256', $body, 'test-private');
        $post($signature)->assertOk()->assertJson(['success' => true]);
        $post($signature)->assertOk();
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
        $this->assertSame('sold', $account->fresh()->status);
        $this->assertNotNull($account->fresh()->sold_at);

        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->assertSee('Pembayaran berhasil')->assertDontSee('data-order-poll', false);
        $this->get('/pesanan')->assertOk()->assertSee($order->merchant_ref)->assertSee('Lunas');
    }

    public function test_the_order_page_catches_up_with_tripay_and_expires_locally(): void
    {
        $account = $this->storeAccount();
        $buyer = User::factory()->buyer()->create();
        $this->checkout($buyer, $account);
        $order = Order::sole();

        $this->detailStatus = 'PAID';
        $this->actingAs($buyer)->getJson(route('orders.status', $order))->assertJson(['status' => 'paid']);
        $this->assertSame('sold', $account->fresh()->status);

        // An unpaid order past its window is expired and releases the account.
        $account2 = $this->storeAccount();
        $this->detailStatus = 'UNPAID';
        $this->createResponse = Http::response(['success' => true, 'data' => ['reference' => 'DEV-2', 'amount' => 350000, 'fee_customer' => 0]]);
        $this->checkout($buyer, $account2);
        $second = Order::latest('id')->first();
        $second->update(['expires_at' => now()->subMinute()]);
        $this->assertNull($account2->fresh()->pendingOrder());
        $this->get(route('orders.show', $second))->assertOk()->assertSee('Pembayaran tidak selesai');
        $this->assertSame('expired', $second->fresh()->status);
        $this->assertSame('available', $account2->fresh()->status);
    }

    public function test_a_payment_for_an_account_sold_meanwhile_is_flagged_for_the_admin(): void
    {
        $account = $this->storeAccount();
        $this->checkout(User::factory()->buyer()->create(), $account);
        $order = Order::sole();
        $account->update(['status' => 'sold']);

        $order->markPaid();
        $this->assertStringContainsString('refund atau ganti akun', $order->fresh()->admin_note);
    }

    public function test_the_super_admin_hands_over_paid_orders(): void
    {
        $account = $this->storeAccount();
        $this->checkout(User::factory()->buyer()->create(['name' => 'Budi Pembeli']), $account);
        $order = Order::sole();
        $order->markPaid();

        $this->actingAs($this->superAdmin)->get('/admin/orders')->assertOk()->assertSee($order->merchant_ref)->assertSee('Sudah diserahkan')->assertSee('Pesanan website');
        $this->patch(route('admin.orders.deliver', $order), ['delivered' => '1'])->assertSessionHas('success');
        $this->assertNotNull($order->fresh()->delivered_at);
        $this->get('/admin/orders?tab=done')->assertOk()->assertSee($order->merchant_ref);

        $this->actingAs(User::factory()->regularAdmin()->create())->get('/admin/orders')->assertForbidden();
    }

    public function test_the_cart_pays_store_accounts_on_the_website_and_the_rest_by_whatsapp(): void
    {
        $store = $this->storeAccount(['price' => 100000]);
        $otherAdmins = GameAccount::factory()->create(['owner_id' => User::factory()->regularAdmin()->create()->id, 'seller_whatsapp' => '6289999999999', 'price' => 200000]);

        $html = $this->getJson('/keranjang/accounts?'.http_build_query(['ids' => [$store->id, $otherAdmins->id]]))->assertOk()->json('html');
        $this->assertStringContainsString(e(route('checkout', ['akun' => [$store->id]])), $html);
        $this->assertStringContainsString('Bayar di website (1 akun · Rp 100.000)', $html);
        $this->assertStringContainsString('https://wa.me/6289999999999', $html);
    }
}
