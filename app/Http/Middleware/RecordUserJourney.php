<?php

namespace App\Http\Middleware;

use App\Helpers\UserJourneyRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecordUserJourney
{
    public function handle(Request $request, Closure $next)
    {
        if (UserJourneyRecorder::isEnabled()
            && Auth::check()
            && $request->isMethod('GET')
            && !UserJourneyRecorder::shouldSkip($request)
        ) {
            $user = Auth::user();
            $channel = 'portal';
            if ($user && method_exists($user, 'hasRole')) {
                $channel = $user->hasRole(['client', 'draft client', 'basic client']) ? 'portal_client' : 'portal_admin';
            }
            UserJourneyRecorder::record($request, [
                'channel' => $channel,
                'event_name' => 'page_view',
            ]);
        }

        return $next($request);
    }
}
