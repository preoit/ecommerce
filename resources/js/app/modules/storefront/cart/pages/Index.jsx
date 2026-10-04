import { Head, Link } from '@inertiajs/react';
import { LoaderCircle, Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import { useState } from 'react';
import StorefrontLayout from '@/app/layouts/StorefrontLayout';

const money = value => `৳${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

function QuantityControl({ item, busy, onChange }) {
    const step = Number(item.quantity_step || 1);
    const minimum = Number(item.min_quantity || 1);
    const decrement = Number(item.quantity) - step;
    const increment = Number(item.quantity) + step;
    const atMaximum = item.max_quantity && increment > Number(item.max_quantity);

    return <div className="inline-flex h-11 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-label="Quantity">
        <button type="button" disabled={busy || decrement < minimum} onClick={() => onChange(item, decrement)} className="grid w-10 place-items-center text-slate-600 transition hover:bg-violet-50 hover:text-violet-700 disabled:cursor-not-allowed disabled:opacity-30" aria-label="Decrease quantity"><Minus className="size-4" /></button>
        <span className="grid min-w-11 place-items-center border-x border-slate-200 bg-slate-50 px-2 text-sm font-black text-slate-900" aria-live="polite">{busy ? <LoaderCircle className="size-4 animate-spin text-violet-600" /> : item.quantity}</span>
        <button type="button" disabled={busy || atMaximum} onClick={() => onChange(item, increment)} className="grid w-10 place-items-center text-slate-600 transition hover:bg-violet-50 hover:text-violet-700 disabled:cursor-not-allowed disabled:opacity-30" aria-label="Increase quantity"><Plus className="size-4" /></button>
    </div>;
}

function CartItem({ item, pending, onChange, onRemove }) {
    const busy = pending === item.cart_key;

    return <article className={`relative overflow-hidden rounded-2xl border bg-white p-4 shadow-sm transition sm:p-5 ${busy ? 'border-violet-200 opacity-75' : 'border-slate-200 hover:border-violet-200'}`} aria-busy={busy}>
        <div className="flex gap-4">
            <Link href={route('storefront.products.show', item.slug)} className="shrink-0">
                {item.image
                    ? <img src={item.image} alt={item.title} className="size-24 rounded-xl border border-slate-100 bg-slate-50 object-contain p-1 sm:size-28" />
                    : <span className="grid size-24 place-items-center rounded-xl bg-slate-100 text-slate-400 sm:size-28"><ShoppingBag className="size-7" /></span>}
            </Link>
            <div className="min-w-0 flex-1">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <Link href={route('storefront.products.show', item.slug)} className="line-clamp-2 text-[15px] font-bold leading-6 text-slate-950 transition hover:text-violet-700 sm:text-base">{item.title}</Link>
                        {(item.variant_name || item.sku) && <div className="mt-2 flex flex-wrap gap-1.5">
                            {item.variant_name && <span className="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">{item.variant_name}</span>}
                            {item.sku && <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">SKU: {item.sku}</span>}
                        </div>}
                    </div>
                    <button type="button" disabled={busy} onClick={() => onRemove(item)} className="inline-flex size-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-40" aria-label={`Remove ${item.title}`}><Trash2 className="size-[18px]" /></button>
                </div>
                <p className="mt-3 text-sm font-semibold text-slate-500">Unit price <span className="ml-1 font-black text-violet-700">{money(item.unit_price)}</span></p>
            </div>
        </div>
        <div className="mt-4 grid grid-cols-[auto_1fr] items-end gap-4 border-t border-slate-100 pt-4 sm:ml-32 sm:grid-cols-[auto_1fr_auto] sm:border-0 sm:pt-0">
            <div><p className="mb-1.5 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Quantity</p><QuantityControl item={item} busy={busy} onChange={onChange} /></div>
            {item.max_quantity && <p className="hidden text-xs font-semibold text-slate-400 sm:block">Maximum {item.max_quantity} available</p>}
            <div className="text-right"><p className="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Item total</p><p className="mt-1 text-lg font-black text-slate-950">{money(item.line_total)}</p></div>
        </div>
    </article>;
}

export default function CartPage({ items: initialItems = [], subtotal: initialSubtotal = 0, cartNotice: initialNotice = '' }) {
    const [items, setItems] = useState(initialItems);
    const [subtotal, setSubtotal] = useState(initialSubtotal);
    const [pending, setPending] = useState(null);
    const [notice, setNotice] = useState(initialNotice || '');

    const sync = summary => {
        setItems(summary.items || []);
        setSubtotal(summary.subtotal || 0);
        if (summary.cartNotice) setNotice(summary.cartNotice);
        window.dispatchEvent(new CustomEvent('cart:updated', { detail: { count: summary.cartCount || 0 } }));
    };

    const request = async (url, method, body) => {
        const response = await fetch(url, {
            method,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: body ? JSON.stringify(body) : undefined,
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || Object.values(result.errors || {})[0]?.[0] || 'Could not update cart.');

        return result;
    };

    const changeQuantity = async (item, quantity) => {
        setPending(item.cart_key);
        setNotice('');
        try {
            sync(await request(route('storefront.cart.update', item.cart_key), 'PATCH', { quantity }));
        } catch (error) {
            setNotice(error.message);
        } finally {
            setPending(null);
        }
    };

    const remove = async item => {
        setPending(item.cart_key);
        setNotice('');
        try {
            sync(await request(route('storefront.cart.remove', item.cart_key), 'DELETE'));
        } catch (error) {
            setNotice(error.message);
        } finally {
            setPending(null);
        }
    };

    const itemCount = items.reduce((total, item) => total + Number(item.quantity || 0), 0);

    return <StorefrontLayout>
        <Head title="Shopping Cart" />
        <main className="mx-auto max-w-[1280px] px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
            <header className="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-5">
                <div><p className="text-sm font-bold text-violet-600">Your order</p><h1 className="mt-1 text-3xl font-black tracking-tight text-slate-950">Shopping Cart</h1></div>
                <p className="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-600">{itemCount} {itemCount === 1 ? 'item' : 'items'}</p>
            </header>

            {notice && <p className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-sm font-semibold text-amber-800" role="alert">{notice}</p>}

            {items.length ? <div className="mt-6 grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_360px]">
                <section className="space-y-3" aria-label="Cart items">
                    {items.map(item => <CartItem key={item.cart_key} item={item} pending={pending} onChange={changeQuantity} onRemove={remove} />)}
                </section>

                <aside className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-24 sm:p-6">
                    <h2 className="text-xl font-black text-slate-950">Order Summary</h2>
                    <dl className="mt-5 space-y-4">
                        <div className="flex items-center justify-between gap-4 text-sm"><dt className="font-semibold text-slate-500">Subtotal</dt><dd className="font-bold text-slate-900">{money(subtotal)}</dd></div>
                        <div className="flex items-center justify-between gap-4 border-t border-dashed border-slate-300 pt-4"><dt className="text-lg font-black text-slate-950">Total</dt><dd className="text-xl font-black text-violet-700">{money(subtotal)}</dd></div>
                    </dl>
                    <p className="mt-4 rounded-xl bg-slate-50 px-3.5 py-3 text-xs font-medium leading-5 text-slate-500">Delivery is calculated at checkout after you select your district.</p>
                    <Link href={route('storefront.checkout')} className="mt-5 flex h-12 items-center justify-center rounded-xl bg-violet-600 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-violet-700">Proceed to Checkout</Link>
                    <Link href={route('storefront.products.index')} className="mt-3 flex h-11 items-center justify-center rounded-xl border border-slate-200 text-sm font-bold text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700">Continue Shopping</Link>
                </aside>
            </div> : <div className="mt-7 grid min-h-80 place-items-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                <div><span className="mx-auto grid size-20 place-items-center rounded-full bg-white text-violet-600 shadow-sm"><ShoppingBag className="size-9" /></span><h2 className="mt-5 text-xl font-black text-slate-950">Your cart is empty</h2><p className="mt-2 text-sm text-slate-500">Add products to your cart and they will appear here.</p><Link href={route('storefront.products.index')} className="mt-6 inline-flex h-11 items-center rounded-xl bg-violet-600 px-5 text-sm font-bold text-white hover:bg-violet-700">Browse Products</Link></div>
            </div>}
        </main>
    </StorefrontLayout>;
}
