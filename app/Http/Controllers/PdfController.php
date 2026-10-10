<?php

namespace App\Http\Controllers;

use App\Models\TempPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\Image;
use Yajra\DataTables\Facades\DataTables;
use App\Models\District;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
class PdfController extends Controller
{
    /**
     * Upload Page
     */
   public function index()
{
    $images = Image::where('user_id', Auth::id())
            ->latest()
            ->get();
	
	$districts = District::where('status',1)->get();
    //    dd($districts);

    return view('pdf.index',compact('images', 'districts'));
}
  


public function list(Request $request)
{
    if ($request->ajax()) {

        $images = Image::with('district')->latest();

        return DataTables::of($images)
            ->addIndexColumn()

            ->addColumn('district', function ($row) {
                return $row->district->district_name ?? '-';
            })

            
            ->addColumn('short_url', function ($row) {

    $url = route('short.url', $row->short_code);

    return '
        <div class="d-flex align-items-center">
            <a href="'.$url.'" target="_blank" class="p-2 text-dark fw-semibold text-decoration-none">'.$url.'</a>

            <button class="btn btn-sm btn-outline-secondary copy-btn"
                    data-url="'.$url.'"
                    title="Copy URL">
                <i class="fas fa-copy"></i>
            </button>
        </div>
    ';
})

->rawColumns(['short_url'])

            ->rawColumns(['short_url'])

            ->make(true);
    }
}
    public function upload(Request $request)
    {
        $request->validate([
            'pdf' => 'required|mimes:pdf|max:51200',
        ]);

        // Upload one PDF at a time. Existing pages for this session are preserved.
        $pdf = $request->file('pdf');

        // Unique filename so multiple PDF uploads do not overwrite each other.
        $fileName = time() . '_' . uniqid();

        $pdfPath = $pdf->storeAs(
            'pdfs',
            $fileName . '.pdf'
        );

        $input = storage_path('app/' . $pdfPath);

        // Folder for converted pages
        $outputFolder = storage_path(
            'app/public/temp-pages'
        );

        if (!file_exists($outputFolder)) {
            mkdir($outputFolder, 0777, true);
        }

        // Output pattern
        $output = $outputFolder .
            '/' .
            $fileName .
            'page%03d.png';

        // Ghostscript Command
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

        $gs = '"C:\\Program Files\\gs\\gs10.08.0\\bin\\gswin64c.exe"';

    } else {

        $gs = '/usr/bin/gs';
    }

$command = $gs .
    ' -dNOPAUSE' .
    ' -dBATCH' .
    ' -sDEVICE=png16m' .
    ' -r150' .
    ' -o "' . $output . '"' .
    ' "' . $input . '"';

exec($command . ' 2>&1', $result, $status);

if ($status != 0) {
    return response()->json([
        'success' => false,
        'command' => $command,
        'status' => $status,
        'output' => $result
    ], 500);
}
//         dd([
//     'status' => $status,
//     'files' => glob($outputFolder . '/*'),
// ]);

        if ($status != 0) {

            return response()->json([
                'success' => false,
                'message' => 'Ghostscript conversion failed.'
            ], 500);

        }

        // Read Generated Images
        $pages = glob(
            $outputFolder .
            '/' .
            $fileName .
            'page*.png'
        );

        // Continue from the last page already uploaded in this session.
        $lastOrder = TempPage::where('session_id', session()->getId())
            ->max('sort_order');

        $sortOrder = $lastOrder ? ((int) $lastOrder + 1) : 1;

        $images = [];

        foreach ($pages as $page) {

            $relativePath = 'temp-pages/' . basename($page);

            $temp = TempPage::create([
                'session_id' => session()->getId(),
                'image_path' => $relativePath,
                'sort_order' => $sortOrder,
            ]);

            $images[] = [
                'id' => $temp->id,
                'image' => asset('storage/' . $relativePath),
                'sort_order' => $sortOrder,
            ];

            $sortOrder++;
        }

