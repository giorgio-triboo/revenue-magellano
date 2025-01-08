<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Publisher;
use App\Models\SubPublisher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PublisherExport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PublisherController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of publishers.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Publisher::class);

        $query = Publisher::query();

        // Carica il conteggio dei sub-publisher
        $query->withCount('subPublishers');

        // Gestione stato (filtro)
        $status = $request->get('status', 'active');
        switch ($status) {
            case 'inactive':
                $query->where('is_active', false);
                break;
            case 'all':
                // Non applicare filtri sullo stato
                break;
            case 'active':
            default:
                $query->where('is_active', true);
                break;
        }

        // Gestione della ricerca
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sub_publisher')) {
            $subPublisher = $request->sub_publisher;
            $query->whereHas('subPublishers', function ($q) use ($subPublisher) {
                $q->where('name', 'like', "%{$subPublisher}%");
            });
        }

        // Ordinamento
        $sortField = $request->get('sort', 'company_name');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSortFields = ['company_name', 'vat_number', 'sub_publishers_count'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'company_name';
        }

        if ($sortField === 'sub_publishers_count') {
            $query->orderBy('sub_publishers_count', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $publishers = $query->paginate(12)->withQueryString();

        // Debug log
        Log::info('Lista publisher caricata', [
            'user_id' => auth()->id(),
            'filters' => $request->all(),
            'status' => $status,
            'total' => $publishers->total()
        ]);

        return view('publishers.index', [
            'publishers' => $publishers,
            'filters' => [
                'search' => $request->search,
                'sub_publisher' => $request->sub_publisher,
                'status' => $status,
                'sort' => $sortField,
                'direction' => $sortDirection
            ],
            'canUpdate' => auth()->user()->isAdmin(),
            'canExport' => auth()->user()->isAdmin()
        ]);
    }


    /**
     * Display the specified publisher with details.
     */
    public function show(Publisher $publisher)
    {
        try {
            $this->authorize('view', $publisher);

            $publisher->load([
                'subPublishers' => function ($query) {
                    $query->orderBy('is_primary', 'desc')
                        ->orderBy('display_name');
                },
                'users' => function ($query) {
                    $query->with('role')
                        ->orderBy('first_name');
                },
                'axData' // Aggiungi questo per caricare i dati AX
            ]);

            Log::info('Visualizzazione dettagli publisher', [
                'user_id' => auth()->id(),
                'publisher_id' => $publisher->id
            ]);

            return view('publishers.show', [
                'publisher' => $publisher,
                'users' => $publisher->users,
                'axData' => $publisher->axData, // Passa i dati AX alla vista
                'canUpdate' => auth()->user()->isAdmin(),
                'canManageUsers' => auth()->user()->isAdmin(),
                'canManageSubPublishers' => auth()->user()->isAdmin()
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nella visualizzazione del publisher', [
                'user_id' => auth()->id(),
                'publisher_id' => $publisher->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Si è verificato un errore nel caricamento dei dettagli.');
        }
    }


    /**
     * Show the form for editing publisher data.
     */
    public function edit(Publisher $publisher)
    {
        try {
            $this->authorize('update', $publisher);

            return view('publishers.edit', [
                'publisher' => $publisher,
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento del form di modifica', [
                'user_id' => auth()->id(),
                'publisher_id' => $publisher->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Si è verificato un errore nel caricamento del form di modifica.');
        }
    }

    /**
     * Update the specified publisher.
     */
    public function update(Request $request, Publisher $publisher)
    {
        try {
            $this->authorize('update', $publisher);

            DB::beginTransaction();

            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'legal_name' => 'required|string|max:255',
                'vat_number' => ['required', 'string'],
                'iban' => 'required|string|max:27',
                'swift' => 'required|string|between:8,11',
                'state' => 'required|string|max:255',
                'state_id' => 'required|string|max:3',
                'county' => 'required|string|max:255',
                'county_id' => 'required|string|max:2',
                'city' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'postal_code' => 'required|string|max:10',
                'is_active' => 'required|in:0,1'
            ]);

            // Converti esplicitamente is_active in boolean
            $validated['is_active'] = (bool) $validated['is_active'];

            $publisher->update($validated);

            if ($publisher->wasChanged('company_name')) {
                $publisher->subPublishers()
                    ->where('is_primary', true)
                    ->update(['display_name' => $validated['company_name']]);
            }

            DB::commit();

            Log::info('Publisher aggiornato con successo', [
                'user_id' => auth()->id(),
                'publisher_id' => $publisher->id,
                'changes' => $publisher->getChanges()
            ]);

            return redirect()
                ->route('publishers.show', $publisher)
                ->with('success', 'Publisher aggiornato con successo.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore nell\'aggiornamento del publisher', [
                'user_id' => auth()->id(),
                'publisher_id' => $publisher->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Si è verificato un errore durante l\'aggiornamento del publisher.');
        }
    }

    /**
     * Export publishers list to Excel.
     */
    /**
     * Export publishers list to Excel.
     */
    public function export(Request $request)
    {
        try {
            $this->authorize('export', Publisher::class);

            // Costruisci la query con i filtri
            $query = Publisher::query();

            // Gestione filtri
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('vat_number', 'like', "%{$search}%")
                        ->orWhere('legal_name', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            // Carica i dati in una collection
            $publishers = $query->get();

            Log::info('Avvio esportazione publisher', [
                'user_id' => auth()->id(),
                'filters' => $request->all(),
                'count' => $publishers->count()
            ]);

            return Excel::download(
                new PublisherExport($publishers),
                'publishers_' . now()->format('Y-m-d_His') . '.xlsx'
            );

        } catch (\Exception $e) {
            Log::error('Errore nell\'esportazione dei publisher', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Si è verificato un errore durante l\'esportazione.');
        }
    }

    /**
     * Get publisher details for API.
     */
    public function getDetails(Publisher $publisher)
    {
        try {
            $this->authorize('view', $publisher);

            $publisherData = $publisher->load([
                'subPublishers' => function ($query) {
                    $query->orderBy('is_primary', 'desc')
                        ->orderBy('display_name');
                },
                'users' => function ($query) {
                    $query->with('role')
                        ->orderBy('first_name');
                }
            ]);

            if (!auth()->user()->isAdmin()) {
                unset($publisherData['iban'], $publisherData['swift']);
            }

            return response()->json($publisherData);

        } catch (\Exception $e) {
            Log::error('Errore nel recupero dei dettagli publisher', [
                'user_id' => auth()->id(),
                'publisher_id' => $publisher->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Si è verificato un errore nel recupero dei dettagli.'
            ], 500);
        }
    }

    /**
     * Search publishers for API.
     */
    public function search(Request $request)
    {
        try {
            $this->authorize('viewAny', Publisher::class);

            $search = $request->get('q', '');
            $publishers = Publisher::where(function ($query) use ($search) {
                $query->where('company_name', 'like', "%{$search}%")
                    ->orWhere('vat_number', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%");
            })
                ->select('id', 'company_name', 'vat_number')
                ->take(10)
                ->get();

            return response()->json($publishers);

        } catch (\Exception $e) {
            Log::error('Errore nella ricerca dei publisher', [
                'user_id' => auth()->id(),
                'search' => $request->get('q'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Si è verificato un errore nella ricerca.'
            ], 500);
        }
    }
}