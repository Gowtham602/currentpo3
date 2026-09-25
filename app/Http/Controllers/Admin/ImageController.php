<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Image;
// use App\Models\ImageClick;
use App\Models\ImageClick;
use App\Models\District;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImageController extends Controller
{
//     public function index()
//     {
// // dd(Carbon::today());
//         // $images = Image::where('user_id', Auth::id())
//         //     ->latest()
//         //     ->get();
//         $images = Image::withCount([
//             'clicks',
//             'clicks as today_clicks' => function ($query) {
//                 $query->whereDate('created_at', Carbon::today());
//             }
//         ])->latest()->get();
//         // dd($images);

//         // for district dropdown
//         $districts = District::where('status', 1)

//             ->orderBy('district_name')
//             ->get();
//         //   dd($districts);
//         return view('dashboard', compact('images', 'districts'));
//     }


public function index()
{
    $images = Image::where('user_id', Auth::id())
        ->whereNotNull('image_name')
        ->with('district')
        ->withCount([
            'clicks',
            'clicks as today_clicks' => function ($query) {
                $query->whereDate('created_at', Carbon::today());
            }
        ])
        ->latest()
        ->get();

    // For district dropdown
    $districts = District::where('status', 1)
        ->orderBy('district_name')
        ->get();

    return view('dashboard', compact('images', 'districts'));
}

    public function getImages(Request $request)
    {
        $query = Image::where('user_id', Auth::id());

        // Search
        if ($request->search['value']) {
            $search = $request->search['value'];
            $query->where('image_name', 'like', "%{$search}%");
        }

        $total = $query->count();

        // Pagination
        $images = $query->latest()
            ->skip($request->start)
            ->take($request->length)
            ->get();

        $data = [];

        foreach ($images as $index => $img) {
            $fullUrl = url('/' . $img->short_code);

            $imageUrl = asset('storage/' . $img->file_path);

            $data[] = [
                $request->start + $index + 1,

                "<a href='{$imageUrl}' target='_blank'>
                {$img->image_name}.jpeg
            </a>",

                "<div class='d-flex gap-2'>
                <a href='{$fullUrl}' target='_blank'>{$fullUrl}</a>
                <button onclick=\"copyLink('{$fullUrl}')\" class='btn btn-sm btn-light'> 
                    
                </button>
            </div>",

                $img->click_count,

                $img->created_at->format('d M Y h:i A')
            ];
        }

        return response()->json([
            "draw" => intval($request->draw),
            "recordsTotal" => $total,
            "recordsFiltered" => $total,
            "data" => $data
        ]);
    }
    public function mobile()
    {
        $imageClicks = ImageClick::join('images', 'image_clicks.image_id', '=', 'images.id')
            ->where('images.user_id', Auth::id())
            ->select(
                'image_clicks.*',
                'images.image_name',
                'images.file_path'
            )
            ->latest()
            ->get();
        // dd($imageClicks);
        return view('mobile', compact('imageClicks'));
    }

    public function store(Request $request)
    {
        dd("hi");
        $request->validate([
            'image_name' => 'required|max:255',
            'image' => 'required|image|mimes:jpeg,jpg|max:2048'
        ]);

        $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->image_name);
        $fileName = $cleanName . '.jpeg';

        if (Storage::disk('public')->exists('images/' . $fileName)) {
            return back()->with('error', 'File name already exists!');
        }

        $path = $request->file('image')
            ->storeAs('images', $fileName, 'public');

        do {
            $shortCode = Str::random(6);
        } while (Image::where('short_code', $shortCode)->exists());

        Image::create([
            'user_id' => Auth::id(),
            'image_name' => $cleanName,
            'file_path' => $path,
            'short_code' => $shortCode
        ]);

        return back()->with('success', 'Image uploaded successfully!');
    }
    // merge and create short url



    //  today 27.8.26
//     public function redirect($code)
// {
//     /*
//     |--------------------------------------------------------------------------
//     | Find Short Code in images table
//     |--------------------------------------------------------------------------
//     */

//     $image = Image::where('short_code', $code)->first();

