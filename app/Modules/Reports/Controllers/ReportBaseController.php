<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

abstract class ReportBaseController extends Controller
{
    protected const MAX_REPORT_RANGE_DAYS = 366;

    /**
     * Resolve date range from request parameters.
     */
    protected function resolvePeriodDateRange(string $period, ?string $startDate, ?string $endDate): array
    {
        if (! empty($startDate) || ! empty($endDate)) {
            $start = $startDate ?: now()->startOfYear()->toDateString();
            $end = $endDate ?: now()->toDateString();

            return [$start, $end];
        }

        return match (strtolower($period)) {
            'daily' => [now()->toDateString(), now()->toDateString()],
            'monthly' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'yearly' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            default => [now()->startOfMonth()->toDateString(), now()->toDateString()],
        };
    }

    /**
     * Validate that the date range is within acceptable limits.
     */
    protected function guardReportDateRange(?string $startDate, ?string $endDate): void
    {
        if (! $startDate || ! $endDate) {
            return;
        }

        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        if ($days > self::MAX_REPORT_RANGE_DAYS) {
            throw ValidationException::withMessages([
                'end_date' => 'Date range is too large. Maximum allowed range is '.self::MAX_REPORT_RANGE_DAYS.' days.',
            ]);
        }
    }
}
