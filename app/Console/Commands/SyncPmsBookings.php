<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBooking;
use App\Models\SyncState;
use App\Services\PmsApiClient;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SyncPmsBookings extends Command
{
    protected $signature = 'app:sync-pms-bookings {--full}';
    protected $description = 'Sync bookings from the PMS API';

    public function __construct(private readonly PmsApiClient $pmsClient)
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting PMS bookings sync...');

        $syncStartedAt = now();

        try {
            $bookingIds = $this->fetchAllBookingIds();

            $this->info("Found {$bookingIds->count()} bookings to sync");

            foreach ($bookingIds as $bookingId) {
                ProcessBooking::dispatch(
                    $bookingId,
                    $this->pmsClient
                );
            }

            if (!$this->option('full')) {
                SyncState::setCursor('bookings', $syncStartedAt);
            }
        } catch (Exception $e) {
            $this->error('Error syncing PMS bookings: ' . $e->getMessage());
            Log::error('PMS Bookings Sync Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }

        return 0;
    }

    /**
     * @throws Exception
     */
    private function fetchAllBookingIds(): Collection
    {
        if (!$this->option('full')) {
            $lastSync = SyncState::getCursor('bookings');

            if ($lastSync) {
                $lastSync = $lastSync->subMinutes(2);

                return $this->pmsClient->fetchAllBookings($lastSync);
            }
        }

        return $this->pmsClient->fetchAllBookings();
    }
}
