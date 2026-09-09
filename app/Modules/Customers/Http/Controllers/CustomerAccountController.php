<?php
namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAccountController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $orders = DB::table('orders')->where('user_id', $request->user()->id);
        $recent = (clone $orders)->latest()->limit(5)->get()->map(fn ($order) => $this->orderData($order));
        $recentIds = DB::table('recently_viewed_products')->where('user_id', $request->user()->id)->latest('viewed_at')->limit(4)->pluck('product_id');
        $products = DB::table('products')->whereIn('id', $recentIds)->get(['id','title','slug','current_price','featured_image_path'])->map(fn ($p) => ['id'=>$p->id,'title'=>$p->title,'slug'=>$p->slug,'price'=>(float)$p->current_price,'image'=>$p->featured_image_path ? '/image/'.rawurlencode(basename($p->featured_image_path)) : null]);
        return Inertia::render('app/modules/customers/pages/Dashboard', [
            'stats' => ['all'=>(clone $orders)->count(),'progress'=>(clone $orders)->whereIn('status',['pending','confirmed','processing','ready_to_ship','shipped'])->count(),'completed'=>(clone $orders)->where('status','completed')->count(),'spent'=>(float)(clone $orders)->where('status','completed')->sum('total')],
            'recentOrders'=>$recent, 'recentProducts'=>$products,
        ]);
    }

    public function orders(Request $request): Response
    {
        $orders = DB::table('orders')->where('user_id',$request->user()->id)->latest()->paginate(10)->through(fn ($order) => $this->orderData($order));
        return Inertia::render('app/modules/customers/pages/Orders', ['orders'=>$orders]);
    }

    public function order(Request $request, int $order): Response
    {
        $record=DB::table('orders')->where('user_id',$request->user()->id)->find($order); abort_unless($record,404);
        $items=DB::table('order_items')->where('order_id',$record->id)->get()->map(fn($item)=>['title'=>$item->product_title,'variant'=>$item->variant_name??null,'sku'=>$item->sku,'quantity'=>$item->quantity,'price'=>(float)$item->unit_price,'total'=>(float)$item->line_total]);
        return Inertia::render('app/modules/customers/pages/OrderShow',['order'=>[...$this->orderData($record),'subtotal'=>(float)$record->subtotal,'shipping'=>(float)$record->shipping_total,'cod'=>(float)($record->cod_surcharge??0),'phone'=>$record->phone,'email'=>$record->email,'address'=>$record->address,'city'=>$record->city,'zone'=>$record->delivery_zone,'items'=>$items]]);
    }

    public function addresses(Request $request): Response
    {
        return Inertia::render('app/modules/customers/pages/Addresses',['addresses'=>$request->user()->addresses()->latest('is_default')->latest()->get()]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data=$this->validateAddress($request); $user=$request->user();
        DB::transaction(function() use($user,$data){ if(($data['is_default']??false)||!$user->addresses()->exists()){$user->addresses()->update(['is_default'=>false]);$data['is_default']=true;} $user->addresses()->create($data); });
        return back()->with('success','Delivery address saved.');
    }

    public function updateAddress(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->own($request,$address); $data=$this->validateAddress($request);
        DB::transaction(function() use($request,$address,$data){if($data['is_default']??false)$request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default'=>false]);$address->update($data);});
        return back()->with('success','Delivery address updated.');
    }

    public function defaultAddress(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->own($request,$address); DB::transaction(function() use($request,$address){$request->user()->addresses()->update(['is_default'=>false]);$address->update(['is_default'=>true]);});
        return back()->with('success','Default address updated.');
    }

    public function destroyAddress(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->own($request,$address); $wasDefault=$address->is_default; $address->delete(); if($wasDefault)$request->user()->addresses()->latest()->first()?->update(['is_default'=>true]);
        return back()->with('success','Delivery address removed.');
    }

    public function reviews(Request $request): Response
    {
        $reviews=DB::table('product_reviews')->leftJoin('products','products.id','=','product_reviews.product_id')->where('product_reviews.user_id',$request->user()->id)->latest('product_reviews.id')->get(['product_reviews.*','products.title as product_title']);
        return Inertia::render('app/modules/customers/pages/Reviews',['reviews'=>$reviews]);
    }

    private function validateAddress(Request $request): array { return $request->validate(['label'=>['required','string','max:60'],'recipient_name'=>['required','string','max:120'],'phone'=>['required','string','max:30'],'delivery_zone'=>['required',Rule::in(['inside_dhaka','outside_dhaka'])],'district'=>['required','string','max:120'],'city'=>['required','string','max:120'],'area'=>['nullable','string','max:120'],'address'=>['required','string','max:1000'],'postal_code'=>['nullable','string','max:30'],'landmark'=>['nullable','string','max:255'],'is_default'=>['boolean']]); }
    private function own(Request $request, CustomerAddress $address): void { abort_unless($address->user_id===$request->user()->id,404); }
    private function orderData(object $o): array { return ['id'=>$o->id,'number'=>$o->order_number,'status'=>$o->status,'total'=>(float)$o->total,'date'=>date('d M Y, h:i A',strtotime($o->created_at))]; }
}