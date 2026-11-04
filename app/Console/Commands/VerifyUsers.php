<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VerifyUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will verify the existing users email address.';

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
        $users = User::all();

        foreach ($users as $user){
            $user->email_verified_at = Carbon::now();
            $user->save();
        }

        dd ("all email accounts are verified now");
    }
}
