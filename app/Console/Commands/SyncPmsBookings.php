<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBookingBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SyncPmsBookings extends Command
{
    protected $signature = 'app:sync-pms-bookings {--full}';
    protected $description = 'Sync bookings from the PMS API';
    private string $pmsBaseUrl;
    private int $batchSize;

    public function __construct()
    {
        parent::__construct();

        $this->pmsBaseUrl = config('services.pms.api_base_url');
        $this->batchSize = config('services.pms.booking_sync_batch_size');
    }

    public function handle()
    {
        $this->info('Starting PMS bookings sync...');

        try {
            $bookingIds = $this->fetchAllBookingIds();

            $this->info("Found {$bookingIds->count()} bookings to sync");

            $chunks = $bookingIds->chunk($this->batchSize);

            foreach ($chunks as $chunk) {
                ProcessBookingBatch::dispatch(
                    $chunk->toArray(),
                );
            }
        } catch (\Exception $e) {
            $this->error('Error syncing PMS bookings: ' . $e->getMessage());
            Log::error('PMS Bookings Sync Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }

        return 0;
    }

    private function fetchAllBookingIds(): Collection
    {
        $apiUrl = $this->pmsBaseUrl . "/api/bookings";

        $response = Http::get($apiUrl);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch. Status code: " . $response->status());
        }

        $data = $response->json('data', []);

        return collect($data);
    }
}
