<?php

namespace App\Http\Controllers;

use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Helpers\FileFirewall;
use App\Helpers\OnboardingHelper;
use App\Helpers\S3Helper;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Currency;
use App\Models\User;
use App\Models\Unit;
use App\Rules\SupportingDocumentsRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list users', ['only' => 'index']);
        $this->middleware('permission:add user', ['only' => 'add']);
        $this->middleware('permission:update user', ['only' => 'edit']);
        $this->middleware('permission:delete user', ['only' => 'delete']);
    }

    public function index(Request $request)
    {
        $clientRoles = ['client', 'draft client', 'basic client', 'API'];

        $users = User::whereDoesntHave('roles', function ($q) use ($clientRoles) {
            // User must NOT have any role outside the allowed client roles
            $q->whereNotIn('name', $clientRoles);
        })->whereHas('roles', function ($q) use ($clientRoles) {
            // User must have at least one of the client roles
            $q->whereIn('name', $clientRoles);
        });

        $perPage = $request->get('per_page');

        if ($request->get('search_string')) {
            $users->where(function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->get('search_string') . '%')
                    ->orWhere('email', 'like', '%' . $request->get('search_string') . '%')
                    ->orWhereHas('roles', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->get('search_string') . '%');
                    });
            });
        }

        if ($perPage == -1 || ($request->get('search_string') && $users->count() < 10)) {
            $perPage = $users->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $users = $users->paginate($perPage);

        return view("user-management.list", compact('users', 'request'));
    }


    public function backOfficeUsers(Request $request)
    {
        $excludedRoles = ['client', 'draft client', 'basic client', 'API'];

        $users = User::whereHas('roles', function ($q) use ($excludedRoles) {
            // User must have at least one role NOT in excluded list
            $q->whereNotIn('name', $excludedRoles);
        });

        $perPage = $request->get('per_page');

        if ($request->get('search_string')) {
            $users->where(function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->get('search_string') . '%')
                    ->orWhere('email', 'like', '%' . $request->get('search_string') . '%')
                    ->orWhereHas('roles', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->get('search_string') . '%');
                    });
            });
        }

        if ($perPage == -1 || ($request->get('search_string') && $users->count() < 10)) {
            $perPage = $users->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $users = $users->paginate($perPage);

        return view("user-management.back-office-user-list", compact('users', 'request'));
    }

    public function add()
    {
        $roles = Role::all();
        $units = Unit::all();
        $currencies = Currency::select('id', 'short')->where('is_active', true)->get();
        $managers = User::whereHas("roles", function ($q) {
            $q->whereIn("name", ['finance manager', 'operations manager']);
        })->select('name', 'id')->get();

        return view("user-management.new", compact('roles', 'managers', 'units', 'currencies'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|unique:users,code',
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed',
            'roles' => 'required',
            'profile_photo' => [new SupportingDocumentsRule]
        ]);

        $user = new User();
        if ($request->has('unit_id'))
            $user->unit_id = $request->get('unit_id');

        $user->code = $request->get('code');

        $user->name = $request->get('name');
        $user->email = $request->get('email');
        $user->is_selfie_required = $request->get('is_selfie_required') ?? 0;
        $user->is_initial_review_required = $request->get('is_initial_review_required') ?? 0;
        $user->is_internal_transfer_approval_required = $request->get('is_internal_transfer_approval_required') ?? 0;
        $user->is_mobile_otp_required = $request->get('is_mobile_otp_required') ?? 0;
        $user->allowed_currencies = $request->get('allowed_currencies');

        $user->password = bcrypt($request->get('password'));

        if ($request->hasFile('profile_photo')) {

            // validate file
            if (!FileFirewall::isAllowedType($request->file('profile_photo'))) {
                return FileFirewall::logBadFileException($request->file('profile_photo'));
            }

            $user->profile_photo_url = S3Helper::fileUpload($request->file('profile_photo'), 'profile-photos');
        }

        // assign line manager if selected
        if ($request->get("manager_id")) {
            $user->manager_id = $request->get("manager_id");
        }

        $user->save();

        // attach roles
        $user->assignRole(array_values($request->get('roles')));

        return redirect('/user-management/users')->with('success', 'User added successfully');
    }

    public function edit($id)
    {
        $user = User::find($id);
        $user->allowed_currencies = $user->allowed_currencies ?? [];
        $currencies = Currency::select('id', 'short')->where('is_active', true)->get();
        $roles = Role::all();
        $units = Unit::all();
        $managers = User::whereHas("roles", function ($q) {
            $q->whereIn("name", ['finance manager', 'operations manager']);
        })
            ->where('id', '!=', $user->id)
            ->select('name', 'id')->get();

        return view("user-management.edit", compact('user', 'roles', 'managers', 'units', 'currencies'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
            'name' => 'required',
            'roles' => 'required',
            'profile_photo' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png']
        ]);

        // check email if not existing for other users
        $isExists = User::where('email', $request->get('email'))->where('id', '!=', $request->get('id'))->first();

        if (!$isExists) {

            $user = User::find($request->get('id'));

            if ($request->has('unit_id'))
                $user->unit_id = $request->get('unit_id');

            $user->code = $request->get('code');

            $user->name = $request->get('name');
            $user->email = $request->get('email');
            $user->is_selfie_required = $request->get('is_selfie_required') ?? 0;
            $user->is_initial_review_required = $request->get('is_initial_review_required') ?? 0;
            $user->is_internal_transfer_approval_required = $request->get('is_internal_transfer_approval_required') ?? 0;
            $user->is_mobile_otp_required = $request->get('is_mobile_otp_required') ?? 0;
            $user->allowed_currencies = $request->get('allowed_currencies');

            // check if new file is uploaded
            if ($request->hasFile('profile_photo')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('profile_photo'))) {
                    return FileFirewall::logBadFileException($request->file('profile_photo'));
                }

                if ($user->profile_photo_url) {
                    S3Helper::fileDelete($user->profile_photo_url);
                }

                $user->profile_photo_url = S3Helper::fileUpload($request->file('profile_photo'), 'profile-photos');
            }

            // assign line manager if selected
            if ($request->get("manager_id")) {
                $user->manager_id = $request->get("manager_id");
            }

            $user->save();

            // detach old roles and attach new roles
            $user->roles()->detach();
            $user->assignRole(array_values($request->get('roles')));

            if (!Common::canUserMakeChange()) {
                return redirect()->back()->with('success', 'Your changes has been submitted for approval, you will be notified accordingly.');
            } else {
                return redirect()->back()->with('success', 'User updated successfully');
            }
        } else {
            return redirect()->back()->with('error', 'Email address already taken by another user');
        }
    }

    public function delete(Request $request)
    {
        $user = User::find($request->get('user_id'));
        $client = Client::where('created_by', $request->get('user_id'))->first();

        // check if user is connected to any client
        if (ClientUser::where("user_id", $user->id)->count() > 0 && $client != null) {
            return redirect(url()->previous())->with('error', "User can't be deleted, there is a client account attached to the user.");
        }

        if ($user->profile_photo_url != "") {
            S3Helper::fileDelete($user->profile_photo_url);
        }

        $user->roles()->detach();
        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully');
    }

    public function changeCredentials(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|confirmed',
        ]);

        $user = User::find($request->get('id'));

        $user->password = bcrypt($request->get('password'));

        $user->save();

        return redirect("user-management/users/edit/" . $user->id)->with('success', 'User credentaisl updated successfully');
    }

    public function profile()
    {
        $user = User::find(Auth::user()->id);

        return view("user-management.profile", compact('user'));
    }

    public function profileUpdate(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);

        $user = User::find(Auth::user()->id);

        $user->name = $request->get('name');

        // check if new file is uploaded
        if ($request->hasFile('profile_photo')) {

            $this->validate($request, [
                'profile_photo' => ['required', new SupportingDocumentsRule, 'mimes:jpeg,jpg,png']
            ]);

            // validate file
            if (!FileFirewall::isAllowedType($request->file('profile_photo'))) {
                return FileFirewall::logBadFileException($request->file('profile_photo'));
            }

            if ($user->profile_photo_url) {
                S3Helper::fileDelete($user->profile_photo_url);
            }

            $user->profile_photo_url = S3Helper::fileUpload($request->file('profile_photo'), 'profile-photos');
        }

        $user->save();

        if (!Common::canUserMakeChange()) {
            return redirect('/profile')->with('success', 'Your changes has been submitted for approval, you will be notified accordingly.');
        } else {
            return redirect('/profile')->with('success', 'Profile details updated successfully');
        }
    }

    public function changePassword()
    {
        return view("user-management.change-password");
    }

    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'current_password' => 'required',
            'new_password' => 'required|confirmed'
        ]);

        if (!Hash::check($request->get('current_password'), Auth::user()->getAuthPassword())) {

            return redirect('/change-password')->with('error', 'Please provide a valid current password.');
        }

        $user = Auth::user();

        $user->password = bcrypt($request->get('new_password'));
        $user->save();

        return redirect('/change-password')->with('success', 'Password changed successfully.');
    }

    public function lockuserAccount(Request $request)
    {
        $user = User::find($request->get("id"));

        $user->locked = true;
        $user->save();

        return redirect('/user-management/users/edit/' . $user->id)->with('success', 'User account has been locked successfully.');
    }

    public function unLockuserAccount(Request $request)
    {
        $user = User::find($request->get("id"));

        $user->locked = false;
        $user->save();

        return redirect('/user-management/users/edit/' . $user->id)->with('success', 'User account has been un-locked successfully.');
    }

    public function generatePassword(Request $request)
    {
        $user = User::find($request->get("id"));

        $password = str::random(8) . "@" . rand(100, 500);

        $user->password = bcrypt($password);
        $user->save();

        // send new password to user
        EmailNotification::notifyNewPassword($user, $password);

        return redirect('/user-management/users/edit/' . $user->id)->with('success', 'New Password has been generated and sent to user on his registered email address');
    }

    public function verifyUser(Request $request)
    {
        $user = User::find($request->get("id"));

        $user->email_verified_at = Carbon::now();
        $user->save();

        return redirect('/user-management/users/edit/' . $user->id)->with('success', 'User has been verified successfully');
    }

    public function impersonate($id)
    {
        $user = User::find($id);

        if ($user->locked) {
            return redirect('/user-management/users')->with('error', 'This user account is locked. Impersonation is not allowed.');
        }

        if (
            !$user->hasRole(["client", "draft client", "basic client"]) ||
            $user->roles->count() > 1 || !env('HAS_REACT_CLIENT_PORTAL_IN_REACT')
        ) {
            // Imperonate user, which is not client.
            Session::put("impersonator", Auth::user()->id);

            Auth::loginUsingId($id, true);

            return redirect("/");
        } else {
            $impersonator = Auth::user()->id;
            $key = (string) Str::uuid();

            Session::put(["_impersonate" => ['key' => $key, 'impersonator' => $impersonator, 'impersonated' => $id]]);

            Auth::loginUsingId($id, true);
            // redirect home
            return redirect(url(env("REACT_APP_BASE_URL") . '/signin?key=' . $key . '&impersonator=' . $impersonator . '&impersonated=' . $id));
        }
    }

    public function switchUserFromNewPortal(Request $request)
    {

        try {
            $this->validate($request, [
                'key' => 'required|uuid',
                'impersonator' => 'required|exists:users,id',
                'impersonated' => 'required|exists:users,id'
            ]);
            $session = DB::table('sessions')->where('user_id', $request->get('impersonated'))->first();

            if ($session) {
                $data = unserialize(base64_decode($session->payload, true));


                if ($data['_impersonate']['key'] != $request->get('key')) {
                    Auth::logout();
                    return redirect("/login")->with("error", "unauthorized access");
                }

                if (
                    $data['_impersonate'] &&
                    $data['_impersonate']['impersonator'] == $request->get('impersonator') &&
                    $data['_impersonate']['impersonated'] == $request->get('impersonated')
                ) {
                    $session = Session::get("_impersonate");
                    Session::remove("_impersonate");

                    // login previous user
                    Auth::loginUsingId($session["impersonator"]);

                    // redirect user management, users list
                    return redirect("/user-management/users");
                } else {
                    Auth::logout();
                    return redirect("/login")->with("error", "unauthorized access");
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            Auth::logout();
            return redirect("/login")->with("error", "unauthorized access");
        }
    }

    public function switchUser()
    {
        if (Session::has("impersonator")) {
            $prev_user_id = Session::get("impersonator");

            // logout current switched user
            Auth::logout();
            Session::remove("impersonator");

            // login previous user
            Auth::loginUsingId($prev_user_id);

            // redirect home
            return redirect("/user-management/users");
        }
    }

    public function userClients(Request $request)
    {
        $user_clients = ClientUser::query();
        $clients = Client::where('status', 'Approved')->get();
        $users = User::all();
        $perPage = $request->get('per_page');

        if ($request->get('search_string')) {
            $user_clients->where(function ($query) use ($request) {
                $query->whereHas(
                    'user',
                    function ($query) use ($request) {
                        $query->where('email', 'like', '%' . $request->get('search_string') . '%')
                            ->orWhereHas('roles', function ($query) use ($request) {
                                $query->where('name', 'like', '%' . $request->get('search_string') . '%');
                            });
                    }
                )->orWhereHas(
                    'client',
                    function ($query) use ($request) {
                        $query->where(
                            'name',
                            'like',
                            '%' . $request->get('search_string') . '%'
                        )
                            ->where(
                                'status',
                                '!=',
                                'AAA'
                            );
                    }
                );
            });
        }

        if (
            $perPage == -1 || ($request->get('search_string') && $user_clients->count() < 10)
        ) {
            $perPage = $user_clients->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $user_clients = $user_clients->paginate($perPage);

        return view("user-management.link-user-to-client", compact('user_clients', 'clients', 'users', 'request'));
    }

    public function linkUserStore(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required',
            'client_id' => 'required'
        ]);

        $client = Client::find($request->get("client_id"));
        $response = ClientHelper::linkUserToClient($client, 0, $request->get("user_id"));

        return redirect("/user-management/link-user")->with($response["type"], $response["message"]);
    }

    public function linkUserDelete(Request $request)
    {
        // delete user link
        ClientUser::find($request->get("cu_id"))->delete();

        return redirect("/user-management/link-user")->with("success", "User link deleted successfully");
    }
}
