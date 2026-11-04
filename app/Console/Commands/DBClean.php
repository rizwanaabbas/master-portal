<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DBClean extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean';

    /**
     * This command will clean all data in the current database.
     *
     * @var string
     */
    protected $description = 'This command will clean all data in the current database';

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
        
        # check for db clean flag
        $flag = env("DB_CLEAN");

        if (env("DB_CLEAN") == true){
            $this->line("clean flag is set stating cleanup...");

            # clean all data tables except the required ones
            self::cleanDataTables();

            # delete all users except admin user
            self::cleanUsers("uneekswatian@gmail.com");






        }else{
            $this->line("clean flag is not set");
        }
        
        
        return 0;
    }

    private static function cleanUsers($admin)
    {
         # remove all user accounts, except the 
         echo "\n---------------------------------------------------------";
         DB::table('users')->where('email', "!=", $admin)->delete();
         echo "\n deleted all users except admin user which is ".$admin;

         $user =   DB::table('users')->where('email', $admin)->first();   

         # remove all role models except admin
         DB::table('model_has_roles')->where('model_id', "!=", $user->id)->delete();
         echo "\n deleted all models except admin role model";
    }

    private static function cleanDataTables()
    {
        $tables = DB::select('SHOW TABLES');
        $required_tables = "client_files_config,countries,currencies,dropdown_configurations,migrations,model_has_roles,module_configurations,modules,own_companies,per_currency_maintenance_fees,permissions,role_has_permissions,roles,users,vista_money_countries,sanctions_reports";
        $ignore = explode(",", $required_tables);

        
        # truncate all data tables, except the ones which are in the ignore list.
        foreach ($tables as $table){
            foreach ($table as $key => $value)

                #clear all tables which are not in the ignore list.
                if (!in_array($value, $ignore)){
                    echo "\n truncating table -> ".$value;
                    DB::table($value)->truncate();
                }
        }
    }
}
