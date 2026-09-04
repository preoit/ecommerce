import { Eye, Image, Pencil, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import IconButton from '@/app/design-system/components/IconButton';
import Pagination from '@/app/design-system/components/Pagination';

const textFromHtml = (html) => html
    ? html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
    : '';

function CategoryActions({ category, onView, onEdit, onDelete }) {
    const actionClass = 'category-table__action rounded-full border-0 bg-[#e7edf5] text-slate-600 hover:bg-violet-100 hover:text-violet-700';

    return (
        <div className="flex items-center justify-end gap-1.5">
            <IconButton icon={Eye} iconClassName="!size-4" label={`View ${category.name}`} onClick={() => onView(category)} className={actionClass} />
            <IconButton icon={Pencil} iconClassName="!size-4" label={`Edit ${category.name}`} onClick={() => onEdit(category)} className={actionClass} />
            <IconButton icon={Trash2} iconClassName="!size-4" label={`Delete ${category.name}`} onClick={() => onDelete(category)} className={`${actionClass} hover:!bg-red-50 hover:!text-red-600`} />
        </div>
    );
}

export default function CategoryTable({ categories, search, onSearchChange, onSearchSubmit, onView, onEdit, onDelete, entityLabel = 'category' }) {
    const [selected, setSelected] = useState([]);
    const [bulkAction, setBulkAction] = useState('');
    const ids = categories.data.map((category) => category.id);
    const allSelected = ids.length > 0 && ids.every((id) => selected.includes(id));
    const toggleAll = () => setSelected(allSelected ? [] : ids);
    const toggle = (id) => setSelected((current) => current.includes(id) ? current.filter((item) => item !== id) : [...current, id]);
    const applyBulkAction = () => {
        if (bulkAction !== 'delete' || selected.length === 0) return;
        const selectedCategories = categories.data.filter((category) => selected.includes(category.id));
        if (!window.confirm(`Delete ${selectedCategories.length} selected ${entityLabel}s?`)) return;
        selectedCategories.forEach((category) => onDelete(category));
        setSelected([]);
    };

    return (
        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="border-b border-slate-200 px-5 py-5">
                <h2 className="text-base font-bold text-slate-950">{entityLabel.charAt(0).toUpperCase() + entityLabel.slice(1)} list</h2>
                <p className="mt-1 text-sm text-slate-500">Manage and organize product {entityLabel}s.</p>
            </div>

            <div className="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/60 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-2">
                    <select value={bulkAction} onChange={(event) => setBulkAction(event.target.value)} className="h-10 rounded-md border-slate-300 bg-white py-0 pl-3 pr-9 text-sm focus:border-violet-500 focus:ring-violet-500">
                        <option value="">Bulk actions</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="button" onClick={applyBulkAction} disabled={!bulkAction || selected.length === 0} className="h-10 rounded-md border border-violet-500 bg-white px-4 text-sm font-medium text-violet-600 transition-colors hover:bg-violet-50 disabled:cursor-not-allowed disabled:opacity-50">Apply</button>
                </div>
                <form onSubmit={onSearchSubmit} className="flex w-full gap-2 sm:max-w-sm" role="search">
                    <label className="relative min-w-0 flex-1">
                        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-500" />
                        <input type="search" value={search} onChange={(event) => onSearchChange(event.target.value)} placeholder="Search" className="h-10 w-full rounded-md border-slate-300 bg-white pl-10 pr-3 text-sm focus:border-violet-500 focus:ring-violet-500" />
                    </label>
                    <button type="submit" className="h-10 rounded-md border border-violet-500 bg-white px-4 text-sm font-medium text-violet-600 transition-colors hover:bg-violet-50">Search</button>
                </form>
            </div>

            {categories.data.length === 0 ? (
                <div className="grid min-h-64 place-items-center p-8 text-center"><div><span className="mx-auto grid size-12 place-items-center rounded-md bg-violet-50 text-violet-600"><Image className="size-6" /></span><h3 className="mt-3 font-bold">No {entityLabel}s found</h3><p className="mt-1 text-sm text-slate-500">Create a {entityLabel} or adjust your search.</p></div></div>
            ) : (
                <>
                    <div className="hidden overflow-x-auto md:block">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold text-slate-600">
                                <tr>
                                    <th className="w-10 px-3 py-3"><input type="checkbox" checked={allSelected} onChange={toggleAll} className="rounded border-slate-300 text-violet-600 focus:ring-violet-500" aria-label="Select all categories" /></th>
                                    <th className="w-14 px-2 py-3">Image</th><th className="px-3 py-3">Name</th><th className="px-3 py-3">Description</th><th className="px-3 py-3">Slug</th><th className="px-3 py-3 text-center">Count</th><th className="px-3 py-3 text-center">Products</th><th className="w-32 px-3 py-3"><span className="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {categories.data.map((category) => {
                                    const description = textFromHtml(category.description);
                                    return (
                                        <tr key={category.id} className="transition-colors hover:bg-violet-50/30">
                                            <td className="px-3 py-3"><input type="checkbox" checked={selected.includes(category.id)} onChange={() => toggle(category.id)} className="rounded border-slate-300 text-violet-600 focus:ring-violet-500" aria-label={`Select ${category.name}`} /></td>
                                            <td className="px-2 py-3">{category.image_url ? <img src={category.image_url} alt="" className="size-10 rounded-md border border-slate-200 object-cover" /> : <span className="grid size-10 place-items-center rounded-md bg-slate-100 text-slate-400"><Image className="size-4" /></span>}</td>
                                            <td className="px-3 py-3"><button type="button" onClick={() => onView(category)} className="category-table__name font-normal text-violet-600 hover:text-violet-800 hover:underline">{category.depth > 0 && <span className="mr-2 text-slate-400" aria-hidden="true">—</span>}{category.name}</button>{category.parent?.name && <p className="mt-0.5 pl-4 text-xs text-slate-400">in {category.parent.name}</p>}</td>
                                            <td className="max-w-xs px-3 py-3 text-slate-500"><p className="truncate">{description || '—'}</p></td>
                                            <td className="px-3 py-3 text-slate-600">/{category.slug}</td>
                                            <td className="px-3 py-3 text-center font-medium text-violet-600">{category.children_count || 0}</td>
                                            <td className="px-3 py-3 text-center font-medium text-slate-700">{category.products_count || 0}</td>
                                            <td className="px-3 py-3"><CategoryActions category={category} onView={onView} onEdit={onEdit} onDelete={onDelete} /></td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    <div className="divide-y divide-slate-200 md:hidden">
                        {categories.data.map((category) => <article key={category.id} className="flex gap-3 p-4 hover:bg-violet-50/30"><input type="checkbox" checked={selected.includes(category.id)} onChange={() => toggle(category.id)} className="mt-3 rounded border-slate-300 text-violet-600 focus:ring-violet-500" aria-label={`Select ${category.name}`} />{category.image_url ? <img src={category.image_url} alt="" className="size-10 rounded-md border border-slate-200 object-cover" /> : <span className="grid size-10 shrink-0 place-items-center rounded-md bg-slate-100 text-slate-400"><Image className="size-4" /></span>}<div className="min-w-0 flex-1"><button type="button" onClick={() => onView(category)} className="text-sm font-normal text-violet-600">{category.depth > 0 && <span className="mr-2 text-slate-400" aria-hidden="true">—</span>}{category.name}</button><p className="truncate text-xs text-slate-500">{textFromHtml(category.description) || 'No description'}</p><p className="mt-1 text-xs text-slate-400">/{category.slug} · {category.children_count || 0} subcategories · {category.products_count || 0} products</p><div className="mt-2"><CategoryActions category={category} onView={onView} onEdit={onEdit} onDelete={onDelete} /></div></div></article>)}
                    </div>
                </>
            )}
            <Pagination links={categories.links} from={categories.from} to={categories.to} total={categories.total} />
        </section>
    );
}
