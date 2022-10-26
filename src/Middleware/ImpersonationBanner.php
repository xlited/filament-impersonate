<?php

namespace XliteDev\FilamentImpersonate\Middleware;

use Closure;
use Illuminate\Http\Response;
use Lab404\Impersonate\Services\ImpersonateManager;

class ImpersonationBanner
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Only touch illuminate responses (avoid binary, etc)
        if (! $response instanceof Response || ! app(ImpersonateManager::class)->isImpersonating()) {
            return $response;
        }

        return $response->setContent(
            str_replace(
                '</body>',
                $this->bannerHtml($request).'</body>',
                $response->getContent()
            )
        );
    }

    protected function bannerHtml($request)
    {
        return view('filament-impersonate::filament-impersonate-banner')->render();
    }
}
