<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageToPdfController extends Controller
{
    public function index()
    {
        return view('image-to-pdf.index');
    }

    public function convert(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        // STEP 1: Support both frontend field names
        $mode = $request->input('mode');

        if (!$mode) {
            $orientation = $request->input('orientation');

            if ($orientation === 'portrait') {
                $mode = 'vertical';
            } elseif ($orientation === 'landscape') {
                $mode = 'horizontal';
            }
        }

        $request->merge([
            'mode' => $mode
        ]);

        // STEP 2: Validate request
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:20',
            'images.*' => 'required|image|mimes:jpg,jpeg,png|max:5120',

            'mode' => 'required|in:vertical,horizontal',

            'spacing' => 'nullable|integer|min:0|max:500',
            'width' => 'nullable|integer|min:100|max:5000',
            'height' => 'nullable|integer|min:100|max:5000',

            'bgcolor' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {

            // STEP 3: Initialize ImageManager
            $manager = new ImageManager(new Driver());

            $images = [];

            foreach ($request->file('images') as $file) {

                $images[] = $manager
                    ->read($file->getRealPath())
                    ->orient();
            }

            $spacing = (int) ($request->spacing ?? 10);
            $bgColor = $request->bgcolor ?? '#ffffff';

            $totalImages = count($images);

            // STEP 4: Vertical merging
            if ($mode === 'vertical') {

                $width = (int) ($request->width ?? 1080);

                foreach ($images as $img) {
                    $img->scale(width: $width);
                }

                $totalHeight = array_sum(
                    array_map(
                        fn($img) => $img->height(),
                        $images
                    )
                ) + ($spacing * ($totalImages - 1));

                $canvas = $manager->create(
                    $width,
                    $totalHeight
                );

                $canvas->fill($bgColor);

                $y = 0;

                foreach ($images as $img) {

                    $canvas->place(
                        $img,
                        'top-left',
                        0,
                        $y
                    );

                    $y += $img->height() + $spacing;
                }

            }

            // STEP 5: Horizontal merging
            else {

                $height = (int) ($request->height ?? 1080);

                foreach ($images as $img) {
                    $img->scale(height: $height);
                }

                $totalWidth = array_sum(
                    array_map(
                        fn($img) => $img->width(),
                        $images
                    )
                ) + ($spacing * ($totalImages - 1));

                $canvas = $manager->create(
                    $totalWidth,
                    $height
                );

                $canvas->fill($bgColor);

                $x = 0;

                foreach ($images as $img) {

                    $canvas->place(
                        $img,
                        'top-left',
                        $x,
                        0
                    );

                    $x += $img->width() + $spacing;
                }
            }

            // STEP 6: Convert merged image to JPEG
            $encodedImage = $canvas->toJpeg(95);

            $base64 = base64_encode(
                (string) $encodedImage
            );

            $imageData = 'data:image/jpeg;base64,' . $base64;

            // STEP 7: Calculate PDF dimensions
            // Convert pixels to PDF points (96 DPI)
            $pdfWidth = $canvas->width() * 0.75;
            $pdfHeight = $canvas->height() * 0.75;

            // STEP 8: Create PDF HTML
            $html = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">

                    <style>
                        @page {
                            margin: 0;
                        }

                        html, body {
                            margin: 0;
                            padding: 0;
                            width: 100%;
                            height: 100%;
                        }

                        img {
                            display: block;
                            width: 100%;
                            height: 100%;
                        }
                    </style>
                </head>

                <body>
                    <img src="' . $imageData . '">
                </body>
                </html>
            ';

            // STEP 9: Generate PDF
            $pdf = Pdf::loadHTML($html)
                ->setPaper([
                    0,
                    0,
                    $pdfWidth,
                    $pdfHeight
                ]);

            $pdfContent = $pdf->output();

            // STEP 10: Return PDF
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                    'attachment; filename="merged-images.pdf"',
                'Content-Length' => strlen($pdfContent),
            ]);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Image to PDF conversion failed.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }
}
