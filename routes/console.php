<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('couriers:sync', function () {
    \App\Modules\Courier\Models\CourierOrder::where('status','submitting')->where('updated_at','<',now()->subMinutes(5))->update(['status'=>'needs_verification','error'=>'Worker interrupted during submission. Verify the booking reference in the merchant panel.']);
    \App\Modules\Courier\Models\CourierOrder::whereNotNull('consignment_id')->whereNotIn('status',['delivered','returned','cancelled'])->chunkById(50,function($bookings) {
        foreach($bookings as $booking) {
            try { app(\App\Modules\Courier\Services\BookingService::class)->sync($booking); }
            catch (\Throwable $e) { $booking->update(['error'=>'Automatic sync failed. Test the courier connection and refresh manually.']); }
        }
    });
});
Schedule::command('couriers:sync')->everyTenMinutes()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
