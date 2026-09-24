<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status === 'blocked') {
            return response()->json([
                'success' => false,
                'is_blocked' => true,
                'message' => 'আপনার অ্যাকাউন্টটি সাময়িকভাবে বন্ধ রাখা হয়েছে।',
                'reason' => $user->block_reason ?? 'কোনো কারণ উল্লেখ করা হয়নি।'
            ], 403);
        }

        return $next($request);
    }
}
