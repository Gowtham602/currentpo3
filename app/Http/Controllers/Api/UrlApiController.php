<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
        
class UrlApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Get Districts
    |--------------------------------------------------------------------------
    */




    
    public function index(Request $request)
{
    $application = $request->attributes->get(
        'application_access'
    );

    if (!$application) {

        return response()->json([
            'status' => false,
            'message' =>
                'Application authentication failed',
        ], 401);
    }


    $images = Image::with('district')
        ->where(
            'application_access_id',
            $application->id
        )
        ->orderByDesc('id')
        ->get();


    return response()->json([
        'status' => true,

        'message' =>
            'Webhook URLs fetched successfully',

        'application' => [
            'id' =>
                $application->id,

            'application_name' =>
                $application->application_name,
        ],

        'data' => $images->map(
            function ($image) {

                return [

                    'id' =>
                        $image->id,

                    'original_url' =>
                        $image->original_url,

                    'district_id' =>
                        $image->district_id,

                    'district_name' =>
                        $image->district?->district_name,

                    'district_shortcode' =>
                        $image->district?->district_shortcode,

                    'short_code' =>
                        $image->short_code,

                    'short_url' =>
                        url($image->short_code),

                    'click_count' =>
                        $image->click_count,

                    'created_at' =>
                        $image->created_at,

                    'updated_at' =>
                        $image->updated_at,
                ];
            }
        ),
    ]);
}
    public function districts()
    {
        $districts = District::where('status', 1)
            ->orderBy('district_name')
            ->get([
                'id',
                'district_name',
                'district_shortcode',
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Districts fetched successfully',
            'data' => $districts,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Original URL
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Get Authenticated Application
        |--------------------------------------------------------------------------
        */

        $application = $request->attributes->get(
            'application_access'
        );

        if (!$application) {
            return response()->json([
                'status' => false,
                'message' => 'Application authentication failed',
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make(
            $request->all(),
            [
                'original_url' => 'required|url|max:2048',

                'district_id' =>
                    'required|exists:districts,id',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Check District
        |--------------------------------------------------------------------------
        */

        $district = District::where('status', 1)
            ->find($request->district_id);

        if (!$district) {
            return response()->json([
                'status' => false,
                'message' => 'District is inactive or not found',
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | District Shortcode
        |--------------------------------------------------------------------------
        */

        $districtCode = strtoupper(
            $district->district_shortcode
        );


        /*
        |--------------------------------------------------------------------------
        | Generate Short Code
        |--------------------------------------------------------------------------
        |
        | Example:
        | AB1CHE
        |
        */

        do {
            $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

            $shortCode =
                $letters[random_int(0, 25)] .
                $letters[random_int(0, 25)] .
                random_int(0, 9) .
                $districtCode;

        } while (
            Image::where(
                'short_code',
                $shortCode
            )->exists()
        );


        /*
        |--------------------------------------------------------------------------
        | Create Image / URL Record
        |--------------------------------------------------------------------------
        |
        | API record:
        |
        | user_id = NULL
        | application_access_id = Application ID
        |
        */

        $image = Image::create([
            'user_id' => NULL,

            'application_access_id' =>
                $application->id,

            'district_id' =>
                $district->id,

            'image_name' => null,

            'file_path' => null,

            'original_url' =>
                $request->original_url,

            'short_code' =>
                $shortCode,

            'click_count' => 0,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => true,

            'message' =>
                'Original URL created successfully',

            'data' => [
                'id' =>
                    $image->id,

                'application_access_id' =>
                    $application->id,

                'application_name' =>
                    $application->application_name,

                'original_url' =>
                    $image->original_url,

                'district_id' =>
                    $district->id,

                'district_name' =>
                    $district->district_name,

                'district_shortcode' =>
                    $district->district_shortcode,

                'short_code' =>
                    $image->short_code,

                'short_url' =>
                    url($image->short_code),
            ],
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Original URL
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $short_code
    ) {
        /*
        |--------------------------------------------------------------------------
        | Get Authenticated Application
        |--------------------------------------------------------------------------
        */

        $application = $request->attributes->get(
            'application_access'
        );

        if (!$application) {
            return response()->json([
                'status' => false,
                'message' => 'Application authentication failed',
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make(
            $request->all(),
            [
                'original_url' =>
                    'required|url|max:2048',

                'district_id' =>
                    'required|exists:districts,id',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Find URL Belonging To This Application
        |--------------------------------------------------------------------------
        |
        | Application A cannot edit Application B's URL.
        |
        */

        $image = Image::where(
            'short_code',
            $short_code
        )
        ->where(
            'application_access_id',
            $application->id
        )
        ->first();


        if (!$image) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Short URL not found for this application',
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Check District
        |--------------------------------------------------------------------------
        */

        $district = District::where('status', 1)
            ->find($request->district_id);

        if (!$district) {
            return response()->json([
                'status' => false,
                'message' =>
                    'District is inactive or not found',
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Update URL
        |--------------------------------------------------------------------------
        */

        $image->update([
            'original_url' =>
                $request->original_url,

            'district_id' =>
                $district->id,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => true,

            'message' =>
                'Original URL updated successfully',

            'data' => [
                'id' =>
                    $image->id,

                'application_access_id' =>
                    $application->id,

                'application_name' =>
                    $application->application_name,

                'original_url' =>
                    $image->original_url,

                'district_id' =>
                    $district->id,

                'district_name' =>
                    $district->district_name,

                'district_shortcode' =>
                    $district->district_shortcode,

                'short_code' =>
                    $image->short_code,

                'short_url' =>
                    url($image->short_code),
            ],
        ]);
    }
}