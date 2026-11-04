<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventDuplicateSubmission
{
    public function handle(Request $request, Closure $next)
    {
        //session()->forget('form_submitted');
        if ($request->session()->has('form_submitted')) {
            return redirect()->back()->with('error', 'Form already submitted. Please wait.');
        }

        // Set session flag after form submission
        $request->session()->put('form_submitted', true);

        return $next($request);
    }
}
