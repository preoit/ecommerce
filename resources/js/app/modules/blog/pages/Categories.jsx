import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import AdminLayout from '@/app/layouts/AdminLayout';
import Dialog from '@/app/design-system/components/Dialog';
import RichTextEditor from '@/app/design-system/components/RichTextEditor';
import { Panel, Field, Errors, ImagePicker, categoryOptions, input, button, secondary, slugify, imageUrl } from './shared';

export function CategoryModal({ category, categories, close }) {
    const form = useForm({ name: category?.name || '', slug: category?.slug || '', parent_id: category?.parent_id || '', description: category?.description || '', image_path: category?.image_path || null });
    const [custom, setCustom] = useState(Boolean(category));
    const [editingPermalink, setEditingPermalink] = useState(false);
    const permalinkBase = `${window.location.origin}/`;
    const permalinkSlug = form.data.slug || 'category-name';
    const changeName = e => {
        const name = e.target.value;
        form.setData({ ...form.data, name, slug: custom ? form.data.slug : slugify(name) });
    };
    const finishPermalink = () => {
        const slug = form.data.slug || slugify(form.data.name);
        form.setData('slug', slug);
        setCustom(Boolean(slug));
        setEditingPermalink(false);
    };
    const submit = e => { e.preventDefault(); form[category ? 'patch' : 'post'](category ? route('blog.categories.update', category.id) : route('blog.categories.store'), { onSuccess: close }); };
    return <Dialog open onClose={close} title={category ? 'Edit blog category' : 'Add blog category'}><form onSubmit={submit} className="max-h-[75vh] space-y-4 overflow-y-auto p-5"><Errors errors={form.errors}/><Field label="Name"><input required className={input} value={form.data.name} onChange={changeName}/><div className="mt-2 text-xs font-normal text-slate-600 dark:text-slate-400">{editingPermalink ? <div className="flex flex-wrap items-center gap-2"><span className="font-medium">Permalink:</span><span className="break-all">{permalinkBase}</span><input required aria-label="Blog category permalink slug" value={form.data.slug} onChange={e => { setCustom(true); form.setData('slug', slugify(e.target.value)); }} className="h-8 min-w-40 flex-1 rounded-md border-slate-300 bg-white px-2 py-1 text-xs text-slate-900 focus:border-violet-500 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" placeholder="category-name" aria-invalid={Boolean(form.errors.slug)} autoFocus/><button type="button" onClick={finishPermalink} className="h-8 rounded-md border border-violet-600 bg-white px-3 font-semibold text-violet-700 hover:bg-violet-50 dark:bg-slate-900 dark:text-violet-300 dark:hover:bg-slate-800">Done</button></div> : <p className="flex flex-wrap items-center gap-x-1.5 gap-y-1"><span className="font-medium">Permalink:</span><span className="break-all">{permalinkBase}<span className="text-violet-700 underline dark:text-violet-300">{permalinkSlug}</span></span><button type="button" onClick={() => setEditingPermalink(true)} className="rounded border border-slate-300 bg-white px-2 py-1 font-semibold text-violet-700 hover:bg-violet-50 dark:border-slate-700 dark:bg-slate-900 dark:text-violet-300 dark:hover:bg-slate-800">Edit</button></p>}{form.errors.slug && <p className="mt-1 font-medium text-rose-600" role="alert">{form.errors.slug}</p>}</div></Field><Field label="Parent category"><select className={input} value={form.data.parent_id} onChange={e => form.setData('parent_id', e.target.value)}><option value="">None</option>{categoryOptions(categories).filter(c => c.id !== category?.id).map(c => <option key={c.id} value={c.id}>{'— '.repeat(c.depth)}{c.name}</option>)}</select></Field><Field label="Description"><RichTextEditor value={form.data.description} onChange={v => form.setData('description', v)}/></Field><ImagePicker value={form.data.image_path} onChange={v => form.setData('image_path', v)}/><button disabled={form.processing} className={button}>Save category</button></form></Dialog>;
}
export default function Categories({ categories }) {
    const [modal, setModal] = useState(false), [selected, setSelected] = useState(null), [search, setSearch] = useState('');
    const { errors } = usePage().props;
    return <AdminLayout><Head title="Blog categories"/><div className="mb-6 flex items-center justify-between"><div><p className="text-sm text-violet-600">Blog</p><h1 className="text-2xl font-bold">Categories</h1></div><button className={button} onClick={() => { setSelected(null); setModal(true); }}><Plus size={16}/>Add category</button></div><Errors errors={errors || {}}/><Panel><input aria-label="Search categories" placeholder="Search categories…" className={`${input} mb-5 max-w-sm`} value={search} onChange={e => setSearch(e.target.value)}/><div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead className="border-b text-slate-500"><tr>{['Category', 'Permalink', 'Blogs', 'Actions'].map(t => <th key={t} className="p-3">{t}</th>)}</tr></thead><tbody>{categoryOptions(categories).filter(c => c.name.toLowerCase().includes(search.toLowerCase())).map(c => <tr key={c.id} className="border-b border-slate-100 dark:border-slate-800"><td className="p-3"><div className="flex items-center gap-3" style={{ paddingLeft: c.depth * 16 }}>{c.image_path && <img className="size-10 rounded-lg object-cover" src={imageUrl(c.image_path)} alt=""/>}<b>{c.name}</b></div></td><td className="p-3">/{c.slug}</td><td className="p-3">{c.posts_count}</td><td className="p-3"><div className="flex gap-2"><button aria-label={`Edit ${c.name}`} className={secondary} onClick={() => { setSelected(c); setModal(true); }}><Pencil size={16}/></button><button aria-label={`Delete ${c.name}`} className={secondary} onClick={() => { if (confirm(`Delete category ${c.name}?`)) router.delete(route('blog.categories.destroy', c.id)); }}><Trash2 size={16}/></button></div></td></tr>)}</tbody></table>{!categories.length && <p className="p-8 text-center text-slate-500">No blog categories yet.</p>}</div></Panel>{modal && <CategoryModal category={selected} categories={categories} close={() => setModal(false)}/>}</AdminLayout>;
}
