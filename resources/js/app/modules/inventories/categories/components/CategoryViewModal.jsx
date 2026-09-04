import { FolderTree } from 'lucide-react';
import Button from '@/app/design-system/components/Button';
import Dialog from '@/app/design-system/components/Dialog';

export default function CategoryViewModal({ category, onClose, entityLabel = 'category' }) {
    if (!category) return null;

    return (
        <Dialog open onClose={onClose} title={category.name} description={`${entityLabel.charAt(0).toUpperCase() + entityLabel.slice(1)} details`}>
            <div className="space-y-5 px-5 py-5 sm:px-6">
                <div className="flex items-center gap-4">
                    {category.image_url ? <img src={category.image_url} alt="" className="size-16 rounded-md border border-slate-200 object-cover" /> : <span className="grid size-16 place-items-center rounded-md bg-violet-50 text-violet-700"><FolderTree className="size-7" /></span>}
                    <div><p className="font-semibold text-slate-900">{category.name}</p><code className="mt-1 inline-block rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-600">/{category.slug}</code></div>
                </div>
                <dl className="grid gap-4 text-sm sm:grid-cols-2">
                    {entityLabel === 'category' && <div><dt className="font-medium text-slate-500">Parent category</dt><dd className="mt-1 text-slate-900">{category.parent?.name || 'None'}</dd></div>}
                    <div><dt className="font-medium text-slate-500">Status</dt><dd className="mt-1"><span className={category.is_active ? 'inline-flex rounded-md border border-violet-100 bg-violet-50 px-2.5 py-1 text-xs font-bold text-violet-700' : 'inline-flex rounded-md border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600'}>{category.is_active ? 'Active' : 'Inactive'}</span></dd></div>
                </dl>
                <div><p className="text-sm font-medium text-slate-500">{entityLabel.charAt(0).toUpperCase() + entityLabel.slice(1)} description</p><div className="rich-text-content mt-2 text-sm text-slate-800" dangerouslySetInnerHTML={{ __html: category.description || `<p>No ${entityLabel} description</p>` }} /></div>
            </div>
            <div className="flex justify-end border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6"><Button type="button" variant="secondary" onClick={onClose}>Close</Button></div>
        </Dialog>
    );
}
