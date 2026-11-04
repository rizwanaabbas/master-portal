<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\ClientUser;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;

class IDORHelper
{
    public static function isAccountBelongToUser($user, $account_id)
    {
        // these permissions have access to all accounts, no need to check
        if ($user->hasRole(["operations manager", "finance manager", "super admin", "finance", "operations", 'audit'])) {
            return true;
        }

        // get linked clients
        $clients = $user->linkClients->pluck("client_id")->toArray();

        // get accounts by clients
        $count = Account::whereIn("client_id", $clients)
            ->where("id", $account_id)
            ->count();

        // check if the account id exists in the client accounts
        if ($count > 0) {
            return true;
        } else {
            throw new Exception('Unauthorized access', 401);
        }
    }
    
    public static function isClientBelongToUser($client_id, $user_id)
    {
        $user = User::find($user_id);
        // these permissions have access to all clients, no need to check
        if ($user->hasRole(["operations manager", "finance manager", "super admin", "finance", "operations", 'audit'])) {
            return true;
        }

        // get linked clients
        $clients = $user->linkClients->pluck("client_id")->toArray();

        // get accounts by clients
        $count = in_array($client_id, $clients) ? 1 : 0;
        
        // check if the account id exists in the client accounts
        if ($count > 0) {
            return true;
        } else {
            throw new Exception('Unauthorized access', 401);
        }
    }

    public static function isLinkedOrSubAccount($user, $client_id)
    {
        // these permissions have access to all accounts, no need to check
        if ($user->hasRole(["operations manager", "finance manager", "super admin", "finance", "operations","basic admin", 'audit'])) {
            return true;
        }

        // Check in client_users.
        $client_ids = ClientUser::where('user_id', $user->id)->pluck('client_id')->toArray();

        if (in_array($client_id, $client_ids)) {
            return true;
        } else {
            throw new Exception('Unauthorized access', 401);
        }
    }

    public static function isEntityBelongsToUser($user, $user_id)
    {
        // these permissions have access to all accounts, no need to check
        if ($user->hasRole(["operations manager", "finance manager", "super admin", "finance", "operations"])) {
            return true;
        }

        if ($user->id == $user_id) {
            return true;
        } else {
            throw new Exception('Unauthorized access', 401);
        }
    }

    public static function isCardBelongsToUser($user, $client)
    {
        // these permissions have access to all accounts, no need to check
        if ($user->hasRole(["operations manager", "finance manager", "super admin", "finance", "operations"])) {
            return true;
        }

        $userIds = $client->users->pluck('user_id')->toArray();

        if (in_array($user->id, $userIds)) {
            return true;
        } else {
            throw new Exception('Unauthorized access', 401);
        }
    }
}
