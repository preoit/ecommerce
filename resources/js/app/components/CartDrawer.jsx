import { Link } from '@inertiajs/react';
import { ArrowRight, LoaderCircle, Minus, PackageOpen, Plus, ShoppingBag, Trash2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const money = value => `৳${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

function DrawerItem({ item, pending, onChange, onRemove, onClose }) {
    const busy = pending === item.cart_key;
    const step = Number(item.quantity_step || 1);
    const minimum = Number(item.min_quantity || 1);
    const decrement = Number(item.quantity) - step;
    const increment = Number(item.quantity) + step;
    const atMaximum = item.max_quantity && increment > Number(item.max_quantity);

    return <article className={`rounded-2xl border bg-white p-3.5 transition ${busy ? 'border-violet-200 opacity-75' : 'border-slate-200'}`} aria-busy={busy}>
        <div className="flex gap-3">
            <Link href={route('storefront.products.show', item.slug)} onClick={onClose} className="shrink-0">
                {item.image
                    ? <img src={item.image} alt={item.title} className="size-[84px] rounded-xl border border-slate-100 bg-slate-50 object-contain p-1" />
                    : <span className="grid size-[84px] place-items-center rounded-xl bg-slate-100 text-slate-400"><ShoppingBag className="size-6" /></span>}
            </Link>
            <div className="min-w-0 flex-1">
                <div className="flex items-start gap-2">
                    <Link href={route('storefront.products.show', item.slug)} onClick={onClose} className="line-clamp-2 flex-1 text-sm font-bold leading-5 text-slate-950 transition hover:text-violet-700">{item.title}</Link>
                    <button type="button" disabled={busy} onClick={() => onRemove(item)} className="grid size-8 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-40" aria-label={`Remove ${item.title}`}><Trash2 className="size-4" /></button>
                </div>
                {(item.variant_name || item.sku) && <div className="mt-1.5 flex flex-wrap gap-1">
                    {item.variant_name && <span className="rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700">{item.variant_name}</span>}
                    {item.sku && <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">SKU: {item.sku}</span>}
                </div>}
                <p className="mt-2 text-sm font-black text-violet-700">{money(item.unit_price)} <span className="font-medium text-slate-400">each</span></p>
            </div>
        </div>
        <div className="mt-3 flex items-end justify-between gap-3 border-t border-slate-100 pt-3">
            <div>
                <p className="mb-1 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">Quantity</p>
                <div className="inline-flex h-9 overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <button type="button" disabled={busy || decrement < minimum} onClick={() => onChange(item, decrement)} className="grid w-9 place-items-center text-slate-600 hover:bg-violet-50 hover:text-violet-700 disabled:opacity-30" aria-label="Decrease quantity"><Minus className="size-3.5" /></button>
                    <span className="grid min-w-9 place-items-center border-x border-slate-200 bg-slate-50 px-2 text-xs font-black" aria-live="polite">{busy ? <LoaderCircle className="size-3.5 animate-spin text-violet-600" /> : item.quantity}</span>
                    <button type="button" disabled={busy || atMaximum} onClick={() => onChange(item, increment)} className="grid w-9 place-items-center text-slate-600 hover:bg-violet-50 hover:text-violet-700 disabled:opacity-30" aria-label="Increase quantity"><Plus className="size-3.5" /></button>
                </div>
            </div>
            <div className="text-right"><p className="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">Item total</p><b className="mt-1 block text-base text-slate-950">{money(item.line_total)}</b></div>
        </div>
    </article>;
}

export default function CartDrawer({ open, onClose }) {
    const [summary, setSummary] = useState({ items: [], subtotal: 0, cartCount: 0 });
    const [loading, setLoading] = useState(false);
    const [pending, setPending] = useState(null);
    const [error, setError] = useState('');
    const closeButton = useRef(null);

    const sync = next => {
        setSummary(next);
        window.dispatchEvent(new CustomEvent('cart:updated', { detail: { count: next.cartCount || 0, drawerSync: true } }));
    };

    const load = async () => {
        setLoading(true);
        setError('');
        try {
            const response = await fetch(route('storefront.cart.summary'), { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Could not load your cart.');
            sync(await response.json());
        } catch (issue) {
            setError(issue.message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { if (open) load(); }, [open]);

    useEffect(() => {
        if (!open) return undefined;
        const previous = document.body.style.overflow;
        const escape = event => { if (event.key === 'Escape') onClose(); };
        const focusTimer = window.setTimeout(() => closeButton.current?.focus(), 100);
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', escape);

        return () => {
            window.clearTimeout(focusTimer);
            document.body.style.overflow = previous;
            document.removeEventListener('keydown', escape);
        };
    }, [open, onClose]);

    useEffect(() => {
        const refresh = event => { if (open && !event.detail?.drawerSync) load(); };
        window.addEventListener('cart:updated', refresh);

        return () => window.removeEventListener('cart:updated', refresh);
    }, [open]);

    const request = async (item, method, quantity) => {
        setPending(item.cart_key);
        setError('');
        try {
            const response = await fetch(route(method === 'DELETE' ? 'storefront.cart.remove' : 'storefront.cart.update', item.cart_key), {
                method,
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: quantity === undefined ? undefined : JSON.stringify({ quantity }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || Object.values(result.errors || {})[0]?.[0] || 'Could not update cart.');
            sync(result);
        } catch (issue) {
            setError(issue.message);
        } finally {
            setPending(null);
        }
    };

    return <div className={`fixed inset-0 z-[100] ${open ? 'pointer-events-auto' : 'pointer-events-none'}`} aria-hidden={!open}>
        <button type="button" aria-label="Close shopping cart" onClick={onClose} className={`absolute inset-0 bg-slate-950/45 backdrop-blur-[2px] transition-opacity duration-300 ${open ? 'opacity-100' : 'opacity-0'}`} />
        <aside role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title" className={`absolute inset-y-0 right-0 flex w-full max-w-[530px] flex-col bg-slate-50 transition-transform duration-300 ease-out ${open ? 'translate-x-0 shadow-[-18px_0_55px_rgba(15,23,42,.20)]' : 'translate-x-full shadow-none'}`}>
            <header className="flex h-20 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-5 sm:px-6">
                <div><h2 id="cart-drawer-title" className="text-xl font-black text-slate-950">Shopping Cart</h2><p className="mt-0.5 text-xs font-semibold text-slate-500">{summary.cartCount || 0} {summary.cartCount === 1 ? 'item' : 'items'} in your cart</p></div>
                <button ref={closeButton} type="button" onClick={onClose} className="grid size-10 place-items-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-violet-500" aria-label="Close cart"><X className="size-5" /></button>
            </header>

            <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                {loading ? <div className="space-y-3 p-4 sm:p-5">{[1, 2, 3].map(key => <div key={key} className="flex animate-pulse gap-3 rounded-2xl border border-slate-200 bg-white p-3.5"><div className="size-[84px] rounded-xl bg-slate-100" /><div className="flex-1 space-y-2 py-1"><div className="h-4 w-4/5 rounded bg-slate-100" /><div className="h-3 w-2/5 rounded bg-slate-100" /><div className="h-9 w-28 rounded bg-slate-100" /></div></div>)}</div>
                    : summary.items.length ? <div className="space-y-3 p-4 sm:p-5">{summary.items.map(item => <DrawerItem key={item.cart_key} item={item} pending={pending} onChange={(current, quantity) => request(current, 'PATCH', quantity)} onRemove={current => request(current, 'DELETE')} onClose={onClose} />)}</div>
                        : <div className="grid min-h-full place-items-center px-8 py-16 text-center"><div><span className="mx-auto grid size-20 place-items-center rounded-full bg-violet-50 text-violet-600"><PackageOpen className="size-9" /></span><h3 className="mt-5 text-xl font-black text-slate-950">Your cart is empty</h3><p className="mx-auto mt-2 max-w-xs text-sm leading-6 text-slate-500">Add products to your cart and they will appear here.</p><Link href={route('storefront.products.index')} onClick={onClose} className="mt-6 inline-flex h-11 items-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-bold text-white">Continue Shopping<ArrowRight className="size-4" /></Link></div></div>}
                {error && <p className="mx-4 mb-5 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-700 sm:mx-5" role="alert">{error}</p>}
            </div>

            {summary.items.length > 0 && <footer className="shrink-0 border-t border-slate-200 bg-white px-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-4 shadow-[0_-8px_24px_rgba(15,23,42,.06)] sm:px-6">
                <dl className="space-y-3">
                    <div className="flex items-center justify-between gap-4 text-sm"><dt className="font-semibold text-slate-500">Subtotal</dt><dd className="font-bold text-slate-900">{money(summary.subtotal)}</dd></div>
                    <div className="flex items-center justify-between gap-4 border-t border-dashed border-slate-300 pt-3"><dt className="text-lg font-black text-slate-950">Total</dt><dd className="text-xl font-black text-violet-700">{money(summary.subtotal)}</dd></div>
                </dl>
                <p className="mt-3 text-xs font-medium text-slate-500">Delivery is calculated at checkout after district selection.</p>
                <div className="mt-4 grid grid-cols-2 gap-3">
                    <Link href={route('storefront.cart')} onClick={onClose} className="flex h-12 items-center justify-center rounded-xl border border-slate-200 text-sm font-bold text-slate-700 transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">View Cart</Link>
                    <Link href={route('storefront.checkout')} onClick={onClose} className="flex h-12 items-center justify-center gap-1.5 rounded-xl bg-violet-600 px-3 text-center text-sm font-bold text-white shadow-sm transition hover:bg-violet-700">Checkout<ArrowRight className="size-4" /></Link>
                </div>
            </footer>}
        </aside>
    </div>;
}
