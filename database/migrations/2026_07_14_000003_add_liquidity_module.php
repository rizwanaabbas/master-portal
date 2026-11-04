<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;

/**
 * "Liquidity" is a master switch for the unified Buy/Sell gateway
 * (routes/liquidity.php), independent of which backend provider (B2c2 or
 * Aquanow) actually serves the trade. It has no ModuleConfiguration of its
 * own — it never calls an external API directly — it only needs the
 * enable/disable toggle already provided generically by
 * settings.modules.list / ModuleController.
 */
return new class extends Migration {
    public function up()
    {
        $module = Module::where('name', 'Liquidity')->first();

        if (!$module) {
            $module = new Module();
            $module->name = 'Liquidity';
            $module->is_enabled = 0;
            $module->save();
        }
    }

    public function down()
    {
        Module::where('name', 'Liquidity')->delete();
    }
};
