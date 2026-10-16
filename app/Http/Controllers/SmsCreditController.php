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

            'rows' => ['required', 'array', 'min:1', 'max:200'], // capped to prevent bulk-insert abuse / DoS

            'rows.*.branch_id' => ['required', 'exists:branches,id'],

            'rows.*.date' => ['required', 'date'],

            'rows.*.message' => ['required', 'string', 'max:500'],

            'rows.*.submit_count' => ['required', 'integer', 'min:0'],

            'rows.*.credit' => ['required', 'numeric', 'min:0'],

        ]);

        //  if credit should be derived from submit_count * rate, compute it here
        // server-side instead of trusting the client value, e.g.:
        // $row['credit'] = $row['submit_count'] * config('sms.rate_per_message');

        DB::transaction(function () use ($validated) {

            foreach ($validated['rows'] as $row) {

                //  verify auth()->user() is permitted to write to $row['branch_id']
                // before creating (tenant/ownership scoping).

                SmsCredit::create($row);

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

        // scope this query to branches the current user is allowed to see,
        // e.g. ->whereIn('branch_id', auth()->user()->branchIds())
        $query = SmsCredit::with('branch')->latest();

        return DataTables::of($query)

            ->addIndexColumn()

            ->addColumn('branch', function ($row) {
                return e(optional($row->branch)->branch_name);
            })


            // ->editColumn('date', function ($row) {
            //     return $row->date ? Carbon::parse($row->date)->format('d-m-Y') : '';
            // })
            ->editColumn('date', function ($row) {

                return Carbon::parse($row->date)->format('d-m-Y')
                    . '<br><small class="text-muted">'
                    . Carbon::parse($row->created_at)->format('h:i A')
                    . '</small>';

            })
            ->editColumn('message', function ($row) {

                return nl2br(e($row->message));

            })
            ->addColumn('action', function ($row) {

                $id = (int) $row->id;

                return '
                    <button
                        class="btn btn-sm btn-primary edit"
                        data-id="' . $id . '">
                        <i class="ri-edit-line"></i>
                    </button>

                    <button
                        class="btn btn-sm btn-danger delete"
                        data-id="' . $id . '">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                ';
            })

            ->rawColumns([
                'date',
                'message',
                'action'
            ])

            ->make(true);
    }


    public function report()
    {
        $branches = Branch::where('status', 1)
            ->orderBy('branch_name')
            ->get();

        return view('sms.report', compact('branches'));
    }


    /**
     * Shared validation rules for the report/export endpoints.
     */
    protected function reportValidationRules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ];
    }

    protected function reportValidationMessages(): array
    {
        return [
            'branch_id.required' => 'Please select a Branch.',
            'branch_id.exists' => 'Selected Branch is invalid.',
            'from_date.required' => 'Please select From Date.',
            'to_date.required' => 'Please select To Date.',
            'to_date.after_or_equal' => 'To Date must be greater than or equal to From Date.',
        ];
    }


    /**
     * Build the base filtered query for reports/exports, with branch
     * ownership enforced.
     */
    protected function buildReportQuery(Request $request)
    {
        // replace this with your actual authorization check, e.g.:
        // abort_unless(auth()->user()->canAccessBranch($request->branch_id), 403);
        $branch = Branch::where('status', 1)->findOrFail($request->branch_id);

        return SmsCredit::with('branch')
            ->where('branch_id', $branch->id)
            ->whereBetween('date', [
                $request->from_date,
                $request->to_date,
            ])
            ->orderBy('date');
    }

    public function reportData(Request $request)
    {
        $request->validate(
            $this->reportValidationRules(),
            $this->reportValidationMessages()
        );

        $records = $this->buildReportQuery($request)->get();

        $data = $records->map(function ($row) {
            return [
                'id' => $row->id,
                'branch' => [
                    'branch_name' => optional($row->branch)->branch_name,
                ],
                'date' => Carbon::parse($row->date)->format('d-m-Y'),
                'time' => Carbon::parse($row->created_at)->format('h:i A'),
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

    public function reportPdf(Request $request)
    {
        $request->validate(
            $this->reportValidationRules(),
            $this->reportValidationMessages()
        );

        $records = $this->buildReportQuery($request)->get();
        $branch = Branch::findOrFail($request->branch_id);
        $totalSubmit = $records->sum('submit_count');
        $totalCredit = $records->sum('credit');

        return Pdf::loadView(
            'sms.pdf',
            compact(
                'records',
                'branch',
                'totalSubmit',
                'totalCredit'
            )
        )
            ->setPaper('A4', 'landscape')
            ->download('SMS_Credit_Report.pdf');
    }


    public function reportExcel(Request $request)
    {
        $request->validate(
            $this->reportValidationRules(),
            $this->reportValidationMessages()
        );

        $records = $this->buildReportQuery($request)->get();

        return Excel::download(
            new SmsCreditExport($records),
            'SMS_Credit_Report.xlsx'
        );
    }
}