        return response()->json([

            'success' => true,

            'images' => $images

        ]);
    }
    public function addImage(Request $request)
{
    $request->validate([
        'image' => 'required|image|max:10240',
        'position' => 'required|integer|min:1'
    ]);

    $file = $request->file('image');

    $name = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

    $folder = storage_path('app/public/temp-pages');

    if (!File::exists($folder)) {
        File::makeDirectory($folder, 0777, true);
    }

    $file->move($folder, $name);

    $pages = TempPage::where('session_id', session()->getId())
        ->orderBy('sort_order')
        ->get();

    foreach ($pages as $page) {

        if ($page->sort_order >= $request->position) {

            $page->increment('sort_order');

        }
    }

    $temp = TempPage::create([

        'session_id' => session()->getId(),

        'image_path' => 'temp-pages/'.$name,

        'sort_order' => $request->position

    ]);

    return response()->json([

        'success' => true,

        'page' => $temp,

        'image' => asset('storage/'.$temp->image_path)

    ]);
}
public function deletePage(Request $request)
{
    $page = TempPage::findOrFail($request->id);

    $fullPath = storage_path('app/public/'.$page->image_path);

    if (File::exists($fullPath)) {

        File::delete($fullPath);

    }

    $order = $page->sort_order;

    $page->delete();

    TempPage::where('session_id', session()->getId())

        ->where('sort_order','>',$order)

        ->decrement('sort_order');

    return response()->json([

        'success'=>true

    ]);
}
public function reorder(Request $request)
{
    $request->validate([
        'pages' => 'required|array',
        'pages.*' => 'required|integer',
    ]);

    $sessionId = session()->getId();

    foreach ($request->pages as $index => $id) {
        TempPage::where('id', $id)
            ->where('session_id', $sessionId)
            ->update([
                'sort_order' => $index + 1,
            ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Page order updated successfully.',
    ]);
}
public function pages()
{
    $pages = TempPage::where(

        'session_id',

        session()->getId()

    )

    ->orderBy('sort_order')

    ->get();

    $data = [];

    foreach($pages as $page){

        $data[] = [

            'id'=>$page->id,

            'image'=>asset('storage/'.$page->image_path),

            'sort_order'=>$page->sort_order

        ];

    }

    return response()->json($data);
}

public function generate(Request $request)
{
    $request->validate([
        'mode'        => 'required|in:vertical,horizontal',
        'district_id' => 'required|exists:districts,id',
        // 'image_name'  => 'required|string|max:255'
    ]);
    $imageName = trim($request->image_name);
    
    
    $district = District::findOrFail($request->district_id);

    $date = now()->format('dMy');

    $prefix = 'POTHYS_' . strtoupper($district->district_shortcode) . '_' . $date;

    $last = Image::where('image_name', 'like', $prefix . '_%')
        ->latest('id')
        ->first();

    $next = 1;

    if ($last) {
        preg_match('/_(\d+)$/', $last->image_name, $m);
        $next = isset($m[1]) ? ((int)$m[1] + 1) : 1;
    }

$imageName = $prefix . '_' . $next;

// Check duplicate image name (same district)
// $exists = Image::where('district_id', $request->district_id)
//     ->whereRaw('LOWER(image_name) = ?', [strtolower($imageName)])
//     ->exists();

// if ($exists) {
//     return response()->json([
//         'success' => false,
//         'message' => 'Image name already exists. Please enter another image name.'
//     ], 422);
// }
        // temp file deleteding to db 
    $pages = TempPage::where('session_id', session()->getId())
        ->orderBy('sort_order')
        ->get();

    if ($pages->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No pages found.'
        ], 422);
    }

    $manager = new ImageManager(new Driver());

    // Only keep valid files. Images are loaded one-by-one below so multiple
    // PDF uploads do not unnecessarily keep every source image in memory.
    $validPaths = [];

    foreach ($pages as $page) {
        $path = storage_path('app/public/' . $page->image_path);

        if (File::exists($path)) {
            $validPaths[] = $path;
        }
    }

    if (empty($validPaths)) {
        return response()->json([
            'success' => false,
            'message' => 'Images not found.'
        ], 422);
    }

    try {
        $background = '#ffffff';

        // First pass: determine the final canvas dimensions.
        $targetWidth = 0;
        $targetHeight = 0;
        $totalHeight = 0;
        $totalWidth = 0;

        foreach ($validPaths as $path) {
            $img = $manager->read($path)->orient();

            if ($request->mode === 'vertical') {
                $targetWidth = max($targetWidth, $img->width());
            } else {
                $targetHeight = max($targetHeight, $img->height());
            }

            unset($img);
        }

        // Second pass: calculate scaled dimensions.
        if ($request->mode === 'vertical') {
            foreach ($validPaths as $path) {
                $img = $manager->read($path)->orient();
                $newHeight = (int) round(
                    $img->height() * ($targetWidth / $img->width())
                );
                $totalHeight += $newHeight;
                unset($img);
            }

            $canvas = $manager->create($targetWidth, $totalHeight)
                ->fill($background);

            $y = 0;

            foreach ($validPaths as $path) {
                $img = $manager->read($path)->orient();
                $img->scale(width: $targetWidth);
                $canvas->place($img, 'top-left', 0, $y);
                $y += $img->height();
                unset($img);
            }
        } else {
            foreach ($validPaths as $path) {
                $img = $manager->read($path)->orient();
                $newWidth = (int) round(
                    $img->width() * ($targetHeight / $img->height())
                );
                $totalWidth += $newWidth;
                unset($img);
            }

            $canvas = $manager->create($totalWidth, $targetHeight)
                ->fill($background);

            $x = 0;

            foreach ($validPaths as $path) {
                $img = $manager->read($path)->orient();
                $img->scale(height: $targetHeight);
                $canvas->place($img, 'top-left', $x, 0);
                $x += $img->width();
                unset($img);
            }
        }
    } catch (\Throwable $e) {
        report($e);

        return response()->json([
            'success' => false,
            'message' => 'Image generation failed: ' . $e->getMessage(),
        ], 500);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Temporary Image
    |--------------------------------------------------------------------------
    */

    $mergedFolder = public_path('merged');

    if (!File::exists($mergedFolder)) {
        File::makeDirectory($mergedFolder, 0777, true);
    }

    $tempFile = time() . '.jpg';

    $canvas->toJpeg(95)->save($mergedFolder . '/' . $tempFile);

    /*
    |--------------------------------------------------------------------------
    | District
    |--------------------------------------------------------------------------
    */

    $district = District::findOrFail($request->district_id);

    /*
    |--------------------------------------------------------------------------
    | Clean Image Name
    |--------------------------------------------------------------------------
    */

//     $imageName = preg_replace(
//     '/[^A-Za-z0-9_-]/',
//     '_',
//     $imageName
// );

    /*
    |--------------------------------------------------------------------------
    | Final Storage Folder
    |--------------------------------------------------------------------------
    */

    $imageFolder = public_path('storage/images');

    if (!File::exists($imageFolder)) {
        File::makeDirectory($imageFolder, 0777, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Unique Short Code
    |--------------------------------------------------------------------------
    */

    do {

        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $shortCode =
            $letters[random_int(0,25)] .
            $letters[random_int(0,25)] .
            random_int(0,9) .
            strtoupper($district->district_shortcode);

    } while (
        Image::where('short_code', $shortCode)->exists()
    );

    /*
    |--------------------------------------------------------------------------
    | Final File Name
    |--------------------------------------------------------------------------
    */

    $newFileName = $imageName . '_' . $shortCode . '.jpg';

    File::move(
        $mergedFolder . '/' . $tempFile,
        $imageFolder . '/' . $newFileName
    );

    /*
    |--------------------------------------------------------------------------
    | Save Database
    |--------------------------------------------------------------------------
    */

    // $image = Image::create([
    //     'user_id'      => 1,
    //     'district_id'  => $district->id,
    //     'image_name'   => $imageName,
    //     'file_path'    => 'images/' . $newFileName,
    //     'short_code'   => $shortCode,
    //     'click_count'  => 0
    // ]);

    /*
    |--------------------------------------------------------------------------
    | Delete Temporary Images
    |--------------------------------------------------------------------------
    */

    // foreach ($pages as $page) {

    //     $tempPath = storage_path('app/public/' . $page->image_path);

    //     if (File::exists($tempPath)) {
    //         File::delete($tempPath);
    //     }
    // }

    // TempPage::where('session_id', session()->getId())->delete();

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    return response()->json([
    'success'      => true,
    'message'      => 'Image Generated Successfully',

    'district_id'  => $district->id,

    'image_name'   => $imageName,

    'file_path'    => 'images/' . $newFileName,

    'image_url'    => asset('storage/images/' . $newFileName),

    'short_code'   => $shortCode,

    'redirect_url' => url('/s/' . $shortCode),
]);
}
// public function saveImage(Request $request)
// {
//     $image = Image::create([
//         'user_id'      => 1,
//         'district_id'  => $request->district_id,
//         'image_name'   => $request->image_name,
//         'file_path'    => $request->file_path,
//         'short_code'   => $request->short_code,
//         'click_count'  => 0
//     ]);

//     return response()->json([
//         'success' => true,
//         'message' => 'Image saved successfully.'
//     ]);
// }

public function saveImage(Request $request)
{
    $request->validate([
        'district_id' => 'required|exists:districts,id',
        // 'image_name'  => 'required|string|max:255',
        'file_path'   => 'required|string',
        'short_code'  => 'required|string',
    ]);

    // Clean image name
    // $imageName = trim($request->image_name);

    $district = District::findOrFail($request->district_id);

    // Example: POTHYS_CHN_02Jul26
    $date = now()->format('dMy');
    $prefix = 'POTHYS_' . strtoupper($district->district_shortcode) . '_' . $date;

    // Get last image for same district and same day
    $lastImage = Image::where('district_id', $district->id)
        ->where('image_name', 'LIKE', $prefix . '_%.jpeg')
        ->latest('id')
        ->first();

    $next = 1;

    if ($lastImage) {

        if (preg_match('/_(\d+)\.jpeg$/i', $lastImage->image_name, $match)) {
            $next = (int) $match[1] + 1;
        }

    }

    // Final image name
    $imageName = $prefix . '_' . $next . '.jpeg';

    $pages = TempPage::where('session_id', session()->getId())->get();

    foreach ($pages as $page) {

        $tempPath = storage_path('app/public/' . $page->image_path);

        if (File::exists($tempPath)) {
            File::delete($tempPath);
        }
    }

TempPage::where('session_id', session()->getId())->delete();
    $image = Image::create([
        'user_id'      => Auth::id() ?? 1,
        'district_id'  => $request->district_id,
        'image_name'   => $imageName,
        'file_path'    => $request->file_path,
        'short_code'   => $request->short_code,
        'click_count'  => 0,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Image saved successfully.',
        'data'    => $image,
    ]);
}

public function redirect($code)
{
    // dd($code,'hi');
    $image = Image::where('short_code', $code)->firstOrFail();

    $image->increment('click_count');

    $file = storage_path($image->file_path);
   

    return redirect(asset('storage/' . $image->file_path));
}
}
