<?php

declare(strict_types=1);

namespace App\Filament\Pages\CostReportWidgets;

use App\Services\Pixelforge\Nanodollars;
use App\Services\Reporting\CostReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

final class UserCostTable extends TableWidget
{
    use InteractsWithPageFilters;
    use ReadsCostReportRange;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('By user')
            ->records(function (): array {
                ['start' => $start, 'end' => $end] = $this->reportRange();

                return app(CostReport::class)->perUser($start, $end);
            })
            ->columns([
                TextColumn::make('name')->label('User')->description(fn (array $record): string => $record['email']),
                TextColumn::make('generations')->label('Billed images')->alignEnd(),
                TextColumn::make('credit_spend')
                    ->label('Credit spend')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Nanodollars::toExactDollars($state)),
                TextColumn::make('provider_cost')
                    ->label('Provider cost')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Nanodollars::toExactDollars($state)),
                TextColumn::make('profit')
                    ->label('Profit')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Nanodollars::toExactDollars($state)),
                TextColumn::make('credits_bought')
                    ->label('Credits bought')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Nanodollars::toExactDollars($state)),
                TextColumn::make('balance')
                    ->label('Balance now')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Nanodollars::toExactDollars($state)),
            ])
            ->emptyStateHeading('No spend or purchases in this period')
            ->paginated([10, 25, 50]);
    }
}
