<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Models\ApiClient;
use Laravel\Passport\ClientRepository;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiClientController extends Controller
{
    public function index()
    {
        // $apiClients = ApiClient::all();
        $apiClients = DB::table('oauth_clients')
            ->leftJoin('users as user', 'oauth_clients.user_id', '=', 'user.id')
            ->select(
                'oauth_clients.id',
                'user.name',
                'user.email',
                'oauth_clients.secret',
                'oauth_clients.provider',
                'oauth_clients.redirect',
                'oauth_clients.personal_access_client',
                'oauth_clients.password_client',
                'oauth_clients.revoked',
                'oauth_clients.created_at',
                'oauth_clients.updated_at'
            )
            ->get();
        // dd($apiClients);

        $users = User::role('API')->where(function ($query) {
            $query->where('locked', 0)
                ->orWhereNull('locked');
        })
            ->get();

        return view('settings.api-clients.configurations.list', compact('apiClients', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $userId  = $request->get('user_id');
            // $user = User::findOrFail($userId);
            $user = User::find($userId);


            if (!$user->hasRole('API')) {
                return redirect('/settings/api-clients')->with('error', 'User is not a client');
            }

            $isExist = DB::table('oauth_clients')->where('user_id', $userId)->exists();

            if ($isExist) {
                return redirect('/settings/api-clients')->with('error', 'API Client key already exists');
            }



            // if (!$user) {
            //     $this->error('User not found');
            //     return;
            // }

            $clientRepository = new ClientRepository();

            $clientRepository->createPasswordGrantClient(
                $user->id,
                $user->name,
                env('APP_URL')
            );





            // $userName = $user->name;
            // $apiKey = $this->generatKey($userName);

            // $apiClient = new ApiClient();
            // $apiClient->user_id = $userId;
            // $apiClient->name = $user->name;
            // $apiClient->api_key = $apiKey;
            // $apiClient->created_by = Auth::user()->id;

            // $apiClient->save();

            return redirect('/settings/api-clients')->with('success', 'API Client created successfully');
        } catch (\Exception $e) {
            // Common::logError($e, __METHOD__, __LINE__, $request);

            Log::error($e->getMessage());
            // Log::error($e->getTrace());
            return redirect('/settings/api-clients')->with('error', 'API Client creation failed');
        }
    }

    private function generatKey($name)
    {
        $timestamp = now()->timestamp;
        $guid = Str::uuid()->toString();
        $apiKey = $timestamp . $name . $guid;
        $hashedApiKey = Hash::make($apiKey);

        return $hashedApiKey;
    }

    public function viewClientApi($id)
    {
        $apiClient = ApiClient::find($id);

        return view('settings.api-clients.configurations.view', compact('apiClient'));
    }

    public function delete(Request $request)
    {
        $apiClient = ApiClient::find($request->get('api_client_id'));

        try {
            $apiClient->delete();

            return redirect('/settings/api-clients')->with('success', 'API Client deleted successfully');
        } catch (\Exception $e) {
            return redirect('/settings/api-clients')->with('error', 'API Client deletion failed');
        }
    }

    public function regenerateApiKey(Request $request)
    {
        $apiClient = ApiClient::find($request->get('api_client_id'));

        $userId  = $apiClient->user_id;
        $user = User::find($userId);

        if (!$user) {
            return redirect('/settings/api-clients')->with('error', 'User not found');
        }

        $userName  = $user->name;
        $apiKey = $this->generatKey($userName);

        $apiClient->api_key = $apiKey;
        $apiClient->save();

        return redirect('/settings/api-clients')->with('success', 'API Key regenerated successfully');
    }

    public function toggleAccess(Request $request)
    {
        $client = DB::table('oauth_clients')->where('id', $request->client_id)->first();

        if (!$client) {
            return back()->with('error', 'API Client not found.');
        }

        $newStatus = $client->revoked == 1 ? 0 : 1;

        DB::table('oauth_clients')
            ->where('id', $client->id)
            ->update(['revoked' => $newStatus]);

        DB::table('users')
            ->where('id', $client->user_id)
            ->update(['locked' => $newStatus]);

        return back()->with('success', $newStatus ? 'API Client access revoked.' : 'API Client access reinstated.');
    }
}
