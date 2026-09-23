import { Link } from '@inertiajs/react';
import { Eye, Pencil, Trash2 } from 'lucide-react';

export const panel = 'rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900';
export const input = 'mt-2 h-11 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 focus:border-violet-500 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white';

export function StatusBadge({ active }) {
    return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'}`}>{active ? 'Active' : 'Inactive'}</span>;
}

export function TableActions({ view, edit, onDelete, canEdit = true, canDelete = true }) {
    const cls = 'grid size-9 place-items-center rounded-lg bg-slate-100 text-slate-600 transition hover:bg-violet-100 hover:text-violet-700';
    return <div className="flex justify-end gap-1.5">
        <Link href={view} className={cls} aria-label="View"><Eye className="size-4" /></Link>
        {canEdit && <Link href={edit} className={cls} aria-label="Edit"><Pencil className="size-4" /></Link>}
        {canDelete && <button type="button" onClick={onDelete} className={`${cls} hover:!bg-rose-100 hover:!text-rose-700`} aria-label="Delete"><Trash2 className="size-4" /></button>}
    </div>;
}

export function ErrorText({ children }) { return children ? <p className="mt-1.5 text-xs font-semibold text-rose-600">{children}</p> : null; }
