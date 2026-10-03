import { Head, Link, router } from '@inertiajs/react';
import ListingProductCard from '@/app/components/ListingProductCard';
import { Heart, PackageSearch, ShoppingBag } from 'lucide-react';
import CustomerShell from '../components/CustomerShell';


export default function Wishlist({ products = [] }) {
    const remove = product => router.post(route('storefront.products.wishlist', product.id), {}, { preserveScroll: true });

    return <CustomerShell title="My Wishlist">
        <Head title="My Wishlist" />
        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-5 sm:p-6">
                <div>
                    <div className="flex items-center gap-2"><Heart className="size-5 fill-violet-100 text-violet-600" /><h2 className="text-xl font-black">Saved products</h2></div>
                    <p className="mt-1 text-sm text-slate-500">{products.length} {products.length === 1 ? 'product' : 'products'} saved for later.</p>
                </div>
                {products.length > 0 && <Link href={route('storefront.products.index')} className="inline-flex items-center gap-2 rounded-xl border border-violet-200 px-4 py-2.5 text-sm font-bold text-violet-700 transition hover:bg-violet-50"><ShoppingBag className="size-4" />Continue shopping</Link>}
            </header>
            {products.length > 0 ? <div className="grid gap-5 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
                {products.map(product => <ListingProductCard key={product.id} product={product} onRemove={remove}/>)}
            </div> : <div className="grid min-h-[360px] place-items-center p-8 text-center"><div><span className="mx-auto grid size-20 place-items-center rounded-full bg-violet-50 text-violet-600"><PackageSearch className="size-9" /></span><h2 className="mt-5 text-2xl font-black">Your wishlist is empty</h2><p className="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">Save products you like and find them here whenever you are ready to order.</p><Link href={route('storefront.products.index')} className="mt-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-violet-200 transition hover:-translate-y-0.5"><ShoppingBag className="size-4" />Browse products</Link></div></div>}
        </section>
    </CustomerShell>;
}
