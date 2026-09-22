<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplicationService
{
    public function handle(
        Request $request,
        Closure $next,
        string $service
    ): Response {

        $application =
            $request->attributes->get(
                'application_access'
            );


        if (!$application) {

            return response()->json([
                'status' => false,
                'message' =>
                    'Application authentication required',
            ], 401);
        }


        $allowed = $application
            ->services()
            ->where('service', $service)
            ->exists();


        if (!$allowed) {

            return response()->json([
                'status' => false,
                'message' =>
                    'This service is not authorized for this application',
            ], 403);
        }


        return $next($request);
    }
}