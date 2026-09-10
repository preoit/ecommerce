<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminCustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $base = User::query()->where('is_admin', false);
        $customers = (clone $base)
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->withCount(['addresses', 'orders'])
            ->withCount(['orders as completed_orders_count' => fn (Builder $query) => $query->where('status', 'completed')])
            ->withSum(['orders as total_spent' => fn (Builder $query) => $query->where('status', 'completed')], 'total')
            ->withMax('orders as last_order_at', 'created_at')
            ->latest('id')->paginate(15)->withQueryString()
            ->through(fn (User $customer) => $this->customerRow($customer));

        return Inertia::render('app/modules/customers/pages/Index', [
            'customers' => $customers,
            'filters' => ['search' => $search],
            'stats' => [
                'registered' => (clone $base)->count(),
                'verified' => (clone $base)->where(fn (Builder $query) => $query->whereNotNull('email_verified_at')->orWhereNotNull('phone_verified_at'))->count(),
                'withOrders' => (clone $base)->whereHas('orders')->count(),
                'totalSpent' => (float) DB::table('orders')->join('users', 'users.id', '=', 'orders.user_id')->where('users.is_admin', false)->where('orders.status', 'completed')->sum('orders.total'),
            ],
        ]);
    }

    public function show(User $customer): Response
    {
        $this->customer($customer);
        $orders = DB::table('orders')->where('user_id', $customer->id)->latest()->get();
        $addresses = DB::table('customer_addresses')->where('user_id', $customer->id)->latest('is_default')->latest()->get();
        $reviews = DB::table('product_reviews')->leftJoin('products', 'products.id', '=', 'product_reviews.product_id')->where('product_reviews.user_id', $customer->id)->latest('product_reviews.id')->get(['product_reviews.id','product_reviews.rating','product_reviews.title','product_reviews.description','product_reviews.status','product_reviews.verified_purchase','product_reviews.created_at','products.title as product_title','products.slug as product_slug']);
        $wishlist = DB::table('wishlists')->join('products', 'products.id', '=', 'wishlists.product_id')->where('wishlists.user_id', $customer->id)->latest('wishlists.id')->get(['wishlists.id','wishlists.created_at','products.title','products.slug','products.featured_image_path']);
        $recent = DB::table('recently_viewed_products')->join('products', 'products.id', '=', 'recently_viewed_products.product_id')->where('recently_viewed_products.user_id', $customer->id)->latest('recently_viewed_products.viewed_at')->limit(50)->get(['recently_viewed_products.id','recently_viewed_products.viewed_at','products.title','products.slug','products.featured_image_path']);
        $completed = $orders->where('status', 'completed');

        return Inertia::render('app/modules/customers/pages/Show', [
            'customer' => [
                'id'=>$customer->id,'name'=>$customer->name,'email'=>$customer->email,'phone'=>$customer->phone,
                'emailVerified'=>$customer->email_verified_at !== null,'phoneVerified'=>$customer->phone_verified_at !== null,
                'registeredAt'=>$this->date($customer->created_at),'updatedAt'=>$this->date($customer->updated_at),
                'canDelete'=>$orders->isEmpty() && $customer->email_verified_at === null && $customer->phone_verified_at === null,
            ],
            'stats' => [
                'orders'=>$orders->count(),'completed'=>$completed->count(),'cancelled'=>$orders->where('status', 'cancelled')->count(),
                'spent'=>(float)$completed->sum('total'),'addresses'=>$addresses->count(),'reviews'=>$reviews->count(),'wishlist'=>$wishlist->count(),'recentlyViewed'=>$recent->count(),
            ],
            'orders' => $orders->map(fn ($order) => ['id'=>$order->id,'number'=>$order->order_number,'status'=>$order->status,'paymentStatus'=>$order->payment_status,'total'=>(float)$order->total,'date'=>$this->date($order->created_at)]),
            'addresses' => $addresses->map(fn ($address) => ['id'=>$address->id,'label'=>$address->label,'recipient'=>$address->recipient_name,'phone'=>$address->phone,'zone'=>$address->delivery_zone,'district'=>$address->district,'city'=>$address->city,'area'=>$address->area,'address'=>$address->address,'postalCode'=>$address->postal_code,'landmark'=>$address->landmark,'isDefault'=>(bool)$address->is_default]),
            'reviews' => $reviews->map(fn ($review) => ['id'=>$review->id,'product'=>$review->product_title,'slug'=>$review->product_slug,'rating'=>$review->rating,'title'=>$review->title,'description'=>$review->description,'status'=>$review->status,'verifiedPurchase'=>(bool)$review->verified_purchase,'date'=>$this->date($review->created_at)]),
            'wishlist' => $wishlist->map(fn ($item) => ['id'=>$item->id,'product'=>$item->title,'slug'=>$item->slug,'image'=>$this->image($item->featured_image_path),'date'=>$this->date($item->created_at)]),
            'recentlyViewed' => $recent->map(fn ($item) => ['id'=>$item->id,'product'=>$item->title,'slug'=>$item->slug,'image'=>$this->image($item->featured_image_path),'date'=>$this->date($item->viewed_at)]),
        ]);
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        $this->customer($customer);
        $data = $request->validate([
            'name'=>['required','string','max:255'],
            'email'=>['nullable','string','lowercase','email','max:255',Rule::unique('users','email')->ignore($customer->id)],
            'phone'=>['required','string','regex:/^\+?[0-9]{10,15}$/',Rule::unique('users','phone')->ignore($customer->id)],
        ]);
        $customer->fill($data);
        if ($customer->isDirty('email')) $customer->email_verified_at = null;
        if ($customer->isDirty('phone')) $customer->phone_verified_at = null;
        $customer->save();
        return back()->with('success', 'Customer information updated successfully.');
    }

    public function destroy(User $customer): RedirectResponse
    {
        $this->customer($customer);
        if ($customer->orders()->exists() || $customer->email_verified_at || $customer->phone_verified_at) {
            throw ValidationException::withMessages(['customer' => 'Only an unverified customer with no purchase history can be deleted.']);
        }
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer account deleted successfully.');
    }

    private function customerRow(User $customer): array
    {
        return ['id'=>$customer->id,'name'=>$customer->name,'email'=>$customer->email,'phone'=>$customer->phone,'emailVerified'=>$customer->email_verified_at !== null,'phoneVerified'=>$customer->phone_verified_at !== null,'orders'=>(int)$customer->orders_count,'completedOrders'=>(int)$customer->completed_orders_count,'spent'=>(float)($customer->total_spent??0),'addresses'=>(int)$customer->addresses_count,'registeredAt'=>$this->date($customer->created_at),'lastOrderAt'=>$customer->last_order_at?$this->date($customer->last_order_at):null,'canDelete'=>(int)$customer->orders_count===0&&$customer->email_verified_at===null&&$customer->phone_verified_at===null];
    }
    private function customer(User $customer): void { abort_if($customer->is_admin, 404); }
    private function date($value): ?string { return $value ? \Illuminate\Support\Carbon::parse($value)->timezone('Asia/Dhaka')->format('d M Y, h:i A') : null; }
    private function image(?string $path): ?string { return $path ? '/image/'.rawurlencode(basename($path)) : null; }
}