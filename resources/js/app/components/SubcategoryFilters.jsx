import { Link } from '@inertiajs/react';
import { ChevronDown, ChevronRight } from 'lucide-react';

export default function SubcategoryFilters({ category, subcategories = [], total = 0, filters = {} }) {
    if (subcategories.length === 0) return null;

    return <details open className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
        <summary className="flex cursor-pointer list-none items-center justify-between p-4 text-sm font-semibold text-slate-900 dark:text-slate-100">
            Subcategories <ChevronDown className="size-4" aria-hidden="true" />
        </summary>
        <nav aria-label="Subcategories" className="space-y-1 border-t border-slate-200 p-2 dark:border-slate-700">
            <Link href={`/${category.slug}`} data={filters} preserveScroll aria-current="page" className="flex items-center justify-between gap-2 rounded-lg bg-violet-50 px-3 py-2 text-sm font-semibold text-violet-700 dark:bg-violet-950 dark:text-violet-200">
                <span className="min-w-0 truncate">All {category.name}</span>
                <span className="shrink-0 rounded-md bg-white px-2 py-0.5 text-xs tabular-nums dark:bg-slate-900">{total}</span>
            </Link>
            {subcategories.map(subcategory => <Link key={subcategory.id} href={`/${subcategory.slug}`} data={filters} preserveScroll className="flex items-center gap-2 rounded-lg py-2 pr-3 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-violet-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-violet-500 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-violet-200" style={{ paddingLeft: `${12 + subcategory.depth * 14}px` }}>
                <ChevronRight className="size-3.5 shrink-0 text-slate-400" aria-hidden="true" />
                <span className="min-w-0 flex-1 break-words">{subcategory.name}</span>
                <span className="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium tabular-nums text-slate-600 dark:bg-slate-800 dark:text-slate-300" aria-label={`${subcategory.productCount} products`}>{subcategory.productCount}</span>
            </Link>)}
        </nav>
    </details>;
}
