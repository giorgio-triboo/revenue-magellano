<?php

namespace App\Http\Controllers;

use App\Models\Statement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StatementsExport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StatementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Statement::class);

        try {
            $query = Statement::with(['publisher', 'subPublisher'])
                            ->where('is_published', true);

            if (auth()->user()->role?->code === 'publisher') {
                $query->where('publisher_id', auth()->user()->publisher_id);
            }

            $query = $this->applyFilters($query, $request);

            $totals = $this->calculateTotals($query);
            $selectedYear = $this->validateYear($request->get('year', Carbon::now()->year));
            $availableYears = $this->getAvailableYears();
            $monthlyStats = $this->calculateMonthlyStats($query, $selectedYear);

            return view('statements.index', [
                'totals' => $totals,
                'monthlyStats' => $monthlyStats,
                'selectedYear' => $selectedYear,
                'availableYears' => $availableYears,
            ]);
            

        } catch (\Exception $e) {
            Log::error('Error retrieving statements', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('statements.index', [
                'totals' => [],
                'monthlyStats' => collect([]),
                'selectedYear' => Carbon::now()->year,
                'availableYears' => collect([]),
            ])->withErrors(['generic' => 'Si è verificato un errore nel recupero dei dati. Riprova più tardi.']);
        }
    }
    protected function validateYear($year)
    {
        $year = filter_var($year, FILTER_VALIDATE_INT);
        $currentYear = Carbon::now()->year;
        
        if (!$year || $year < 2000 || $year > ($currentYear + 1)) {
            return $currentYear;
        }
        
        return $year;
    }



    public function details(Request $request)
{
    $this->authorize('viewAny', Statement::class);

    try {
        $query = Statement::with(['publisher', 'subPublisher'])
                        ->where('is_published', true);

        if (auth()->user()->role?->code === 'publisher') {
            $query->where('publisher_id', auth()->user()->publisher_id);
        }

        // Applica i filtri alla query
        $query = $this->applyFilters($query, $request);

        // Calcola i totali sul dataset filtrato prima della paginazione
        $totals = $this->calculateTotals($query);

        // Dopo aver calcolato i totali, applica la paginazione per la tabella
        $statements = $query->orderBy('statement_year', 'desc')
            ->orderBy('statement_month', 'desc')
            ->paginate(15);

        $years = $this->getAvailableYears();

        return view('statements.details', [
            'statements' => $statements,
            'years' => $years,
            'filters' => $this->getFilters($request),
            'stat' => $totals  // Questo userà lo stesso formato dell'index per i totali
        ]);

    } catch (\Exception $e) {
        Log::error('Error retrieving statements details', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return view('statements.details', [
            'statements' => collect([]),
            'years' => collect([]),
            'filters' => [],
            'stat' => ['fatturazione' => 0],
            'error' => 'Si è verificato un errore nel recupero dei dati. Riprova più tardi.',
        ])->withErrors(['generic' => 'Errore nel recupero dei dati']);
    }
}

    public function show(Statement $statement)
    {
        try {
            Log::info('Accessing statement details', [
                'statement_id' => $statement->id,
                'user_id' => auth()->id(),
            ]);

            $this->authorize('view', $statement);

            if (!$statement->is_published) {
                abort(404, 'Consuntivo non trovato');
            }

            $statement->load(['publisher', 'subPublisher', 'fileUpload.user']);

            return view('statements.show', compact('statement'));

        } catch (\Exception $e) {
            Log::error('Error accessing statement details', [
                'statement_id' => $statement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return redirect()->route('statements.index')
                    ->withErrors(['authorization' => $e->getMessage()]);
            }

            return redirect()->route('statements.index')
                ->withErrors(['generic' => 'Errore nel recupero dei dettagli del consuntivo']);
        }
    }

    protected function calculateTotals($query)
    {
        $totals = [
            'fatturazione' => 0
        ];

        if ($query->count() > 0) {
            $totals['fatturazione'] = $query->sum('total_amount');
        }

        return $totals;
    }

    protected function calculateMonthlyStats($query, $year)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $stats = new Collection();

        $maxMonth = ($year == $currentYear) ? $currentMonth : 12;

        for ($month = 1; $month <= $maxMonth; $month++) {
            $monthlyQuery = clone $query;
            $monthlyQuery->where('statement_year', $year)
                        ->where('statement_month', $month);

            $total = $monthlyQuery->sum('total_amount');

            $stats->push([
                'month' => Carbon::create()->month($month)->locale('it')->translatedFormat('F'),
                'month_number' => $month,
                'fatturazione' => $total,
                'has_data' => $total > 0
            ]);
        }

        return $stats->sortByDesc('month_number')->values();
    }

    protected function applyFilters($query, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\p{N}\s\-\_\.]+$/u'],
                'statement_month' => ['nullable', 'integer', 'between:1,12'],
                'revenue_type' => ['nullable', 'string', 'in:cpl,cpc,cpm,tmk,crg,cpa,sms'],
                'statement_year' => ['nullable', 'integer', 'between:2000,' . (date('Y') + 1)],
            ]);

            if ($validator->fails()) {
                Log::warning('Invalid filter parameters', [
                    'errors' => $validator->errors(),
                    'user_id' => auth()->id()
                ]);
                return $query; // Return unfiltered query if validation fails
            }

            if ($request->filled('search')) {
                $search = strip_tags($request->search);
                $search = addcslashes($search, '%_'); // Escape LIKE special characters
                
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('campaign_name', 'like', '%' . $search . '%')
                        ->orWhereHas('publisher', function ($q) use ($search) {
                            $q->where('company_name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('subPublisher', function ($q) use ($search) {
                            $q->where('display_name', 'like', '%' . $search . '%');
                        });
                });
            }

            if ($request->filled('statement_month')) {
                $month = filter_var($request->statement_month, FILTER_VALIDATE_INT);
                if ($month && $month >= 1 && $month <= 12) {
                    $query->where('statement_month', $month);
                }
            }

            if ($request->filled('revenue_type')) {
                $revenueType = strip_tags($request->revenue_type);
                if (in_array($revenueType, ['cpl', 'cpc', 'cpm', 'tmk', 'crg', 'cpa', 'sms'], true)) {
                    $query->where('revenue_type', $revenueType);
                }
            }

            if ($request->filled('statement_year')) {
                $year = $this->validateYear($request->statement_year);
                $query->where('statement_year', $year);
            }

            return $query;

        } catch (\Exception $e) {
            Log::error('Error applying filters', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            return $query; // Return unfiltered query in case of error
        }
    }


    protected function getAvailableYears()
    {
        $query = Statement::select('statement_year')
            ->where('is_published', true)
            ->distinct()
            ->orderByDesc('statement_year');

        if (auth()->user()->role?->code === 'publisher') {
            $query->where('publisher_id', auth()->user()->publisher_id);
        }

        return $query->pluck('statement_year');
    }

    protected function getFilters(Request $request)
    {
        return [
            'statement_year' => $request->statement_year,
            'statement_month' => $request->statement_month,
            'revenue_type' => $request->revenue_type,
            'search' => $request->search,
        ];
    }

    public function export(Request $request)
{
    $this->authorize('viewAny', Statement::class);

    try {
        Log::info('Starting statements export', [
            'user_id' => auth()->id(),
            'filters' => $request->all()
        ]);

        // Base query with relationships
        $query = Statement::with(['publisher', 'subPublisher'])
                        ->where('is_published', true);

        // Apply publisher filter for non-admin users
        if (auth()->user()->role?->code === 'publisher') {
            $query->where('publisher_id', auth()->user()->publisher_id);
        }

        // Apply all filters from the request
        $query = $this->applyFilters($query, $request);

        // Get the filtered data
        $statements = $query->orderBy('statement_year', 'desc')
                          ->orderBy('statement_month', 'desc')
                          ->get();

        // Generate filename with timestamp
        $filename = sprintf(
            'statements_export_%s.xlsx',
            now()->format('Y_m_d_His')
        );

        Log::info('Exporting statements', [
            'count' => $statements->count(),
            'filename' => $filename
        ]);

        // Return Excel download using the StatementsExport class
        return Excel::download(
            new StatementsExport($statements),
            $filename
        );

    } catch (\Exception $e) {
        Log::error('Error exporting statements', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->back()->withErrors([
            'export' => 'Si è verificato un errore durante l\'esportazione dei dati. Riprova più tardi.'
        ]);
    }
}
}