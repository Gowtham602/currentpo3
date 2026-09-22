<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplicationCors
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $application =
            $request->attributes->get(
                'application_access'
            );


        if (!$application) {
            return $next($request);
        }


        $origin = $request->header('Origin');


        $allowedDomains = collect(
            explode(
                ',',
                $application->allowed_cors_domains ?? ''
            )
        )
        ->map(fn ($domain) => trim($domain))
        ->filter();


        /*
        |--------------------------------------------------------------------------
        | Handle OPTIONS / Preflight
        |--------------------------------------------------------------------------
        */

        if ($request->isMethod('OPTIONS')) {

            if (
                $origin &&
                $allowedDomains->contains($origin)
            ) {

                return response('', 204)
                    ->header(
                        'Access-Control-Allow-Origin',
                        $origin
                    )
                    ->header(
                        'Access-Control-Allow-Methods',
                        'GET, POST, PUT, DELETE, OPTIONS'
                    )
                    ->header(
                        'Access-Control-Allow-Headers',
                        'Authorization, Content-Type, Accept, X-Application-ID'
                    )
                    ->header(
                        'Access-Control-Allow-Credentials',
                        'true'
                    );
            }


            return response('', 403);
        }


        $response = $next($request);


        if (
            $origin &&
            $allowedDomains->contains($origin)
        ) {

            $response->headers->set(
                'Access-Control-Allow-Origin',
                $origin
            );

            $response->headers->set(
                'Access-Control-Allow-Credentials',
                'true'
            );
        }


        return $response;
    }
}