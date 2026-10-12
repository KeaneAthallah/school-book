<?php

namespace App\Support;

/**
 * Chart.js configuration builders.
 *
 * Keeps the shape of every dashboard chart in one place so the six role views
 * do not each hand-roll a config array, and so the palette stays consistent.
 */
final class Chart
{
    /**
     * Invoiced vs verified payments over the selected period.
     *
     * @param  array{labels: list<string>, invoiced: list<float>, paid: list<float>, granularity: string}  $series
     * @return array<string, mixed>
     */
    public static function salesSeries(array $series): array
    {
        return [
            'type' => 'line',
            'currency' => true,
            'legend' => true,
            'data' => [
                'labels' => $series['labels'],
                'datasets' => [
                    [
                        'label' => 'Invoice',
                        'data' => $series['invoiced'],
                        'borderColor' => '#2560e8',
                        'backgroundColor' => 'rgba(37, 96, 232, 0.16)',
                        'fill' => true,
                        'tension' => 0.35,
                        'pointRadius' => 2.5,
                        'pointHoverRadius' => 5,
                        'borderWidth' => 2.4,
                    ],
                    [
                        'label' => 'Pembayaran',
                        'data' => $series['paid'],
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.14)',
                        'fill' => true,
                        'tension' => 0.35,
                        'pointRadius' => 2.5,
                        'pointHoverRadius' => 5,
                        'borderWidth' => 2.4,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array{label: string, value: int, color: string}>  $rows
     * @return array<string, mixed>
     */
    public static function doughnut(array $rows): array
    {
        return [
            'type' => 'doughnut',
            'cutout' => '62%',
            'legend' => true,
            'data' => [
                'labels' => array_column($rows, 'label'),
                'datasets' => [[
                    'data' => array_column($rows, 'value'),
                    'backgroundColor' => array_column($rows, 'color'),
                    'borderWidth' => 2,
                ]],
            ],
        ];
    }

    /**
     * @param  array<int, array{label: string, value: float}>  $rows
     * @return array<string, mixed>
     */
    public static function horizontalBars(array $rows, string $color = '#2560e8'): array
    {
        return [
            'type' => 'bar',
            'horizontal' => true,
            'currency' => true,
            'legend' => false,
            'data' => [
                'labels' => array_column($rows, 'label'),
                'datasets' => [[
                    'label' => 'Penjualan',
                    'data' => array_column($rows, 'value'),
                    'backgroundColor' => $color,
                    'borderRadius' => 6,
                    'barThickness' => 16,
                ]],
            ],
        ];
    }
}
