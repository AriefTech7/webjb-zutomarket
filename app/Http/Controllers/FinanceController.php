<?php

namespace App\Http\Controllers;

use App\FinanceLedger;
use App\Models\FinancialTransaction;
use App\Models\GameAccount;
use App\Models\TradeRequest;
use App\MonthlyFinanceReport;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request, MonthlyFinanceReport $report): View
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = CarbonImmutable::createFromFormat('!Y-m', $data['month'] ?? now()->format('Y-m'));
        $totals = $report->totals($month);
        $previous = $report->totals($month->subMonth());
        $trend = $report->trend($month);
        $chartMaximum = max(1, ...array_map(fn (array $period): int => abs($period['profit']), $trend));
        $transactions = $report->transactions()->with('reversal')
            ->where('occurred_on', '>=', $month->startOfMonth()->toDateString())
            ->where('occurred_on', '<', $month->startOfMonth()->addMonth()->toDateString())
            ->orderByDesc('occurred_on')->orderByDesc('id')->paginate(20)->withQueryString();
        $legacySold = GameAccount::where('status', 'sold')->whereDoesntHave('financialTransactions', fn ($query) => $query->whereIn('type', ['sale', 'trade_in']))->count();

        return view('admin.finance', compact('month', 'totals', 'previous', 'trend', 'chartMaximum', 'transactions', 'legacySold'));
    }

    public function create(Request $request): View
    {
        $data = $request->validate([
            'type' => ['nullable', Rule::in(['sale', 'trade_in'])],
            'account' => ['nullable', 'integer', Rule::exists('game_accounts', 'id')->whereNull('deleted_at')],
            'trade_request' => ['nullable', 'integer', 'exists:trade_requests,id'],
        ]);
        $type = $data['type'] ?? 'sale';
        $accounts = GameAccount::query()->select(['id', 'title', 'price', 'cost_price', 'status'])
            ->whereNotIn('id', FinancialTransaction::whereNotNull('active_sale_account_id')->select('active_sale_account_id'))
            ->orderBy('title')->get();
        $selectedAccount = $accounts->firstWhere('id', $data['account'] ?? null);
        $tradeRequests = $type === 'trade_in' ? TradeRequest::with('category')->where('status', 'accepted')->orderByDesc('id')->get() : collect();
        $selectedTrade = $data['trade_request'] ?? null;

        return view('admin.finance-form', compact('type', 'accounts', 'selectedAccount', 'tradeRequests', 'selectedTrade'));
    }

    public function store(Request $request, FinanceLedger $ledger): RedirectResponse
    {
        $data = $request->validate([
            'submission_id' => ['required', 'uuid'],
            'type' => ['required', Rule::in(['sale', 'trade_in'])],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'game_account_id' => ['required', 'integer', Rule::exists('game_accounts', 'id')->whereNull('deleted_at')],
            'amount' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'cost_price' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'trade_request_id' => ['exclude_unless:type,trade_in', 'required', 'integer', 'exists:trade_requests,id'],
            'trade_value' => ['exclude_unless:type,trade_in', 'required', 'integer', 'min:0', 'max:999999999999'],
            'received_title' => ['exclude_unless:type,trade_in', 'required', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        try {
            $ledger->record($data, $request->user()->id);
        } catch (UniqueConstraintViolationException $exception) {
            if (! FinancialTransaction::where('submission_id', $data['submission_id'])->where('user_id', $request->user()->id)->exists()) {
                throw $exception;
            }
        }

        return redirect()->route('admin.finance', ['month' => substr($data['occurred_on'], 0, 7)])
            ->with('success', 'Transaksi tersimpan. Laporan bulanan dan stok sudah diperbarui.');
    }

    public function reverse(Request $request, FinancialTransaction $transaction, FinanceLedger $ledger): RedirectResponse
    {
        $data = $request->validate([
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:5000'],
        ]);
        $ledger->reverse($transaction, $data['occurred_on'], $data['reason'], $request->user()->id);

        return redirect()->route('admin.finance', ['month' => substr($data['occurred_on'], 0, 7)])
            ->with('success', 'Pembatalan penuh tercatat pada tanggal yang dipilih. Riwayat transaksi asal tetap tersimpan.');
    }
}
