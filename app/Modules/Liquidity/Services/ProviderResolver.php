<?php

namespace App\Modules\Liquidity\Services;

use App\Helpers\Common;

class ProviderResolver
{
    public const B2C2 = 'b2c2';
    public const AQUANOW = 'aquanow';

    /** Module name => provider slug, checked in priority order. */
    private const PROVIDERS = [
        'B2c2' => self::B2C2,
        'Aquanow' => self::AQUANOW,
    ];

    /**
     * Return the slug of the currently enabled liquidity provider, or null if
     * the "Liquidity" master switch is off, or if none (or, unexpectedly,
     * more than one — first enabled wins) of the provider modules is enabled.
     */
    public static function active(): ?string
    {
        if (!Common::isModuleEnabled('Liquidity')) {
            return null;
        }

        foreach (self::PROVIDERS as $moduleName => $slug) {
            if (Common::isModuleEnabled($moduleName)) {
                return $slug;
            }
        }

        return null;
    }
}
