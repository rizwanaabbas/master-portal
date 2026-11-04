<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Module;

class UpdateModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:module {current_name} {new_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the name of the previously registered module.';

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
        try {
            $module = Module::where('name', $this->argument('current_name'))->first();

            $module->name = $this->argument('new_name');

            $module->save();
            echo "Module name updated successfully!";
        } catch (\Exception $ex) {
            dd($ex->getMessage());
        }
    }
}