//     if (!$image) {

//         /*
//         |--------------------------------------------------------------------------
//         | Check old upload_image table
//         |--------------------------------------------------------------------------
//         */

//         $searchUrl = "https://po3.in/" . $code;

//         $uploadImage = DB::connection('images')
//             ->table('upload_image')
//             ->where('bit_url', $searchUrl)
//             ->first();

//         if ($uploadImage) {

//             return redirect(
//                 "https://pothysadv.in/pothys-imgupload-api/images/"
//                 . $uploadImage->image_name
//             );
//         }

//         abort(404);
//     }

//     /*
//     |--------------------------------------------------------------------------
//     | Increase Click Count
//     |--------------------------------------------------------------------------
//     */

//     $image->increment('click_count');


//     /*
//     |--------------------------------------------------------------------------
//     | Device Information
//     |--------------------------------------------------------------------------
//     */

//     $agent = new Agent();

//     $device = $agent->isMobile()
//         ? 'Mobile'
//         : ($agent->isTablet()
//             ? 'Tablet'
//             : 'Desktop');


//     /*
//     |--------------------------------------------------------------------------
//     | Browser & IP
//     |--------------------------------------------------------------------------
//     */

//     $browser = $agent->browser();

//     $ip = request()->ip();


//     /*
//     |--------------------------------------------------------------------------
//     | Location
//     |--------------------------------------------------------------------------
//     */

//     $location = Location::get($ip);
    

//     $country = $location
//         ? $location->countryName
//         : 'Unknown';

//     $city = $location?->cityName ?? 'Unknown';


//     /*
//     |--------------------------------------------------------------------------
//     | Save Click Analytics
//     |--------------------------------------------------------------------------
//     */

//     ImageClick::create([
//         'image_id'    => $image->id,
//         'ip_address'  => $ip,
//         'browser'     => $browser,
//         'device_type' => $device,
//         'country'     => $country,
//         'city'        => $city,
//     ]);


//     /*
//     |--------------------------------------------------------------------------
//     | SHORT URL → EXTERNAL URL
//     |--------------------------------------------------------------------------
//     */

//     if (!empty($image->original_url)) {

//         return redirect()->away(
//             $image->original_url
//         );
//     }


//     /*
//     |--------------------------------------------------------------------------
//     | SHORT URL → IMAGE
//     |--------------------------------------------------------------------------
//     */

//     if (!empty($image->file_path)) {

//         return redirect(
//             asset('storage/' . $image->file_path)
//         );
//     }


//     /*
//     |--------------------------------------------------------------------------
//     | No Destination
//     |--------------------------------------------------------------------------
//     */

//     abort(404);
// }

