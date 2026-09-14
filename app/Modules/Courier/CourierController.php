<?php
namespace App\Modules\Courier;
use App\Http\Controllers\Controller;
use App\Modules\Courier\Models\{Courier,CourierOrder};
use App\Modules\Courier\Services\{Provider,BookingService};
use App\Modules\Courier\Jobs\SubmitParcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule,ValidationException};
use Inertia\Inertia;
class CourierController extends Controller {
    public function settings() {
        return Inertia::render('app/modules/courier/pages/Settings',['couriers'=>Courier::all()->map(function($c){
            $data=$c->toArray(); $definition=config('couriers.providers.'.$c->slug);
            return [...$data,'fields'=>$definition['fields'],'configured'=>array_keys(array_filter($c->credentials??[])),'liveUrl'=>$definition['live'],'sandboxUrl'=>$definition['sandbox']];
        }),'statuses'=>BookingService::STATUSES]);
    }
    public function save(Request $r,Courier $courier) {
        $definition=config('couriers.providers.'.$courier->slug);
        $data=$r->validate(['active'=>'required|boolean','sandbox_mode'=>'required|boolean','api_url'=>'required|string|max:255','credentials'=>'nullable|array:'.implode(',',$definition['fields']),'credentials.*'=>'nullable|string|max:1000','status_mapping'=>'nullable|array:'.implode(',',BookingService::STATUSES),'status_mapping.*'=>['nullable',Rule::in(['confirmed','processing','ready_to_ship','shipped','completed','returned','cancelled'])]]);
        $expected=$definition[$data['sandbox_mode']?'sandbox':'live'];
        if (!$expected || rtrim($data['api_url'],'/')!==$expected) throw ValidationException::withMessages(['api_url'=>'Select the official URL for the selected environment.']);
        $credentials=array_merge($courier->credentials??[],array_filter($data['credentials']??[],fn($v)=>filled($v)));
        if ($data['active']) foreach($definition['fields'] as $key) if(blank($credentials[$key]??null)) throw ValidationException::withMessages(['credentials.'.$key=>'Required to activate this courier.']);
        if ($courier->api_url!==$expected && CourierOrder::where('courier_id',$courier->id)->whereNotNull('active_order_id')->exists()) throw ValidationException::withMessages(['api_url'=>'Existing bookings must be resolved before changing environment.']);
        $courier->update([...$data,'api_url'=>$expected,'credentials'=>$credentials,'tested_at'=>null]);
        return back()->with('success','Courier settings updated successfully.');
    }
    public function test(Courier $courier) {
        try { $result=Provider::resolve($courier)->testConnection(); $courier->update(['tested_at'=>now()]); return response()->json($result); }
        catch (\Throwable $e) { return response()->json(['message'=>'Connection failed. Verify credentials, environment and provider availability.'],422); }
    }
    public function locations(Request $r,Courier $courier) {
        $d=$r->validate(['type'=>'required|in:cities,zones,areas,stores','parent'=>'nullable|integer|min:1']);
        try { return response()->json(Provider::resolve($courier)->locations($d['type'],$d['parent']??null)); }
        catch (\Throwable $e) { return response()->json(['message'=>'Unable to load courier locations. Test the connection in Settings.'],422); }
    }
    public function order(int $order) {
        $o=DB::table('orders')->find($order); abort_unless($o,404);
        return response()->json(['couriers'=>Courier::where('active',true)->get(['id','name','slug','sandbox_mode']), 'defaults'=>['recipient_name'=>$o->customer_name,'phone'=>$o->phone,'address'=>$o->address.', '.$o->city,'district'=>$o->city,'weight'=>1,'delivery_type'=>48,'instruction'=>$o->note??'','cod_amount'=>$o->payment_status==='paid'?0:(float)$o->total,'customer_delivery_charge'=>(float)$o->shipping_total], 'bookings'=>CourierOrder::with('courier:id,name,slug')->where('order_id',$order)->latest()->get()->map(fn($b)=>$this->details($b))]);
    }
    private function details(CourierOrder $b): array {
        return [...$b->toArray(),'logs'=>DB::table('courier_status_logs')->where('courier_order_id',$b->id)->latest('id')->limit(50)->get(['status','api_status','created_at'])];
    }
    public function book(Request $r,BookingService $service) {
        $d=$r->validate(['courier_id'=>'required|exists:couriers,id','orders'=>'required|array|min:1|max:50','orders.*.id'=>'required|integer|distinct|exists:orders,id','orders.*.fields'=>'nullable|array']);
        $courier=Courier::findOrFail($d['courier_id']); $results=[];
        foreach($d['orders'] as $entry) {
            try { DB::transaction(function() use($service,$entry,$courier) { $b=$service->reserve($entry['id'],$courier,$entry['fields']??[]); SubmitParcel::dispatch($b->id)->onConnection('database')->onQueue('couriers'); }); $results[]=['order_id'=>$entry['id'],'message'=>'Queued for '.$courier->name,'success'=>true]; }
            catch (ValidationException $e) { $results[]=['order_id'=>$entry['id'],'message'=>collect($e->errors())->flatten()->implode(' '),'success'=>false]; }
            catch (\Throwable $e) { $results[]=['order_id'=>$entry['id'],'message'=>'Booking could not be queued. Check the order booking details before retrying.','success'=>false]; }
        }
        return response()->json(['results'=>$results]);
    }
    public function refresh(CourierOrder $booking,BookingService $service) {
        try { $service->sync($booking); return response()->json(['message'=>'Courier status refreshed.']); }
        catch(ValidationException $e) { throw $e; }
        catch(\Throwable $e) { return response()->json(['message'=>'Status could not be refreshed. Try again after testing the connection.'],422); }
    }
    public function cancel(CourierOrder $booking) { return Provider::resolve($booking->courier)->cancelParcel($booking->consignment_id??''); }
    public function reconcile(Request $r,CourierOrder $booking,BookingService $service) {
        abort_unless($booking->status==='needs_verification',422);
        $d=$r->validate(['consignment_id'=>'required|string|max:100']);
        // Do not release the duplicate lock based on an unverified "not found" report.
        $raw=Provider::resolve($booking->courier)->getStatus($d['consignment_id']);
        $reference=$raw['data']['merchant_order_id']??$raw['consignment']['invoice']??null;
        if ($reference!==$booking->reference) throw ValidationException::withMessages(['courier'=>'The API did not verify this consignment belongs to the booking reference. Contact courier support; the duplicate lock remains active.']);
        $booking->update(['consignment_id'=>$d['consignment_id'],'status'=>'pending','booked_at'=>now()]);
        $service->sync($booking->fresh());
        return response()->json(['message'=>'Consignment linked. Verify its recipient and reference in the courier panel.']);
    }
    public function label(CourierOrder $booking) {
        abort_unless($booking->consignment_id,404);
        return Inertia::render('app/modules/courier/pages/Label',['booking'=>$this->details($booking->load('courier:id,name'))]);
    }
    public function settlement(Request $r,CourierOrder $booking) {
        abort_unless($booking->booked_at && !$booking->sandbox_mode,422);
        $d=$r->validate(['amount'=>'required|numeric|min:0|max:'.$booking->cod_amount,'reference'=>'required|string|max:150']);
        DB::transaction(function() use($r,$booking,$d) {
            $booking->update(['collected_cod'=>$d['amount']]);
            DB::table('courier_status_logs')->insert(['courier_order_id'=>$booking->id,'status'=>$booking->status,'api_status'=>'manual_settlement','response'=>\Illuminate\Support\Facades\Crypt::encryptString(json_encode(['amount'=>$d['amount'],'reference'=>$d['reference'],'admin_id'=>$r->user()->id])),'created_at'=>now()]);
        });
        return response()->json(['message'=>'COD settlement recorded.']);
    }
    public function index(Request $r) {
        $d=$r->validate(['from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d|after_or_equal:from']);
        $q=CourierOrder::where('sandbox_mode',false)->when($d['from']??null,fn($q,$date)=>$q->where('created_at','>=',\Carbon\Carbon::parse($date,'Asia/Dhaka')->utc()))->when($d['to']??null,fn($q,$date)=>$q->where('created_at','<=',\Carbon\Carbon::parse($date,'Asia/Dhaka')->endOfDay()->utc()));
        $stats=(clone $q)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total','status');
        $booked=(clone $q)->whereNotNull('booked_at');
        $payload=['counts'=>$stats,'total'=>(clone $booked)->count(),'cod'=>(clone $booked)->sum('cod_amount'),'collected'=>(clone $booked)->sum('collected_cod'),'collectionVerified'=>(clone $booked)->whereNotNull('collected_cod')->count(),'filters'=>$d,'bookings'=>$q->with('courier:id,name')->latest()->paginate(25)->withQueryString()];
        if ($r->expectsJson()) return response()->json($payload);
        return Inertia::render('app/modules/courier/pages/Dashboard',$payload);
    }
}
