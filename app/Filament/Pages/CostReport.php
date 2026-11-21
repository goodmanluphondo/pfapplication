<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\CostReportWidgets\CostReportStats;
use App\Filament\Pages\CostReportWidgets\DailyCostTable;
use App\Filament\Pages\CostReportWidgets\UserCostTable;
use App\Services\Reporting\CostReport as CostReportData;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class CostReport extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Cost report';

    protected static ?string $navigationLabel = 'Cost report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 1;

    public static function getRoutePath(Panel $panel): string
    {
        return '/cost-report';
    }

    public function getSubheading(): string
    {
        return 'Credit spend, Pixelforge costs and profit per UTC day, billed on the day each image finished. '
            .'Our provider cost is checked against Pixelforge\'s usage report.';
    }

    public function filtersForm(Schema $schema): Schema
    {
        $defaultRange = CostReportData::rangeFrom(null, null);

        return $schema
            ->components([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('From (UTC)')
                            ->default($defaultRange['start']->toDateString())
                            ->maxDate(now('UTC')),
                        DatePicker::make('endDate')
                            ->label('To (UTC)')
                            ->default($defaultRange['end']->toDateString())
                            ->maxDate(now('UTC'))
                            ->helperText('Pixelforge reports at most '.CostReportData::MAXIMUM_DAYS.' days at a time.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public function getColumns(): int
    {
        return 1;
    }

    /**
     * @return list<class-string>
     */
    public function getWidgets(): array
    {
        return [
            CostReportStats::class,
            DailyCostTable::class,
            UserCostTable::class,
        ];
    }
}
