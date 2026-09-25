<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uses the authenticated user's language, falling back to the app default.
 * The frontend reads the same value from <html lang> (resources/js/i18n).
 */
class SetLocale
{
    public const array SUPPORTED = ['es', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if (is_string($locale) && in_array($locale, self::SUPPORTED, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
