import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ArrowRight, Clock, Package, PackageCheck, RotateCcw, Truck, Wallet } from 'lucide-react';
import { api } from './ui';

const number = value => Number(value || 0).toLocaleString('en-BD');
const money = value => '৳' + Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 });
function dateRange(period) {
    if (period === 'all') return {};
    const parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Dhaka', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date());
    const part = name => parts.find(item => item.type === name).value;
    const end = new Date(part('year') + '-' + part('month') + '-' + part('day') + 'T00:00:00Z');
    const start = new Date(end);
    start.setUTCDate(start.getUTCDate() - Number(period) + 1);
    return { from: start.toISOString().slice(0, 10), to: end.toISOString().slice(0, 10) };
}

export default function CourierSummary() {
    const [data, setData] = useState(null), [period, setPeriod] = useState('all');
    const [loading, setLoading] = useState(true), [error, setError] = useState(''), [retry, setRetry] = useState(0);
    useEffect(() => {
        let active = true;
        setLoading(true); setError('');
        api(route('couriers.index', dateRange(period)))
            .then(result => { if (active) setData(result); })
            .catch(() => { if (active) setError('Courier overview could not be loaded.'); })
            .finally(() => { if (active) setLoading(false); });
        return () => { active = false; };
    }, [period, retry]);
    const counts = data?.bookedCounts || {};
    const count = (...keys) => keys.reduce((sum, key) => sum + Number(counts[key] || 0), 0);
    const total = Number(data?.total || 0);
    const segments = [
        { label: 'Pending', value: count('pending', 'pickup_requested'), Icon: Clock, bar: 'bg-amber-400', tone: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' },
        { label: 'In Transit', value: count('picked', 'in_transit'), Icon: Truck, bar: 'bg-sky-500', tone: 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300' },
        { label: 'Delivered', value: count('delivered'), Icon: PackageCheck, bar: 'bg-emerald-500', tone: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' },
        { label: 'Returned', value: count('returned'), Icon: RotateCcw, bar: 'bg-rose-400', tone: 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' },
        { label: 'Partial delivered', value: count('partial_delivered'), bar: 'bg-teal-400' },
        { label: 'Cancelled', value: count('cancelled'), bar: 'bg-slate-400' },
    ];
    const other = Math.max(0, total - segments.reduce((sum, item) => sum + item.value, 0));
    if (other) segments.push({ label: 'Other', value: other, bar: 'bg-violet-400' });
    const cards = [{ label: 'Total parcels', value: total, Icon: Package, tone: 'bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300' }, ...segments.slice(0, 4)];
    const attention = Number(data?.counts?.needs_verification || 0) + Number(data?.counts?.failed || 0);
    const details = route('couriers.index', dateRange(period));

    return <section aria-labelledby="courier-overview-title" aria-busy={loading} className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <header className="flex flex-col gap-4 border-b border-slate-100 p-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-3"><span className="grid size-10 shrink-0 place-items-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300"><Truck className="size-5" /></span><div><h2 id="courier-overview-title" className="text-lg font-bold text-slate-950 dark:text-white">Courier Overview</h2><p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Parcel delivery and COD collection · Live bookings</p></div></div>
            <div className="flex flex-wrap items-center gap-3"><select aria-label="Courier overview date range" value={period} onChange={event => setPeriod(event.target.value)} className="h-10 max-w-full rounded-lg border-slate-200 bg-white py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"><option value="all">All time</option><option value="1">Today</option><option value="7">Last 7 days</option><option value="30">Last 30 days</option></select><Link href={details} className="inline-flex items-center gap-1 text-sm font-semibold text-violet-700 hover:underline dark:text-violet-300">View details<ArrowRight className="size-4" /></Link></div>
        </header>
        {loading ? <div role="status" className="p-5"><p className="text-sm text-slate-500">Loading courier overview…</p><div aria-hidden="true" className="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-5">{[0,1,2,3,4].map(key => <div key={key} className="h-24 animate-pulse rounded-lg bg-slate-100 dark:bg-slate-800" />)}</div></div> : error ? <div role="alert" className="p-5 text-sm text-rose-700 dark:text-rose-300">{error}<button type="button" onClick={() => setRetry(value => value + 1)} className="ml-3 font-semibold underline">Retry</button></div> : <>
            <div className="grid grid-cols-2 gap-3 p-5 lg:grid-cols-5">{cards.map(({ label, value, Icon, tone }, index) => <div key={label} className={'min-w-0 rounded-lg border border-slate-100 p-4 dark:border-slate-800 ' + (index === 0 ? 'col-span-2 bg-violet-50/40 dark:bg-violet-950/10 lg:col-span-1' : '')}><div className="flex items-center gap-2"><span className={'grid size-8 shrink-0 place-items-center rounded-lg ' + tone}><Icon className="size-4" /></span><span className="text-xs font-medium text-slate-500 dark:text-slate-400">{label}</span></div><b className="mt-3 block break-words text-2xl font-bold tabular-nums text-slate-950 dark:text-white">{number(value)}</b></div>)}</div>
            <div className="grid gap-5 px-5 pb-5 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
                <div className="min-w-0 rounded-lg bg-slate-50 p-4 dark:bg-slate-800/40"><h3 className="text-sm font-semibold">Delivery status</h3>{total > 0 ? <><div aria-hidden="true" className="mt-4 flex h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">{segments.filter(item => item.value > 0).map(item => <span key={item.label} className={item.bar} style={{ width: (item.value / total * 100) + '%' }} />)}</div><ul className="mt-4 flex flex-wrap gap-x-5 gap-y-2">{segments.filter(item => item.value > 0).map(item => <li key={item.label} className="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300"><span aria-hidden="true" className={'size-2 shrink-0 rounded-full ' + item.bar} />{item.label}<b>{number(item.value)} · {(item.value / total * 100).toFixed(1)}%</b></li>)}</ul></> : <div className="mt-3 flex items-start gap-3"><Package className="mt-0.5 size-5 shrink-0 text-slate-400" /><div><p className="text-sm font-medium">No courier bookings yet</p><p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Delivery progress will appear when parcels are booked in this date range.</p></div></div>}{attention > 0 && <Link href={details} className="mt-3 block text-xs font-medium text-amber-700 hover:underline dark:text-amber-300">{number(attention)} submission(s) need review →</Link>}</div>
                <div className="grid min-w-0 gap-4 rounded-lg border border-slate-100 p-4 dark:border-slate-800 sm:grid-cols-2"><div><p className="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400"><Wallet className="size-4 text-violet-500" />Total COD</p><b className="mt-3 block break-words text-xl font-bold tabular-nums text-slate-950 dark:text-white">{money(data?.cod)}</b><p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Booked parcel COD amount</p></div><div className="border-t border-slate-100 pt-3 dark:border-slate-800 sm:border-l sm:border-t-0 sm:pl-4 sm:pt-0"><p className="text-xs text-slate-500 dark:text-slate-400">Collected COD</p><b className={'mt-3 block break-words font-bold ' + (data?.collectionVerified ? 'text-xl tabular-nums text-emerald-700 dark:text-emerald-300' : 'text-sm text-slate-600 dark:text-slate-300')}>{data?.collectionVerified ? money(data.collected) : 'Not reconciled'}</b><p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{data?.collectionVerified ? number(data.collectionVerified) + ' parcel settlement(s) verified' : 'Awaiting verified settlement'}</p></div></div>
            </div>
        </>}
    </section>;
}
