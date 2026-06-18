<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->company && ! $user->company->onboarding_completed) {
            if (! $request->routeIs('onboarding.index')) {
                return redirect()->route('onboarding.index');
            }
        }

        return $next($request);
    }
}
