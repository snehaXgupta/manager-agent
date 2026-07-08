<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\DeveloperToken;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateDeveloperToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization');

        if (!$authorization || !str_starts_with($authorization, 'Bearer ')) {
            return response()->json([
                'error' => 'Unauthorized. Please provide a Bearer token in the Authorization header.'
            ], 401);
        }

        $tokenStr = substr($authorization, 7);
        $tokenHash = hash('sha256', $tokenStr);

        $developerToken = DeveloperToken::with('user')->where('token_hash', $tokenHash)->first();

        if (!$developerToken) {
            return response()->json([
                'error' => 'Unauthorized. Invalid API key.'
            ], 401);
        }

        $user = $developerToken->user;

        // Log in the user so auth() helpers work in controllers
        auth()->login($user);

        // Security check: If route has an 'id' parameter representing a user
        $id = $request->route('id');
        if ($id) {
            if ($user->role !== 'admin' && (int)$user->id !== (int)$id) {
                if ($request->is('api/managers/*')) {
                    return response()->json([
                        'error' => 'Forbidden. You do not have permission to access resources for this manager.'
                    ], 403);
                }

                if ($request->is('api/employees/*')) {
                    $targetUser = User::find($id);
                    if (!$targetUser || ((int)$targetUser->manager_id !== (int)$user->id && (int)$targetUser->id !== (int)$user->id)) {
                        return response()->json([
                            'error' => 'Forbidden. You do not have permission to access resources for this employee.'
                        ], 403);
                    }
                }
            }
        }

        // Security check for Timer Controller endpoints:
        // /api/timer/start and /api/timer/stop pass user_id in the request body.
        if ($request->has('user_id') && ($request->is('api/timer/start') || $request->is('api/timer/stop'))) {
            $targetUserId = (int) $request->input('user_id');
            if ($user->role !== 'admin' && (int)$user->id !== $targetUserId) {
                // Check if the target user reports to this manager
                $targetUser = User::find($targetUserId);
                if (!$targetUser || (int)$targetUser->manager_id !== (int)$user->id) {
                    return response()->json([
                        'error' => 'Forbidden. You do not have permission to control timers for this user.'
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
