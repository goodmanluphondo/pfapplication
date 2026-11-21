<?php

declare(strict_types=1);

namespace App\Filament\Pages\CostReportWidgets;

use App\Services\Pixelforge\Nanodollars;
use App\Services\Reporting\CostReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

final class DailyCostTable extends TableWidget
{
    use InteractsWithPageFilters;
    use ReadsCostReportRange;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('By day')
            ->records(function (): array {
                ['start' => $start, 'end' => $end] = $this->reportRange();

                return app(CostReport::class)->daily($start, $end);
            })
            ->columns([
                TextColumn::make('date')->label('Date (UTC)'),
                TextColumn::make('generations')->label('Billed images')->alignEnd(),
                TextColumn::make('credit_spend')
                    ->label('Credit spend')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Nanodollars::toExactDollars($state)),
                TextColumn::make('provider_cost')
                    ->label('Provider cost (ours)')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Nanodollars::toExactDollars($state)),
                TextColumn::make('pixelforge_cost')
                    ->label('Pixelforge reports')
                    ->alignEnd()
                    ->placeholder('Unavailable')
                    ->formatStateUsing(fn (int $state): string => Nanodollars::toExactDollars($state)),
                TextColumn::make('difference')
                    ->label('Difference')
                    ->alignEnd()
                    ->placeholder('—')
                    ->badge()
                    ->color(fn (?int $state): string => $state === 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Matches' : Nanodollars::toExactDollars($state)),
                TextColumn::make('profit')
                    ->label('Profit')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Nanodollars::toExactDollars($state)),
            ])
            ->paginated([10, 31, 92])
            ->defaultPaginationPageOption(31);
    }
}
