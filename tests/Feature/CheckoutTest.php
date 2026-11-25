<?php

namespace Tests\Feature;

use App\Http\Controllers\JokiOrderController;
use App\Models\ChatConversation;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\Voucher;
use App\Services\JokiPricing;
use App\Services\Tripay;
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

        // "Chat pembeli" opens the live chat with the buyer, a hand-over message ready to edit.
        $this->get('/admin/orders?tab=done')->assertSee(route('admin.orders.chat', $order), false)->assertSee('Chat pembeli');
        $response = $this->get(route('admin.orders.chat', $order));
        $conversation = ChatConversation::where('user_id', $order->user_id)->sole();
        $response->assertRedirect(route('admin.chat.show', $conversation));
        $this->get(route('admin.chat.show', $conversation))->assertOk()->assertSee('Budi Pembeli')
            ->assertSee('terima kasih sudah membayar pesanan '.$order->merchant_ref, false);
        $this->get(route('admin.orders.chat', $order));
        $this->assertSame(1, ChatConversation::count());

        $this->actingAs(User::factory()->regularAdmin()->create())->get('/admin/orders')->assertForbidden();
        $this->get(route('admin.orders.chat', $order))->assertForbidden();
    }

    public function test_the_super_admin_switches_the_payment_gateway_off_and_on(): void
    {
        $account = $this->storeAccount();
        $buyer = User::factory()->buyer()->create();
        $this->actingAs($this->superAdmin)->get('/admin/orders')->assertOk()->assertSee('Gunakan payment gateway')->assertSee('Matikan (OFF)');

        // Off: every "Beli" goes to WhatsApp and the checkout refuses.
        $this->patch(route('admin.orders.gateway'), ['enabled' => '0'])->assertSessionHas('success');
        $this->assertFalse($account->fresh()->usesPaymentGateway());
        $this->get('/admin/orders')->assertSee('Nyalakan (ON)');
        $this->actingAs($buyer)->get('/akun/'.$account->slug)->assertOk()->assertDontSee('data-checkout-button', false)->assertSee(e($account->buyWhatsappUrl()), false);
        $this->get('/checkout?akun[]='.$account->id)->assertRedirect(route('cart'));
        $this->assertStringNotContainsString('Bayar di website', $this->getJson('/keranjang/accounts?'.http_build_query(['ids' => [$account->id]]))->json('html'));

        // On again.
        $this->actingAs($this->superAdmin)->patch(route('admin.orders.gateway'), ['enabled' => '1'])->assertSessionHas('success');
        $this->actingAs($buyer)->get('/akun/'.$account->slug)->assertSee('data-checkout-button', false);

        // Only the super admin may flip it.
        $this->actingAs(User::factory()->regularAdmin()->create())->patch(route('admin.orders.gateway'), ['enabled' => '0'])->assertForbidden();
        $this->assertTrue($account->fresh()->usesPaymentGateway());
    }

    public function test_the_super_admin_creates_switches_and_deletes_vouchers(): void
    {
        $this->actingAs($this->superAdmin)->get('/admin/vouchers')->assertOk()->assertSee('Buat kode voucher')->assertSee('name="code"', false);
        $this->post('/admin/vouchers', ['code' => ' hemat 10 ', 'type' => 'percent', 'value' => '10', 'max_discount' => '20.000', 'min_purchase' => '100.000', 'quota' => 5, 'per_user' => 1, 'ends_at' => '2030-01-31T23:59'])->assertSessionHasNoErrors();
        $voucher = Voucher::sole();
        $this->assertSame(['HEMAT10', 'percent', 10, 20000, 100000, 5, 1, true], [$voucher->code, $voucher->type, $voucher->value, $voucher->max_discount, $voucher->min_purchase, $voucher->quota, $voucher->per_user, $voucher->is_active]);
        $this->assertSame('2030-01-31 16:59', $voucher->ends_at->format('Y-m-d H:i'));
        $this->get('/admin/vouchers')->assertSee('HEMAT10')->assertSee('10% (maks Rp 20.000)')->assertSee('Dipakai 0 / 5');

        $this->post('/admin/vouchers', ['code' => 'HEMAT10', 'type' => 'fixed', 'value' => '5000'])->assertSessionHasErrors('code');
        $this->post('/admin/vouchers', ['code' => 'ada spasi!', 'type' => 'fixed', 'value' => '5000'])->assertSessionHasErrors('code');
        $this->post('/admin/vouchers', ['code' => 'BESAR', 'type' => 'percent', 'value' => '150'])->assertSessionHasErrors('value');
        $this->post('/admin/vouchers', ['code' => 'POTONG25', 'type' => 'fixed', 'value' => 'Rp 25.000', 'max_discount' => '9999'])->assertSessionHasNoErrors();
        $this->assertSame([25000, null, null], [Voucher::where('code', 'POTONG25')->value('value'), Voucher::where('code', 'POTONG25')->value('max_discount'), Voucher::where('code', 'POTONG25')->value('starts_at')]);

        $this->patch(route('admin.vouchers.toggle', $voucher))->assertSessionHas('success');
        $this->assertFalse($voucher->fresh()->is_active);
        $this->delete(route('admin.vouchers.destroy', $voucher))->assertSessionHas('success');
        $this->assertSame(1, Voucher::count());

        $this->actingAs(User::factory()->regularAdmin()->create())->get('/admin/vouchers')->assertForbidden();
        $this->post('/admin/vouchers', ['code' => 'CURANG', 'type' => 'fixed', 'value' => 1000])->assertForbidden();
    }

    public function test_a_buyer_pays_less_with_a_voucher(): void
    {
        $account = $this->storeAccount(['price' => 350000]);
        $buyer = User::factory()->buyer()->create();
        Voucher::create(['code' => 'HEMAT10', 'type' => 'percent', 'value' => 10, 'max_discount' => 30000, 'per_user' => 1]);

        // "Pakai" reloads the checkout with the discount; fees are worked out on the discounted price.
        $this->actingAs($buyer)->get('/checkout?akun[]='.$account->id.'&voucher=hemat10')->assertOk()
            ->assertSee('Voucher HEMAT10')->assertSee('−Rp 30.000', false)->assertSee('Rp 320.000')->assertSee('data-subtotal="320000"', false)
            ->assertSee('data-fee="2990"', false)->assertSee('name="voucher" value="HEMAT10"', false);
        $this->get('/checkout?akun[]='.$account->id.'&voucher=SALAH')->assertOk()->assertSee('Kode voucher tidak ditemukan.')->assertSee('data-subtotal="350000"', false);

        $this->checkout($buyer, $account, ['voucher' => 'HEMAT10']);
        $order = Order::sole();
        $this->assertSame([350000, 30000, 'HEMAT10', 320000], [$order->subtotal, $order->discount, $order->voucher_code, $order->payable()]);
        Http::assertSent(fn (HttpRequest $request): bool => str_ends_with($request->url(), 'transaction/create') && $request['amount'] === 320000
            && $request['order_items'][0]['price'] === 320000 && $request['signature'] === hash_hmac('sha256', 'T1234'.$order->merchant_ref.'320000', 'test-private'));
        $this->get(route('orders.show', $order))->assertSee('Voucher HEMAT10')->assertSee('−Rp 30.000', false);

        // One use per buyer: the waiting order already counts.
        $other = $this->storeAccount(['price' => 200000]);
        $this->get('/checkout?akun[]='.$other->id.'&voucher=HEMAT10')->assertSee('Kamu sudah memakai voucher ini.');
        $this->checkout($buyer, $other, ['voucher' => 'HEMAT10'])->assertSessionHas('error', 'Kamu sudah memakai voucher ini.');
        $this->assertSame(1, Order::count());
    }

    public function test_voucher_rules(): void
    {
        $buyer = User::factory()->buyer()->create();
        $voucher = fn (array $attributes) => Voucher::create(['code' => 'V'.Voucher::count(), 'type' => 'fixed', 'value' => 10000, 'per_user' => null, ...$attributes]);

        $this->assertSame('Kode voucher tidak berlaku.', $voucher(['is_active' => false])->problemFor(100000, $buyer));
        $this->assertSame('Kode voucher tidak berlaku.', $voucher(['starts_at' => now()->addDay()])->problemFor(100000, $buyer));
        $this->assertSame('Kode voucher sudah kedaluwarsa.', $voucher(['ends_at' => now()->subMinute()])->problemFor(100000, $buyer));
        $this->assertSame('Voucher ini berlaku untuk pembelian minimal Rp 150.000.', $voucher(['min_purchase' => 150000])->problemFor(100000, $buyer));
        $this->assertStringContainsString('total bayar minimal', $voucher(['value' => 95000])->problemFor(100000, $buyer));
        $this->assertNull($voucher([])->problemFor(100000, $buyer));

        // Percent with a cap; a fixed voucher never takes more than the price.
        $this->assertSame([25000, 50000], [$voucher(['type' => 'percent', 'value' => 25])->discountFor(100000), $voucher(['type' => 'percent', 'value' => 25, 'max_discount' => 50000])->discountFor(400000)]);
        $this->assertSame(5000, $voucher(['value' => 9000])->discountFor(5000));

        // Quota: a waiting order holds a use until it expires.
        $limited = $voucher(['quota' => 1]);
        $order = Order::create(['merchant_ref' => 'ZMT1', 'user_id' => User::factory()->buyer()->create()->id, 'status' => 'unpaid', 'subtotal' => 100000, 'voucher_id' => $limited->id, 'voucher_code' => $limited->code, 'discount' => 10000, 'fee' => 0, 'total' => 90000, 'payment_method' => 'QRIS', 'payment_name' => 'QRIS', 'customer_name' => 'A', 'customer_email' => 'a@b.c', 'customer_phone' => '0812', 'expires_at' => now()->addHour()]);
        $this->assertSame('Kuota voucher ini sudah habis.', $limited->problemFor(100000, $buyer));
        $order->update(['expires_at' => now()->subMinute()]);
        $this->assertNull($limited->problemFor(100000, $buyer));

        // Tripay gets item prices that add up to the discounted amount.
        $this->assertSame([270000, 180000], Tripay::splitDiscount([300000, 200000], 50000));
        $this->assertSame([66667, 33333], Tripay::splitDiscount([100000, 50000], 50000));
    }

    public function test_joki_pricing_on_the_server_matches_the_calculator(): void
    {
        $pricing = JokiPricing::fromConfig();
        $this->assertSame(['Epic V', 'Epic IV', 'Epic III', 'Epic II', 'Epic I', 'Legend V', 'Legend IV', 'Legend III', 'Legend II', 'Legend I', 'Mythic', 'Mythical Honor', 'Mythical Glory', 'Mythical Immortal'], array_keys($pricing->rankOptions()));

        // Same examples as tests/Unit/Joki.test.js.
        $quote = $pricing->quote('Epic I', 3, 'Mythic', 2);
        $this->assertSame([29, 2 * 6000 + 25 * 7000 + 2 * 8000], [$quote['stars'], $quote['total']]);
        $this->assertSame([['name' => 'Epic', 'stars' => 2, 'price' => 6000], ['name' => 'Legend', 'stars' => 25, 'price' => 7000], ['name' => 'Mythic', 'stars' => 2, 'price' => 8000]], $quote['lines']);
        $this->assertSame([80000, 8000, 88000], array_values(array_intersect_key($pricing->quote('Mythic', 0, 'Mythic', 10, 10), array_flip(['subtotal', 'extra', 'total']))));
        $this->assertSame('Epic I bintang 3', $quote['from']);
        $this->assertSame('Mythical Honor (30 bintang Mythic)', $pricing->quote('Mythic', 0, 'Mythical Honor', 30)['to']);

        // Not higher, unknown rank or impossible stars: no price.
        $this->assertNull($pricing->quote('Legend V', 0, 'Epic I', 0));
        $this->assertNull($pricing->quote('Rank Palsu', 0, 'Epic I', 0));
        $this->assertNull($pricing->quote('Epic V', 9, 'Epic I', 0));
        $this->assertSame([225000, null], [JokiPricing::packagePrice('Honor – Glory'), JokiPricing::packagePrice('Paket Palsu')]);
    }

    public function test_a_joki_is_paid_on_the_website(): void
    {
        $buyer = User::factory()->buyer()->create();
        $brief = ['mode' => 'calculator', 'service' => 'regular', 'fromRank' => 'Epic I', 'fromStars' => 3, 'toRank' => 'Mythic', 'toStars' => 2,
            'heroes' => ['Layla', 'Miya', 'Tigreal'], 'userId' => '123456789', 'serverId' => '2001', 'loginVia' => 'Moonton', 'loginId' => 'budi@example.com',
            'notes' => 'Jangan pakai Fanny', 'contactName' => 'Budi Joki', 'contactPhone' => '812 3456 7890', 'total' => 1000, 'password' => 'rahasia'];

        // The joki page offers website payment; there is no password field; guests sign in first.
        $this->get('/joki-ml')->assertOk()->assertSee('Bayar di website')->assertSee('action="'.route('joki.prepare').'"', false)
            ->assertSee('data-joki-checkout', false)->assertDontSee('data-signed-in', false)->assertDontSee('type="password"', false);
        $this->post('/joki/bayar', $brief)->assertRedirect(route('account.login'));
        $this->actingAs($buyer)->get('/joki-ml')->assertSee('data-signed-in', false);

        // The price comes from the server, not from the browser ("total" is ignored), and no password is kept.
        $this->post('/joki/bayar', $brief)->assertRedirect(route('checkout', ['joki' => 1]));
        $draft = session(JokiOrderController::SESSION_KEY);
        $this->assertSame(203000, $draft['price']);
        $this->assertStringNotContainsString('rahasia', json_encode($draft));
        $this->get('/checkout?joki=1')->assertOk()->assertSee('Pesanan joki')->assertSee('Epic I bintang 3 → Mythic (2 bintang Mythic)', false)
            ->assertSee('Rp 203.000')->assertSee('123456789 (2001)')->assertSee('Jangan pakai Fanny')->assertSee('value="Budi Joki"', false)->assertSee('value="081234567890"', false)
            ->assertSee('name="joki" value="1"', false);

        $this->post('/checkout', ['joki' => 1, 'method' => 'QRIS', 'customer_name' => 'Budi Joki', 'customer_phone' => '08123456789', 'agree' => '1']);
        $order = Order::sole();
        $item = $order->items()->sole();
        $this->assertSame(['joki', null, 203000], [$item->kind, $item->game_account_id, $item->price]);
        $this->assertSame('Moonton', $item->details['Masuk melalui']);
        $this->assertNull(session(JokiOrderController::SESSION_KEY));
        Http::assertSent(fn (HttpRequest $request): bool => str_ends_with($request->url(), 'transaction/create') && $request['amount'] === 203000 && str_starts_with($request['order_items'][0]['name'], 'Joki Regular ML: Epic I'));

        // Paid: nothing to mark sold, no "refund" warning; the admin sees the brief and opens the chat.
        $order->markPaid();
        $this->assertSame(['paid', null], [$order->fresh()->status, $order->fresh()->admin_note]);
        $this->get(route('orders.show', $order))->assertSee('memulai joki');
        $this->actingAs($this->superAdmin)->get('/admin/orders')->assertSee('Joki')->assertSee('Request hero')->assertSee('Layla, Miya, Tigreal');
        $this->get(route('admin.orders.chat', $order))->assertSessionHas('chat_draft', fn (string $draft): bool => str_contains($draft, 'kirim kata sandi'));
    }

    public function test_joki_packages_and_the_gateway_switch(): void
    {
        $buyer = User::factory()->buyer()->create();
        $brief = ['mode' => 'package', 'packages' => ['Honor – Glory', 'Mythic – Mythic Honor'], 'heroes' => ['A', 'B', 'C'], 'userId' => '111', 'loginVia' => 'Moonton', 'loginId' => 'x@y.z', 'contactName' => 'Budi', 'contactPhone' => '81234567890'];

        $this->actingAs($buyer)->post('/joki/bayar', [...$brief, 'packages' => ['Paket Palsu']])->assertSessionHas('error');
        $this->post('/joki/bayar', $brief)->assertRedirect(route('checkout', ['joki' => 1]));
        $this->assertSame(425000, session(JokiOrderController::SESSION_KEY)['price']);
        $this->post('/joki/bayar', ['mode' => 'calculator', 'service' => 'regular', 'fromRank' => 'Legend V', 'fromStars' => 0, 'toRank' => 'Epic I', 'toStars' => 0, ...array_diff_key($brief, ['mode' => 1, 'packages' => 1])])
            ->assertSessionHas('error', 'Rank tujuan harus lebih tinggi dari rank saat ini.');

        // Without a brief the joki checkout sends the buyer back to the joki page.
        session()->forget(JokiOrderController::SESSION_KEY);
        $this->get('/checkout?joki=1')->assertRedirect(route('joki'));

        // Gateway off: the joki goes back to WhatsApp.
        Setting::create(['key' => 'payment_gateway', 'value' => '0']);
        $this->get('/joki-ml')->assertSee('Pesan via WhatsApp')->assertDontSee('data-joki-checkout', false);
        $this->post('/joki/bayar', $brief)->assertRedirect(route('joki'))->assertSessionHas('error');
    }

    public function test_each_account_can_be_switched_between_website_payment_and_whatsapp(): void
    {
        $account = $this->storeAccount(['title' => 'Akun Saklar']);
        $buyer = User::factory()->buyer()->create();

        // The list shows the switch; clicking it moves the account to WhatsApp.
        $this->actingAs($this->superAdmin)->get('/admin/accounts')->assertOk()->assertSee(route('admin.accounts.gateway', $account), false)->assertSee('Bayar di website');
        $this->patch(route('admin.accounts.gateway', $account))->assertSessionHas('success');
        $this->assertFalse($account->fresh()->use_gateway);
        $this->assertFalse($account->fresh()->usesPaymentGateway());
        $this->get('/admin/accounts')->assertSee('Lewat WhatsApp');
        $this->actingAs($buyer)->get('/akun/'.$account->slug)->assertDontSee('data-checkout-button', false)->assertSee(e($account->buyWhatsappUrl()), false);
        $this->get('/checkout?akun[]='.$account->id)->assertRedirect(route('cart'));

        // The edit form has the same switch.
        $data = ['title' => $account->title, 'category_id' => $account->category_id, 'price' => 350000, 'description' => 'Deskripsi', 'specifications' => 'Rank Mythic', 'status' => 'available'];
        $this->actingAs($this->superAdmin)->get('/admin/accounts/'.$account->id.'/edit')->assertSee('Bayar di website (payment gateway)')->assertDontSee('name="use_gateway" value="1" class="size-4 accent-lime-300" checked', false);
        $this->put('/admin/accounts/'.$account->id, $data + ['use_gateway' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($account->fresh()->usesPaymentGateway());

        // Regular admins can neither flip it nor change it through the form.
        $other = User::factory()->regularAdmin()->create();
        $this->actingAs($other)->patch(route('admin.accounts.gateway', $account))->assertForbidden();
        $own = GameAccount::factory()->create(['owner_id' => $other->id]);
        $this->get('/admin/accounts/'.$own->id.'/edit')->assertDontSee('Bayar di website (payment gateway)');
        $this->put('/admin/accounts/'.$own->id, ['title' => $own->title, 'category_id' => $own->category_id, 'price' => 350000, 'description' => 'D', 'specifications' => 'S', 'status' => 'available', 'use_gateway' => '0'])->assertSessionHasNoErrors();
        $this->assertTrue($own->fresh()->use_gateway);
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
