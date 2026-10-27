<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\SmsCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\SmsCreditExport;
use Maatwebsite\Excel\Facades\Excel;

class SmsCreditController extends Controller
{
    /**
     * Display SMS Credit Page
     */
    public function index()
    {
        $branches = Branch::where('status', 1)
            ->orderBy('branch_name')
            ->get();

        return view('sms.index', compact('branches'));
    }

    /**
     * Store Multiple SMS Credits
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'rows' => ['required', 'array', 'min:1'],

            'rows.*.branch_id' => ['required', 'exists:branches,id'],

            'rows.*.date' => ['required', 'date'],

            'rows.*.message' => ['required', 'string', 'max:500'],

            'rows.*.submit_count' => ['required', 'numeric', 'min:0'],

            'rows.*.credit' => ['required', 'numeric', 'min:0'],

        ]);

        DB::transaction(function () use ($validated) {

            foreach ($validated['rows'] as $row) {

                // SmsCredit::create($row);
                 SmsCredit::create(array_merge($row, [
            'user_id' => auth()->id(),
        ]));

            }

        });

        return response()->json([
            'success' => true,
            'message' => 'SMS Credits saved successfully.'
        ]);
    }

    /**
     * DataTable List
     */
    
    public function list(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        // $query = SmsCredit::with('branch')->latest();
         $query = SmsCredit::with('branch')
        ->where('user_id', auth()->id())
        ->latest();

        return DataTables::of($query)

            ->addIndexColumn()

            ->addColumn('branch', function ($row) {
                return optional($row->branch)->branch_name;
            })

            ->editColumn('date', function ($row) {
                return date('d-m-Y', strtotime($row->date));
            })

            ->addColumn('action', function ($row) {

                return '
                    <button
                        class="btn btn-sm btn-primary edit"
                        data-id="'.$row->id.'">
                        <i class="ri-edit-line"></i>
                    </button>

                    <button
                        class="btn btn-sm btn-danger delete"
                        data-id="'.$row->id.'">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                ';
            })

            ->rawColumns(['action'])

            ->make(true);
    }

  
    public function report()
    {
        
    $branches = Branch::where('status',1)
                ->orderBy('branch_name')
                ->get();

    return view('sms.report', compact('branches'));
    }


//   public function reportData(Request $request)
// {
//     $request->validate([
//     'branch_id' => 'required|exists:branches,id',
//     'from_date' => 'required|date',
//     'to_date' => 'required|date|after_or_equal:from_date',
// ], [
//     'branch_id.required' => 'Please select a Branch.',
//     'branch_id.exists' => 'Selected Branch is invalid.',

//     'from_date.required' => 'Please select From Date.',

//     'to_date.required' => 'Please select To Date.',
//     'to_date.after_or_equal' => 'To Date must be greater than or equal to From Date.',
// ]);

//     $query = SmsCredit::with('branch')
//      ->where('user_id', auth()->id())
//         ->whereBetween('date', [
//             $request->from_date,
//             $request->to_date
//         ]);

//     if (!empty($request->branch_id)) {
//         $query->where('branch_id', $request->branch_id);
//     }

//     $records = $query->orderBy('date')->get();

//     $data = $records->map(function ($row) {
//         return [
//             'id' => $row->id,
//             'branch' => [
//                 'branch_name' => optional($row->branch)->branch_name,
//             ],
//             'date' => $row->date->format('d-m-Y'),
//             'message' => $row->message,
//             'submit_count' => $row->submit_count,
//             'credit' => $row->credit,
//         ];
//     });

//     return response()->json([
//         'data'    => $data,
//         'submit'  => $records->sum('submit_count'),
//         'credit'  => $records->sum('credit'),
//     ]);
// }



public function reportData(Request $request)
{
    dd([
    'logged_in_user_id' => auth()->id(),
    'database_user_ids' => SmsCredit::distinct()
        ->pluck('user_id'),
]);
    $request->validate([
        'branch_id' => 'required|exists:branches,id',
        'from_date' => 'required|date',
        'to_date' => 'required|date|after_or_equal:from_date',
    ], [
        'branch_id.required' => 'Please select a Branch.',
        'branch_id.exists' => 'Selected Branch is invalid.',
        'from_date.required' => 'Please select From Date.',
        'to_date.required' => 'Please select To Date.',
        'to_date.after_or_equal' =>
            'To Date must be greater than or equal to From Date.',
    ]);

    $records = SmsCredit::with('branch')
        ->where('user_id', auth()->id())
        ->whereBetween('date', [
            $request->from_date,
            $request->to_date,
        ])
        ->where('branch_id', $request->branch_id)
        ->orderBy('date')
        ->get();

    $data = $records->map(function ($row) {
        return [
            'id' => $row->id,
            'branch' => [
                'branch_name' => optional($row->branch)->branch_name,
            ],
            'date' => $row->date
                ? $row->date->format('d-m-Y')
                : '-',
            'message' => $row->message,
            'submit_count' => $row->submit_count,
            'credit' => $row->credit,
        ];
    });

    return response()->json([
        'data' => $data,
        'submit' => $records->sum('submit_count'),
        'credit' => $records->sum('credit'),
    ]);
}

// public function reportPdf(Request $request)
// {
//     $query = SmsCredit::with('branch')
//         ->whereBetween('date', [
//             $request->from_date,
//             $request->to_date
//         ]);

//     if ($request->filled('branch_id')) {
//         $query->where('branch_id', $request->branch_id);
//     }

//     $records = $query->orderBy('date')->get();

//     $totalSubmit = $records->sum('submit_count');
//     $totalCredit = $records->sum('credit');

//     return Pdf::loadView(
//         'sms.pdf',
//         compact(
//             'records',
//             'totalSubmit',
//             'totalCredit'
//         )
//     )
//     ->setPaper('A4', 'landscape')
//     ->download('SMS_Credit_Report.pdf');
// }


// public function reportExcel(Request $request)
// {
//     $query = SmsCredit::with('branch')
//         ->whereBetween('date', [
//             $request->from_date,
//             $request->to_date
//         ]);

//     if ($request->filled('branch_id')) {
//         $query->where('branch_id', $request->branch_id);
//     }

//     $records = $query->orderBy('date')->get();

//     return Excel::download(
//         new SmsCreditExport($records),
//         'SMS_Credit_Report.xlsx'
//     );
// }



public function reportPdf(Request $request)
{
    $validated = $request->validate([
        'from_date' => 'required|date',
        'to_date' => 'required|date|after_or_equal:from_date',
        'branch_id' => 'nullable|exists:branches,id',
    ]);

    $query = SmsCredit::with('branch')
        ->where('user_id', auth()->id())
        ->whereBetween('date', [
            $validated['from_date'],
            $validated['to_date'],
        ]);

    if (!empty($validated['branch_id'])) {
        $query->where('branch_id', $validated['branch_id']);
    }

    $records = $query->orderBy('date')->get();

    $totalSubmit = $records->sum('submit_count');
    $totalCredit = $records->sum('credit');

    return Pdf::loadView(
        'sms.pdf',
        compact('records', 'totalSubmit', 'totalCredit')
    )
        ->setPaper('A4', 'landscape')
        ->download('SMS_Credit_Report.pdf');
}

public function reportExcel(Request $request)
{
    $validated = $request->validate([
        'from_date' => 'required|date',
        'to_date' => 'required|date|after_or_equal:from_date',
        'branch_id' => 'nullable|exists:branches,id',
    ]);

    $query = SmsCredit::with('branch')
        ->where('user_id', auth()->id())
        ->whereBetween('date', [
            $validated['from_date'],
            $validated['to_date'],
        ]);

    if (!empty($validated['branch_id'])) {
        $query->where('branch_id', $validated['branch_id']);
    }

    $records = $query->orderBy('date')->get();

    return Excel::download(
        new SmsCreditExport($records),
        'SMS_Credit_Report.xlsx'
    );
}

}