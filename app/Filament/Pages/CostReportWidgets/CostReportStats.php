<?php

declare(strict_types=1);

namespace App\Filament\Pages\CostReportWidgets;

use App\Services\Pixelforge\Nanodollars;
use App\Services\Reporting\CostReport;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class CostReportStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use ReadsCostReportRange;

    protected static bool $isLazy = false;

    /**
     * @return list<Stat>
     */
    protected function getStats(): array
    {
        ['start' => $start, 'end' => $end] = $this->reportRange();
        $totals = app(CostReport::class)->totals($start, $end);
        $pixelforgeCost = $totals['pixelforge_cost'];
        $difference = $pixelforgeCost === null ? null : $totals['provider_cost'] - $pixelforgeCost;

        return [
            Stat::make('Credit spend', Nanodollars::toExactDollars($totals['credit_spend']))
                ->description($totals['generations'].' billed images'),
            Stat::make('Provider cost (our records)', Nanodollars::toExactDollars($totals['provider_cost']))
                ->description(match (true) {
                    $pixelforgeCost === null => 'Pixelforge usage unavailable',
                    $difference === 0 => 'Matches Pixelforge',
                    default => 'Pixelforge reports '.Nanodollars::toExactDollars($pixelforgeCost),
                })
                ->color($difference === null || $difference === 0 ? 'success' : 'danger'),
            Stat::make('Profit', Nanodollars::toExactDollars($totals['profit']))
                ->color($totals['profit'] < 0 ? 'danger' : 'success'),
        ];
    }
}
