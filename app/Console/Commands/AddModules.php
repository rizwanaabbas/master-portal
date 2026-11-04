<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Module;

class AddModules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'register:modules {modules*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register module(s), after applying module->is_enabled checks in all relevant places in code, for Enabling/Disabling it via admin.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $modules = $this->argument('modules');

        $modulesModel = new Module();

        foreach ($modules as $module) {
            try {
                $modulesModel->name = $module;
                $modulesModel->is_enabled = false;

                $modulesModel->save();
            } catch (\Exception $ex) {
                dd($ex->getMessage());
            }
        }
    }
}
