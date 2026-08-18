<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class ResolveApiLocale
{
    private const SUPPORTED = ['pt-BR', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $profileLocale = $request->user()?->locale;
        $headerLocale = $request->getPreferredLanguage(self::SUPPORTED);

        $locale = $this->normalizeLocale($profileLocale)
            ?? $this->normalizeLocale($headerLocale)
            ?? 'pt-BR';

        App::setLocale($locale);

        return $next($request);
    }

    private function normalizeLocale(?string $locale): ?string
    {
        if ($locale === null) {
            return null;
        }

        $normalized = str_replace('_', '-', $locale);

        return in_array($normalized, self::SUPPORTED, true) ? $normalized : null;
    }
}
