<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $userRole = strtolower(
            trim((string) $user->role)
        );

        $allowedRoles = array_map(
            static fn (string $role): string =>
                strtolower(trim($role)),
            $roles
        );

        if (! in_array(
            $userRole,
            $allowedRoles,
            true
        )) {
            return $this->redirectToCorrectDashboard(
                $request
            );
        }

        return $next($request);
    }

    private function redirectToCorrectDashboard(
        Request $request
    ): Response {
        $role = strtolower(
            trim((string) $request->user()->role)
        );

        return match ($role) {
            'admin' =>
                redirect()->route('dashboard'),

            'owner',
            'investor' =>
                redirect()->route(
                    'investor.dashboard'
                ),

            'owner2',
            'investor2' =>
                redirect()->route(
                    'investor2.dashboard'
                ),

            default =>
                abort(
                    403,
                    'Unauthorized account role.'
                ),
        };
    }
}