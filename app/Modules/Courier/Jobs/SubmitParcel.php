<?php
namespace App\Modules\Courier\Jobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Modules\Courier\Models\CourierOrder;
use App\Modules\Courier\Services\BookingService;
class SubmitParcel implements ShouldQueue {
    use Queueable;
    public int $tries=1;
    public int $timeout=70;
    public function __construct(public int $bookingId) {}
    public function handle(BookingService $service): void { $service->submit(CourierOrder::findOrFail($this->bookingId)); }
}
