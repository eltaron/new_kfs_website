<?php

namespace App\Support\Filament;

use Filament\FontProviders\GoogleFontProvider;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;

/**
 * Shared KFS identity for every Filament panel (docs/kfs-design-system.md).
 *
 *   KfsBranding::apply($panel)                                  // cyan primary
 *   KfsBranding::apply($panel, primary: KfsBranding::GOLD)      // gold primary
 *
 * Sets colors, fonts, logo, favicon and injects public/css/kfs-filament.css.
 * Call it before any panel-specific ->renderHook() so panel CSS can still override.
 */
final class KfsBranding
{
    public const CYAN = '#008FA5';   // --kfs-cyan-deep (water / digital)
    public const GOLD = '#D8B03E';   // --kfs-gold (heritage)
    public const OLIVE = '#657342';  // --kfs-olive (land)
    public const DANGER = '#A63A32';
    public const INFO = '#147C92';

    /** Body: IBM Plex Sans Arabic · headings: Tajawal · large digits: Playfair Display. */
    public const FONT_URL = 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700'
        . '&family=Tajawal:wght@500;700;800&family=Playfair+Display:wght@500;600&display=swap';

    public static function apply(Panel $panel, string $primary = self::CYAN, string $logoHeight = '2.75rem'): Panel
    {
        return $panel
            ->colors(self::colors($primary))
            ->font('IBM Plex Sans Arabic', url: self::FONT_URL, provider: GoogleFontProvider::class)
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight($logoHeight)
            ->favicon(asset('favicon/favicon.ico'))
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => self::headHtml($primary));
    }

    /** @return array<string, array<int, string>> */
    public static function colors(string $primary = self::CYAN): array
    {
        return [
            'primary' => self::palette($primary),
            'warning' => self::palette(self::GOLD),
            'success' => self::palette(self::OLIVE),
            'danger' => self::palette(self::DANGER),
            'info' => self::palette(self::INFO),
            'gray' => self::GRAY,
        ];
    }

    /**
     * Color::hex() palette, but with 600/700 kept dark enough for Filament's
     * white-on-600 buttons (WCAG AA) — matters for the light gold.
     *
     * @return array<int, string>
     */
    public static function palette(string $hex): array
    {
        $shades = Color::hex($hex);

        if (strcasecmp($hex, self::GOLD) === 0) {
            $shades[600] = '143, 105, 23';  // --kfs-gold-deep #8F6917
            $shades[700] = '121, 83, 41';   // --kfs-bronze #795329
        }

        return $shades;
    }

    /** Warm stone gray ending in the KFS dark government surfaces (#34413D / #26302D / #1D2422). */
    public const GRAY = [
        50 => '250, 248, 240',
        100 => '243, 240, 228',
        200 => '229, 225, 210',
        300 => '208, 203, 186',
        400 => '160, 157, 144',
        500 => '120, 117, 106',
        600 => '91, 89, 81',
        700 => '64, 66, 61',
        800 => '52, 65, 61',
        900 => '38, 48, 45',
        950 => '29, 36, 34',
    ];

    public static function headHtml(string $primary = self::CYAN): string
    {
        $html = '<meta name="theme-color" content="#26302D">';

        // Gold buttons: charcoal text whenever the light gold (500) is the background
        // (dark mode + hover) — white on #D8B03E fails contrast (spec: .btn-kfs).
        if (strcasecmp($primary, self::GOLD) === 0) {
            $html .= '<style>.dark .fi-btn.fi-color-primary:not(.fi-btn-outlined),.dark .fi-btn.fi-color-primary:not(.fi-btn-outlined) *,'
                . '.fi-btn.fi-color-primary:not(.fi-btn-outlined):hover,.fi-btn.fi-color-primary:not(.fi-btn-outlined):hover *{color:#282724!important}</style>';
        }
        $css = public_path('css/kfs-filament.css');

        if (is_file($css)) {
            $html .= '<link rel="stylesheet" href="' . e(asset('css/kfs-filament.css')) . '?v=' . filemtime($css) . '">';
        }

        return $html;
    }
}
