<?php

namespace App\Http\Middleware;

use App\Models\ApplicationAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplicationApiAuth
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        /*
        |--------------------------------------------------------------------------
        | 1. Get applicationid from header
        |--------------------------------------------------------------------------
        |
        | Header:
        | applicationid: YOUR_API_TOKEN
        |
        */

        $applicationId = $request->header('applicationid');

        if (!$applicationId) {

            return response()->json([
                'status' => false,
                'message' => 'applicationid is required',
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | 2. Find Application
        |--------------------------------------------------------------------------
        */

        $application = ApplicationAccess::where(
            'api_token',
            $applicationId
        )->first();

        

        if (!$application) {

            return response()->json([
                'status' => false,
                'message' => 'Invalid applicationid',
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Check Application Status
        |--------------------------------------------------------------------------
        */

        if (!$application->status) {

            return response()->json([
                'status' => false,
                'message' => 'Application access is disabled',
            ], 403);
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Check Allowed CORS Domain
        |--------------------------------------------------------------------------
        */

        $origin = $request->header('Origin');

        if ($origin) {

            $allowedDomains = preg_split(
                '/[\r\n,]+/',
                $application->allowed_cors_domains ?? ''
            );

            $allowedDomains = array_map(
                function ($domain) {
                    return rtrim(
                        trim($domain),
                        '/'
                    );
                },
                $allowedDomains
            );

            $allowedDomains = array_filter(
                $allowedDomains
            );


            $origin = rtrim(
                trim($origin),
                '/'
            );


            /*
            |--------------------------------------------------------------------------
            | Origin not allowed
            |--------------------------------------------------------------------------
            */

            if (!in_array(
                $origin,
                $allowedDomains,
                true
            )) {

                return response()->json([
                    'status' => false,
                    'message' =>
                        'This domain is not allowed for this application',
                ], 403);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 5. Store authenticated application
        |--------------------------------------------------------------------------
        */

        $request->attributes->set(
            'application_access',
            $application
        );


        /*
        |--------------------------------------------------------------------------
        | 6. Continue request
        |--------------------------------------------------------------------------
        */

        $response = $next($request);


        /*
        |--------------------------------------------------------------------------
        | 7. Add CORS response headers
        |--------------------------------------------------------------------------
        */

        if ($origin) {

            $response->headers->set(
                'Access-Control-Allow-Origin',
                $origin
            );

            $response->headers->set(
                'Access-Control-Allow-Headers',
                'applicationid, Content-Type, Accept'
            );

            $response->headers->set(
                'Access-Control-Allow-Methods',
                'GET, POST, PUT, OPTIONS'
            );

            $response->headers->set(
                'Vary',
                'Origin'
            );
        }


        return $response;
    }
}