<?php

namespace App;

use App\Models\FinancialTransaction;
use App\Models\GameAccount;
use App\Models\TradeRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinanceLedger
{
    /** @param array{submission_id: string, type: string, occurred_on: string, game_account_id: int|string, amount: int|string, cost_price?: int|string|null, trade_request_id?: int|string|null, trade_value?: int|string|null, received_title?: string|null, notes?: string|null} $data */
    public function record(array $data, int $userId): FinancialTransaction
    {
        if (! in_array($data['type'], ['sale', 'trade_in'], true)) {
            throw ValidationException::withMessages(['type' => 'Hanya penjualan dan tukar tambah yang dapat dicatat.']);
        }

        return DB::transaction(function () use ($data, $userId): FinancialTransaction {
            $existing = FinancialTransaction::where('submission_id', $data['submission_id'])->where('user_id', $userId)->first();
            if ($existing) {
                return $existing;
            }
            $type = $data['type'];
            $amount = (int) $data['amount'];
            $values = [
                'type' => $type, 'submission_id' => $data['submission_id'], 'occurred_on' => $data['occurred_on'], 'user_id' => $userId,
                'notes' => $data['notes'] ?? null,
            ];

            $account = GameAccount::query()->lockForUpdate()->findOrFail($data['game_account_id']);
            $values += [
                'game_account_id' => $account->id, 'description' => $account->title,
                'category_name' => $account->category->name,
            ];

            if (FinancialTransaction::where('active_sale_account_id', $account->id)->exists()) {
                throw ValidationException::withMessages(['game_account_id' => 'Penjualan akun ini sudah dicatat.']);
            }

            if ($account->cost_price === null && ! isset($data['cost_price'])) {
                throw ValidationException::withMessages(['cost_price' => 'Isi modal akun agar laba dapat dihitung. Gunakan 0 hanya jika tanpa modal.']);
            }
            $cost = $account->cost_price ?? (int) $data['cost_price'];
            $purchaseDate = FinancialTransaction::where('purchase_account_id', $account->id)->first(['occurred_on'])?->occurred_on->toDateString();
            $receivedDate = FinancialTransaction::where('received_account_id', $account->id)->whereDoesntHave('reversal')->first(['occurred_on'])?->occurred_on->toDateString();
            $refundDate = $account->financialTransactions()->where('type', 'reversal')->max('occurred_on');
            $refundDate = $refundDate ? substr($refundDate, 0, 10) : null;
            if (($purchaseDate && $data['occurred_on'] < $purchaseDate) || ($receivedDate && $data['occurred_on'] < $receivedDate) || ($refundDate && $data['occurred_on'] < $refundDate)) {
                throw ValidationException::withMessages(['occurred_on' => 'Tanggal penjualan tidak boleh mendahului perolehan stok.']);
            }

            $tradeValue = $type === 'trade_in' ? (int) $data['trade_value'] : 0;
            if ($type === 'trade_in') {
                $trade = TradeRequest::query()->lockForUpdate()->findOrFail($data['trade_request_id']);
                if ($trade->status !== 'accepted' || FinancialTransaction::where('active_trade_request_id', $trade->id)->exists()) {
                    throw ValidationException::withMessages(['trade_request_id' => 'Pengajuan harus berstatus Diterima dan belum diselesaikan.']);
                }
                $received = GameAccount::create([
                    'category_id' => $trade->category_id, 'title' => $data['received_title'],
                    'slug' => Str::limit(Str::slug($data['received_title']), 140, '').'-'.Str::uuid(),
                    'price' => $tradeValue, 'cost_price' => $tradeValue,
                    'description' => $trade->description, 'specifications' => 'Lengkapi spesifikasi sebelum menerbitkan akun.',
                    'main_image' => 'images/zuto-maskot.webp', 'status' => 'draft',
                ]);
                $trade->update(['status' => 'completed']);
                $values += ['trade_request_id' => $trade->id, 'active_trade_request_id' => $trade->id, 'received_account_id' => $received->id, 'received_title' => $received->title];
            }

            $account->update(['status' => 'sold', 'cost_price' => $cost]);

            return FinancialTransaction::create($values + [
                'active_sale_account_id' => $account->id, 'revenue' => $amount, 'cost_of_goods' => $cost,
                'cash_in' => max(0, $amount - $tradeValue), 'cash_out' => max(0, $tradeValue - $amount),
                'trade_value' => $tradeValue, 'sold_units' => 1,
            ]);
        }, 3);
    }

    public function reverse(FinancialTransaction $transaction, string $date, string $reason, int $userId): void
    {
        DB::transaction(function () use ($transaction, $date, $reason, $userId): void {
            $transaction = FinancialTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if ($transaction->type === 'reversal' || $transaction->reversal()->exists()) {
                throw ValidationException::withMessages(['transaction' => 'Transaksi ini sudah dibatalkan atau merupakan pembatalan.']);
            }
            if ($date < $transaction->occurred_on->toDateString()) {
                throw ValidationException::withMessages(['occurred_on' => 'Tanggal pembatalan tidak boleh mendahului transaksi asal.']);
            }

            $account = $transaction->game_account_id ? GameAccount::query()->lockForUpdate()->find($transaction->game_account_id) : null;
            if ($transaction->type === 'purchase') {
                if (! $account || $account->status === 'sold' || $account->financialTransactions()->where('id', '!=', $transaction->id)->exists()) {
                    throw ValidationException::withMessages(['transaction' => 'Pembelian tidak dapat dibatalkan setelah stok memiliki transaksi lain.']);
                }
                $account->update(['cost_price' => null, 'status' => 'draft']);
                $account->delete();
            }

            if ($transaction->type === 'trade_in') {
                $received = GameAccount::query()->lockForUpdate()->find($transaction->received_account_id);
                if (! $received || $received->status !== 'draft' || $received->financialTransactions()->exists()) {
                    throw ValidationException::withMessages(['transaction' => 'Kembalikan akun tukar tambah ke Draft. Pembatalan ditolak jika akun sudah memiliki transaksi lanjutan.']);
                }
                $received->delete();
                TradeRequest::whereKey($transaction->trade_request_id)->update(['status' => 'accepted']);
            }

            if (in_array($transaction->type, ['sale', 'trade_in'], true)) {
                if (! $account) {
                    throw ValidationException::withMessages(['transaction' => 'Stok asal tidak ditemukan.']);
                }
                $account->update(['status' => 'draft']);
            }

            FinancialTransaction::create([
                'type' => 'reversal', 'occurred_on' => $date, 'user_id' => $userId,
                'game_account_id' => $transaction->game_account_id, 'reversal_of_id' => $transaction->id,
                'description' => Str::limit('Pembatalan: '.$transaction->description, 255, ''), 'category_name' => $transaction->category_name,
                'revenue' => -$transaction->revenue, 'cost_of_goods' => -$transaction->cost_of_goods,
                'operating_expense' => -$transaction->operating_expense,
                'cash_in' => $transaction->cash_out, 'cash_out' => $transaction->cash_in,
                'sold_units' => -$transaction->sold_units, 'notes' => $reason,
            ]);
            $transaction->update(['active_sale_account_id' => null, 'active_trade_request_id' => null, 'purchase_account_id' => null]);
        }, 3);
    }
}
