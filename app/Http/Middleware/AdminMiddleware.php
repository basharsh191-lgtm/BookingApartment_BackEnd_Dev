<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle(Request $request, Closure $next): Response
    {


        if (Auth::user()->user_type !== 'admin') {
        return response()->json([
            'status' => false,
            'message' =>'You are not authorized to log in as a user'
        ], 403);
    }
        return $next($request);
    }
}
