<?php

namespace App;

use App\Models\FinancialTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class MonthlyFinanceReport
{
    public function transactions(): Builder
    {
        return FinancialTransaction::query()->where(function (Builder $query): void {
            $query->whereIn('type', ['sale', 'trade_in'])
                ->orWhere(function (Builder $query): void {
                    $query->where('type', 'reversal')->whereHas('originalTransaction', fn (Builder $original): Builder => $original->whereIn('type', ['sale', 'trade_in']));
                });
        });
    }

    /** @return array{revenue: int, cost_of_goods: int, sold_units: int, profit: int} */
    public function totals(CarbonImmutable $month): array
    {
        $row = $this->transactions()
            ->where('occurred_on', '>=', $month->startOfMonth()->toDateString())
            ->where('occurred_on', '<', $month->startOfMonth()->addMonth()->toDateString())
            ->selectRaw('SUM(revenue) AS revenue, SUM(cost_of_goods) AS cost_of_goods, SUM(sold_units) AS sold_units')
            ->first();
        $totals = [];
        foreach (['revenue', 'cost_of_goods', 'sold_units'] as $field) {
            $totals[$field] = (int) $row->$field;
        }
        $totals['profit'] = $totals['revenue'] - $totals['cost_of_goods'];

        return $totals;
    }

    /** @return array<int, array{month: string, label: string, profit: int}> */
    public function trend(CarbonImmutable $month): array
    {
        $start = $month->startOfMonth()->subMonths(5);
        $rows = $this->transactions()
            ->where('occurred_on', '>=', $start->toDateString())
            ->where('occurred_on', '<', $month->startOfMonth()->addMonth()->toDateString())
            ->selectRaw('occurred_on, SUM(revenue) AS revenue, SUM(cost_of_goods) AS cost_of_goods')
            ->groupBy('occurred_on')->get()->groupBy(fn (FinancialTransaction $row): string => $row->occurred_on->format('Y-m'));
        $trend = [];
        for ($offset = 0; $offset < 6; $offset++) {
            $period = $start->addMonths($offset);
            $profit = (int) ($rows->get($period->format('Y-m'))?->sum(fn (FinancialTransaction $row): int => $row->revenue - $row->cost_of_goods) ?? 0);
            $trend[] = ['month' => $period->format('Y-m'), 'label' => $period->translatedFormat('M Y'), 'profit' => $profit];
        }

        return $trend;
    }
}
