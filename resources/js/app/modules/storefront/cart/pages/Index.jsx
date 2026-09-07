import { Head, Link } from '@inertiajs/react';
import { Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import { useState } from 'react';
import StorefrontLayout from '@/app/layouts/StorefrontLayout';

const money = value => `৳${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

export default function CartPage({ items: initialItems = [], subtotal: initialSubtotal = 0 }) {
    const [items, setItems] = useState(initialItems);
    const [subtotal, setSubtotal] = useState(initialSubtotal);
    const [pending, setPending] = useState(null);
    const [notice, setNotice] = useState('');
    const sync = summary => {
        setItems(summary.items || []); setSubtotal(summary.subtotal || 0);
        window.dispatchEvent(new CustomEvent('cart:updated', { detail: { count: summary.cartCount || 0 } }));
    };
    const request = async (url, method, body) => {
        const response = await fetch(url, { method, headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: body ? JSON.stringify(body) : undefined });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || Object.values(result.errors || {})[0]?.[0] || 'Could not update cart.');
        return result;
    };
    const changeQuantity = async (item, quantity) => {
        if (quantity < 1 || (item.max_quantity && quantity > item.max_quantity)) return;
        setPending(item.product_id); setNotice('');
        try { sync(await request(route('storefront.cart.update', item.product_id), 'PATCH', { quantity })); } catch (error) { setNotice(error.message); } finally { setPending(null); }
    };
    const remove = async item => {
        setPending(item.product_id); setNotice('');
        try { sync(await request(route('storefront.cart.remove', item.product_id), 'DELETE')); } catch (error) { setNotice(error.message); } finally { setPending(null); }
    };
    return <StorefrontLayout><Head title="Shopping cart" /><div className="mx-auto max-w-[1180px] px-4 py-10 sm:px-6 lg:px-8"><h1 className="text-3xl font-bold text-slate-950">Shopping cart</h1>{notice && <p className="mt-4 rounded-lg bg-rose-50 p-3 text-sm font-semibold text-rose-700">{notice}</p>}{items.length ? <div className="mt-7 grid gap-7 lg:grid-cols-[1fr_340px]"><section className="overflow-hidden rounded-2xl border border-slate-200 bg-white"><div className="divide-y divide-slate-200">{items.map(item => <article key={item.product_id} className="flex gap-4 p-4 sm:p-5">{item.image ? <img src={item.image} alt={item.title} className="size-20 rounded-xl border border-slate-100 object-cover sm:size-24" /> : <div className="grid size-20 place-items-center rounded-xl bg-slate-100 text-slate-400 sm:size-24"><ShoppingBag /></div>}<div className="min-w-0 flex-1"><Link href={route('storefront.products.show', item.slug)} className="font-bold text-slate-900 hover:text-violet-700">{item.title}</Link><p className="mt-1 text-sm text-violet-700">{money(item.unit_price)}</p><div className="mt-4 flex items-center justify-between gap-3"><div className="flex h-10 items-center rounded-lg border border-slate-300"><button disabled={pending === item.product_id} onClick={() => changeQuantity(item, item.quantity - 1)} className="grid size-9 place-items-center disabled:opacity-40"><Minus className="size-4" /></button><span className="w-9 text-center text-sm font-semibold">{item.quantity}</span><button disabled={pending === item.product_id} onClick={() => changeQuantity(item, item.quantity + 1)} className="grid size-9 place-items-center disabled:opacity-40"><Plus className="size-4" /></button></div><div className="flex items-center gap-4"><b>{money(item.line_total)}</b><button disabled={pending === item.product_id} onClick={() => remove(item)} className="text-slate-400 hover:text-rose-600 disabled:opacity-40" aria-label={`Remove ${item.title}`}><Trash2 className="size-5" /></button></div></div></div></article>)}</div></section><aside className="h-fit rounded-2xl border border-slate-200 bg-white p-6"><h2 className="text-xl font-bold">Order summary</h2><div className="mt-5 flex justify-between border-b border-slate-200 pb-4 text-slate-600"><span>Subtotal</span><b className="text-slate-950">{money(subtotal)}</b></div><div className="mt-4 flex justify-between text-lg font-bold"><span>Total</span><span>{money(subtotal)}</span></div><p className="mt-2 text-xs text-slate-500">Delivery charge will be confirmed before delivery.</p><Link href={route('storefront.checkout')} className="mt-6 flex h-12 items-center justify-center rounded-xl bg-violet-600 font-bold text-white hover:bg-violet-700">Proceed to checkout</Link><Link href={route('storefront.products.index')} className="mt-4 block text-center text-sm font-semibold text-violet-700 hover:underline">Continue shopping</Link></aside></div> : <div className="mt-7 grid min-h-64 place-items-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center"><div><ShoppingBag className="mx-auto size-10 text-slate-400" /><h2 className="mt-4 text-xl font-bold">Your cart is empty</h2><Link href={route('storefront.products.index')} className="mt-5 inline-flex rounded-xl bg-violet-600 px-5 py-3 font-semibold text-white">Browse products</Link></div></div>}</div></StorefrontLayout>;
}
