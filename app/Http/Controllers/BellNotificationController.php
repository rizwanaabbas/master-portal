<?php

namespace App\Http\Controllers;

use App\Models\BellNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BellNotificationController extends Controller
{
    //

    public function delete(Request $request)
    {
        BellNotification::find ($request->get('nid'))->delete();

        return true;
    }

    public function deleteAll(Request $request)
    {
        // delete all notifications for logged in user.
        BellNotification::where ('notify_user_id', Auth::user()->id)->delete();

        return true;
    }
}
