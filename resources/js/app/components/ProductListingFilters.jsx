import { router, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function ProductListingFilters({ url, filters = {}, priceBounds = { min: 0, max: 0 } }) {
    const { errors = {} } = usePage().props;
    const [minimum, setMinimum] = useState(filters.min_price ?? '');
    const [maximum, setMaximum] = useState(filters.max_price ?? '');
    const [busy, setBusy] = useState(false);
    useEffect(() => { setMinimum(filters.min_price ?? ''); setMaximum(filters.max_price ?? ''); }, [filters.min_price, filters.max_price, url]);
    const apply = (changes) => router.get(url, { ...filters, page: undefined, ...changes }, { preserveScroll: true, preserveState: true, replace: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });
    const panel = 'overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900';
    const heading = 'flex cursor-pointer list-none items-center justify-between p-4 text-sm font-semibold text-slate-900 dark:text-slate-100';
    const input = 'mt-2 w-full min-w-0 rounded-lg border-slate-300 bg-transparent text-sm text-slate-900 focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:text-slate-100';
    return <div className="space-y-3" aria-label="Price and availability filters">
        <details open className={panel}><summary className={heading}>Price Range<ChevronDown className="size-4" /></summary>
            <form className="space-y-4 border-t border-slate-200 p-4 dark:border-slate-700" onSubmit={event => { event.preventDefault(); apply({ min_price: minimum || undefined, max_price: maximum || undefined }); }}>
                <input aria-label="Maximum price" type="range" min={priceBounds.min} max={Math.max(priceBounds.min, priceBounds.max)} step="1" disabled={priceBounds.min === priceBounds.max} value={maximum === '' ? priceBounds.max : Math.min(priceBounds.max, Math.max(priceBounds.min, Number(maximum)))} onChange={event => setMaximum(event.target.value)} className="w-full accent-violet-600" />
                <div className="grid grid-cols-2 gap-3"><label className="text-xs text-slate-500">Minimum<input aria-label="Minimum price" type="number" min="0" step="0.01" placeholder={priceBounds.min} value={minimum} onChange={event => setMinimum(event.target.value)} className={input} /></label><label className="text-xs text-slate-500">Maximum<input aria-label="Maximum price amount" type="number" min={minimum || 0} step="0.01" placeholder={priceBounds.max} value={maximum} onChange={event => setMaximum(event.target.value)} className={input} /></label></div>
                {(errors.min_price || errors.max_price) && <p role="alert" className="text-xs text-rose-600">{errors.min_price || errors.max_price}</p>}
                <button disabled={busy} className="w-full rounded-lg bg-violet-50 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100 disabled:opacity-50 dark:bg-violet-950 dark:text-violet-200">Apply price</button>
            </form>
        </details>
        <details open className={panel}><summary className={heading}>Availability<ChevronDown className="size-4" /></summary><label className="flex cursor-pointer items-center gap-3 border-t border-slate-200 p-4 text-sm text-slate-700 dark:border-slate-700 dark:text-slate-200"><input type="checkbox" checked={Boolean(Number(filters.in_stock))} disabled={busy} onChange={event => apply({ in_stock: event.target.checked ? 1 : undefined })} className="rounded border-slate-300 text-violet-600 focus:ring-violet-500" />In Stock</label></details>
        {(filters.min_price != null || filters.max_price != null || Number(filters.in_stock) === 1) && <button disabled={busy} onClick={() => apply({ min_price: undefined, max_price: undefined, in_stock: undefined })} className="text-sm font-medium text-violet-600">Reset price & availability</button>}
    </div>;
}
