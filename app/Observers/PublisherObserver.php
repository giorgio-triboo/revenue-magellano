<?php

namespace App\Observers;

use App\Models\Publisher;
use App\Models\AxData;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PublisherObserver
{
    public function created(Publisher $publisher)
    {
        try {
            // Create the main sub-publisher
            $subPublisher = $publisher->subPublishers()->create([
                'display_name' => $publisher->company_name,
                'invoice_group' => 'main',
                'ax_name' => $publisher->company_name,
                'channel_detail' => 'Tutti Terzi Non Rilevanti',
                'is_primary' => true,
                'notes' => 'Sub-publisher principale creato automaticamente'
            ]);

            // Get the last vendor account number from the database
            $lastVendAccount = AxData::orderBy('ax_vend_account', 'desc')
                ->whereNotNull('ax_vend_account')
                ->where('ax_vend_account', 'like', 'TRD1%')
                ->first();

            $vendAccount = $this->generateVendAccount($lastVendAccount);
            $vendId = $this->generateVendId($vendAccount);

            // Determine nationality based on VAT number prefix
            $countryCode = substr($publisher->vat_number, 0, 2);
            $isItalian = $countryCode === 'IT';

            // Set AX fields based on nationality
            $salesTaxGroup = $isItalian ? 'AcqDom' : 'AcqUe';
            $numberSequenceGroupId = $isItalian ? 'TBA.A_ITEX' : 'TBA.A_UE';

            // Create corresponding AX data
            $axData = new AxData([
                'publisher_id' => $publisher->id,
                'ax_vend_account' => $vendAccount,
                'ax_vend_id' => $vendId,
                'vend_group' => 'I',
                'party_type' => 'N',
                'tax_withhold_calculate' => 'N',
                'ax_vat_number' => $publisher->vat_number,
                'item_id' => 'MADV_PERF',
                'email' => request()->input('email'),
                'cost_profit_center' => 'R00008',
                'payment' => null,
                'payment_mode' => null,
                'currency_code' => 'EUR',
                'tax_item_group' => null,
                'sales_tax_group' => $salesTaxGroup,
                'number_sequence_group_id' => $numberSequenceGroupId
            ]);

            $publisher->axData()->save($axData);

            Log::info('Publisher, sub-publisher and AX data created successfully', [
                'publisher_id' => $publisher->id,
                'sub_publisher_id' => $subPublisher->id,
                'ax_data_id' => $axData->id,
                'vend_account' => $vendAccount,
                'vend_id' => $vendId,
                'country_code' => $countryCode,
                'is_italian' => $isItalian,
                'sales_tax_group' => $salesTaxGroup,
                'number_sequence_group_id' => $numberSequenceGroupId,
                'channel_detail' => 'Tutti Terzi Non Rilevanti'
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating publisher related data', [
                'publisher_id' => $publisher->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updated(Publisher $publisher)
    {
        if ($publisher->isDirty('is_active')) {
            try {
                if (!$publisher->is_active) {
                    // Disattivazione
                    $publisher->users()->update([
                        'is_active' => false,
                        'is_validated' => false
                    ]);

                    if ($publisher->axData) {
                        // Here you could add any necessary AX data updates when publisher is deactivated
                    }

                    Log::info('Publisher, users and related data deactivated', [
                        'publisher_id' => $publisher->id,
                        'users_count' => $publisher->users()->count()
                    ]);
                } else {
                    // Attivazione
                    $publisher->users()->update([
                        'is_active' => true,
                        'is_validated' => true
                    ]);

                    Log::info('Publisher and users activated', [
                        'publisher_id' => $publisher->id,
                        'users_count' => $publisher->users()->count()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error updating publisher related data', [
                    'publisher_id' => $publisher->id,
                    'is_active' => $publisher->is_active,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }

    private function generateVendAccount($lastVendAccount)
    {
        if (!$lastVendAccount) {
            return 'TRD100001'; // First vendor account
        }

        // Extract the numeric part and increment
        $lastNumber = intval(substr($lastVendAccount->ax_vend_account, 4));
        $newNumber = $lastNumber + 1;

        // Format with leading zeros
        return 'TRD1' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    private function generateVendId($vendAccount)
    {
        // Remove 'TRD' prefix and add '00' prefix
        return '00' . substr($vendAccount, 3);
    }
}