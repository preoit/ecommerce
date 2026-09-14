<?php
namespace App\Modules\Courier\Services;
use App\Modules\Courier\Models\{Courier,CourierOrder};
use Illuminate\Support\Facades\{DB,Crypt,Validator};
use Illuminate\Validation\ValidationException;
class BookingService {
    public const STATUSES=['pending','pickup_requested','picked','in_transit','delivered','partial_delivered','returned','cancelled'];
    public function reserve(int $orderId, Courier $courier, array $input): CourierOrder {
        abort_unless($courier->active,422,'This courier is inactive.');
        Provider::resolve($courier);
        return DB::transaction(function () use ($orderId,$courier,$input) {
            $order=DB::table('orders')->where('id',$orderId)->lockForUpdate()->first(); abort_unless($order,404);
            if (!in_array($order->status,['confirmed','processing','ready_to_ship'])) throw ValidationException::withMessages(['courier'=>'Confirm this order before booking.']);
            if (CourierOrder::where('active_order_id',$orderId)->exists()) throw ValidationException::withMessages(['courier'=>'This order already has a booking or a submission awaiting verification.']);
            $items=DB::table('order_items')->where('order_id',$orderId)->get();
            if ($items->isEmpty()) throw ValidationException::withMessages(['courier'=>'Add products before booking a parcel.']);
            $data=array_merge(['recipient_name'=>$order->customer_name,'phone'=>$order->phone,'address'=>$order->address.', '.$order->city,'district'=>$order->city,'weight'=>1,'delivery_type'=>48,'instruction'=>$order->note??''],$input);
            $data=Validator::make($data,['recipient_name'=>'required|string|min:2|max:100','phone'=>['required','regex:/^01[3-9][0-9]{8}$/'],'address'=>'required|string|min:10|max:250','district'=>'nullable|string|max:100','weight'=>'required|numeric|min:0.5|max:10','delivery_type'=>'required|in:48,12','instruction'=>'nullable|string|max:500',...Provider::resolve($courier)->parcelRules()])->validate();
            $data['cod_amount']=$order->payment_status==='paid'?0:(float)$order->total;
            $data['quantity']=(int)$items->sum('quantity'); $data['description']=$items->map(fn($i)=>$i->product_title.' x'.$i->quantity)->implode(', ');
            $data['reference']='ORD'.$orderId.'-'.strtoupper(bin2hex(random_bytes(4)));
            return CourierOrder::create(['order_id'=>$orderId,'active_order_id'=>$orderId,'courier_id'=>$courier->id,'reference'=>$data['reference'],'api_url'=>$courier->api_url,'sandbox_mode'=>$courier->sandbox_mode,'cod_amount'=>$data['cod_amount'],'request_data'=>$data,'status'=>'queued']);
        });
    }
    public function submit(CourierOrder $booking): void {
        if (!CourierOrder::whereKey($booking->id)->where('status','queued')->update(['status'=>'submitting','updated_at'=>now()])) return;
        try {
            $order=DB::table('orders')->find($booking->order_id);
            if (!$order || !in_array($order->status,['confirmed','processing','ready_to_ship'])) {
                $booking->update(['status'=>'failed','active_order_id'=>null,'error'=>'Order is no longer eligible for courier booking.']); return;
            }
            $payload=$booking->request_data;
            $payload['cod_amount']=$order->payment_status==='paid'?0:(float)$order->total;
            $booking->update(['cod_amount'=>$payload['cod_amount'],'request_data'=>$payload]);
            $courier=$booking->courier;
            if (!$courier->active || $courier->api_url!==$booking->api_url || $courier->sandbox_mode!==$booking->sandbox_mode) throw new \RuntimeException('Courier settings changed before submission.');
            $result=Provider::resolve($courier)->createParcel($payload);
            if (empty($result['consignment_id'])) throw new \RuntimeException('Courier returned no consignment ID.');
            $booking->update(['consignment_id'=>(string)$result['consignment_id'],'courier_order_id'=>$result['courier_order_id'],'tracking_code'=>$result['tracking_code'],'delivery_charge'=>$result['delivery_charge'],'response_data'=>$result['raw'],'status'=>'pending','booked_at'=>now(),'error'=>null]);
            $this->log($booking,'pending','created');
        } catch (\Throwable $e) {
            $booking->update(['status'=>'needs_verification','error'=>'Submission could not be confirmed. Check the courier merchant panel using reference '.$booking->reference.' before attempting another booking.']);
            $this->log($booking,'needs_verification','submission_unconfirmed');
        }
    }
    public function sync(CourierOrder $booking): void {
        if (!$booking->consignment_id) throw ValidationException::withMessages(['courier'=>'Verify this reference in the merchant panel and attach the consignment ID first.']);
        $courier=$booking->courier;
        if ($courier->api_url!==$booking->api_url || $courier->sandbox_mode!==$booking->sandbox_mode) throw ValidationException::withMessages(['courier'=>'Switch courier settings back to the booking environment to refresh it.']);
        $raw=Provider::resolve($courier)->getStatus($booking->consignment_id);
        $api=(string)($raw['delivery_status']??$raw['data']['order_status']??'unknown');
        $key=str_replace([' ','-'], '_',strtolower($api));
        $status=match($key) {
            'delivered'=>'delivered','partial_delivered','partial_delivery'=>'partial_delivered','returned','return'=>'returned','cancelled','canceled','pickup_cancelled'=>'cancelled',
            'picked'=>'picked','pickup_requested','assigned_for_pickup'=>'pickup_requested',
            'in_transit','at_the_sorting_hub','received_at_last_mile_hub','assigned_for_delivery'=>'in_transit',
            'pending','in_review','hold','order_created'=>'pending',default=>null,
        };
        DB::transaction(function () use($booking,$courier,$raw,$api,$status) {
            $current=CourierOrder::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $values=['api_status'=>$api,'response_data'=>$raw,'synced_at'=>now(),'error'=>null];
            if ($status && !in_array($current->status,['delivered','returned','cancelled'])) $values['status']=$status;
            if (($values['status']??null)==='delivered') $values['delivered_at']=$current->delivered_at??now();
            if (($values['status']??null)==='cancelled') $values['active_order_id']=null;
            $current->update($values);
            if ($current->wasChanged('status') || $current->wasChanged('api_status')) $this->log($current,$current->status,$api);
            $mapped=$courier->status_mapping[$current->status]??null;
            if ($mapped && !$current->sandbox_mode) DB::table('orders')->where('id',$current->order_id)->whereNotIn('status',['completed','returned','cancelled'])->update(['status'=>$mapped,'updated_at'=>now()]);
        });
    }
    private function log(CourierOrder $booking,string $status,string $api): void {
        DB::table('courier_status_logs')->insert(['courier_order_id'=>$booking->id,'status'=>$status,'api_status'=>$api,'response'=>Crypt::encryptString(json_encode($booking->response_data)),'created_at'=>now()]);
    }
}
