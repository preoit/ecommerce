import { Head, Link, router } from '@inertiajs/react';
import { Heart, PackageSearch, ShoppingBag, Trash2 } from 'lucide-react';
import CustomerShell from '../components/CustomerShell';

const money = value => `৳${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;

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
                {products.map(product => <article key={product.id} className="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 transition hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-lg">
                    <button type="button" onClick={() => remove(product)} className="absolute right-5 top-5 z-10 grid size-9 place-items-center rounded-full border border-rose-100 bg-white/95 text-rose-500 shadow-sm transition hover:bg-rose-500 hover:text-white" aria-label={`Remove ${product.title} from wishlist`}><Trash2 className="size-4" /></button>
                    <Link href={route('storefront.products.show', product.slug)}>
                        <div className="aspect-square overflow-hidden rounded-xl bg-slate-50">{product.image ? <img src={product.image} alt={product.title} className="h-full w-full object-contain p-3 transition duration-300 group-hover:scale-105" /> : <span className="grid h-full place-items-center text-sm text-slate-400">No image</span>}</div>
                        <div className="px-1 pb-2 pt-4"><p className="text-xs font-bold uppercase tracking-wider text-violet-600">{product.brand || 'Products'}</p><h3 className="mt-1 line-clamp-2 min-h-12 font-bold leading-6 text-slate-900">{product.title}</h3><div className="mt-3 flex flex-wrap items-center gap-2"><strong className="text-lg text-violet-600">{money(product.price)}</strong>{product.regularPrice > product.price && <del className="text-sm text-slate-400">{money(product.regularPrice)}</del>}{product.discount > 0 && <span className="rounded-full bg-rose-50 px-2 py-1 text-xs font-bold text-rose-600">-{product.discount}%</span>}</div></div>
                    </Link>
                </article>)}
            </div> : <div className="grid min-h-[360px] place-items-center p-8 text-center"><div><span className="mx-auto grid size-20 place-items-center rounded-full bg-violet-50 text-violet-600"><PackageSearch className="size-9" /></span><h2 className="mt-5 text-2xl font-black">Your wishlist is empty</h2><p className="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">Save products you like and find them here whenever you are ready to order.</p><Link href={route('storefront.products.index')} className="mt-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-violet-200 transition hover:-translate-y-0.5"><ShoppingBag className="size-4" />Browse products</Link></div></div>}
        </section>
    </CustomerShell>;
}