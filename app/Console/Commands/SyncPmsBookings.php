<?php

namespace App\Console\Commands;

use App\Helpers\BookingSyncCache;
use App\Jobs\ProcessBooking;
use App\Models\SyncState;
use App\Services\PmsApiClient;
use App\Services\PmsRateLimiter;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\ProgressBar;

class SyncPmsBookings extends Command
{
    protected $signature = 'app:sync-pms-bookings {--full}';
    protected $description = 'Sync bookings from the PMS API';

    public function __construct(private readonly PmsApiClient   $pmsClient,
                                private readonly PmsRateLimiter $rateLimiter,
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting PMS bookings sync...');

        $syncStartedAt = now();

        try {
            $bookingIds = $this->fetchAllBookingIds();
            $total = $bookingIds->count();

            $this->info("Found {$total} bookings to sync");

            BookingSyncCache::initialize();

            $this->dispatchJobs($bookingIds);
            $this->updateSyncCursor($syncStartedAt);

        } catch (Exception $e) {
            $this->error('Error syncing PMS bookings: ' . $e->getMessage());

            Log::error('PMS Bookings Sync Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }

        $this->monitorProgress($total);

        return 0;
    }

    private function dispatchJobs(Collection $bookingIds): void
    {
        $bar = new ProgressBar($this->output, $bookingIds->count());
        $bar->setFormat(' %current%/%max% Dispatching jobs ');
        $bar->start();

        foreach ($bookingIds as $bookingId) {
            ProcessBooking::dispatch($bookingId)->onQueue('pms');
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("All jobs dispatched. Monitoring progress...");
    }

    private function updateSyncCursor($syncStartedAt): void
    {
        if (!$this->option('full')) {
            SyncState::setCursor('bookings', $syncStartedAt);
        }
    }

    private function monitorProgress(int $total): void
    {
        $bar = new ProgressBar($this->output, $total);
        $bar->setFormat(' %current%/%max% Completed (Success: %success% | Failed: %failed%) ');
        $bar->start();

        while (true) {
            $success = BookingSyncCache::getSuccess();
            $failed  = BookingSyncCache::getFailed();
            $completed = $success + $failed;

            $bar->setMessage($success, 'success');
            $bar->setMessage($failed, 'failed');
            $bar->setProgress($completed);

            if ($completed >= $total) {
                break;
            }

            usleep(500_000);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Sync completed. Success: {$success}, Failed: {$failed}");
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

                $this->rateLimiter->throttle();
                return $this->pmsClient->fetchAllBookings($lastSync);
            }
        }

        $this->rateLimiter->throttle();
        return $this->pmsClient->fetchAllBookings();
    }
}