public function redirect($code)
{
    /*
    |--------------------------------------------------------------------------
    | Find Short Code
    |--------------------------------------------------------------------------
    */

    $image = Image::where('short_code', $code)->first();


    /*
    |--------------------------------------------------------------------------
    | Old upload_image table support
    |--------------------------------------------------------------------------
    */

    if (!$image) {

        $searchUrl = "https://po3.in/" . $code;

        $uploadImage = DB::connection('images')
            ->table('upload_image')
            ->where('bit_url', $searchUrl)
            ->first();

        if ($uploadImage) {

            return redirect(
                "https://pothysadv.in/pothys-imgupload-api/images/"
                . $uploadImage->image_name
            );
        }

        abort(404);
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK MANUAL ACTIVE / INACTIVE
    |--------------------------------------------------------------------------
    */

     /*
    |--------------------------------------------------------------------------
    | Check Active / Deactive
    |--------------------------------------------------------------------------
    */
    if (!$image->is_active) {

        return redirect('https://pothysmart.com/home_');
    }



    /*
    |--------------------------------------------------------------------------
    | CHECK EXPIRY DATE
    |--------------------------------------------------------------------------
    |
    | expires_at is a DATE field.
    | The URL remains active for the complete expiry date.
    |
    */

   
    if (
        $image->expires_at &&
        \Carbon\Carbon::parse($image->expires_at)
            ->startOfDay()
            ->lt(now()->startOfDay())
    ) {

        return redirect('https://pothysmart.com/home_');
    }


    /*
    |--------------------------------------------------------------------------
    | Increase Click Count
    |--------------------------------------------------------------------------
    */

    $image->increment('click_count');


    /*
    |--------------------------------------------------------------------------
    | Device Information
    |--------------------------------------------------------------------------
    */

    $agent = new Agent();

    $device = $agent->isMobile()
        ? 'Mobile'
        : ($agent->isTablet()
            ? 'Tablet'
            : 'Desktop');


    /*
    |--------------------------------------------------------------------------
    | Browser & IP
    |--------------------------------------------------------------------------
    */

    $browser = $agent->browser();

    $ip = request()->ip();


    /*
    |--------------------------------------------------------------------------
    | Location
    |--------------------------------------------------------------------------
    */

    $location = Location::get($ip);

    $country = $location
        ? $location->countryName
        : 'Unknown';

    $city = $location?->cityName ?? 'Unknown';


    /*
    |--------------------------------------------------------------------------
    | Save Click Analytics
    |--------------------------------------------------------------------------
    */

    ImageClick::create([
        'image_id'    => $image->id,
        'ip_address'  => $ip,
        'browser'     => $browser,
        'device_type' => $device,
        'country'     => $country,
        'city'        => $city,
    ]);


    /*
    |--------------------------------------------------------------------------
    | SHORT URL → EXTERNAL URL
    |--------------------------------------------------------------------------
    */

    if (!empty($image->original_url)) {

        return redirect()->away(
            $image->original_url
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SHORT URL → IMAGE
    |--------------------------------------------------------------------------
    */

    if (!empty($image->file_path)) {

        return redirect(
            asset('storage/' . $image->file_path)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | No Destination
    |--------------------------------------------------------------------------
    */

    abort(404);
}


    // public function edit($id)
    // {
    //     // dd($id);
    //     $image = Image::findOrFail($id);

    //     return view('image.edit', compact('image'));
    // }
    public function edit($short_code)
{
    // dd($short_code);
    $image = Image::where('short_code', $short_code)->firstOrFail();

    return view('image.edit', compact('image'));
}


//     public function update(Request $request, $id)
// {
//     $request->validate([
//         'image' => 'required|image|max:2048'
//     ]);

//     $image = Image::findOrFail($id);

//     $extension = $request->file('image')->getClientOriginalExtension();
//     $fileName = $image->image_name . '.' . $extension;

//     // Save the new file first
//     $path = $request->file('image')->storeAs('images', $fileName, 'public');

//     // Only after successful save, delete the old file if it's different
//     if ($image->file_path != $path &&
//         Storage::disk('public')->exists($image->file_path)) {
//         Storage::disk('public')->delete($image->file_path);
//     }

//     $image->update([
//         'file_path' => $path
//     ]);

//     return back()->with('success', 'Image updated successfully.');
// }


public function update(Request $request, $short_code)
{
    $request->validate([
        'image' => 'nullable|image|max:2048',
        'expires_at' => 'nullable|date|after_or_equal:today',
    ]);

    $image = Image::where('short_code', $short_code)
        ->firstOrFail();


    // ==========================================
    // UPDATE IMAGE ONLY IF NEW IMAGE SELECTED
    // ==========================================

    if ($request->hasFile('image')) {

        $fileName = basename($image->file_path);

        if (Storage::disk('public')->exists($image->file_path)) {

            Storage::disk('public')->delete(
                $image->file_path
            );
        }

        $path = $request->file('image')->storeAs(
            'images',
            $fileName,
            'public'
        );

        $image->file_path = $path;
    }


    // ==========================================
    // UPDATE EXPIRY DATE
    // ==========================================

    $image->expires_at = $request->expires_at;


    // ==========================================
    // KEEP URL ACTIVE
    // ==========================================

    $image->is_active = true;


    // ==========================================
    // SAVE
    // ==========================================

    $image->save();


    return redirect()
        ->route('dashboard')
        ->with(
            'success',
            'Image updated successfully.'
        );
}
    public function destroy($id)
    {
        $image = Image::findOrFail($id);

        if (Storage::disk('public')->exists($image->file_path)) {
            Storage::disk('public')->delete($image->file_path);
        }

        $image->delete();

        return redirect()->back()->with('success', 'Image deleted successfully.');
    }
    // public function process(Request $request)
    // {
    //     $request->validate([
    //         'images.*' => 'required|image',
    //         'mode' => 'required|in:vertical,horizontal',
    //     ]);

    //     $manager = new ImageManager(new Driver());

    //     $images = [];

    //     foreach ($request->file('images') as $file) {
    //         $images[] = $manager->read($file)->orient();
    //     }

    //     $width = 1080;

    //     foreach ($images as $img) {
    //         $img->scale(width: $width);
    //     }

    //     $totalHeight = array_sum(array_map(fn($img) => $img->height(), $images));

    //     $canvas = $manager->create($width, $totalHeight);

    //     $y = 0;
    //     foreach ($images as $img) {
    //         $canvas->place($img, 'top-left', 0, $y);
    //         $y += $img->height();
    //     }

    //     // SAVE PATH
    //     $path = public_path('storage/images');

    //     if (!file_exists($path)) {
    //         mkdir($path, 0777, true);
    //     }

    //     // TEMP NAME (ONLY TEMP, NOT FINAL NAME)
    //     $fileName = 'temp_' . time() . '.jpg';

    //     $canvas->toJpeg(95)->save($path . '/' . $fileName);

    //     return response()->json([
    //         'image' => asset('storage/images/' . $fileName),
    //         'file_path' => 'images/' . $fileName
    //     ]);
    // }
    //merge and shorl url create and save to db 
    // public function saveImage(Request $request)
    // {
    //     dd($request);
    //     $request->validate([
    //         'image_name' => 'required',
    //         'file_path' => 'required'
    //     ]);

    //     $cleanName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->image_name);

    //     $path = public_path('storage/');

    //     $oldPath = $path . $request->file_path;
    //     $newFileName = $cleanName . '.jpg';
    //     $newPath = $path . 'images/' . $newFileName;

    //     //  duplicate check
    //     if (file_exists($newPath)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Image name already exists!'
    //         ], 422);
    //     }

    //     //  rename file
    //     rename($oldPath, $newPath);

    //     // generate shortcode
    //     do {
    //         $shortCode = Str::random(6);
    //     } while (Image::where('short_code', $shortCode)->exists());

    //     // save DB
    //     Image::create([
    //         'user_id' => Auth::id(),
    //         'image_name' => $cleanName,
    //         'file_path' => 'images/' . $newFileName,
    //         'short_code' => $shortCode
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Image saved successfully!',
    //         'short_url' => url('/s/' . $shortCode)
    //     ]);
    // }

    public function process(Request $request)
    {
        ini_set('memory_limit', '512M');
        $request->validate([
            'images.*' => 'required|image',
            'mode'     => 'required|in:vertical,horizontal',

        ]);

        $manager = new ImageManager(new Driver());

        $images = [];

        foreach ($request->file('images') as $file) {
            $images[] = $manager->read($file)->orient();
        }

        $mode = $request->mode;
        $spacing = (int) ($request->spacing ?? 0);
        $bgColor = $request->bgcolor ?? '#ffffff';

        if ($mode === 'vertical') {

            $width = (int) ($request->width ?? 1080);

            foreach ($images as $img) {
                $img->scale(width: $width);
            }

            $totalHeight = array_sum(
                array_map(fn($img) => $img->height(), $images)
            ) + ($spacing * (count($images) - 1));

            $canvas = $manager->create($width, $totalHeight);

            $y = 0;

            foreach ($images as $img) {
                $canvas->place($img, 'top-left', 0, $y);
                $y += $img->height() + $spacing;
            }
        } else {

            $height = (int) ($request->height ?? 1080);

            foreach ($images as $img) {
                $img->scale(height: $height);
            }

            $totalWidth = array_sum(
                array_map(fn($img) => $img->width(), $images)
            ) + ($spacing * (count($images) - 1));

            $canvas = $manager->create($totalWidth, $height);

            $x = 0;

            foreach ($images as $img) {
                $canvas->place($img, 'top-left', $x, 0);
                $x += $img->width() + $spacing;
            }
        }

        $savePath = public_path('storage/images');

        if (!file_exists($savePath)) {
            mkdir($savePath, 0777, true);
        }

        $fileName = 'temp_' . time() . '.jpg';

        $canvas->toJpeg(95)->save(
            $savePath . '/' . $fileName
        );

        return response()->json([
            'image' => asset('storage/images/' . $fileName),
            'file_path' => 'images/' . $fileName
        ]);
    }

    public function saveImage(Request $request)
    {
        $request->validate([
            'file_path'   => 'required',
            'district_id' => 'required|exists:districts,id',
            'expires_at' => 'nullable|date|after_or_equal:today',
        ]);

        $district  = District::findOrFail($request->district_id);
        $shortcode = strtoupper($district->district_shortcode);
        $baseName  = 'POTHYS_' . $shortcode . '_' . now()->format('dMy');

        // Find the next free suffix: _1, _2, _3...
        $suffix = 0;
        do {
            $suffix++;
            $cleanName = $baseName . '_' . $suffix;
        } while (Image::where('image_name', $cleanName)->exists());

        $path    = public_path('storage/');
        $oldPath = $path . $request->file_path;

        $newFileName = $cleanName . '.jpg';
        $newPath     = $path . 'images/' . $newFileName;
        rename($oldPath, $newPath);

        // Generate shortcode
        do {
            $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $shortCode =
                $letters[random_int(0, 25)] .
                $letters[random_int(0, 25)] .
                random_int(0, 9) .
                $shortcode;
        } while (
            Image::where('short_code', $shortCode)->exists()
        );

        Image::create([
            'user_id'     => Auth::id(),
            'district_id' => $district->id,
            'image_name'  => $cleanName,
            'file_path'   => 'images/' . $newFileName,
            'short_code'  => $shortCode,
            'expires_at' => $request->expires_at,
            'is_active' => true,
        ]);

        return response()->json([
            'status'     => 'success',
            'message'    => 'Image saved successfully',
            'image_name' => $cleanName,
            'short_url'  => url($shortCode)
        ]);
    }

    public function todayViewers($imageId)
    {
        $todayClicks = ImageClick::where('image_id', $imageId)
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->get();

        return view('image.today-viewers', compact('todayClicks'));
    }

    // analyticsview
// public function analysis(Request $request, Image $image)
// {
//     // dd($request);
//     $request->validate([
//     'from' => 'required|date',
//     'to'   => 'required|date|after_or_equal:from',
// ]);
//     $from = $request->from ?? $image->created_at->toDateString();
//     $to = $request->to ?? now()->toDateString();

//     $query = ImageClick::where('image_id', $image->id)
//         ->whereBetween('created_at', [
//             $from . ' 00:00:00',
//             $to . ' 23:59:59'
//         ]);

//     $clicks = (clone $query)->latest()->get();

//     return view('image.analysis', [
//         'image' => $image,
//         'clicks' => $clicks,
//         'from' => $from,
//         'to' => $to,
//         'totalViews' => $clicks->count(),
//         'todayViews' => $clicks->where('created_at', '>=', today())->count(),
//         'uniqueVisitors' => $clicks->unique('ip_address')->count(),
//     ]);
// }



public function analysis(Request $request, Image $image)
{
    // Default dates
    $from = $request->filled('from')
        ? $request->from
        : $image->created_at->toDateString();

    $to = $request->filled('to')
        ? $request->to
        : now()->toDateString();

    // Validate only when user clicks Search
    if ($request->has('from') || $request->has('to')) {

        $request->validate([
            'from' => [
                'required',
                'date',
                'after_or_equal:' . $image->created_at->toDateString(),
                'before_or_equal:' . now()->toDateString(),
            ],
            'to' => [
                'required',
                'date',
                'after_or_equal:from',
                'before_or_equal:' . now()->toDateString(),
            ],
        ], [
            'from.after_or_equal' => 'From Date cannot be before Image Created Date.',
            'from.before_or_equal' => 'From Date cannot be greater than today.',
            'to.after_or_equal' => 'To Date must be greater than or equal to From Date.',
            'to.before_or_equal' => 'Future dates are not allowed.',
        ]);
    }

    $query = ImageClick::where('image_id', $image->id)
        ->whereBetween('created_at', [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay(),
        ]);

    $clicks = (clone $query)
        ->latest()
        ->get();

    $todayViews = ImageClick::where('image_id', $image->id)
        ->whereDate('created_at', today())
        ->count();

    $uniqueVisitors = (clone $query)
        ->distinct('ip_address')
        ->count('ip_address');

    return view('image.analysis', [
        'image' => $image,
        'clicks' => $clicks,
        'from' => $from,
        'to' => $to,
        'totalViews' => $clicks->count(),
        'todayViews' => $todayViews,
        'uniqueVisitors' => $uniqueVisitors,
    ]);
}

//     public function getTodayViewers()
// {
//     $query = ImageClick::with('image')
//         ->whereDate('created_at', today());

//     return DataTables::of($query)
//         ->addColumn('image_name', function ($row) {
//             return $row->image->image_name;
//         })
//         ->make(true);
// }



public function saveUrl(Request $request)
{
    $request->validate([
        'original_url' => 'required|url|max:2048',
        'district_id'  => 'required|exists:districts,id',
    ]);

    // Get selected district
    $district = District::findOrFail($request->district_id);

    // District shortcode
    $shortcode = strtoupper($district->district_shortcode);

    /*
    |--------------------------------------------------------------------------
    | Generate Short Code
    |--------------------------------------------------------------------------
    | Format: XX9XXX
    | Example: AB2CHN
    |--------------------------------------------------------------------------
    */

    do {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $shortCode =
            $letters[random_int(0, 25)] .
            $letters[random_int(0, 25)] .
            random_int(0, 9) .
            $shortcode;

    } while (
        Image::where('short_code', $shortCode)->exists()
    );

    /*
    |--------------------------------------------------------------------------
    | Save URL in images table
    |--------------------------------------------------------------------------
    */

    Image::create([
        'user_id'      => Auth::id(),
        'district_id'  => $district->id,
        'image_name'   => null,
        'file_path'    => null,
        'original_url' => $request->original_url,
        'short_code'   => $shortCode,
        'click_count'  => 0,
    ]);

    return redirect()
        ->route('url-shortener.index')
        ->with('success', 'Short URL created successfully!');
}

public function urlShortener()
{
    $districts = District::where('status', 1)
        ->orderBy('district_name')
        ->get();

    $urls = Image::where('user_id', Auth::id())
        ->whereNotNull('original_url')
        ->with('district')
        ->latest()
        ->get();

    return view(
        'url-shortener.index',
        compact('districts', 'urls')
    );
}

public function updateExpiry(Request $request)
{
    $request->validate([
        'image_id' => 'required|integer|exists:images,id',

        'expires_at' => [
            'nullable',
            'date',
            'after_or_equal:today',
        ],
    ]);


    // Find image belonging to logged-in user

    $image = Image::where('id', $request->image_id)
        ->where('user_id', Auth::id())
        ->firstOrFail();


    // ==========================================
    // UPDATE EXPIRY DATE
    // ==========================================

    $image->expires_at = $request->expires_at;


    // ==========================================
    // ACTIVE
    // ==========================================

    // If user selects a future/today date
    // or removes expiry completely,
    // activate the URL.

    $image->is_active = true;


    $image->save();


    return response()->json([

        'success' => true,

        'message' => 'Expiry date updated successfully.',

        'expires_at' => $image->expires_at,

        'is_active' => $image->is_active,

    ]);
}


 public function deactivate($id)
{
    $image = Image::findOrFail($id);

    $image->update([
        'is_active' => false,
    ]);

    return back()->with(
        'success',
        'Image URL deactivated successfully.'
    );
}
}
