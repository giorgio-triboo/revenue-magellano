<?php

namespace App\Observers;

use App\Models\Publisher;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PublisherObserver
{
    public function created(Publisher $publisher)
    {
        $publisher->subPublishers()->create([
            'display_name' => $publisher->company_name,
            'invoice_group' => 'main',
            'ax_name' => $publisher->company_name,
            'is_primary' => true,
            'notes' => 'Sub-publisher principale creato automaticamente'
        ]);
    }

    public function updated(Publisher $publisher)
    {
        if ($publisher->isDirty('is_active') && !$publisher->is_active) {
            try {
                $publisher->users()->update(['is_active' => false]);

                Log::info('Publisher e utenti disattivati', [
                    'publisher_id' => $publisher->id,
                    'users_count' => $publisher->users()->count()
                ]);
            } catch (\Exception $e) {
                Log::error('Errore disattivazione utenti', [
                    'publisher_id' => $publisher->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}