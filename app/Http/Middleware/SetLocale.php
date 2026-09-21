<?php

namespace App\Http\Middleware;

use App\Domains\Accounts\Models\CompanySetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs the request in the caller's language, so validation messages and API
 * errors match the interface. The user's own preference wins; "default" (or no
 * preference) defers to the active company's language, then to app.locale.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        if ($locale !== null && $this->isAvailable($locale)) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    private function resolve(Request $request): ?string
    {
        $user = $request->user();

        if ($user !== null && method_exists($user, 'getSettings')) {
            $language = $user->getSettings(['language'])->get('language');

            if ($language && $language !== 'default') {
                return $language;
            }
        }

        $companyId = $request->header('company') ?? $user?->company_id;

        if ($companyId) {
            return CompanySetting::getSetting('language', $companyId);
        }

        return null;
    }

    private function isAvailable(string $locale): bool
    {
        return $locale === 'en' || is_file(lang_path($locale.'.json'));
    }
}
