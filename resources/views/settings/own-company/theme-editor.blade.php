@extends('layouts.cp')
@section('title', 'Theme Visual Editor - Own Company')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class="fw-300">Theme Visual Editor</span>
        </h1>
        <a href="{{ url('/settings/own-company-details') }}" class="btn btn-primary mr-3">
            <i class="fal fa-arrow-alt-left"></i> Go Back
        </a>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <h2>Create / Edit bank theme CSS</h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')

                <p class="text-muted mb-4">
                    Pick a base theme, adjust background, text, and hover colors, then save as a
                    <strong>custom</strong> CSS file under
                    <code>public/assets/css/themes/custom/</code>
                    (gitignored — bank-specific; deploys will not overwrite it).
                    Use <em>Auto</em> next to Hover to darken the background as a starting point.
                </p>

                <form method="post" action="{{ url('/settings/own-company/theme') }}" id="theme-editor-form">
                    @csrf
                    <input type="hidden" name="mode" id="save-mode" value="create">

                    <div class="row">
                        <div class="col-lg-7">
                            <div class="form-group">
                                <label class="form-label" for="base_theme">Base theme</label>
                                <select name="base_theme" id="base_theme" class="form-control">
                                    <optgroup label="Stock themes">
                                        @foreach ($stockThemes as $theme)
                                            <option value="{{ $theme }}" @if ($baseTheme === $theme) selected @endif>
                                                {{ $theme }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                    @if (count($customThemes))
                                        <optgroup label="Custom (this bank)">
                                            @foreach ($customThemes as $theme)
                                                <option value="{{ $theme }}" @if ($baseTheme === $theme) selected @endif>
                                                    {{ $theme }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                <small class="form-text text-muted">Current company theme:
                                    <code>{{ $currentTheme }}</code></small>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="theme_name">Custom theme name (optional)</label>
                                <input type="text" class="form-control" name="theme_name" id="theme_name"
                                    placeholder="e.g. monfi-gold or cib-blue"
                                    value="{{ old('theme_name') }}">
                                <small class="form-text text-muted">
                                    Saved as <code>custom/&lt;timestamp&gt;-&lt;name&gt;.css</code>.
                                    Leave blank for <code>custom/bank-&lt;timestamp&gt;.css</code>.
                                </small>
                            </div>

                            <h5 class="mb-2">Theme palette</h5>
                            <p class="text-muted small mb-3">
                                Background, text, and button hover for each color.
                            </p>
                            <div class="row">
                                @php
                                    $labels = [
                                        'primary' => 'Primary',
                                        'secondary' => 'Secondary',
                                        'success' => 'Success',
                                        'info' => 'Info',
                                        'warning' => 'Warning',
                                        'danger' => 'Danger',
                                        'dark' => 'Dark / Fusion',
                                    ];
                                @endphp
                                @foreach ($labels as $key => $label)
                                    @php
                                        $bgVal = old($key, $palette[$key] ?? '#000000');
                                        $textVal = old($key . '_text', $textColors[$key] ?? '#ffffff');
                                        $hoverVal = old($key . '_hover', $hoverColors[$key] ?? $bgVal);
                                    @endphp
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ $label }}</label>
                                        <div class="d-flex align-items-center mb-1">
                                            <small class="text-muted mr-2" style="width: 36px;">BG</small>
                                            <input type="color" class="form-control theme-color-picker"
                                                id="color-{{ $key }}" name="{{ $key }}"
                                                value="{{ $bgVal }}"
                                                style="max-width: 52px; padding: 2px;">
                                            <input type="text" class="form-control theme-color-hex ml-1"
                                                data-for="{{ $key }}"
                                                value="{{ $bgVal }}"
                                                maxlength="7" pattern="^#?[0-9A-Fa-f]{6}$">
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <small class="text-muted mr-2" style="width: 36px;">Text</small>
                                            <input type="color" class="form-control theme-text-picker"
                                                id="color-{{ $key }}-text" name="{{ $key }}_text"
                                                value="{{ $textVal }}"
                                                style="max-width: 52px; padding: 2px;">
                                            <input type="text" class="form-control theme-text-hex ml-1"
                                                data-for="{{ $key }}"
                                                value="{{ $textVal }}"
                                                maxlength="7" pattern="^#?[0-9A-Fa-f]{6}$">
                                            <button type="button" class="btn btn-sm btn-outline-secondary ml-1 btn-auto-text"
                                                data-target="theme" data-key="{{ $key }}" title="Auto contrast">
                                                Auto
                                            </button>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted mr-2" style="width: 36px;">Hover</small>
                                            <input type="color" class="form-control theme-hover-picker"
                                                id="color-{{ $key }}-hover" name="{{ $key }}_hover"
                                                value="{{ $hoverVal }}"
                                                style="max-width: 52px; padding: 2px;">
                                            <input type="text" class="form-control theme-hover-hex ml-1"
                                                data-for="{{ $key }}"
                                                value="{{ $hoverVal }}"
                                                maxlength="7" pattern="^#?[0-9A-Fa-f]{6}$">
                                            <button type="button" class="btn btn-sm btn-outline-secondary ml-1 btn-auto-hover"
                                                data-key="{{ $key }}" title="Auto darken BG">
                                                Auto
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <hr>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h5 class="mb-0">Badge colors <code class="small">badge badge-*</code></h5>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-sync-badges">
                                    Copy from theme palette
                                </button>
                            </div>
                            <p class="text-muted small mb-3">
                                Editable separately from the theme palette (background + text).
                            </p>
                            <div class="row">
                                @foreach ($labels as $key => $label)
                                    @php
                                        $badgeVal = old('badge_' . $key, $badgeColors[$key] ?? ($palette[$key] ?? '#000000'));
                                        $badgeTextVal = old('badge_' . $key . '_text', $badgeTextColors[$key] ?? '#ffffff');
                                    @endphp
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">badge-{{ $key }}</label>
                                        <div class="d-flex align-items-center mb-1">
                                            <small class="text-muted mr-2" style="width: 28px;">BG</small>
                                            <input type="color" class="form-control badge-color-picker"
                                                id="badge-color-{{ $key }}" name="badge_{{ $key }}"
                                                value="{{ $badgeVal }}"
                                                style="max-width: 52px; padding: 2px;">
                                            <input type="text" class="form-control badge-color-hex ml-1"
                                                data-for="{{ $key }}"
                                                value="{{ $badgeVal }}"
                                                maxlength="7" pattern="^#?[0-9A-Fa-f]{6}$">
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted mr-2" style="width: 28px;">Text</small>
                                            <input type="color" class="form-control badge-text-picker"
                                                id="badge-color-{{ $key }}-text" name="badge_{{ $key }}_text"
                                                value="{{ $badgeTextVal }}"
                                                style="max-width: 52px; padding: 2px;">
                                            <input type="text" class="form-control badge-text-hex ml-1"
                                                data-for="{{ $key }}"
                                                value="{{ $badgeTextVal }}"
                                                maxlength="7" pattern="^#?[0-9A-Fa-f]{6}$">
                                            <button type="button" class="btn btn-sm btn-outline-secondary ml-1 btn-auto-text"
                                                data-target="badge" data-key="{{ $key }}" title="Auto contrast">
                                                Auto
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="form-group mt-2">
                                <button type="submit" class="btn btn-primary" id="btn-create"
                                    onclick="document.getElementById('save-mode').value='create'">
                                    <i class="fal fa-plus"></i> Create new custom theme
                                </button>
                                @if ($isCustom)
                                    <button type="submit" class="btn btn-warning" id="btn-update"
                                        onclick="document.getElementById('save-mode').value='update'">
                                        <i class="fal fa-save"></i> Update current custom theme
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="border rounded p-3 bg-white shadow-sm">
                                <h5 class="mb-3">Live preview</h5>

                                <h6 class="mb-2">Buttons <small class="text-muted">(hover to see hover color)</small></h6>
                                <div class="mb-3 d-flex flex-wrap" id="preview-buttons">
                                    @foreach ($labels as $key => $label)
                                        @php
                                            $bg = $palette[$key] ?? '#888';
                                            $tx = $textColors[$key] ?? '#fff';
                                        @endphp
                                        <button type="button"
                                            class="btn btn-sm mr-1 mb-1 preview-btn"
                                            id="preview-btn-{{ $key }}"
                                            data-key="{{ $key }}"
                                            style="background: {{ $bg }}; color: {{ $tx }}; border: 0;">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                                <div class="small text-muted mb-3" id="preview-hover-hint">
                                    Hover a button — hover BG shown below:
                                    <span id="preview-hover-swatch"
                                        style="display:inline-block;width:18px;height:18px;border-radius:3px;vertical-align:middle;border:1px solid #ccc;background:#ccc;"></span>
                                    <code id="preview-hover-hex" class="ml-1">—</code>
                                </div>

                                <h6 class="mb-2">Alerts / messages</h6>
                                <div class="mb-3" id="preview-alerts">
                                    @foreach (['success', 'info', 'warning', 'danger'] as $key)
                                        @php
                                            $bg = $palette[$key] ?? '#888';
                                            $tx = $textColors[$key] ?? '#212529';
                                        @endphp
                                        <div class="alert py-2 px-3 mb-2 preview-alert" id="preview-alert-{{ $key }}"
                                            data-key="{{ $key }}" role="alert"
                                            style="background: {{ $bg }}; color: {{ $tx }}; border: 0;">
                                            Sample {{ $key }} message
                                        </div>
                                    @endforeach
                                </div>

                                <h6 class="mb-2">Badges</h6>
                                <div class="mb-3" id="preview-badges">
                                    @foreach (['primary', 'secondary', 'success', 'info', 'warning', 'danger', 'dark'] as $key)
                                        @php
                                            $b = $badgeColors[$key] ?? ($palette[$key] ?? '#888');
                                            $bt = $badgeTextColors[$key] ?? '#fff';
                                        @endphp
                                        <span class="badge mr-1 mb-1 preview-badge" id="preview-badge-{{ $key }}"
                                            data-badge="{{ $key }}"
                                            style="background: {{ $b }}; color: {{ $bt }};">
                                            badge-{{ $key }}
                                        </span>
                                    @endforeach
                                </div>

                                <hr>
                                <div class="card p-3" id="preview-card"
                                    style="border-top: 3px solid {{ $palette['primary'] ?? '#ffc800' }};">
                                    <strong id="preview-card-title"
                                        style="color: {{ $palette['primary'] ?? '#ffc800' }};">
                                        Sample card
                                    </strong>
                                    <p class="mb-2 text-muted small">
                                        Buttons and alerts use the same BG + Text colors from the theme palette.
                                    </p>
                                    <button type="button" class="btn btn-sm preview-btn" id="preview-btn-card"
                                        data-key="primary"
                                        style="background: {{ $palette['primary'] ?? '#ffc800' }}; color: {{ $textColors['primary'] ?? '#fff' }}; border: 0;">
                                        Primary action
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <hr class="my-4">
                <form method="post" action="{{ url('/settings/own-company/theme/reset') }}" id="theme-reset-form"
                    onsubmit="return confirm('Reset company theme to stock CSS {{ $defaultStockTheme }}? Your current custom file will not be deleted; the company will use the stock parent so you can reconfigure and save again.');">
                    @csrf
                    <input type="hidden" name="stock_theme" id="reset-stock-theme" value="{{ $defaultStockTheme }}">
                    <button type="submit" class="btn btn-outline-danger" id="btn-reset-theme">
                        <i class="fal fa-undo"></i> Reset to default CSS
                        <code class="ml-1">{{ $defaultStockTheme }}</code>
                    </button>
                    <small class="form-text text-muted mt-2 d-block">
                        Switches Own Company back to the stock parent theme
                        (default <code>{{ $defaultStockTheme }}</code>).
                        Pickers reload with stock colors so you can configure and create a new custom theme.
                        If the Base theme dropdown is a stock file, that is used instead.
                    </small>
                </form>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    <script>
        (function() {
            var colorKeys = ['primary', 'secondary', 'success', 'info', 'warning', 'danger', 'dark'];
            var badgeKeys = colorKeys.slice();

            function normalizeHex(v) {
                v = (v || '').trim();
                if (v.charAt(0) !== '#') v = '#' + v;
                return /^#[0-9A-Fa-f]{6}$/.test(v) ? v.toLowerCase() : null;
            }

            function contrastInk(hex) {
                hex = (hex || '').replace('#', '');
                if (hex.length !== 6) return '#ffffff';
                var r = parseInt(hex.substr(0, 2), 16);
                var g = parseInt(hex.substr(2, 2), 16);
                var b = parseInt(hex.substr(4, 2), 16);
                var brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000;
                return brightness >= 160 ? '#212529' : '#ffffff';
            }

            function adjustBrightness(hex, amount) {
                hex = (hex || '').replace('#', '');
                if (hex.length !== 6) return '#000000';
                var r = parseInt(hex.substr(0, 2), 16) / 255;
                var g = parseInt(hex.substr(2, 2), 16) / 255;
                var b = parseInt(hex.substr(4, 2), 16) / 255;
                var max = Math.max(r, g, b), min = Math.min(r, g, b);
                var h, s, l = (max + min) / 2;
                if (max === min) {
                    h = s = 0;
                } else {
                    var d = max - min;
                    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
                    switch (max) {
                        case r: h = ((g - b) / d) + (g < b ? 6 : 0); break;
                        case g: h = ((b - r) / d) + 2; break;
                        default: h = ((r - g) / d) + 4; break;
                    }
                    h *= 60;
                }
                l = Math.max(0, Math.min(1, l + amount));
                function hue2rgb(p, q, t) {
                    if (t < 0) t += 1;
                    if (t > 1) t -= 1;
                    if (t < 1 / 6) return p + (q - p) * 6 * t;
                    if (t < 1 / 2) return q;
                    if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
                    return p;
                }
                var r2, g2, b2;
                if (s === 0) {
                    r2 = g2 = b2 = l;
                } else {
                    var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
                    var p = 2 * l - q;
                    r2 = hue2rgb(p, q, (h / 360) + 1 / 3);
                    g2 = hue2rgb(p, q, h / 360);
                    b2 = hue2rgb(p, q, (h / 360) - 1 / 3);
                }
                function toHex(n) {
                    var s = Math.round(n * 255).toString(16);
                    return s.length === 1 ? '0' + s : s;
                }
                return '#' + toHex(r2) + toHex(g2) + toHex(b2);
            }

            function mixWithWhite(hex, whiteAmount) {
                hex = (hex || '').replace('#', '');
                if (hex.length !== 6) return '#ffffff';
                var r = parseInt(hex.substr(0, 2), 16);
                var g = parseInt(hex.substr(2, 2), 16);
                var b = parseInt(hex.substr(4, 2), 16);
                r = Math.round(r + (255 - r) * whiteAmount);
                g = Math.round(g + (255 - g) * whiteAmount);
                b = Math.round(b + (255 - b) * whiteAmount);
                function toHex(n) {
                    var s = n.toString(16);
                    return s.length === 1 ? '0' + s : s;
                }
                return '#' + toHex(r) + toHex(g) + toHex(b);
            }

            function isLight(hex) {
                hex = (hex || '').replace('#', '');
                if (hex.length !== 6) return false;
                var r = parseInt(hex.substr(0, 2), 16);
                var g = parseInt(hex.substr(2, 2), 16);
                var b = parseInt(hex.substr(4, 2), 16);
                return (((r * 299) + (g * 587) + (b * 114)) / 1000) >= 160;
            }

            function getThemeBg(key) {
                var el = document.getElementById('color-' + key);
                return el ? el.value : '#000000';
            }

            function getThemeText(key) {
                var el = document.getElementById('color-' + key + '-text');
                return el ? el.value : '#ffffff';
            }

            function getThemeHover(key) {
                var el = document.getElementById('color-' + key + '-hover');
                return el ? el.value : adjustBrightness(getThemeBg(key), -0.12);
            }

            function getBadgeBg(key) {
                var el = document.getElementById('badge-color-' + key);
                return el ? el.value : '#000000';
            }

            function getBadgeText(key) {
                var el = document.getElementById('badge-color-' + key + '-text');
                return el ? el.value : '#ffffff';
            }

            function setPair(prefix, key, bg, text) {
                var bgPicker = document.getElementById(prefix + key);
                var bgHex = document.querySelector((prefix.indexOf('badge') === 0 ? '.badge-color-hex' : '.theme-color-hex') + '[data-for="' + key + '"]');
                var textPicker = document.getElementById(prefix + key + '-text');
                var textHex = document.querySelector((prefix.indexOf('badge') === 0 ? '.badge-text-hex' : '.theme-text-hex') + '[data-for="' + key + '"]');
                if (bgPicker && bg) bgPicker.value = bg;
                if (bgHex && bg) bgHex.value = bg;
                if (textPicker && text) textPicker.value = text;
                if (textHex && text) textHex.value = text;
            }

            function setHover(key, hover) {
                var picker = document.getElementById('color-' + key + '-hover');
                var hex = document.querySelector('.theme-hover-hex[data-for="' + key + '"]');
                if (picker && hover) picker.value = hover;
                if (hex && hover) hex.value = hover;
            }

            function applyBtnStyle(el, bg, text, hover) {
                if (!el) return;
                el.style.background = bg;
                el.style.color = text;
                el.style.border = '0';
                el.dataset.bg = bg;
                el.dataset.text = text;
                el.dataset.hover = hover || adjustBrightness(bg, -0.12);
            }

            function updatePreview() {
                var primary = getThemeBg('primary');
                var card = document.getElementById('preview-card');
                var title = document.getElementById('preview-card-title');
                if (card) card.style.borderTopColor = primary;
                if (title) title.style.color = primary;

                colorKeys.forEach(function(key) {
                    var bg = getThemeBg(key);
                    var text = getThemeText(key);
                    var hover = getThemeHover(key);
                    document.querySelectorAll('.preview-btn[data-key="' + key + '"]').forEach(function(btn) {
                        applyBtnStyle(btn, bg, text, hover);
                    });
                });

                ['success', 'info', 'warning', 'danger'].forEach(function(key) {
                    var alert = document.getElementById('preview-alert-' + key);
                    if (!alert) return;
                    // Same solid BG + text as buttons
                    var bg = getThemeBg(key);
                    var text = getThemeText(key);
                    alert.style.background = bg;
                    alert.style.color = text;
                    alert.style.border = '1px solid ' + bg;
                });

                badgeKeys.forEach(function(key) {
                    var badge = document.getElementById('preview-badge-' + key);
                    if (!badge) return;
                    badge.style.background = getBadgeBg(key);
                    badge.style.color = getBadgeText(key);
                });
            }

            function bindHexSync(pickerSel, hexSel, getKey, onChange) {
                document.querySelectorAll(pickerSel).forEach(function(el) {
                    el.addEventListener('input', function() {
                        var key = getKey(el);
                        var hex = document.querySelector(hexSel + '[data-for="' + key + '"]');
                        if (hex) hex.value = el.value;
                        onChange();
                    });
                });
                document.querySelectorAll(hexSel).forEach(function(el) {
                    el.addEventListener('change', function() {
                        var key = el.getAttribute('data-for');
                        var v = normalizeHex(el.value);
                        if (!v) return;
                        el.value = v;
                        onChange(key, v);
                    });
                });
            }

            bindHexSync('.theme-color-picker', '.theme-color-hex', function(el) { return el.name; }, function(key, v) {
                if (key && v) {
                    var picker = document.getElementById('color-' + key);
                    if (picker) picker.value = v;
                }
                updatePreview();
            });
            bindHexSync('.theme-text-picker', '.theme-text-hex', function(el) {
                return el.name.replace(/_text$/, '');
            }, function(key, v) {
                if (key && v) {
                    var picker = document.getElementById('color-' + key + '-text');
                    if (picker) picker.value = v;
                }
                updatePreview();
            });
            bindHexSync('.theme-hover-picker', '.theme-hover-hex', function(el) {
                return el.name.replace(/_hover$/, '');
            }, function(key, v) {
                if (key && v) {
                    var picker = document.getElementById('color-' + key + '-hover');
                    if (picker) picker.value = v;
                }
                updatePreview();
            });
            bindHexSync('.badge-color-picker', '.badge-color-hex', function(el) {
                return el.name.replace(/^badge_/, '');
            }, function(key, v) {
                if (key && v) {
                    var picker = document.getElementById('badge-color-' + key);
                    if (picker) picker.value = v;
                }
                updatePreview();
            });
            bindHexSync('.badge-text-picker', '.badge-text-hex', function(el) {
                return el.name.replace(/^badge_/, '').replace(/_text$/, '');
            }, function(key, v) {
                if (key && v) {
                    var picker = document.getElementById('badge-color-' + key + '-text');
                    if (picker) picker.value = v;
                }
                updatePreview();
            });

            document.querySelectorAll('.btn-auto-text').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var key = btn.getAttribute('data-key');
                    var target = btn.getAttribute('data-target');
                    if (target === 'badge') {
                        var ink = contrastInk(getBadgeBg(key));
                        setPair('badge-color-', key, null, ink);
                    } else {
                        var ink2 = contrastInk(getThemeBg(key));
                        setPair('color-', key, null, ink2);
                    }
                    updatePreview();
                });
            });

            document.querySelectorAll('.btn-auto-hover').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var key = btn.getAttribute('data-key');
                    setHover(key, adjustBrightness(getThemeBg(key), -0.12));
                    updatePreview();
                });
            });

            document.getElementById('btn-sync-badges').addEventListener('click', function() {
                badgeKeys.forEach(function(key) {
                    setPair('badge-color-', key, getThemeBg(key), getThemeText(key));
                });
                updatePreview();
            });

            // Live hover preview on buttons
            var hoverSwatch = document.getElementById('preview-hover-swatch');
            var hoverHex = document.getElementById('preview-hover-hex');
            document.querySelectorAll('.preview-btn').forEach(function(btn) {
                btn.addEventListener('mouseenter', function() {
                    var hover = btn.dataset.hover || adjustBrightness(btn.dataset.bg || '#000', -0.12);
                    var text = btn.dataset.text || '#fff';
                    btn.style.background = hover;
                    btn.style.color = text;
                    if (hoverSwatch) hoverSwatch.style.background = hover;
                    if (hoverHex) hoverHex.textContent = hover;
                });
                btn.addEventListener('mouseleave', function() {
                    btn.style.background = btn.dataset.bg || '';
                    btn.style.color = btn.dataset.text || '';
                });
            });

            var defaultStock = @json($defaultStockTheme);
            var stockThemes = @json($stockThemes);
            function syncResetTarget() {
                var baseEl = document.getElementById('base_theme');
                var resetEl = document.getElementById('reset-stock-theme');
                if (!baseEl || !resetEl) return;
                var base = baseEl.value;
                resetEl.value = (stockThemes.indexOf(base) !== -1) ? base : defaultStock;
            }
            syncResetTarget();

            document.getElementById('base_theme').addEventListener('change', function() {
                var base = this.value;
                window.location = "{{ url('/settings/own-company/theme') }}?base=" + encodeURIComponent(base);
            });

            updatePreview();
        })();
    </script>
@stop
