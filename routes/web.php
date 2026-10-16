<?php
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ShortUrlController;
use App\Http\Controllers\SmsCreditController;
use App\Http\Controllers\Admin\ApplicationAccessController;
use App\Http\Controllers\Admin\SmsExcelImportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

//  HOME → MERGE PAGE
Route::get('/', function () {
    // return view('imageupload'); // merge UI
    return redirect()->route('login');
});

//  MERGE PROCESS
Route::post('/process-images', [ImageController::class, 'process'])
    ->name('image.process');

//  SHORT URL (PUBLIC)
// Route::get('/s/{code}', [ImageController::class, 'redirect'])
//     ->name('short.url');
Route::get('/{code}', [ImageController::class, 'redirect'])
    ->where('code', '[A-Z]{2}[0-9]{1}[A-Z]{3}')
    ->name('short.url');
    

// merge and short url for 
Route::post('/save-image', [ImageController::class, 'saveImage'])->name('save.image');

// =========================
// AUTH REQUIRED
// =========================
Route::middleware(['auth'])->group(function () {

    // DASHBOARD
    Route::get('/dashboard', [ImageController::class, 'index'])
        ->name('dashboard');

    // MOBILE ANALYTICS
    Route::get('/next', [ImageController::class, 'mobile'])
        ->name('next');

    Route::get('/mobile-data', [ImageController::class, 'mobileData'])->name('mobile.data');

    Route::get('/get-images', [ImageController::class, 'getImages'])->name('get.images');

    //edit 
    Route::get('/image/{short_code}/edit', [ImageController::class, 'edit'])->name('image.edit');
    Route::put('/image/{short_code}', [ImageController::class, 'update'])->name('image.update');
    Route::delete('/image/{id}', [ImageController::class, 'destroy'])->name('image.destroy');

    Route::get('/today-viewers/{image}', [ImageController::class, 'todayViewers'])->name('today.viewers');

    //analyticsview
    Route::get('/image/{image}/analysis', [ImageController::class, 'analysis'])->name('image.analysis');

    // UPLOAD (OLD FEATURE)
    Route::post('/upload', [ImageController::class, 'store'])
        ->name('image.upload');



    Route::get(
        '/url-shortener',
        [ImageController::class, 'urlShortener']
    )->name('url-shortener.index');

    // Create Short URL
    Route::post(
        '/url-shortener',
        [ImageController::class, 'saveUrl']
    )->name('url-shortener.store');

    Route::get('/pdf', [PdfController::class, 'index'])
        ->name('pdf.index');

    Route::post('/pdf/upload', [PdfController::class, 'upload'])
        ->name('pdf.upload');

    Route::post(
        '/pdf/add-image',
        [PdfController::class, 'addImage']
    )
        ->name('pdf.add.image');

    Route::post(
        '/pdf/delete-page',
        [PdfController::class, 'deletePage']
    )
        ->name('pdf.delete.page');

    Route::post('/pdf/reorder',[PdfController::class, 'reorder'])->name('pdf.reorder');

    Route::get(
        '/pdf/pages',
        [PdfController::class, 'pages']
    )
        ->name('pdf.pages');

    Route::post('/pdf/generate', [PdfController::class, 'generate'])
        ->name('pdf.generate');

    //save 
    Route::post('/pdf/save-image', [PdfController::class, 'saveImage'])
        ->name('pdf.save.image');

    Route::get('/pdf/data-list', [PdfController::class, 'list'])->name('images.list');


    //reports

    // SmsCredit
    Route::get('/sms-credit', [SmsCreditController::class, 'index'])
        ->name('sms.index');

    Route::post('/sms-credit/store', [SmsCreditController::class, 'store'])
        ->name('sms.store');

    // Route::get('/sms-credit/list', [SmsCreditController::class, 'list'])
    //     ->name('sms.list');
    Route::get('/sms/list', [SmsCreditController::class, 'list'])->name('sms.list');

    Route::get('/sms-credit/report', [SmsCreditController::class, 'report'])
        ->name('sms.report');

    Route::post('/sms-credit/report/data', [SmsCreditController::class, 'reportData'])
        ->name('sms.report.data');

    Route::get('/sms-credit/report/pdf', [SmsCreditController::class, 'reportPdf'])
        ->name('sms.report.pdf');

    Route::get('/sms-credit/report/excel', [SmsCreditController::class, 'reportExcel'])
        ->name('sms.report.excel');

    Route::get(
        '/application-access',
        [ApplicationAccessController::class, 'index']
    )->name('application-access.index');

    Route::get(
        '/application-access/create',
        [ApplicationAccessController::class, 'create']
    )->name('application-access.create');

    Route::post(
        '/application-access',
        [ApplicationAccessController::class, 'store']
    )->name('application-access.store');

    Route::post(
        '/application-access/{id}/regenerate-token',
        [ApplicationAccessController::class, 'regenerateToken']
    )->name('application-access.regenerate-token');

//    deactive url 
Route::patch(
    '/image/{id}/deactivate',
    [ImageController::class, 'deactivate']
)->name('image.deactivate');

Route::post(
    '/image/update-expiry',
    [ImageController::class, 'updateExpiry']
)->name('image.update.expiry');

//excel  greencard report 
Route::get('/sms-import', [SmsExcelImportController::class,'index' ])->name('sms-import.index');

Route::post('/sms-import', [SmsExcelImportController::class,'upload'])->name('sms-import.upload');




});



// PROFILE
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
