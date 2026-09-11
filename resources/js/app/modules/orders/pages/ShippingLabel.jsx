import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';

const money = value => `\u09F3${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;
const deliveryArea = value => value === 'inside_dhaka' ? 'Inside Dhaka' : value === 'outside_dhaka' ? 'Outside Dhaka' : 'Delivery';
const code39 = { '0': 'nnnwwnwnn', '1': 'wnnwnnnnw', '2': 'nnwwnnnnw', '3': 'wnwwnnnnn', '4': 'nnnwwnnnw', '5': 'wnnwwnnnn', '6': 'nnwwwnnnn', '7': 'nnnwnnwnw', '8': 'wnnwnnwnn', '9': 'nnwwnnwnn', A: 'wnnnnwnnw', B: 'nnwnnwnnw', C: 'wnwnnwnnn', D: 'nnnnwwnnw', E: 'wnnnwwnnn', F: 'nnwnwwnnn', G: 'nnnnnwwnw', H: 'wnnnnwwnn', I: 'nnwnnwwnn', J: 'nnnnwwwnn', K: 'wnnnnnnww', L: 'nnwnnnnww', M: 'wnwnnnnwn', N: 'nnnnwnnww', O: 'wnnnwnnwn', P: 'nnwnwnnwn', Q: 'nnnnnnwww', R: 'wnnnnnwwn', S: 'nnwnnnwwn', T: 'nnnnwnwwn', U: 'wwnnnnnnw', V: 'nwwnnnnnw', W: 'wwwnnnnnn', X: 'nwnnwnnnw', Y: 'wwnnwnnnn', Z: 'nwwnwnnnn', '-': 'nwnnnnwnw', '*': 'nwnnwnwnn' };

function Barcode({ value }) {
    const safe = String(value).toUpperCase().replace(/[^A-Z0-9-]/g, '');
    const units = `*${safe}*`.split('').flatMap(character => [...code39[character].split('').map((width, index) => ({ bar: index % 2 === 0, width: width === 'w' ? 3 : 1 })), { bar: false, width: 1 }]);
    return <><div aria-label={`Barcode ${safe}`} className="flex h-14 items-stretch justify-center">{units.map((unit, index) => <i key={index} className={unit.bar ? 'bg-slate-950' : 'bg-white'} style={{ width: `${unit.width}px` }} />)}</div><p className="mt-2 text-center font-mono text-sm font-black tracking-[.25em]">{safe}</p></>;
}

export default function ShippingLabel({ order }) {
    const { website } = usePage().props;
    const itemCount = order.items.reduce((total, item) => total + Number(item.quantity || 0), 0);
    const merchantPhone = website?.footer?.phone || '';
    const collectable = String(order.paymentStatus).toLowerCase() === 'paid' ? 0 : order.total;

    return <><Head title={`${order.number} Shipping Label`} />
        <style>{`@page{size:A5 portrait;margin:0}@media print{html,body,#app{margin:0!important;padding:0!important;background:#fff!important}.label-toolbar{display:none!important}.label-workspace{padding:0!important;background:#fff!important}.shipping-label{width:148mm!important;min-height:210mm!important;height:210mm!important;margin:0!important;box-shadow:none!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}}`}</style>
        <div className="label-workspace min-h-screen bg-slate-100 px-4 pb-10">
            <div className="label-toolbar sticky top-0 z-20 mx-auto mb-6 flex max-w-4xl items-center justify-between gap-4 border-b border-slate-200 bg-slate-100/95 py-4 backdrop-blur">
                <Link href={route('orders.show', order.id)} className="inline-flex items-center gap-2 text-sm font-bold text-violet-700 hover:underline"><ArrowLeft className="size-4" />Back to order</Link>
                <button type="button" onClick={() => window.print()} className="inline-flex h-11 items-center gap-2 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 px-5 text-sm font-black text-slate-950 shadow-lg shadow-amber-200 transition hover:-translate-y-0.5"><Printer className="size-4" />Print A5 Label</button>
            </div>
            <article className="shipping-label mx-auto min-h-[210mm] w-[148mm] max-w-full bg-white p-[10mm] text-slate-950 shadow-xl">
                <header className="flex items-start justify-between gap-5 border-b-2 border-slate-950 pb-5"><div>{website?.logo ? <img src={website.logo} alt={website.name || 'Store'} className="h-14 max-w-40 object-contain object-left" /> : <h1 className="text-2xl font-black">{website?.name || 'iTTiBA'}</h1>}<p className="mt-2 text-xs font-semibold">Merchant{merchantPhone ? ` · ${merchantPhone}` : ''}</p></div><div className="text-right"><p className="text-[10px] font-black uppercase tracking-[.18em] text-slate-500">Shipping label</p><p className="mt-1 text-xl font-black">{order.number}</p><span className={`mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black uppercase ${collectable > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}`}>{collectable > 0 ? `COD ${money(collectable)}` : 'PAID'}</span></div></header>
                <section className="grid grid-cols-2 border-b-2 border-slate-950"><div className="border-r-2 border-slate-950 py-5 pr-5"><p className="text-[10px] font-black uppercase tracking-[.16em] text-slate-500">Deliver to</p><h2 className="mt-2 text-xl font-black">{order.customerName}</h2><p className="mt-2 text-base font-bold">{order.phone}</p><p className="mt-3 text-sm font-semibold leading-6">{order.address}, {order.city}</p></div><div className="py-5 pl-5"><p className="text-[10px] font-black uppercase tracking-[.16em] text-slate-500">Delivery details</p><dl className="mt-3 space-y-3 text-sm"><div><dt className="text-xs text-slate-500">Area</dt><dd className="font-black">{deliveryArea(order.deliveryZone)}</dd></div><div><dt className="text-xs text-slate-500">Items</dt><dd className="font-black">{itemCount}</dd></div><div><dt className="text-xs text-slate-500">Payment</dt><dd className="font-black uppercase">{order.paymentMethod}</dd></div></dl></div></section>
                <section className="border-b border-slate-300 py-5"><div className="flex items-center justify-between"><h3 className="text-sm font-black uppercase tracking-wide">Parcel contents</h3><span className="text-xs font-bold text-slate-500">{order.items.length} product lines</span></div><div className="mt-3 space-y-2">{order.items.map((item, index) => <div key={`${item.title}-${index}`} className="flex justify-between gap-4 text-sm"><span className="font-semibold">{item.title}{item.variantName ? ` · ${item.variantName}` : ''}</span><b>×{item.quantity}</b></div>)}</div></section>
                {order.note && <section className="mt-5 rounded-lg border-2 border-emerald-500 bg-emerald-50 p-4"><h3 className="text-xs font-black uppercase tracking-wide text-emerald-800">Read before confirm</h3><p className="mt-1 text-sm font-semibold leading-5">{order.note}</p></section>}
                <footer className="mt-8"><Barcode value={order.number} /><p className="mt-6 text-center text-[10px] text-slate-400">Generated {order.generatedAt}</p></footer>
            </article>
        </div>
    </>;
}
