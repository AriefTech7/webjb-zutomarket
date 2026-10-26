<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Models\GameAccount;
use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_pages_and_writes_require_admin_login(): void
    {
        $transaction = FinancialTransaction::factory()->create();

        $this->get('/admin/finance')->assertRedirect('/admin/login');
        $this->get('/admin/finance/create')->assertRedirect('/admin/login');
        $this->post('/admin/finance')->assertRedirect('/admin/login');
        $this->post('/admin/finance/'.$transaction->id.'/reverse')->assertRedirect('/admin/login');
        $this->assertDatabaseCount('financial_transactions', 1);
    }

    public function test_empty_month_has_zero_totals_and_six_month_profit_trend(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));

        $response = $this->get('/admin/finance?month=2024-02');

        $response->assertOk()->assertSee('Belum ada transaksi pada bulan ini.');
        $this->assertSame(0, $response->viewData('totals')['profit']);
        $this->assertCount(6, $response->viewData('trend'));
        $this->assertSame('2023-09', $response->viewData('trend')[0]['month']);
        $this->assertSame('2024-02', $response->viewData('trend')[5]['month']);
        $this->get('/admin/finance?month=2026-13')->assertSessionHasErrors('month');
    }

    public function test_profit_report_excludes_legacy_purchase_and_expense_without_deleting_history(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $this->legacyPurchase($account, '2026-09-20');
        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'amount' => 1000000])->assertSessionHasNoErrors();
        FinancialTransaction::factory()->create(['occurred_on' => '2026-10-10', 'description' => 'Iklan Oktober', 'operating_expense' => 100000, 'cash_out' => 100000]);

        $response = $this->get('/admin/finance?month=2026-10');

        $response->assertOk()->assertDontSee('Iklan Oktober');
        $this->assertSame([
            'revenue' => 1000000, 'cost_of_goods' => 600000, 'sold_units' => 1, 'profit' => 400000,
        ], $response->viewData('totals'));
        $this->assertSame(0, $response->viewData('previous')['cost_of_goods']);
        $this->assertSame(0, $response->viewData('previous')['profit']);
        $this->assertSame(400000, $response->viewData('trend')[5]['profit']);
        $this->assertDatabaseCount('financial_transactions', 3);
        $this->assertDatabaseHas('game_accounts', ['id' => $account->id, 'status' => 'sold', 'cost_price' => 600000]);
    }

    public function test_sale_uses_final_price_and_preserves_snapshots_when_catalog_changes(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['title' => 'Akun lama <script>alert(1)</script>', 'price' => 1000000, 'cost_price' => 500000]);

        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'amount' => 900000, 'cost_price' => 1])->assertSessionHasNoErrors();
        $account->update(['title' => 'Judul baru', 'price' => 1200000]);

        $this->assertDatabaseHas('financial_transactions', ['description' => 'Akun lama <script>alert(1)</script>', 'revenue' => 900000, 'cost_of_goods' => 500000, 'cash_in' => 900000]);
        $this->get('/admin/finance?month=2026-10')->assertSee('Akun lama &lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'amount' => 900000])->assertSessionHasErrors('game_account_id');
        $this->assertDatabaseCount('financial_transactions', 1);
        $this->get('/admin/accounts/'.$account->id.'/edit')->assertOk()->assertSee('readonly', false);
    }

    public function test_sale_requires_known_cost_and_accepts_explicit_zero_cost(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create();

        $this->record(['type' => 'sale', 'game_account_id' => $account->id])->assertSessionHasErrors(['cost_price' => 'Isi modal akun agar laba dapat dihitung. Gunakan 0 hanya jika tanpa modal.']);
        $this->assertDatabaseCount('financial_transactions', 0);
        $this->assertSame('available', $account->fresh()->status);

        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'cost_price' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('financial_transactions', ['game_account_id' => $account->id, 'cost_of_goods' => 0]);
    }

    public function test_old_sold_accounts_are_not_automatically_counted_and_can_be_backfilled(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['status' => 'sold', 'cost_price' => 300000]);

        $response = $this->get('/admin/finance?month=2026-10');

        $this->assertSame(1, $response->viewData('legacySold'));
        $this->assertSame(0, $response->viewData('totals')['revenue']);
        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'occurred_on' => '2025-12-31'])->assertSessionHasNoErrors();
        $january = $this->get('/admin/finance?month=2026-01');
        $this->assertSame(0, $january->viewData('totals')['revenue']);
        $this->assertSame(1000000, $january->viewData('previous')['revenue']);
    }

    #[TestWith([700000, 300000, 0])]
    #[TestWith([1200000, 0, 200000])]
    #[TestWith([1000000, 0, 0])]
    public function test_trade_in_separates_revenue_from_cash_and_creates_draft_stock(int $tradeValue, int $cashIn, int $cashOut): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $trade = $this->acceptedTrade();

        $this->record(['type' => 'trade_in', 'game_account_id' => $account->id, 'trade_request_id' => $trade->id, 'trade_value' => $tradeValue, 'received_title' => 'Akun pelanggan'])->assertSessionHasNoErrors();

        $transaction = FinancialTransaction::firstOrFail();
        $this->assertSame(1000000, $transaction->revenue);
        $this->assertSame(600000, $transaction->cost_of_goods);
        $this->assertSame($cashIn, $transaction->cash_in);
        $this->assertSame($cashOut, $transaction->cash_out);
        $this->assertDatabaseHas('game_accounts', ['id' => $transaction->received_account_id, 'title' => 'Akun pelanggan', 'status' => 'draft', 'cost_price' => $tradeValue]);
        $this->assertDatabaseHas('trade_requests', ['id' => $trade->id, 'status' => 'completed']);
        $this->get('/admin/trade-requests/'.$trade->id)->assertOk()->assertSee('sudah masuk laporan keuangan');
        $this->get('/admin/trade-requests')->assertOk()->assertSee('Selesai');
        $this->get('/admin/finance?month=2026-10')->assertOk()->assertSee('href="'.route('admin.accounts.edit', $transaction->received_account_id).'"', false);
        $report = $this->get('/admin/finance?month=2026-10');
        $this->assertSame(400000, $report->viewData('totals')['profit']);
        $report->assertDontSee('Uang masuk')->assertDontSee('Uang keluar')->assertDontSee('Arus kas');
        $this->get('/akun/'.GameAccount::findOrFail($transaction->received_account_id)->slug)->assertNotFound();
    }

    public function test_unaccepted_trade_cannot_create_sale_or_new_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $trade = $this->acceptedTrade();
        $trade->update(['status' => 'pending']);

        $this->record(['type' => 'trade_in', 'game_account_id' => $account->id, 'trade_request_id' => $trade->id, 'trade_value' => 700000, 'received_title' => 'Belum diterima'])->assertSessionHasErrors('trade_request_id');

        $this->assertDatabaseCount('financial_transactions', 0);
        $this->assertDatabaseCount('game_accounts', 1);
        $this->assertSame('available', $account->fresh()->status);
    }

    public function test_completed_trade_cannot_be_reused_or_changed_or_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $other = GameAccount::factory()->create(['cost_price' => 500000]);
        $trade = $this->acceptedTrade();
        $payload = ['type' => 'trade_in', 'game_account_id' => $account->id, 'trade_request_id' => $trade->id, 'trade_value' => 700000, 'received_title' => 'Akun pelanggan'];
        $this->record($payload)->assertSessionHasNoErrors();

        $this->record(array_replace($payload, ['game_account_id' => $other->id]))->assertSessionHasErrors('trade_request_id');
        $this->put('/admin/trade-requests/'.$trade->id, ['status' => 'accepted'])->assertSessionHasErrors('status');
        $this->delete('/admin/trade-requests/'.$trade->id)->assertSessionHasErrors('trade_request');

        $this->assertDatabaseCount('financial_transactions', 1);
        $this->assertSame('completed', $trade->fresh()->status);
        $this->assertSame('available', $other->fresh()->status);
    }

    public function test_refund_reverses_sale_in_refund_month_and_preserves_original_month(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'occurred_on' => '2026-09-30'])->assertSessionHasNoErrors();
        $sale = FinancialTransaction::firstOrFail();

        $this->post('/admin/finance/'.$sale->id.'/reverse', ['occurred_on' => '2026-10-10', 'reason' => 'Pembeli mengembalikan akun dan uang dikembalikan'])->assertSessionHasNoErrors();

        $response = $this->get('/admin/finance?month=2026-10');
        $this->assertSame(1000000, $response->viewData('previous')['revenue']);
        $this->assertSame(-1000000, $response->viewData('totals')['revenue']);
        $this->assertSame(-600000, $response->viewData('totals')['cost_of_goods']);
        $this->assertSame(-400000, $response->viewData('totals')['profit']);
        $response->assertSee('Koreksi refund')->assertDontSee('Uang keluar')->assertDontSee('Arus kas');
        $this->assertSame(400000, $response->viewData('trend')[4]['profit']);
        $this->assertSame(-400000, $response->viewData('trend')[5]['profit']);
        $this->assertSame('draft', $account->fresh()->status);
        $this->assertDatabaseHas('financial_transactions', ['id' => $sale->id, 'revenue' => 1000000]);
        $this->post('/admin/finance/'.$sale->id.'/reverse', ['occurred_on' => '2026-10-10', 'reason' => 'Duplikat'])->assertSessionHasErrors('transaction');
        $this->assertDatabaseCount('financial_transactions', 2);
        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'occurred_on' => '2026-10-09'])->assertSessionHasErrors('occurred_on');
        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'occurred_on' => '2026-10-11'])->assertSessionHasNoErrors();
    }

    public function test_trade_refund_returns_both_accounts_and_reverses_only_actual_cash(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $trade = $this->acceptedTrade();
        $this->record(['type' => 'trade_in', 'game_account_id' => $account->id, 'trade_request_id' => $trade->id, 'trade_value' => 700000, 'received_title' => 'Akun masuk'])->assertSessionHasNoErrors();
        $sale = FinancialTransaction::firstOrFail();

        $this->post('/admin/finance/'.$sale->id.'/reverse', ['occurred_on' => '2026-10-11', 'reason' => 'Semua akun dan selisih tunai dikembalikan'])->assertSessionHasNoErrors();

        $this->assertSoftDeleted('game_accounts', ['id' => $sale->received_account_id]);
        $this->assertSame('draft', $account->fresh()->status);
        $this->assertSame('accepted', $trade->fresh()->status);
        $this->assertDatabaseHas('financial_transactions', ['type' => 'reversal', 'cash_out' => 300000, 'revenue' => -1000000]);
        $response = $this->get('/admin/finance?month=2026-10');
        $this->assertSame(0, $response->viewData('totals')['profit']);
        $this->assertSame(0, $response->viewData('totals')['sold_units']);
    }

    public function test_trade_refund_is_rejected_after_incoming_account_has_been_sold(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $trade = $this->acceptedTrade();
        $this->record(['type' => 'trade_in', 'game_account_id' => $account->id, 'trade_request_id' => $trade->id, 'trade_value' => 700000, 'received_title' => 'Akun masuk'])->assertSessionHasNoErrors();
        $sale = FinancialTransaction::firstOrFail();
        $this->record(['type' => 'sale', 'game_account_id' => $sale->received_account_id])->assertSessionHasNoErrors();

        $this->post('/admin/finance/'.$sale->id.'/reverse', ['occurred_on' => '2026-10-11', 'reason' => 'Tidak boleh'])->assertSessionHasErrors('transaction');

        $this->assertDatabaseCount('financial_transactions', 2);
        $this->assertSame('sold', $account->fresh()->status);
        $this->assertSame('completed', $trade->fresh()->status);
    }

    public function test_legacy_purchase_cannot_be_refunded_after_stock_is_sold(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $payload = ['type' => 'purchase', 'game_account_id' => $account->id, 'amount' => 600000];
        $purchase = $this->legacyPurchase($account);

        $this->record($payload)->assertSessionHasErrors('type');
        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'occurred_on' => '2026-10-09'])->assertSessionHasErrors('occurred_on');
        $this->record(['type' => 'sale', 'game_account_id' => $account->id])->assertSessionHasNoErrors();
        $this->post('/admin/finance/'.$purchase->id.'/reverse', ['occurred_on' => '2026-10-11', 'reason' => 'Tidak boleh'])->assertSessionHasErrors('transaction');
        $this->assertDatabaseCount('financial_transactions', 2);
    }

    public function test_legacy_purchase_refund_clears_cost_and_restores_cash_without_changing_profit(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $purchase = $this->legacyPurchase($account);

        $this->post('/admin/finance/'.$purchase->id.'/reverse', ['occurred_on' => '2026-10-11', 'reason' => 'Pembelian dikembalikan'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('game_accounts', ['id' => $account->id, 'status' => 'draft', 'cost_price' => null]);
        $this->assertSoftDeleted($account);
        $response = $this->get('/admin/finance?month=2026-10');
        $this->assertSame(0, $response->viewData('totals')['profit']);
        $this->assertCount(0, $response->viewData('transactions'));
    }

    public function test_legacy_expense_and_its_reversal_remain_stored_but_do_not_affect_sales_profit(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $expense = FinancialTransaction::factory()->create([
            'occurred_on' => '2026-10-10', 'description' => 'Hosting <script>alert(1)</script>',
            'operating_expense' => 100000, 'cash_out' => 100000,
        ]);

        $this->assertDatabaseCount('financial_transactions', 1);
        $response = $this->get('/admin/finance?month=2026-10');
        $response->assertDontSee('Hosting')->assertDontSee('<script>alert(1)</script>', false);
        $this->assertSame(0, $response->viewData('totals')['profit']);
        $this->post('/admin/finance/'.$expense->id.'/reverse', ['occurred_on' => '2026-10-11', 'reason' => 'Biaya dikembalikan'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('financial_transactions', ['reversal_of_id' => $expense->id, 'operating_expense' => -100000, 'cash_in' => 100000]);
        $response = $this->get('/admin/finance?month=2026-10');
        $this->assertCount(0, $response->viewData('transactions'));
        $this->assertSame(0, $response->viewData('totals')['profit']);
    }

    public function test_recorded_accounts_cannot_be_deleted_or_unsold_outside_finance(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $this->record(['type' => 'sale', 'game_account_id' => $account->id])->assertSessionHasNoErrors();
        $payload = $account->fresh()->only(['title', 'slug', 'category_id', 'price', 'description', 'specifications']);

        $this->put('/admin/accounts/'.$account->id, $payload + ['status' => 'available'])->assertSessionHasErrors('status');
        $this->delete('/admin/accounts/'.$account->id)->assertSessionHasErrors('account');
        $this->put('/admin/accounts/'.$account->id, $payload + ['status' => 'sold', 'cost_price' => 1])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('game_accounts', ['id' => $account->id, 'status' => 'sold', 'cost_price' => 600000]);
        $this->assertDatabaseCount('financial_transactions', 1);
    }

    public function test_direct_sold_status_is_rejected_to_prevent_unrecorded_sales(): void
    {
        $this->actingAs(User::factory()->create());
        $account = GameAccount::factory()->create();
        $payload = $account->only(['title', 'slug', 'category_id', 'price', 'description', 'specifications']);

        $this->put('/admin/accounts/'.$account->id, $payload + ['status' => 'sold'])->assertSessionHasErrors('status');

        $this->assertSame('available', $account->fresh()->status);
        $this->assertDatabaseCount('financial_transactions', 0);
    }

    #[TestWith(['amount', -1])]
    #[TestWith(['amount', 1000000000000])]
    #[TestWith(['amount', '12.5'])]
    #[TestWith(['occurred_on', '2026-10-25'])]
    #[TestWith(['occurred_on', '2026-02-30'])]
    #[TestWith(['type', 'unknown'])]
    public function test_invalid_transaction_input_makes_no_financial_changes(string $field, int|string $value): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));

        $account = GameAccount::factory()->create(['cost_price' => 600000]);

        $this->record(['type' => 'sale', 'game_account_id' => $account->id, $field => $value])->assertSessionHasErrors($field);

        $this->assertDatabaseCount('financial_transactions', 0);
    }

    public function test_refund_date_must_not_precede_transaction_and_reason_is_required(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $expense = FinancialTransaction::factory()->create(['occurred_on' => '2026-10-10']);

        $this->post('/admin/finance/'.$expense->id.'/reverse', ['occurred_on' => '2026-10-09', 'reason' => 'Salah tanggal'])->assertSessionHasErrors('occurred_on');
        $this->post('/admin/finance/'.$expense->id.'/reverse', ['occurred_on' => '2026-10-11'])->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('financial_transactions', 1);
    }

    public function test_all_transaction_forms_render_and_preselect_account_and_trade(): void
    {
        $this->actingAs(User::factory()->create());
        $account = GameAccount::factory()->create();
        $trade = $this->acceptedTrade();

        foreach (['sale', 'trade_in'] as $type) {
            $response = $this->get('/admin/finance/create?'.http_build_query(['type' => $type, 'account' => $account->id, 'trade_request' => $trade->id]));
            $response->assertOk()->assertSee('Konfirmasi dan catat transaksi')->assertDontSee('Pembelian stok')->assertDontSee('Biaya operasional');
        }
        $this->get('/admin/trade-requests/'.$trade->id)->assertSee('Konfirmasi penyelesaian tukar tambah');
    }

    public function test_incoming_trade_stock_cannot_be_purchased_again_or_published_without_a_photo(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $trade = $this->acceptedTrade();
        $this->record(['type' => 'trade_in', 'game_account_id' => $account->id, 'trade_request_id' => $trade->id, 'trade_value' => 700000, 'received_title' => str_repeat('a', 150)])->assertSessionHasNoErrors();
        $received = GameAccount::findOrFail(FinancialTransaction::firstOrFail()->received_account_id);
        $payload = $received->only(['title', 'slug', 'category_id', 'price', 'description', 'specifications']);

        $this->record(['type' => 'purchase', 'game_account_id' => $received->id])->assertSessionHasErrors('type');
        $this->put('/admin/accounts/'.$received->id, $payload + ['status' => 'available'])->assertSessionHasErrors('main_image');
        $this->delete('/admin/accounts/'.$received->id)->assertSessionHasErrors('account');

        $this->assertSame('draft', $received->fresh()->status);
        $this->assertDatabaseCount('financial_transactions', 1);
        $this->put('/admin/accounts/'.$received->id, $payload + ['status' => 'draft', 'cost_price' => 1])->assertSessionHasNoErrors();
        $this->assertSame(700000, $received->fresh()->cost_price);
    }

    public function test_monthly_pagination_preserves_filter_and_totals_include_all_pages(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        FinancialTransaction::factory()->count(21)->create(['type' => 'sale', 'occurred_on' => '2026-09-30', 'revenue' => 100000, 'cost_of_goods' => 50000, 'sold_units' => 1, 'operating_expense' => 0]);
        FinancialTransaction::factory()->create(['type' => 'sale', 'occurred_on' => '2026-10-01', 'revenue' => 900000]);

        $response = $this->get('/admin/finance?month=2026-09');

        $response->assertOk()->assertSee('month=2026-09', false)->assertSee('page=2', false);
        $this->assertSame(1050000, $response->viewData('totals')['profit']);
        $this->assertCount(20, $response->viewData('transactions'));
        $this->assertSame(1050000, $response->viewData('trend')[5]['profit']);
    }

    public function test_missing_transaction_fields_and_unknown_stock_are_rejected_without_writes(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));

        $this->post('/admin/finance', [])->assertSessionHasErrors(['submission_id', 'type', 'occurred_on', 'amount', 'game_account_id']);
        $this->record(['type' => 'sale', 'game_account_id' => 9999])->assertSessionHasErrors('game_account_id');
        $this->record(['type' => 'trade_in'])->assertSessionHasErrors(['game_account_id', 'trade_request_id', 'trade_value', 'received_title']);
        $this->record(['type' => 'expense'])->assertSessionHasErrors('type');

        $this->assertDatabaseCount('financial_transactions', 0);
    }

    #[TestWith(['purchase'])]
    #[TestWith(['expense'])]
    public function test_removed_transaction_types_are_rejected_by_form_and_submission(string $type): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);

        $this->get('/admin/finance/create?type='.$type)->assertSessionHasErrors('type');
        $this->record(['type' => $type, 'game_account_id' => $account->id, 'description' => 'Tidak boleh dicatat'])->assertSessionHasErrors('type');

        $this->assertDatabaseCount('financial_transactions', 0);
        $this->assertSame('available', $account->fresh()->status);
        $this->assertSame(600000, $account->fresh()->cost_price);
        $this->get('/admin/accounts/'.$account->id.'/edit')->assertDontSee('Catat pembelian stok')->assertSee('Modal akun');
    }

    public function test_sale_submission_remains_idempotent(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $payload = ['type' => 'sale', 'game_account_id' => $account->id, 'submission_id' => (string) Str::uuid()];
        $this->record($payload)->assertSessionHasNoErrors();

        $this->record($payload)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('financial_transactions', 1);
        $this->assertDatabaseHas('financial_transactions', ['game_account_id' => $account->id, 'revenue' => 1000000]);
    }

    public function test_refund_and_two_sales_show_only_corrected_profit_without_cash_flow(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 600000]);
        $other = GameAccount::factory()->create(['cost_price' => 500000]);
        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'amount' => 100000])->assertSessionHasNoErrors();
        $sale = FinancialTransaction::firstOrFail();
        $this->post('/admin/finance/'.$sale->id.'/reverse', ['occurred_on' => '2026-10-10', 'reason' => 'Koreksi transaksi awal'])->assertSessionHasNoErrors();
        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'amount' => 1000000])->assertSessionHasNoErrors();
        $this->record(['type' => 'sale', 'game_account_id' => $other->id, 'amount' => 800000])->assertSessionHasNoErrors();

        $response = $this->get('/admin/finance?month=2026-10');

        $response->assertSee('Laporan untung/rugi bulanan')->assertSee('Koreksi refund')
            ->assertDontSee('Uang masuk')->assertDontSee('Uang keluar')->assertDontSee('Arus kas')
            ->assertDontSee('Biaya operasional')->assertDontSee('Pembelian stok');
        $this->assertSame(['revenue' => 1800000, 'cost_of_goods' => 1100000, 'sold_units' => 2, 'profit' => 700000], $response->viewData('totals'));
        $this->assertSame(700000, $response->viewData('trend')[5]['profit']);
        $this->assertDatabaseHas('financial_transactions', ['reversal_of_id' => $sale->id, 'cash_out' => 100000]);
        $this->assertDatabaseCount('financial_transactions', 4);
    }

    public function test_sale_below_cost_shows_negative_monthly_profit(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(now()->setDate(2026, 10, 24));
        $account = GameAccount::factory()->create(['cost_price' => 1000000]);
        $this->record(['type' => 'sale', 'game_account_id' => $account->id, 'amount' => 800000])->assertSessionHasNoErrors();

        $response = $this->get('/admin/finance?month=2026-10');

        $response->assertSee('Rp -200.000');
        $this->assertSame(-200000, $response->viewData('totals')['profit']);
        $this->assertSame(-200000, $response->viewData('trend')[5]['profit']);
    }

    private function legacyPurchase(GameAccount $account, string $date = '2026-10-10'): FinancialTransaction
    {
        return FinancialTransaction::factory()->create([
            'type' => 'purchase', 'occurred_on' => $date, 'game_account_id' => $account->id,
            'purchase_account_id' => $account->id, 'description' => $account->title,
            'operating_expense' => 0, 'cash_out' => 600000,
        ]);
    }

    /** @param array<string, int|string|null> $data */
    private function record(array $data): TestResponse
    {
        return $this->post('/admin/finance', array_replace([
            'submission_id' => (string) Str::uuid(), 'occurred_on' => '2026-10-10', 'amount' => 1000000,
        ], $data));
    }

    private function acceptedTrade(): TradeRequest
    {
        return TradeRequest::create([
            'category_id' => Category::factory()->create()->id, 'name' => 'Pelanggan tukar tambah',
            'email' => 'pelanggan@example.com', 'whatsapp_number' => '6281234567890',
            'description' => 'Akun pelanggan lengkap dengan hero dan skin.', 'status' => 'accepted',
        ]);
    }
}
