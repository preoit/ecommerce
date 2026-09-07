import { Head, Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useState } from 'react';
import Button from '@/app/design-system/components/Button';
import Dialog from '@/app/design-system/components/Dialog';
import FormField from '@/app/design-system/components/FormField';
import RichTextEditor from '@/app/design-system/components/RichTextEditor';
import AdminLayout from '@/app/layouts/AdminLayout';
import CategoryImageUpload from './CategoryImageUpload';
import { slugify } from '../utils/slugify';

const inputClasses = 'block h-10 w-full rounded-md border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500';

export default function CategoryFormModal({ open, onClose = () => {}, parentOptions, category = null, resource = 'categories', entityLabel = 'category', inlineStoreUrl = null, onCreated = null, page = false }) {
    const [editingPermalink, setEditingPermalink] = useState(false);
    const [inlineProcessing, setInlineProcessing] = useState(false);
    const form = useForm({
        name: category?.name || '',
        parent_id: category?.parent_id || '',
        slug: category?.slug || '',
        short_description: category?.short_description || '',
        description: category?.description || '',
        image_path: category?.image_path || null,
        seo_title: category?.seo_title || '',
        meta_description: category?.meta_description || '',
        focus_keyword: category?.focus_keyword || '',
        canonical_url: category?.canonical_url || '',
        meta_robots: category?.meta_robots || 'index,follow',
        og_title: category?.og_title || '',
        og_description: category?.og_description || '',
    });

    const close = () => {
        if (form.processing || inlineProcessing) return;
        form.reset();
        form.clearErrors();
        onClose();
    };

    const changeName = (event) => {
        const name = event.target.value;
        form.setData((data) => ({ ...data, name, slug: slugify(name) }));
    };

    const submit = (event) => {
        event.preventDefault();
        form.clearErrors();
        if (inlineStoreUrl && !category) {
            setInlineProcessing(true);
            fetch(inlineStoreUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify(form.data),
            }).then(async (response) => ({ response, result: await response.json() })).then(({ response, result }) => {
                if (!response.ok) {
                    Object.entries(result.errors || { name: [result.message || `${entityLabel} could not be saved.`] }).forEach(([field, messages]) => form.setError(field, Array.isArray(messages) ? messages[0] : messages));
                    return;
                }
                onCreated?.(result.category);
                form.reset();
                form.clearErrors();
                onClose();
            }).finally(() => setInlineProcessing(false));
            return;
        }
        form.transform((data) => category ? { ...data, _method: 'put' } : data);
        form.post(
            category ? route(`inventories.${resource}.update`, category.id) : route(`inventories.${resource}.store`),
            {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    form.clearErrors();
                    onClose();
                },
                onError: () => {
                    requestAnimationFrame(() => document.querySelector('[aria-invalid="true"]')?.focus());
                },
            },
        );
    };

    const permalinkBase = `${window.location.origin}/`;
    const permalinkSlug = form.data.slug || `${entityLabel}-name`;

    const formContent = (
            <form onSubmit={submit}>
                <div className={`${page ? '' : 'max-h-[calc(100vh-13rem)] overflow-y-auto'} space-y-5 px-5 py-5 sm:px-6`}>
                    {Object.keys(form.errors).length > 0 && (
                        <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                            <p className="font-semibold">{entityLabel} could not be saved.</p>
                            <ul className="mt-1 list-disc space-y-0.5 pl-5">
                                {Object.entries(form.errors).map(([field, message]) => <li key={field}>{message}</li>)}
                            </ul>
                        </div>
                    )}
                    <FormField label={`${entityLabel.charAt(0).toUpperCase() + entityLabel.slice(1)} name`} htmlFor="category-name" required error={form.errors.name}>
                        <input id="category-name" value={form.data.name} onChange={changeName} className={inputClasses} placeholder={entityLabel === 'brand' ? 'e.g. Apple' : 'e.g. Mobile Phones'} aria-invalid={Boolean(form.errors.name)} autoFocus />
                        {entityLabel !== 'unit' && <div className="mt-2 text-xs text-slate-600">
                            {editingPermalink ? (
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium">Permalink:</span>
                                    <span className="break-all">{permalinkBase}</span>
                                    <input
                                        id="category-slug"
                                        aria-label="Permalink slug"
                                        value={form.data.slug}
                                        onChange={(event) => form.setData('slug', slugify(event.target.value))}
                                        className="h-8 min-w-40 flex-1 rounded-md border-slate-300 px-2 py-1 text-xs focus:border-violet-500 focus:ring-violet-500"
                                        placeholder={`${entityLabel}-name`}
                                        aria-invalid={Boolean(form.errors.slug)}
                                        autoFocus
                                    />
                                    <button type="button" onClick={() => setEditingPermalink(false)} className="h-8 rounded-md border border-violet-600 bg-white px-3 font-semibold text-violet-700 hover:bg-violet-50">Done</button>
                                </div>
                            ) : (
                                <p className="flex flex-wrap items-center gap-x-1.5 gap-y-1">
                                    <span className="font-medium">Permalink:</span>
                                    <span className="break-all">{permalinkBase}<span className="text-violet-700 underline">{permalinkSlug}</span></span>
                                    <button type="button" onClick={() => setEditingPermalink(true)} className="rounded border border-slate-300 bg-white px-2 py-1 font-semibold text-violet-700 hover:bg-violet-50">Edit</button>
                                </p>
                            )}
                            {form.errors.slug && <p className="mt-1 font-medium text-red-600" role="alert">{form.errors.slug}</p>}
                        </div>}
                    </FormField>
                    {entityLabel === 'category' && <FormField label={`Parent ${entityLabel}`} htmlFor="parent-category" hint={`Leave empty to create a top-level ${entityLabel}.`} error={form.errors.parent_id}>
                        <select id="parent-category" value={form.data.parent_id} onChange={(event) => form.setData('parent_id', event.target.value)} className={inputClasses} aria-invalid={Boolean(form.errors.parent_id)}>
                            <option value="">None (Top-level category)</option>
                            {parentOptions.filter((option) => option.id !== category?.id).map((option) => <option key={option.id} value={option.id}>{`${'-- '.repeat(option.depth)}${option.name}`}</option>)}
                        </select>
                    </FormField>}
                    {(entityLabel === 'category' || entityLabel === 'brand') && <FormField label="Short description" htmlFor="category-short-description" hint={entityLabel === 'category' ? 'Shown near the breadcrumb on the category page.' : `A short customer-facing summary of this ${entityLabel}.`} error={form.errors.short_description}>
                        <RichTextEditor
                            id="category-short-description"
                            value={form.data.short_description}
                            onChange={(description) => form.setData('short_description', description)}
                            placeholder={`A short summary of this ${entityLabel}.`}
                        />
                    </FormField>}
                    {entityLabel !== 'unit' && <><FormField label={entityLabel === 'category' ? 'Long description' : 'Brand description'} htmlFor="category-description" hint={entityLabel === 'category' ? 'Shown below the product list and pagination.' : 'Describe this brand for customers.'} error={form.errors.description}>
                        <RichTextEditor
                            id="category-description"
                            value={form.data.description}
                            onChange={(description) => form.setData('description', description)}
                            placeholder={entityLabel === 'brand' ? 'Describe this brand and its products.' : 'Describe what customers will find in this category.'}
                        />
                    </FormField>
                    <FormField label={entityLabel === 'brand' ? 'Brand logo' : 'Icon or image'} htmlFor="category-image" hint={entityLabel === 'brand' ? 'Select the brand logo from the Media Library.' : 'Select a square image from the Media Library.'} error={form.errors.image_path}>
                        <CategoryImageUpload onChange={(imagePath) => form.setData('image_path', imagePath)} />
                    </FormField></>}
                </div>
                {(entityLabel === 'category' || entityLabel === 'brand') && <section className="mx-5 mb-5 rounded-xl border border-violet-100 bg-violet-50/40 p-4 sm:mx-6">
                    <h3 className="font-bold text-slate-800">SEO Settings</h3>
                    <p className="mt-1 text-xs text-slate-500">Control search results and social sharing. Empty fields use the name and short description.</p>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                        <FormField label="SEO title" htmlFor="entity-seo-title" error={form.errors.seo_title}><input id="entity-seo-title" value={form.data.seo_title} onChange={(event) => form.setData('seo_title', event.target.value)} className={inputClasses} maxLength="160" placeholder={`${entityLabel} SEO title`} /></FormField>
                        <FormField label="Focus keyword" htmlFor="entity-focus-keyword" error={form.errors.focus_keyword}><input id="entity-focus-keyword" value={form.data.focus_keyword} onChange={(event) => form.setData('focus_keyword', event.target.value)} className={inputClasses} placeholder="Primary search keyword" /></FormField>
                        <div className="sm:col-span-2"><FormField label="Meta description" htmlFor="entity-meta-description" error={form.errors.meta_description}><textarea id="entity-meta-description" value={form.data.meta_description} onChange={(event) => form.setData('meta_description', event.target.value)} className="block min-h-24 w-full rounded-md border-slate-300 text-sm" maxLength="320" /></FormField></div>
                        <FormField label="Canonical URL" htmlFor="entity-canonical-url" error={form.errors.canonical_url}><input id="entity-canonical-url" type="url" value={form.data.canonical_url} onChange={(event) => form.setData('canonical_url', event.target.value)} className={inputClasses} placeholder="Leave empty for automatic URL" /></FormField>
                        <FormField label="Robots" htmlFor="entity-meta-robots" error={form.errors.meta_robots}><select id="entity-meta-robots" value={form.data.meta_robots} onChange={(event) => form.setData('meta_robots', event.target.value)} className={inputClasses}><option value="index,follow">Index, Follow</option><option value="noindex,follow">Noindex, Follow</option><option value="noindex,nofollow">Noindex, Nofollow</option></select></FormField>
                        <FormField label="OG title" htmlFor="entity-og-title" error={form.errors.og_title}><input id="entity-og-title" value={form.data.og_title} onChange={(event) => form.setData('og_title', event.target.value)} className={inputClasses} maxLength="160" /></FormField>
                        <FormField label="OG description" htmlFor="entity-og-description" error={form.errors.og_description}><textarea id="entity-og-description" value={form.data.og_description} onChange={(event) => form.setData('og_description', event.target.value)} className="block min-h-20 w-full rounded-md border-slate-300 text-sm" maxLength="320" /></FormField>
                    </div>
                </section>}                <div className="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                    {Object.keys(form.errors).length > 0 && <span className="mr-auto text-xs font-medium text-red-600">Fix the highlighted fields and try again.</span>}
                    <Button type="button" variant="secondary" onClick={close} disabled={form.processing || inlineProcessing}>Cancel</Button>
                    <Button type="submit" icon={Save} loading={form.processing || inlineProcessing} className="!border-violet-600 !bg-violet-600 hover:!bg-violet-700 focus-visible:!ring-violet-500">{category ? `Update ${entityLabel}` : `Save ${entityLabel}`}</Button>
                </div>
            </form>
    );

    const formTitle = category ? `Edit ${entityLabel}` : `Add ${entityLabel}`;
    const formDescription = category ? `Update this ${entityLabel} and its catalog details.` : `Create a ${entityLabel} for your product catalog.`;

    if (page) {
        return <AdminLayout><Head title={formTitle} /><div className="mx-auto max-w-5xl space-y-6"><div className="flex items-center justify-between gap-4"><div><p className="text-sm font-semibold text-violet-600">Inventories</p><h1 className="text-2xl font-bold text-slate-950">{formTitle}</h1><p className="mt-1 text-sm text-slate-500">{formDescription}</p></div><Link href={route(`inventories.${resource}.index`)} className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Back to list</Link></div><section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">{formContent}</section></div></AdminLayout>;
    }

    return <Dialog open={open} onClose={close} title={formTitle} description={formDescription}>{formContent}</Dialog>;
}
