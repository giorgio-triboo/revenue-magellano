<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Publisher;
use App\Models\FileUpload;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        Log::info('Dashboard accessed', [
            'user_id' => $user->id,
            'role' => $user->role->code
        ]);

        $data = [
            'section' => $user->isAdmin() ? 'admin' : 'publisher',
            'charts' => $this->getChartData($user),
            'stats' => $this->getStats($user)
        ];

        return view('dashboard.index', $data);
    }

    private function getChartData($user)
    {
        $currentYear = date('Y');
        
        if ($user->isAdmin()) {
            return [
                'publisherRankings' => $this->getPublisherRankings(),
                'monthlyRevenue' => $this->getTotalMonthlyRevenue($currentYear),
            ];
        } else {
            return [
                'publisherRevenue' => $this->getPublisherMonthlyRevenue($user->publisher_id, $currentYear)
            ];
        }
    }

    private function getStats($user)
    {
        if ($user->isAdmin()) {
            return [
                'totalUsers' => User::count(),
                'activePublishers' => Publisher::where('is_active', true)->count(),
                'monthlyUploads' => FileUpload::whereMonth('created_at', now()->month)->count()
            ];
        }
        return [];
    }

    private function getPublisherRankings()
    {
        return DB::table('statements')
            ->select('publishers.legal_name', DB::raw('SUM(statements.total_amount) as total_revenue'))
            ->join('publishers', 'statements.publisher_id', '=', 'publishers.id')
            ->where('competence_year', date('Y'))
            ->where('publishers.is_active', true)
            ->where('statements.is_published', true)
            ->groupBy('publishers.id', 'publishers.legal_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'publisher' => $item->legal_name,
                    'value' => round($item->total_revenue, 2)
                ];
            });
    }

    private function getTotalMonthlyRevenue($year)
    {
        // Inizializza un array con tutti i mesi a 0
        $monthlyData = array_fill(1, 12, [
            'month' => 0,
            'value' => 0
        ]);
        
        // Ottieni i dati dal database
        $query = DB::table('statements')
            ->select(DB::raw('competence_month as month'), DB::raw('SUM(total_amount) as total'))
            ->where('competence_year', $year)
            ->where('is_published', true);

        if (!auth()->user()->isAdmin()) {
            $query->where('publisher_id', auth()->user()->publisher_id);
        }

        $results = $query->groupBy('month')
            ->orderBy('month')
            ->get();
        
        // Aggiorna i valori per i mesi che hanno dati
        foreach ($results as $result) {
            $monthlyData[$result->month] = [
                'month' => $result->month,
                'value' => round($result->total, 2)
            ];
        }
        
        // Converti in array numerato da 0
        return array_values($monthlyData);
    }

    private function getPublisherMonthlyRevenue($publisherId, $year)
    {
        // Inizializza un array con tutti i mesi a 0
        $monthlyData = array_fill(1, 12, [
            'month' => 0,
            'value' => 0
        ]);
        
        // Ottieni i dati dal database
        $results = DB::table('statements')
            ->select(DB::raw('competence_month as month'), DB::raw('SUM(total_amount) as total'))
            ->where('publisher_id', $publisherId)
            ->where('competence_year', $year)
            ->where('is_published', true)
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        // Aggiorna i valori per i mesi che hanno dati
        foreach ($results as $result) {
            $monthlyData[$result->month] = [
                'month' => $result->month,
                'value' => round($result->total, 2)
            ];
        }
        
        // Converti in array numerato da 0
        return array_values($monthlyData);
    }
}