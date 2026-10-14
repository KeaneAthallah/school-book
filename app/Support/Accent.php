<?php

namespace App\Support;

/**
 * Maps UI icons to section accent tones so related surfaces — page
 * headers, card headers, KPI icons, quick actions and the sidebar —
 * share one color per area of the app.
 *
 * All class strings are declared literally here (never built from
 * variables) so Tailwind's scanner picks them up.
 */
final class Accent
{
    /** Gradient chips for headers and KPI icons (white glyph). */
    public const GRADIENTS = [
        'brand' => 'bg-gradient-to-br from-brand-500 to-brand-700 text-white',
        'indigo' => 'bg-gradient-to-br from-indigo-500 to-indigo-700 text-white',
        'violet' => 'bg-gradient-to-br from-violet-500 to-violet-700 text-white',
        'sky' => 'bg-gradient-to-br from-sky-400 to-sky-600 text-white',
        'cyan' => 'bg-gradient-to-br from-cyan-400 to-cyan-600 text-white',
        'emerald' => 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white',
        'amber' => 'bg-gradient-to-br from-amber-400 to-orange-500 text-white',
        'orange' => 'bg-gradient-to-br from-orange-400 to-orange-600 text-white',
        'rose' => 'bg-gradient-to-br from-rose-400 to-rose-600 text-white',
        'fuchsia' => 'bg-gradient-to-br from-fuchsia-400 to-fuchsia-600 text-white',
        'slate' => 'bg-gradient-to-br from-slate-500 to-slate-700 text-white',
    ];

    /** Soft pastel chips for small tiles and empty states. */
    public const PASTELS = [
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300',
        'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
        'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
        'cyan' => 'bg-cyan-50 text-cyan-600 dark:bg-cyan-500/15 dark:text-cyan-300',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
        'orange' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-300',
        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
        'fuchsia' => 'bg-fuchsia-50 text-fuchsia-600 dark:bg-fuchsia-500/15 dark:text-fuchsia-300',
        'slate' => 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
    ];

    /** Colored glyphs for bare icons (sidebar navigation). */
    public const ICON_COLORS = [
        'brand' => 'text-brand-500 dark:text-brand-400',
        'indigo' => 'text-indigo-500 dark:text-indigo-400',
        'violet' => 'text-violet-500 dark:text-violet-400',
        'sky' => 'text-sky-500 dark:text-sky-400',
        'cyan' => 'text-cyan-500 dark:text-cyan-400',
        'emerald' => 'text-emerald-500 dark:text-emerald-400',
        'amber' => 'text-amber-500 dark:text-amber-400',
        'orange' => 'text-orange-500 dark:text-orange-400',
        'rose' => 'text-rose-500 dark:text-rose-400',
        'fuchsia' => 'text-fuchsia-500 dark:text-fuchsia-400',
        'slate' => 'text-slate-500 dark:text-slate-400',
    ];

    /** Icon name -> tone name. */
    private const ICON_TONES = [
        'dashboard' => 'brand',
        'search' => 'brand',
        'plus' => 'brand',
        'cart' => 'indigo',
        'school' => 'indigo',
        'clipboard' => 'sky',
        'database' => 'violet',
        'users' => 'violet',
        'shield' => 'violet',
        'boxes' => 'emerald',
        'book' => 'emerald',
        'checkCircle' => 'emerald',
        'check' => 'emerald',
        'truck' => 'amber',
        'bolt' => 'amber',
        'inbox' => 'amber',
        'sun' => 'amber',
        'receipt' => 'cyan',
        'calendar' => 'cyan',
        'refresh' => 'cyan',
        'wallet' => 'rose',
        'bell' => 'rose',
        'alert' => 'rose',
        'logout' => 'rose',
        'percent' => 'fuchsia',
        'camera' => 'fuchsia',
        'chart' => 'orange',
        'clock' => 'slate',
        'settings' => 'slate',
        'fileText' => 'slate',
        'printer' => 'slate',
        'filter' => 'slate',
        'monitor' => 'slate',
        'moon' => 'slate',
        'close' => 'slate',
        'menu' => 'slate',
    ];

    public static function toneFor(string $icon): string
    {
        return self::ICON_TONES[$icon] ?? 'brand';
    }

    public static function gradientFor(string $icon): string
    {
        return self::GRADIENTS[self::toneFor($icon)];
    }

    public static function gradientByName(string $tone): string
    {
        return self::GRADIENTS[$tone] ?? self::GRADIENTS['brand'];
    }

    public static function pastelFor(string $icon): string
    {
        return self::PASTELS[self::toneFor($icon)];
    }

    public static function pastelByName(string $tone): string
    {
        return self::PASTELS[$tone] ?? self::PASTELS['brand'];
    }

    public static function iconColor(string $icon): string
    {
        return self::ICON_COLORS[self::toneFor($icon)];
    }
}
