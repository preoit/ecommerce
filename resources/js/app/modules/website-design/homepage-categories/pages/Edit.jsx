import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ExternalLink, GripVertical, LayoutGrid, Plus, Save, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import Button from '@/app/design-system/components/Button';
import AdminLayout from '@/app/layouts/AdminLayout';

const defaultSection = categoryId => ({ category_id: Number(categoryId), enabled: true, limit: 8, sort: 'latest' });

export default function HomepageCategoriesEdit({ sections = [], categories = [] }) {
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        sections: sections.map(section => ({
            category_id: Number(section.category_id),
            enabled: section.enabled ?? true,
            limit: Number(section.limit || 8),
            sort: section.sort || 'latest',
        })),
    });
    const [selectedCategory, setSelectedCategory] = useState('');
    const [dragging, setDragging] = useState(null);
    const categoryMap = useMemo(() => new Map(categories.map(category => [Number(category.id), category])), [categories]);
    const used = new Set(data.sections.map(section => Number(section.category_id)));
    const available = categories.filter(category => !used.has(Number(category.id)));

    const replace = (index, values) => setData('sections', data.sections.map((section, current) => current === index ? { ...section, ...values } : section));
    const move = (index, direction) => {
        const destination = index + direction;
        if (destination < 0 || destination >= data.sections.length) return;
        const next = [...data.sections];
        [next[index], next[destination]] = [next[destination], next[index]];
        setData('sections', next);
    };
    const drop = destination => {
        if (dragging === null || dragging === destination) return setDragging(null);
        const next = [...data.sections];
        const [section] = next.splice(dragging, 1);
        next.splice(destination, 0, section);
        setData('sections', next);
        setDragging(null);
    };
    const add = () => {
        const categoryId = Number(selectedCategory || available[0]?.id);
        if (!categoryId) return;
        setData('sections', [...data.sections, defaultSection(categoryId)]);
        setSelectedCategory('');
    };
    const submit = event => {
        event.preventDefault();
        patch(route('website-design.homepage-categories.update'), { preserveScroll: true });
    };

    return <AdminLayout>
        <Head title="Homepage Categories" />
        <form onSubmit={submit} className="mx-auto max-w-6xl space-y-6">
            <header className="flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-3"><span className="grid size-11 place-items-center rounded-xl bg-violet-100 text-violet-700"><LayoutGrid className="size-5" /></span><div><p className="text-sm font-semibold text-violet-600">Website Design</p><h1 className="text-2xl font-bold text-slate-950">Homepage Categories</h1></div></div>
                <Link href="/" target="_blank" className="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:border-violet-300 hover:text-violet-700">View storefront<ExternalLink className="size-4" /></Link>
            </header>

            <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 className="text-lg font-bold text-slate-900">Add a main category</h2>
                <p className="mt-1 text-sm leading-6 text-slate-500">Only parent categories are available. Products assigned to any child category will still appear inside its main-category section.</p>
                <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                    <select value={selectedCategory} onChange={event => setSelectedCategory(event.target.value)} disabled={!available.length} className="h-11 min-w-0 flex-1 rounded-lg border-slate-300 text-sm disabled:bg-slate-100">
                        <option value="">{available.length ? 'Select a main category' : 'All main categories have been added'}</option>
                        {available.map(category => <option key={category.id} value={category.id}>{category.name}</option>)}
                    </select>
                    <button type="button" onClick={add} disabled={!available.length} className="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-slate-900 px-5 text-sm font-bold text-white hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-40"><Plus className="size-4" />Add category</button>
                </div>
            </section>

            <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex flex-wrap items-start justify-between gap-3"><div><h2 className="text-lg font-bold text-slate-900">Section order</h2><p className="mt-1 text-sm text-slate-500">Drag sections or use the arrow buttons to control their storefront position.</p></div><span className="rounded-full bg-violet-50 px-3 py-1 text-sm font-bold text-violet-700">{data.sections.length} sections</span></div>
                {data.sections.length ? <div className="mt-5 space-y-3">{data.sections.map((section, index) => {
                    const category = categoryMap.get(Number(section.category_id));
                    return <article key={section.category_id} draggable onDragStart={() => setDragging(index)} onDragEnd={() => setDragging(null)} onDragOver={event => event.preventDefault()} onDrop={() => drop(index)} className={`rounded-xl border p-4 transition ${dragging === index ? 'border-violet-400 bg-violet-50 opacity-70' : 'border-slate-200 bg-white'}`}>
                        <div className="flex items-start gap-3">
                            <button type="button" className="mt-1 cursor-grab text-slate-400 active:cursor-grabbing" aria-label={`Drag ${category?.name || 'category'} section`}><GripVertical className="size-5" /></button>
                            <span className="grid size-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-sm font-black text-slate-600">{index + 1}</span>
                            <div className="min-w-0 flex-1"><h3 className="truncate font-bold text-slate-900">{category?.name || 'Unavailable category'}</h3><p className="mt-0.5 truncate text-sm text-slate-500">/{category?.slug || 'category'}</p></div>
                            <label className="flex shrink-0 items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" checked={section.enabled} onChange={event => replace(index, { enabled: event.target.checked })} className="rounded border-slate-300 text-violet-600 focus:ring-violet-500" />Enabled</label>
                        </div>
                        <div className="mt-4 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-[1fr_1fr_auto]">
                            <label className="text-sm font-semibold text-slate-700">Products shown<select value={section.limit} onChange={event => replace(index, { limit: Number(event.target.value) })} className="mt-2 h-10 w-full rounded-lg border-slate-300 text-sm">{[4, 8, 12, 16].map(limit => <option key={limit} value={limit}>{limit} products</option>)}</select></label>
                            <label className="text-sm font-semibold text-slate-700">Product order<select value={section.sort} onChange={event => replace(index, { sort: event.target.value })} className="mt-2 h-10 w-full rounded-lg border-slate-300 text-sm"><option value="latest">Latest first</option><option value="featured">Featured first</option><option value="best_seller">Best sellers first</option></select></label>
                            <div className="flex items-end justify-end gap-1">
                                <button type="button" onClick={() => move(index, -1)} disabled={index === 0} className="grid size-10 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:border-violet-300 hover:text-violet-700 disabled:opacity-30" aria-label="Move section up"><ArrowUp className="size-4" /></button>
                                <button type="button" onClick={() => move(index, 1)} disabled={index === data.sections.length - 1} className="grid size-10 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:border-violet-300 hover:text-violet-700 disabled:opacity-30" aria-label="Move section down"><ArrowDown className="size-4" /></button>
                                <button type="button" onClick={() => setData('sections', data.sections.filter((_, current) => current !== index))} className="grid size-10 place-items-center rounded-lg border border-rose-100 text-rose-600 hover:bg-rose-50" aria-label="Remove section"><Trash2 className="size-4" /></button>
                            </div>
                        </div>
                    </article>;
                })}</div> : <div className="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-12 text-center"><LayoutGrid className="mx-auto size-9 text-slate-400" /><h3 className="mt-3 font-bold text-slate-800">No category sections yet</h3><p className="mt-1 text-sm text-slate-500">Add a main category above to start building the storefront homepage.</p></div>}
            </section>

            {Object.keys(errors).length > 0 && <div role="alert" className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{Object.values(errors)[0]}</div>}
            {recentlySuccessful && <div role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">Homepage category sections saved successfully.</div>}
            <div className="flex justify-end"><Button type="submit" icon={Save} loading={processing} className="!border-violet-600 !bg-violet-600 hover:!bg-violet-700">Save homepage sections</Button></div>
        </form>
    </AdminLayout>;
}
