<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $fallback = PlatformSetting::get('default_locale', config('app.fallback_locale', 'ar'));
        } catch (\Throwable) {
            $fallback = config('app.fallback_locale', 'ar');
        }

        $locale = $fallback;

        if (auth()->check()) {
            $user = auth()->user();
            $locale = $user->locale ?? $user->company->default_locale ?? $fallback;
        } else {
            $locale = session('locale', $fallback);
        }

        if (! in_array($locale, ['ar', 'en'])) {
            $locale = 'ar';
        }

        app()->setLocale($locale);
        view()->share('dir', $locale === 'ar' ? 'rtl' : 'ltr');

        return $next($request);
    }
}
