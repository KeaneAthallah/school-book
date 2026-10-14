<?php

namespace App\Dashboard;

/**
 * Builds the KPI card payloads consumed by <x-dashboard.kpi-grid>.
 *
 * Values stay raw here and are formatted by the view, so services never deal in
 * presentation strings and the grid stays the single place that decides how a
 * number or an amount is rendered.
 */
final class Kpi
{
    /**
     * Semantic icon tones, matching the palette used across BOOKFLOW.
     *
     * Icon chips use vibrant gradients so every dashboard reads
     * with color at a glance.
     */
    private const TONES = [
        'brand' => 'bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-sm',
        'violet' => 'bg-gradient-to-br from-violet-500 to-violet-700 text-white shadow-sm',
        'amber' => 'bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-sm',
        'sky' => 'bg-gradient-to-br from-sky-400 to-sky-600 text-white shadow-sm',
        'emerald' => 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-sm',
        'rose' => 'bg-gradient-to-br from-rose-400 to-rose-600 text-white shadow-sm',
        'slate' => 'bg-gradient-to-br from-slate-500 to-slate-700 text-white shadow-sm',
    ];

    private const VALUE_TONES = [
        'default' => 'text-ink',
        'amber' => 'text-amber-600 dark:text-amber-400',
        'emerald' => 'text-emerald-700 dark:text-emerald-400',
        'rose' => 'text-rose-600 dark:text-rose-400',
        'brand' => 'text-brand-600 dark:text-brand-300',
    ];

    /**
     * @param  array{hint?: string|null, delta?: float|null, goodWhenDown?: bool, valueTone?: string, format?: string}  $options
     * @return array<string, mixed>
     */
    public static function number(string $label, int|float $value, string $icon, string $tone = 'brand', array $options = []): array
    {
        return self::card($label, $value, 'number', $icon, $tone, $options);
    }

    /**
     * @param  array{hint?: string|null, delta?: float|null, goodWhenDown?: bool, valueTone?: string}  $options
     * @return array<string, mixed>
     */
    public static function money(string $label, string $value, string $icon, string $tone = 'brand', array $options = []): array
    {
        return self::card($label, $value, 'currency', $icon, $tone, $options);
    }

    /**
     * A KPI shown without a comparable previous period.
     *
     * @param  array{hint?: string|null, format?: string, valueTone?: string}  $options
     * @return array<string, mixed>
     */
    public static function point(string $label, int|float|string $value, string $icon, string $tone = 'brand', array $options = []): array
    {
        $options['delta'] = null;

        return self::card($label, $value, $options['format'] ?? 'number', $icon, $tone, $options);
    }

    /**
     * Percentage change, or null when there is no comparable base. Returning
     * null (rather than 0 or 100) is what keeps the UI from inventing growth.
     */
    public static function delta(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return null;
        }

        return (((float) $current - (float) $previous) / (float) $previous) * 100;
    }

    /**
     * @param  array{hint?: string|null, delta?: float|null, goodWhenDown?: bool, valueTone?: string}  $options
     * @return array<string, mixed>
     */
    private static function card(string $label, int|float|string $value, string $format, string $icon, string $tone, array $options): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'format' => $format,
            'icon' => $icon,
            'iconTone' => self::TONES[$tone] ?? self::TONES['brand'],
            'tone' => self::VALUE_TONES[$options['valueTone'] ?? 'default'] ?? self::VALUE_TONES['default'],
            'hint' => $options['hint'] ?? null,
            'delta' => $options['delta'] ?? null,
            'goodWhenDown' => (bool) ($options['goodWhenDown'] ?? false),
        ];
    }
}
