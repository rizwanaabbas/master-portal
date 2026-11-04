<?php

namespace App\Http\Controllers;

use App\Models\OwnCompany;
use App\Services\ThemeCssService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class OwnCompanyThemeController extends Controller
{
    /** @var ThemeCssService */
    protected $themes;

    public function __construct(ThemeCssService $themes)
    {
        $this->themes = $themes;
    }

    public function edit(Request $request)
    {
        $company = OwnCompany::firstOrFail();
        $stockThemes = $this->themes->listStockThemes();
        $customThemes = $this->themes->listCustomThemes();

        $currentTheme = $company->theme ?: 'cust-theme-14.css';
        $baseTheme = $request->query('base', $currentTheme);

        try {
            $this->themes->resolveThemePath($baseTheme);
        } catch (InvalidArgumentException $e) {
            $baseTheme = in_array('cust-theme-14.css', $stockThemes, true)
                ? 'cust-theme-14.css'
                : ($stockThemes[0] ?? 'cust-theme-14.css');
        }

        try {
            $palette = $this->themes->extractPalette($baseTheme);
            // Prefer colors already saved on the active custom theme when editing it.
            $colorSource = ($this->themes->isCustomTheme($currentTheme) && $request->query('base') === null)
                ? $currentTheme
                : $baseTheme;
            if ($this->themes->isCustomTheme($colorSource)) {
                $this->themes->ensureThemeOverrides($colorSource);
            }
            $badgeColors = $this->themes->extractBadgeColors($colorSource, $palette);
            $textColors = $this->themes->extractTextColors($colorSource, $palette);
            $badgeTextColors = $this->themes->extractBadgeTextColors($colorSource, $badgeColors);
            $hoverColors = $this->themes->extractHoverColors($colorSource, $palette);
            // When editing the active custom theme, use its saved palette bases too.
            if ($this->themes->isCustomTheme($colorSource) && $request->query('base') === null) {
                $palette = $this->themes->extractPalette($colorSource);
            }
        } catch (Throwable $e) {
            return redirect('/settings/own-company-details')
                ->with('error', 'Unable to read theme CSS: ' . $e->getMessage());
        }

        $isCustom = $this->themes->isCustomTheme($currentTheme);
        $defaultStockTheme = in_array('cust-theme-14.css', $stockThemes, true)
            ? 'cust-theme-14.css'
            : ($stockThemes[0] ?? 'cust-theme-14.css');

        return view('settings.own-company.theme-editor', compact(
            'company',
            'stockThemes',
            'customThemes',
            'currentTheme',
            'baseTheme',
            'palette',
            'badgeColors',
            'textColors',
            'badgeTextColors',
            'hoverColors',
            'isCustom',
            'defaultStockTheme'
        ));
    }

    /**
     * Switch company theme back to a stock parent CSS (e.g. cust-theme-14.css)
     * so the bank can reconfigure from defaults.
     */
    public function reset(Request $request)
    {
        $this->validate($request, [
            'stock_theme' => 'nullable|string',
        ]);

        $company = OwnCompany::firstOrFail();
        $stockThemes = $this->themes->listStockThemes();
        $default = in_array('cust-theme-14.css', $stockThemes, true)
            ? 'cust-theme-14.css'
            : ($stockThemes[0] ?? 'cust-theme-14.css');

        $stockTheme = $request->get('stock_theme') ?: $default;

        try {
            if ($this->themes->isCustomTheme($stockTheme) || !in_array($stockTheme, $stockThemes, true)) {
                $stockTheme = $default;
            }
            $this->themes->resolveThemePath($stockTheme);
        } catch (Throwable $e) {
            return redirect('/settings/own-company/theme')
                ->with('error', 'Unable to reset theme: ' . $e->getMessage());
        }

        $previous = (string) $company->theme;
        $company->theme = $stockTheme;
        $company->save();

        return redirect('/settings/own-company/theme?base=' . urlencode($stockTheme))
            ->with(
                'success',
                'Theme reset to stock CSS ' . $stockTheme
                . ($previous && $previous !== $stockTheme ? ' (was ' . $previous . ').' : '.')
                . ' Adjust colors and save a new custom theme when ready.'
            );
    }

    public function previewPalette(Request $request)
    {
        $this->validate($request, [
            'base_theme' => 'required|string',
        ]);

        try {
            $palette = $this->themes->extractPalette($request->get('base_theme'));
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'palette' => collect(ThemeCssService::COLOR_KEYS)->mapWithKeys(function ($key) use ($palette) {
                return [$key => $palette[$key] ?? '#000000'];
            }),
            'shades' => $palette['shades']['primary'] ?? [],
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'base_theme' => 'required|string',
            'theme_name' => 'nullable|string|max:80',
            'mode' => 'required|in:create,update',
        ];
        foreach (ThemeCssService::COLOR_KEYS as $key) {
            $rules[$key] = 'required|string';
            $rules[$key . '_text'] = 'required|string';
            $rules[$key . '_hover'] = 'required|string';
        }
        foreach (ThemeCssService::BADGE_KEYS as $key) {
            $rules['badge_' . $key] = 'required|string';
            $rules['badge_' . $key . '_text'] = 'required|string';
        }
        $this->validate($request, $rules);

        $company = OwnCompany::firstOrFail();
        $colors = $request->only(ThemeCssService::COLOR_KEYS);
        $textColors = [];
        $hoverColors = [];
        $badgeColors = [];
        $badgeTextColors = [];
        foreach (ThemeCssService::COLOR_KEYS as $key) {
            $textColors[$key] = $request->get($key . '_text');
            $hoverColors[$key] = $request->get($key . '_hover');
        }
        foreach (ThemeCssService::BADGE_KEYS as $key) {
            $badgeColors[$key] = $request->get('badge_' . $key);
            $badgeTextColors[$key] = $request->get('badge_' . $key . '_text');
        }

        try {
            if ($request->get('mode') === 'update' && $this->themes->isCustomTheme((string) $company->theme)) {
                $relative = $this->themes->updateCustomTheme(
                    (string) $company->theme,
                    $request->get('base_theme'),
                    $colors,
                    $badgeColors,
                    $textColors,
                    $badgeTextColors,
                    $hoverColors
                );
            } else {
                $relative = $this->themes->createCustomTheme(
                    $request->get('base_theme'),
                    $colors,
                    $request->get('theme_name'),
                    $badgeColors,
                    $textColors,
                    $badgeTextColors,
                    $hoverColors
                );
            }

            $company->theme = $relative;
            $company->save();
        } catch (Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect('/settings/own-company-details')
            ->with('success', 'Theme saved as ' . $relative . '. Custom themes live outside git and will not be overwritten by deploy.');
    }
}
