import { Link } from '@inertiajs/react';
import MediaLibraryPicker from '@/app/components/MediaLibraryPicker';

export const input = 'mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
export const button = 'inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-violet-700 disabled:opacity-50';
export const secondary = 'inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-violet-600 hover:bg-violet-50 dark:border-slate-700 dark:text-violet-300 dark:hover:bg-slate-800';
export const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
export const imageUrl = (path) => path ? `/image/${encodeURIComponent(path.split('/').pop())}` : null;
export const date = (value) => value ? new Date(value).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Not published';
export function Panel({ title, children }) { return <section className="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">{title && <h2 className="mb-4 text-lg font-bold">{title}</h2>}{children}</section>; }
export function Field({ label, children }) { return <label className="block text-sm font-semibold">{label}{children}</label>; }
export function Errors({ errors }) { return Object.keys(errors).length > 0 && <div role="alert" className="mb-4 rounded-xl bg-rose-50 p-4 text-sm text-rose-700">{Object.entries(errors).map(([key, value]) => <p key={key}>{value}</p>)}</div>; }
export function ImagePicker({ value, onChange, multiple = false, label = 'Choose image' }) {
    const file = path => ({ id: path, path, url: imageUrl(path) });
    return <MediaLibraryPicker value={multiple ? (value || []).map(file) : value ? file(value) : null} multiple={multiple} label={label} onChange={files => onChange(multiple ? files.map(item => item.path) : files?.path || null)} />;
}
export function Pagination({ links }) { return <nav aria-label="Pagination" className="mt-6 flex flex-wrap gap-2">{links?.map((link, index) => link.url ? <Link key={index} href={link.url} className={link.active ? button : secondary}>{link.label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»')}</Link> : <span key={index} className="px-3 py-2 text-sm text-slate-400">{link.label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»')}</span>)}</nav>; }
export function categoryOptions(categories, parent = null, depth = 0, seen = []) {
    return categories.filter(c => (c.parent_id || null) === parent && !seen.includes(c.id)).flatMap(c => [{ ...c, depth }, ...categoryOptions(categories, c.id, depth + 1, [...seen, c.id])]);
}
