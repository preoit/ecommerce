import { Head, router, useForm } from '@inertiajs/react';
import { AlertCircle, ArrowRight, Info, MapPin, Minus, Plus, Truck, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import CheckoutLayout from '@/app/layouts/CheckoutLayout';
import { bangladeshDistricts, detectDeliveryZone, thanasByDistrict } from '@/app/utils/deliveryZone';

const money = value => `৳${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;

function FloatingField({ name, label, value, error, onChange, type = 'text', required = false, children }) {
    return <label className="block min-w-0" data-field={name}><span className={`relative block rounded-xl border bg-white transition focus-within:ring-2 ${error ? 'border-rose-500 focus-within:border-rose-500 focus-within:ring-rose-100' : 'border-slate-300 focus-within:border-violet-500 focus-within:ring-violet-100'}`}><span className={`pointer-events-none absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 text-[11px] font-medium ${error ? 'text-rose-600' : 'text-slate-500'}`}>{label}{required && <span className="ml-0.5 text-rose-500">*</span>}</span>{children || <input name={name} value={value} type={type} onChange={event => onChange(event.target.value)} aria-invalid={Boolean(error)} aria-describedby={error ? `${name}-error` : undefined} className="h-12 w-full rounded-xl border-0 bg-transparent px-4 text-sm text-slate-900 focus:ring-0" />}{error && <AlertCircle className="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-rose-500" />}</span>{error && <small id={`${name}-error`} className="mt-1.5 block text-xs font-medium text-rose-600">{error}</small>}</label>;
}

export default function CheckoutPage({ items: initialItems = [], subtotal: initialSubtotal = 0, deliverySettings = {}, addresses = [], customer = null }) {
    const defaultAddress = addresses.find(address => address.is_default) || null;
    const { data, setData } = useForm({ customer_name: defaultAddress?.recipient_name || customer?.name || '', phone: defaultAddress?.phone || customer?.phone || '', email: customer?.email || '', city: defaultAddress?.city || '', address: defaultAddress?.address || '', note: '', delivery_zone: defaultAddress?.delivery_zone || 'inside_dhaka', payment_method: 'cod', address_id: defaultAddress?.id || null, save_address: false, address_label: 'Home', district: defaultAddress?.district || '', area: defaultAddress?.area || '', postal_code: defaultAddress?.postal_code || '', landmark: defaultAddress?.landmark || '' });
    const chooseAddress = address => setData({ ...data, address_id: address.id, customer_name: address.recipient_name, phone: address.phone, city: address.city, address: address.address, delivery_zone: address.delivery_zone, district: address.district || '', area: address.area || '', postal_code: address.postal_code || '', landmark: address.landmark || '' });
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState({});
    const [items, setItems] = useState(initialItems);
    const [subtotal, setSubtotal] = useState(Number(initialSubtotal));
    const [pendingItem, setPendingItem] = useState(null);
    const [cartNotice, setCartNotice] = useState('');
    useEffect(() => {
        if (!data.address_id && (data.district || data.city || data.address)) {
            setData('delivery_zone', detectDeliveryZone(data));
        }
    }, [data.address_id, data.district, data.city, data.address]);
    useEffect(() => {
        const first = Object.keys(errors)[0];
        if (!first) return;
        const field = document.querySelector(`[data-field="${first}"]`);
        field?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        field?.querySelector('input, select, textarea')?.focus({ preventScroll: true });
    }, [errors]);
    const updateField = (key, value) => {
        setData(key, value);
        if (errors[key]) setErrors(current => { const next = { ...current }; delete next[key]; return next; });
    };
    const updateCart = async (item, quantity = null) => {
        setPendingItem(item.cart_key); setCartNotice('');
        try {
            const response = await fetch(route(quantity === null ? 'storefront.cart.remove' : 'storefront.cart.update', item.cart_key), { method: quantity === null ? 'DELETE' : 'PATCH', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: quantity === null ? undefined : JSON.stringify({ quantity }) });
            const result = await response.json(); if (!response.ok) throw new Error(result.message || 'Cart could not be updated.');
            setItems(result.items || []); setSubtotal(Number(result.subtotal || 0)); if (!(result.items || []).length) router.visit(route('storefront.cart'));
        } catch (error) { setCartNotice(error.message); } finally { setPendingItem(null); }
    };
    const delivery = useMemo(() => {
        if (!deliverySettings.enabled) return { shipping: 0, cod: 0, total: 0, free: true, heavy: 0 };
        const zoneField = data.delivery_zone === 'inside_dhaka' ? 'delivery_inside_dhaka' : 'delivery_outside_dhaka';
        const base = Number(data.delivery_zone === 'inside_dhaka' ? deliverySettings.insideDhaka : deliverySettings.outsideDhaka);
        const override = deliverySettings.productOverrideEnabled ? Math.max(0, ...items.map(item => Number(item[zoneField] || 0))) : 0;
        const weight = items.reduce((sum, item) => sum + Number(item.weight || 0) * Number(item.quantity || 0), 0);
        const heavy = deliverySettings.heavyEnabled && weight > Number(deliverySettings.heavyThreshold || 0) ? Math.ceil(weight - Number(deliverySettings.heavyThreshold || 0)) * Number(deliverySettings.heavyPerKg || 0) : 0;
        const free = deliverySettings.freeEnabled && deliverySettings.freeThreshold !== null && Number(subtotal) >= Number(deliverySettings.freeThreshold);
        const shipping = free ? 0 : (override || base) + heavy;
        const cod = deliverySettings.codEnabled && data.payment_method === 'cod' ? Number(deliverySettings.codSurcharge || 0) : 0;
        return { shipping, cod, total: shipping + cod, free, heavy };
    }, [data.delivery_zone, data.payment_method, deliverySettings, items, subtotal]);
    const thanaOptions = thanasByDistrict[data.district] || (data.delivery_zone === 'inside_dhaka' ? thanasByDistrict.Dhaka : null);
    const deliveryAmountFor = zone => {
        if (!deliverySettings.enabled) return 'Free';
        if (deliverySettings.freeEnabled && deliverySettings.freeThreshold !== null && Number(subtotal) >= Number(deliverySettings.freeThreshold)) return 'Free';
        const field = zone === 'inside_dhaka' ? 'delivery_inside_dhaka' : 'delivery_outside_dhaka';
        const base = Number(zone === 'inside_dhaka' ? deliverySettings.insideDhaka : deliverySettings.outsideDhaka);
        const override = deliverySettings.productOverrideEnabled ? Math.max(0, ...items.map(item => Number(item[field] || 0))) : 0;
        const weight = items.reduce((sum, item) => sum + Number(item.weight || 0) * Number(item.quantity || 0), 0);
        const heavy = deliverySettings.heavyEnabled && weight > Number(deliverySettings.heavyThreshold || 0) ? Math.ceil(weight - Number(deliverySettings.heavyThreshold || 0)) * Number(deliverySettings.heavyPerKg || 0) : 0;
        return money((override || base) + heavy);
    };
    const submit = event => {
        event.preventDefault();
        const validation = {};
        if (!data.customer_name.trim()) validation.customer_name = 'Full name is required.';
        if (!data.phone.trim()) validation.phone = 'Phone number is required.';
        else if (!/^(?:\+?88)?01[3-9]\d{8}$/.test(data.phone.replace(/[\s-]/g, ''))) validation.phone = 'Enter a valid Bangladesh phone number.';
        if (data.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) validation.email = 'Enter a valid email address.';
        if (!data.address_id && !data.district.trim()) validation.district = 'District is required.';
        if (!data.city.trim()) validation.city = data.district === 'Dhaka' ? 'Thana or area is required.' : 'City or area is required.';
        if (!data.address.trim()) validation.address = 'Full delivery address is required.';
        if (Object.keys(validation).length) { setErrors(validation); return; }
        setProcessing(true); setErrors({});
        router.post(route('storefront.checkout.place-order'), data, { onError: setErrors, onFinish: () => setProcessing(false) });
    };

    return <CheckoutLayout><Head title="Checkout" /><div className="grid gap-7 lg:grid-cols-[1fr_360px]"><form onSubmit={submit} className="order-2 rounded-2xl border border-slate-200 bg-white p-6 lg:order-1"><h1 className="text-2xl font-bold">Delivery information</h1><p className="mt-1 text-sm text-slate-500">Enter your details to place the order.</p>{addresses.length > 0 && <section className="mt-6"><div className="flex items-center justify-between"><h2 className="font-bold">Choose a saved address</h2><button type="button" onClick={()=>setData('address_id',null)} className="text-sm font-bold text-violet-600">Use new address</button></div><div className="mt-3 grid gap-3 sm:grid-cols-2">{addresses.map(address=><button type="button" key={address.id} onClick={()=>chooseAddress(address)} className={`rounded-xl border-2 p-4 text-left transition ${data.address_id===address.id?'border-violet-600 bg-violet-50':'border-slate-200 hover:border-violet-300'}`}><span className="flex items-center justify-between"><b>{address.label}</b>{address.is_default&&<small className="rounded-full bg-emerald-50 px-2 py-1 font-bold text-emerald-700">Default</small>}</span><span className="mt-2 block text-sm font-semibold">{address.recipient_name} · {address.phone}</span><span className="mt-1 line-clamp-2 block text-xs leading-5 text-slate-500">{address.address}, {address.city}</span></button>)}</div></section>}<div className="mt-7 grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
    <FloatingField name="customer_name" label="Full name" value={data.customer_name} error={errors.customer_name} onChange={value => updateField('customer_name', value)} required />
    <FloatingField name="phone" label="Phone number" value={data.phone} error={errors.phone} onChange={value => updateField('phone', value)} type="tel" required />
    <FloatingField name="email" label="Email (optional)" value={data.email} error={errors.email} onChange={value => updateField('email', value)} type="email" />
    <FloatingField name="district" label="District" value={data.district} error={errors.district} required={!data.address_id}>
        <select name="district" value={data.district} onChange={event => { setData(current => ({ ...current, district: event.target.value, city: '', area: '', address_id: null })); if (errors.district) setErrors(current => { const next = { ...current }; delete next.district; return next; }); }} aria-invalid={Boolean(errors.district)} aria-describedby={errors.district ? 'district-error' : undefined} className="h-12 w-full rounded-xl border-0 bg-transparent px-4 pr-10 text-sm text-slate-900 focus:ring-0">
            <option value="">Select district</option>
            {bangladeshDistricts.map(district => <option key={district} value={district}>{district}</option>)}
        </select>
    </FloatingField>
    <div className="sm:col-span-2">{thanaOptions ? <FloatingField name="city" label="Thana / area" value={data.city} error={errors.city} required>
        <select name="city" value={data.city} onChange={event => { const value = event.target.value; setData(current => ({ ...current, city: value, area: value, address_id: null })); if (errors.city) setErrors(current => { const next = { ...current }; delete next.city; return next; }); }} aria-invalid={Boolean(errors.city)} aria-describedby={errors.city ? 'city-error' : undefined} className="h-12 w-full rounded-xl border-0 bg-transparent px-4 pr-10 text-sm text-slate-900 focus:ring-0">
            <option value="">Select thana / area</option>
            {!thanaOptions.includes(data.city) && data.city && <option value={data.city}>{data.city}</option>}
            {thanaOptions.map(thana => <option key={thana} value={thana}>{thana}</option>)}
        </select>
    </FloatingField> : <FloatingField name="city" label="City / thana / area" value={data.city} error={errors.city} onChange={value => { updateField('city', value); setData('area', value); }} required />}</div>
    <div className="sm:col-span-2"><FloatingField name="address" label="Full delivery address" value={data.address} error={errors.address} required>
        <textarea name="address" value={data.address} onChange={event => updateField('address', event.target.value)} aria-invalid={Boolean(errors.address)} aria-describedby={errors.address ? 'address-error' : undefined} rows="3" className="min-h-24 w-full resize-y rounded-xl border-0 bg-transparent px-4 py-3 text-sm text-slate-900 focus:ring-0" />
    </FloatingField></div>
</div><section className="mt-5 rounded-xl border border-slate-200 p-4"><h2 className="flex items-center gap-2 font-bold"><MapPin className="size-5 text-violet-600"/>Delivery area</h2><div className="mt-3 grid gap-3 sm:grid-cols-2"><label className={`flex items-center justify-between gap-3 rounded-xl border-2 p-4 text-sm font-semibold ${data.delivery_zone === 'inside_dhaka' ? 'border-violet-600 bg-violet-50 text-violet-700' : 'border-slate-200'}`}><span className="flex items-center gap-2"><input type="radio" className="text-violet-600" checked={data.delivery_zone === 'inside_dhaka'} onChange={()=>setData('delivery_zone','inside_dhaka')}/>Inside Dhaka</span><b className="text-violet-700">{deliveryAmountFor('inside_dhaka')}</b></label><label className={`flex items-center justify-between gap-3 rounded-xl border-2 p-4 text-sm font-semibold ${data.delivery_zone === 'outside_dhaka' ? 'border-violet-600 bg-violet-50 text-violet-700' : 'border-slate-200'}`}><span className="flex items-center gap-2"><input type="radio" className="text-violet-600" checked={data.delivery_zone === 'outside_dhaka'} onChange={()=>setData('delivery_zone','outside_dhaka')}/>Outside Dhaka</span><b className="text-violet-700">{deliveryAmountFor('outside_dhaka')}</b></label></div>{errors.delivery_zone && <small className="mt-2 block text-rose-600">{errors.delivery_zone}</small>}</section>{customer && !data.address_id && <section className="mt-4 rounded-xl border border-slate-200 p-4"><label className="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" checked={data.save_address} onChange={event=>setData('save_address',event.target.checked)} className="rounded text-violet-600"/>Save this address to my account</label>{data.save_address&&<label className="mt-3 block text-sm font-semibold">Address name<input value={data.address_label} onChange={event=>setData('address_label',event.target.value)} placeholder="Home, Office..." className="mt-2 w-full rounded-xl border-slate-300"/></label>}</section>}<label className="mt-4 block text-sm font-semibold">Order note (optional)<textarea value={data.note} onChange={event => setData('note', event.target.value)} className="mt-2 min-h-20 w-full rounded-xl border-slate-300" /></label><section className="mt-6 rounded-xl border border-violet-200 bg-violet-50 p-4"><h2 className="font-bold">Payment method</h2><label className="mt-3 flex items-center gap-2 text-sm font-semibold"><input type="radio" checked={data.payment_method === 'cod'} onChange={() => setData('payment_method', 'cod')} className="text-violet-600 focus:ring-violet-500" />Cash on Delivery</label></section><button disabled={processing} className="mt-6 h-12 w-full rounded-xl bg-violet-600 font-bold text-white disabled:opacity-60">{processing ? 'Placing order...' : 'Place order'}</button></form><aside className="order-1 h-fit overflow-hidden rounded-[28px] lg:order-2 border border-slate-200 bg-white shadow-[0_16px_45px_rgba(15,23,42,.08)]"><div className="p-5 sm:p-7"><div className="flex items-center justify-between"><h2 className="text-xl font-extrabold text-slate-950">Order Summary</h2><span className="grid size-7 place-items-center rounded-full border border-slate-300 text-slate-400"><Info className="size-4"/></span></div>{cartNotice && <p className="mt-4 rounded-lg bg-rose-50 p-3 text-xs font-semibold text-rose-600">{cartNotice}</p>}<div className="mt-6 space-y-5">{items.map(item => <article key={item.cart_key || item.product_id} className="grid grid-cols-[64px_minmax(0,1fr)_auto] items-center gap-3"><div className="size-16 overflow-hidden rounded-xl bg-slate-50">{item.image ? <img src={item.image} alt={item.title} className="h-full w-full object-contain p-1"/> : <span className="grid h-full place-items-center text-xs text-slate-400">No image</span>}</div><div className="min-w-0"><b className="block truncate text-sm text-slate-900">{item.title}</b>{item.variant_name && <small className="mt-0.5 block truncate text-slate-500">{item.variant_name}</small>}<span className="mt-1 block text-sm font-semibold text-slate-500">{money(item.unit_price)}</span></div><div className="flex flex-col items-end gap-3"><button type="button" onClick={()=>updateCart(item)} disabled={pendingItem===item.cart_key} className="text-slate-500 transition hover:text-rose-600 disabled:opacity-40" aria-label={`Remove ${item.title}`}><X className="size-4"/></button><div className="inline-flex h-9 items-center overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"><button type="button" onClick={()=>updateCart(item,Math.max(item.min_quantity || 1,item.quantity-(item.quantity_step || 1)))} disabled={pendingItem===item.cart_key || item.quantity<=(item.min_quantity || 1)} className="grid size-9 place-items-center text-slate-500 hover:bg-violet-50 disabled:opacity-30"><Minus className="size-3.5"/></button><b className="min-w-7 text-center text-sm">{item.quantity}</b><button type="button" onClick={()=>updateCart(item,item.quantity+(item.quantity_step || 1))} disabled={pendingItem===item.cart_key || (item.max_quantity && item.quantity>=item.max_quantity)} className="grid size-9 place-items-center text-slate-500 hover:bg-violet-50 disabled:opacity-30"><Plus className="size-3.5"/></button></div></div></article>)}</div><section className="mt-7"><h3 className="text-lg font-extrabold text-slate-950">Promotion Code</h3><div className="mt-3 flex h-12 items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50"><input disabled placeholder="Add Promo Code" className="min-w-0 flex-1 border-0 bg-transparent px-4 text-sm text-slate-500 focus:ring-0 disabled:cursor-not-allowed"/><span className="mx-3 grid size-8 place-items-center border-l border-slate-200 pl-3 text-slate-700"><ArrowRight className="size-5"/></span></div><p className="mt-2 text-xs text-slate-400">Promotion codes are coming soon.</p></section><section className="mt-7"><h3 className="text-lg font-extrabold text-slate-950">Order Total</h3><div className="mt-5 space-y-3 border-b border-slate-200 pb-4 text-sm"><div className="flex justify-between"><span className="text-slate-500">Subtotal</span><b className="text-slate-700">{money(subtotal)}</b></div><div className="flex justify-between"><span className="text-slate-500">Delivery charge</span><b className={delivery.free ? 'text-emerald-600' : 'text-slate-700'}>{delivery.free ? 'Free' : money(delivery.shipping)}</b></div>{delivery.cod > 0 && <div className="flex justify-between"><span className="text-slate-500">COD charge</span><b className="text-slate-700">{money(delivery.cod)}</b></div>}{delivery.heavy > 0 && !delivery.free && <p className="text-xs text-slate-400">Delivery includes {money(delivery.heavy)} heavy-item surcharge.</p>}</div><div className="mt-4 flex items-end justify-between"><b className="text-base text-slate-950">Total</b><strong className="text-xl text-violet-600">{money(Number(subtotal)+delivery.total)}</strong></div></section></div></aside></div></CheckoutLayout>;
}
