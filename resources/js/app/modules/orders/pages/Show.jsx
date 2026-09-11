import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Check, CircleDollarSign, Clock3, FileText, MapPin, MessageSquareText, Package, Phone, Printer, ShoppingBag, TriangleAlert, UserRound, X } from 'lucide-react';
import { useState } from 'react';
import AdminLayout from '@/app/layouts/AdminLayout';

const money = value => `৳${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;
const orderSteps = [['pending', 'Pending'], ['confirmed', 'Confirmed'], ['processing', 'Processing'], ['ready_to_ship', 'Ready To Ship'], ['shipped', 'Shipped'], ['completed', 'Completed']];
const exceptionStatuses = [['cancelled', 'Cancelled'], ['returned', 'Returned']];
const deliveryArea = value => value === 'inside_dhaka' ? 'Inside Dhaka' : value === 'outside_dhaka' ? 'Outside Dhaka' : 'Delivery';
const badgeClass = key => ['paid', 'completed'].includes(String(key).toLowerCase()) ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : ['cancelled', 'returned', 'failed'].includes(String(key).toLowerCase()) ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-violet-50 text-violet-700 ring-violet-200';
const code39 = { '0': 'nnnwwnwnn', '1': 'wnnwnnnnw', '2': 'nnwwnnnnw', '3': 'wnwwnnnnn', '4': 'nnnwwnnnw', '5': 'wnnwwnnnn', '6': 'nnwwwnnnn', '7': 'nnnwnnwnw', '8': 'wnnwnnwnn', '9': 'nnwwnnwnn', A: 'wnnnnwnnw', B: 'nnwnnwnnw', C: 'wnwnnwnnn', D: 'nnnnwwnnw', E: 'wnnnwwnnn', F: 'nnwnwwnnn', G: 'nnnnnwwnw', H: 'wnnnnwwnn', I: 'nnwnnwwnn', J: 'nnnnwwwnn', K: 'wnnnnnnww', L: 'nnwnnnnww', M: 'wnwnnnnwn', N: 'nnnnwnnww', O: 'wnnnwnnwn', P: 'nnwnwnnwn', Q: 'nnnnnnwww', R: 'wnnnnnwwn', S: 'nnwnnnwwn', T: 'nnnnwnwwn', U: 'wwnnnnnnw', V: 'nwwnnnnnw', W: 'wwwnnnnnn', X: 'nwnnwnnnw', Y: 'wwnnwnnnn', Z: 'nwwnwnnnn', '-': 'nwnnnnwnw', '*': 'nwnnwnwnn' };

function Barcode({ value }) {
    const safe = String(value).toUpperCase().replace(/[^A-Z0-9-]/g, '');
    const units = `*${safe}*`.split('').flatMap(character => [...code39[character].split('').map((width, index) => ({ bar: index % 2 === 0, width: width === 'w' ? 3 : 1 })), { bar: false, width: 1 }]);
    return <><div aria-label={`Barcode ${safe}`} className="flex h-14 items-stretch justify-center">{units.map((unit, index) => <i key={index} className={unit.bar ? 'bg-slate-950' : 'bg-white'} style={{ width: `${unit.width}px` }} />)}</div><p className="mt-2 text-center font-mono text-sm font-black tracking-[.25em]">{safe}</p></>;
}

function ShippingLabel({ order, website, close }) {
    const itemCount = order.items.reduce((total, item) => total + Number(item.quantity || 0), 0);
    const merchantPhone = website?.footer?.phone || '';
    const collectable = String(order.paymentStatus).toLowerCase() === 'paid' ? 0 : order.total;

    return <div className="fixed inset-0 z-[100] grid place-items-center overflow-y-auto bg-slate-950/60 p-4 backdrop-blur-sm print:static print:block print:bg-white print:p-0" role="dialog" aria-modal="true" aria-label="Shipping label preview" onMouseDown={event => event.target === event.currentTarget && close()}>
        <div className="w-full max-w-[760px] rounded-2xl bg-slate-100 p-4 shadow-2xl print:max-w-none print:rounded-none print:bg-white print:p-0 print:shadow-none">
            <div className="mb-4 flex items-center justify-between print:hidden"><div><h2 className="font-black text-slate-950">A5 Shipping Label</h2><p className="text-xs text-slate-500">Preview before printing</p></div><button type="button" onClick={close} className="grid size-9 place-items-center rounded-lg bg-white text-slate-500 shadow-sm hover:text-slate-900"><X className="size-5" /></button></div>
            <article className="shipping-label mx-auto min-h-[210mm] w-[148mm] max-w-full bg-white p-[10mm] text-slate-950 shadow-sm print:m-0 print:h-[210mm] print:w-[148mm] print:max-w-none print:shadow-none">
                <header className="flex items-start justify-between gap-5 border-b-2 border-slate-950 pb-5"><div>{website?.logo ? <img src={website.logo} alt={website.name || 'Store'} className="h-14 max-w-40 object-contain object-left" /> : <h1 className="text-2xl font-black">{website?.name || 'iTTiBA'}</h1>}<p className="mt-2 text-xs font-semibold">Merchant{merchantPhone ? ` · ${merchantPhone}` : ''}</p></div><div className="text-right"><p className="text-[10px] font-black uppercase tracking-[.18em] text-slate-500">Shipping label</p><p className="mt-1 text-xl font-black">{order.number}</p><span className={`mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black uppercase ${collectable > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}`}>{collectable > 0 ? `COD ${money(collectable)}` : 'PAID'}</span></div></header>
                <section className="grid grid-cols-2 gap-0 border-b-2 border-slate-950"><div className="border-r-2 border-slate-950 py-5 pr-5"><p className="text-[10px] font-black uppercase tracking-[.16em] text-slate-500">Deliver to</p><h2 className="mt-2 text-xl font-black">{order.customerName}</h2><p className="mt-2 text-base font-bold">{order.phone}</p><p className="mt-3 text-sm font-semibold leading-6">{order.address}, {order.city}</p></div><div className="py-5 pl-5"><p className="text-[10px] font-black uppercase tracking-[.16em] text-slate-500">Delivery details</p><dl className="mt-3 space-y-3 text-sm"><div><dt className="text-xs text-slate-500">Area</dt><dd className="font-black">{deliveryArea(order.deliveryZone)}</dd></div><div><dt className="text-xs text-slate-500">Items</dt><dd className="font-black">{itemCount}</dd></div><div><dt className="text-xs text-slate-500">Payment</dt><dd className="font-black uppercase">{order.paymentMethod}</dd></div></dl></div></section>
                <section className="border-b border-slate-300 py-5"><div className="flex items-center justify-between"><h3 className="text-sm font-black uppercase tracking-wide">Parcel contents</h3><span className="text-xs font-bold text-slate-500">{order.items.length} product lines</span></div><div className="mt-3 space-y-2">{order.items.map((item, index) => <div key={`${item.title}-${index}`} className="flex justify-between gap-4 text-sm"><span className="font-semibold">{item.title}{item.variantName ? ` · ${item.variantName}` : ''}</span><b>×{item.quantity}</b></div>)}</div></section>
                {order.note && <section className="mt-5 rounded-lg border-2 border-emerald-500 bg-emerald-50 p-4"><h3 className="text-xs font-black uppercase tracking-wide text-emerald-800">Read before confirm</h3><p className="mt-1 text-sm font-semibold leading-5">{order.note}</p></section>}
                <footer className="mt-8"><Barcode value={order.number} /><p className="mt-6 text-center text-[10px] text-slate-400">Generated {order.shippingLabelGeneratedAt}</p></footer>
            </article>
            <div className="mt-4 flex justify-end gap-3 print:hidden"><button type="button" onClick={close} className="h-11 rounded-xl border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Close</button><button type="button" onClick={() => window.print()} className="inline-flex h-11 items-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-bold text-white shadow-lg shadow-violet-200 hover:bg-violet-700"><Printer className="size-4" />Print A5 Label</button></div>
        </div>
        <style>{`@media print { @page { size: A5 portrait; margin: 0; } body * { visibility: hidden !important; } .shipping-label, .shipping-label * { visibility: visible !important; } .shipping-label { position: fixed !important; inset: 0 auto auto 0 !important; overflow: hidden !important; } }`}</style>
    </div>;
}

function StatusCard({ order, processing, updateStatus }) {
    const currentStep = orderSteps.findIndex(([value]) => value === order.statusKey);
    const nextStep = currentStep >= 0 ? orderSteps[currentStep + 1] : null;

    return <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 className="font-bold">Update order status</h2>
        <p className="mt-1 text-xs text-slate-500">The next available status is shown below.</p>
        <ol className="mt-5">{orderSteps.map(([value, label], index) => {
            const reached = currentStep >= 0 && index <= currentStep;
            const active = order.statusKey === value;
            return <li key={value} className="relative flex min-h-16 gap-3 last:min-h-0">
                {index < orderSteps.length - 1 && <span aria-hidden="true" className={index < currentStep ? 'absolute left-[15px] top-8 h-[calc(100%-1rem)] w-0.5 bg-violet-500' : 'absolute left-[15px] top-8 h-[calc(100%-1rem)] w-0.5 bg-slate-200 dark:bg-slate-700'} />}
                <span aria-current={active ? 'step' : undefined} className={reached ? 'relative z-10 grid size-8 shrink-0 place-items-center rounded-full bg-violet-600 text-white ring-4 ring-violet-100' : 'relative z-10 grid size-8 shrink-0 place-items-center rounded-full border-2 border-slate-300 bg-white text-xs font-bold text-slate-500 dark:bg-slate-900'}>{reached ? <Check className="size-4" /> : index + 1}</span>
                <div className="min-w-0 pb-5"><span className={active ? 'block text-sm font-bold text-violet-700' : 'block text-sm font-semibold text-slate-700 dark:text-slate-200'}>{label}</span><span className="mt-0.5 block text-xs text-slate-400">{active ? 'Current status' : reached ? 'Completed' : 'Upcoming'}</span></div>
            </li>;
        })}</ol>
        {nextStep ? <button type="button" onClick={() => updateStatus(nextStep[0])} disabled={processing} className="mt-5 h-11 w-full rounded-xl bg-violet-600 font-bold text-white transition hover:bg-violet-700 disabled:opacity-50">{processing ? 'Updating...' : nextStep[1]}</button> : order.statusKey === 'completed' ? <div className="mt-5 rounded-xl bg-emerald-50 p-3 text-center text-sm font-bold text-emerald-700">Order completed</div> : null}
        <div className="mt-5 border-t border-slate-200 pt-4 dark:border-slate-700"><p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Exception status</p><div className="mt-2 grid grid-cols-2 gap-2">{exceptionStatuses.map(([value, label]) => <button type="button" key={value} disabled={processing || order.statusKey === value} onClick={() => updateStatus(value)} className={order.statusKey === value ? 'rounded-lg border border-rose-500 bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700 disabled:opacity-70' : 'rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-500 hover:border-rose-300 hover:text-rose-600 disabled:opacity-50'}>{label}</button>)}</div></div>
    </section>;
}

export default function OrderShow({ order }) {
    const [processing, setProcessing] = useState(false);
    const [labelOpen, setLabelOpen] = useState(false);
    const [labelProcessing, setLabelProcessing] = useState(false);
    const { website } = usePage().props;
    const canUseLabel = ['confirmed', 'processing', 'ready_to_ship', 'shipped', 'completed'].includes(order.statusKey);
    const updateStatus = status => {
        if (!status || processing) return;
        setProcessing(true);
        router.patch(route('orders.status.update', order.id), { status }, { preserveScroll: true, onFinish: () => setProcessing(false) });
    };
    const openLabel = () => {
        if (order.shippingLabelGeneratedAt) {
            setLabelOpen(true);
            return;
        }
        setLabelProcessing(true);
        router.post(route('orders.shipping-label.generate', order.id), {}, {
            preserveScroll: true,
            onSuccess: () => setLabelOpen(true),
            onFinish: () => setLabelProcessing(false),
        });
    };

    return <AdminLayout><Head title={`Order ${order.number}`} /><main className="mx-auto max-w-7xl space-y-5">
        <Link href={route('orders.index')} className="inline-flex items-center gap-2 text-sm font-bold text-violet-700 hover:underline"><ArrowLeft className="size-4" />Back to orders</Link>
        <header className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6"><div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center"><div><div className="flex flex-wrap items-center gap-2"><h1 className="text-2xl font-black tracking-tight text-slate-950 dark:text-white">Order {order.number}</h1><span className={`rounded-full px-3 py-1 text-xs font-bold capitalize ring-1 ${badgeClass(order.paymentStatus)}`}>{order.paymentStatus}</span><span className={`rounded-full px-3 py-1 text-xs font-bold ring-1 ${badgeClass(order.statusKey)}`}>{order.status}</span></div><div className="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-500"><span className="inline-flex items-center gap-1.5"><Clock3 className="size-4 text-violet-500" />{order.date}</span><span className="inline-flex items-center gap-1.5"><ShoppingBag className="size-4 text-violet-500" />{order.items.length} {order.items.length === 1 ? 'item' : 'items'}</span>{order.shippingLabelGeneratedAt && <span className="inline-flex items-center gap-1.5 font-semibold text-emerald-600"><Check className="size-4" />Label generated</span>}</div></div><div className="flex flex-wrap items-center gap-3"><div className="w-fit rounded-xl bg-violet-50 px-5 py-3 lg:text-right"><p className="text-xs font-bold uppercase tracking-wide text-violet-500">Order total</p><strong className="mt-1 block text-2xl text-violet-700">{money(order.total)}</strong></div>{canUseLabel && <button type="button" onClick={openLabel} disabled={labelProcessing} className="inline-flex h-12 items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 text-sm font-bold text-white shadow-lg shadow-violet-200 transition hover:-translate-y-0.5 disabled:opacity-50"><FileText className="size-4" />{labelProcessing ? 'Generating...' : order.shippingLabelGeneratedAt ? 'View / Print Label' : 'Generate Shipping Label'}</button>}</div></div></header>

        {order.hasStockShortage && <div className="flex gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800"><TriangleAlert className="mt-0.5 size-5 shrink-0" /><div><b>Stock unavailable — action required</b><p className="mt-1 text-sm">One or more products need stock before this order can be fulfilled.</p></div></div>}

        <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div className="space-y-6">
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"><div className="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800"><h2 className="flex items-center gap-2 font-bold"><Package className="size-5 text-violet-600" />Ordered products</h2><span className="text-xs font-semibold text-slate-400">{order.items.length} items</span></div><div className="grid border-b border-slate-100 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-950/30 md:grid-cols-3"><div className="flex items-start gap-3 border-b border-slate-100 p-5 dark:border-slate-800 md:border-b-0 md:border-r"><span className="grid size-9 shrink-0 place-items-center rounded-lg bg-violet-100 text-violet-700"><UserRound className="size-4" /></span><div className="min-w-0"><p className="text-[11px] font-black uppercase tracking-wide text-slate-400">Customer</p><p className="mt-1 truncate text-sm font-bold text-slate-900 dark:text-white">{order.customerName}</p><p className="mt-1 text-xs text-slate-500">Total: {order.customerOrderCount || 1} {(order.customerOrderCount || 1) === 1 ? 'order' : 'orders'}</p></div></div><div className="flex items-start gap-3 border-b border-slate-100 p-5 dark:border-slate-800 md:border-b-0 md:border-r"><span className="grid size-9 shrink-0 place-items-center rounded-lg bg-amber-100 text-amber-700"><MapPin className="size-4" /></span><div className="min-w-0"><p className="text-[11px] font-black uppercase tracking-wide text-slate-400">Delivery address</p><span className="mt-1 inline-flex rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-bold text-violet-700">{deliveryArea(order.deliveryZone)}</span><p className="mt-1.5 text-sm leading-5 text-slate-600 dark:text-slate-300">{order.address}, {order.city}</p></div></div><div className="flex items-start gap-3 p-5"><span className="grid size-9 shrink-0 place-items-center rounded-lg bg-sky-100 text-sky-700"><Phone className="size-4" /></span><div className="min-w-0"><p className="text-[11px] font-black uppercase tracking-wide text-slate-400">Contact information</p><a href={`tel:${order.phone}`} className="mt-1 block text-sm font-bold text-slate-800 hover:text-violet-700 dark:text-slate-200">{order.phone}</a>{order.email && <a href={`mailto:${order.email}`} className="mt-1 block truncate text-xs font-semibold text-slate-500 hover:text-violet-700">{order.email}</a>}</div></div></div><div className="divide-y divide-slate-100 dark:divide-slate-800">{order.items.map((item, index) => <article key={`${item.sku || item.title}-${index}`} className="grid grid-cols-[64px_minmax(0,1fr)] items-center gap-4 p-5 sm:grid-cols-[64px_minmax(0,1fr)_auto]"><div className="grid size-16 place-items-center overflow-hidden rounded-xl bg-slate-50 ring-1 ring-slate-100">{item.image ? <img src={item.image} alt="" className="h-full w-full object-contain p-1" /> : <Package className="size-6 text-slate-300" />}</div><div className="min-w-0">{item.slug ? <a href={route('storefront.products.show', item.slug)} target="_blank" rel="noopener noreferrer" className="font-bold text-slate-900 hover:text-violet-700 hover:underline dark:text-white">{item.title}</a> : <h3 className="font-bold">{item.title}</h3>}<p className="mt-1 text-xs text-slate-500">SKU: {item.sku || 'N/A'} · Quantity {item.quantity}</p>{item.variantName && <span className="mt-2 inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-bold text-violet-700">{item.variantName}</span>}{item.stockShortageQuantity > 0 && <p className="mt-2 text-xs font-bold text-rose-600">Stock shortage: {item.stockShortageQuantity}</p>}</div><div className="col-start-2 sm:col-start-auto sm:text-right"><b>{money(item.lineTotal)}</b><p className="mt-1 text-xs text-slate-400">{money(item.unitPrice)} × {item.quantity}</p></div></article>)}</div></section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><div className="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"><h2 className="flex items-center gap-2 font-bold"><CircleDollarSign className="size-5 text-violet-600" />Payment details</h2><span className={`rounded-full px-3 py-1 text-xs font-bold capitalize ring-1 ${badgeClass(order.paymentStatus)}`}>{order.paymentStatus}</span></div><dl className="mt-4 space-y-3 text-sm"><div className="flex justify-between"><dt className="text-slate-500">Payment method</dt><dd className="font-bold uppercase">{order.paymentMethod}</dd></div><div className="flex justify-between"><dt className="text-slate-500">Products subtotal</dt><dd className="font-semibold">{money(order.subtotal)}</dd></div><div className="flex justify-between"><dt className="text-slate-500">{deliveryArea(order.deliveryZone)}</dt><dd className="font-semibold">{Number(order.shippingTotal) > 0 ? money(order.shippingTotal) : <span className="rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">Free</span>}</dd></div>{order.codSurcharge > 0 && <div className="flex justify-between"><dt className="text-slate-500">COD charge</dt><dd className="font-semibold">{money(order.codSurcharge)}</dd></div>}<div className="flex justify-between border-t border-slate-200 pt-4 text-lg dark:border-slate-700"><dt className="font-black">Grand total</dt><dd className="font-black text-violet-700">{money(order.total)}</dd></div></dl></section>
            </div>

            <aside className="space-y-5">
                {order.note && <section className="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 shadow-sm"><h2 className="flex items-center gap-2 font-bold text-emerald-800"><MessageSquareText className="size-5" />Read before confirm</h2><p className="mt-3 text-sm leading-6 text-slate-700">{order.note}</p></section>}
                <StatusCard order={order} processing={processing} updateStatus={updateStatus} />
            </aside>
        </div>
        {labelOpen && <ShippingLabel order={order} website={website} close={() => setLabelOpen(false)} />}
    </main></AdminLayout>;
}
