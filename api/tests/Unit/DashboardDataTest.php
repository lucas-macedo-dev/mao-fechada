<?php

declare(strict_types=1);

use App\DataTransferObjects\Output\CategoryBreakdownData;
use App\DataTransferObjects\Output\DashboardChartData;
use App\DataTransferObjects\Output\DashboardSummaryData;
use App\DataTransferObjects\Output\MonthlyComparisonData;
use App\DataTransferObjects\Output\MtdComparisonData;
use App\DataTransferObjects\Output\WeekdayExpenseData;

it('shapes the dashboard summary payload with totals, chart, and recent transactions', function () {
    $data = new DashboardSummaryData('2026-06', 500.0, 300.0, [['id' => 1]]);

    expect($data->toArray())->toBe([
        'month'               => '2026-06',
        'totals'              => ['entradas' => 500.0, 'saidas' => 300.0, 'saldo' => 200.0],
        'chart'               => ['entradas' => 500.0, 'saidas' => 300.0],
        'recent_transactions' => [['id' => 1]],
    ]);
});

it('shapes the dashboard chart payload as a series', function () {
    $data = new DashboardChartData('2026-06', 500.0, 300.0);

    expect($data->toArray())->toBe([
        'month'  => '2026-06',
        'series' => ['entradas' => 500.0, 'saidas' => 300.0],
    ]);
});

it('shapes the monthly comparison payload', function () {
    $months = [['month' => '2026-06', 'income' => 100.0, 'expense' => 50.0]];
    $data = new MonthlyComparisonData($months);

    expect($data->toArray())->toBe(['months' => $months]);
});

it('shapes the mtd comparison payload with four independent metrics', function () {
    $section = ['current' => [], 'previous' => [], 'change_percent' => null];
    $data = new MtdComparisonData($section, $section, $section, $section);

    expect($data->toArray())->toBe([
        'income'       => $section,
        'expense'      => $section,
        'balance'      => $section,
        'installments' => $section,
    ]);
});

it('shapes the weekday expense payload as a plain array', function () {
    $days = [['weekday' => 1, 'date' => '2026-06-15', 'expense' => 0.0]];
    $data = new WeekdayExpenseData($days);

    expect($data->toArray())->toBe($days);
});

it('shapes a category breakdown row and its collection helper', function () {
    $row = new CategoryBreakdownData('Moradia', 150.0);

    expect($row->toArray())->toBe(['name' => 'Moradia', 'value' => 150.0]);
    expect(CategoryBreakdownData::collection([$row]))->toBe([['name' => 'Moradia', 'value' => 150.0]]);
});
