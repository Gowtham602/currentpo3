<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApplicationAccessController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Application List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $applications = ApplicationAccess::latest()->get();

        return view(
            'application_access.index',
            compact('applications')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Application Page
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return view('application_access.create');
    }


    /*
    |--------------------------------------------------------------------------
    | Create Application
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'application_name' =>
                'required|string|max:255',

            'allowed_cors_domains' =>
                'nullable|string',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Generate API Token
        |--------------------------------------------------------------------------
        |
        | API token itself is a UUID.
        | No separate UUID.
        | No hashing.
        |
        */

        $apiToken = (string) Str::uuid();


        /*
        |--------------------------------------------------------------------------
        | Save Application
        |--------------------------------------------------------------------------
        */

        $application = ApplicationAccess::create([

            'api_token' =>
                $apiToken,

            'application_name' =>
                $request->application_name,

            'allowed_cors_domains' =>
                $request->allowed_cors_domains,

            'status' =>
                true,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Show API Token
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('application-access.index')
            ->with(
                'success',
                'Application created successfully.'
            )
            ->with(
                'api_credentials',
                [
                    'application_name' =>
                        $application->application_name,

                    'api_token' =>
                        $application->api_token,
                ]
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Regenerate API Token
    |--------------------------------------------------------------------------
    */

    public function regenerateToken($id)
    {
        $application =
            ApplicationAccess::findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | Generate New API Token
        |--------------------------------------------------------------------------
        */

        $apiToken =
            (string) Str::uuid();


        /*
        |--------------------------------------------------------------------------
        | Save New API Token
        |--------------------------------------------------------------------------
        */

        $application->api_token =
            $apiToken;

        $application->save();


        /*
        |--------------------------------------------------------------------------
        | Show New API Token
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('application-access.index')
            ->with(
                'success',
                'API token regenerated successfully.'
            )
            ->with(
                'api_credentials',
                [
                    'application_name' =>
                        $application->application_name,

                    'api_token' =>
                        $application->api_token,
                ]
            );
    }
}