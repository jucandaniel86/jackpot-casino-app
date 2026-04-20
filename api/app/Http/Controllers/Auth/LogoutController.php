<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Spatie\Activitylog\Facades\Activity;

class LogoutController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // Get user who requested the logout
        $user = $request->user(); //or Auth::user()
        if(!$user) {
            throw new AuthenticationException();
        }
        // Revoke current user token
        $user->tokens()->delete();

	    activity()
		    ->causedBy($user)
		    ->log("Logout");

        return true;
    }
}