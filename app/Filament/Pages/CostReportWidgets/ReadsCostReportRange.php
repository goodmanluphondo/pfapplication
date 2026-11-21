<?php

declare(strict_types=1);

namespace App\Filament\Pages\CostReportWidgets;

use App\Services\Reporting\CostReport;
use Carbon\CarbonImmutable;

trait ReadsCostReportRange
{
    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    protected function reportRange(): array
    {
        $startDate = $this->pageFilters['startDate'] ?? null;
        $endDate = $this->pageFilters['endDate'] ?? null;

        return CostReport::rangeFrom(
            is_string($startDate) ? $startDate : null,
            is_string($endDate) ? $endDate : null,
        );
    }
}
