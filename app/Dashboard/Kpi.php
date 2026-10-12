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
     */
    private const TONES = [
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
        'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
        'slate' => 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
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
