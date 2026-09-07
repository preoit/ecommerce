import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useState } from 'react';
import Button from '@/app/design-system/components/Button';
import Dialog from '@/app/design-system/components/Dialog';
import FormField from '@/app/design-system/components/FormField';
import RichTextEditor from '@/app/design-system/components/RichTextEditor';
import CategoryImageUpload from './CategoryImageUpload';
import { slugify } from '../utils/slugify';

const inputClasses = 'block h-10 w-full rounded-md border-slate-300 text-sm shadow-sm placeholder:text-slate-400 focus:border-violet-500 focus:ring-violet-500';

export default function CategoryFormModal({ open, onClose, parentOptions, category = null, resource = 'categories', entityLabel = 'category', inlineStoreUrl = null, onCreated = null }) {
    const [editingPermalink, setEditingPermalink] = useState(false);
    const [inlineProcessing, setInlineProcessing] = useState(false);
    const form = useForm({
        name: category?.name || '',
        parent_id: category?.parent_id || '',
        slug: category?.slug || '',
        short_description: category?.short_description || '',
        description: category?.description || '',
        image_path: category?.image_path || null,
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

    return (
        <Dialog open={open} onClose={close} title={category ? `Edit ${entityLabel}` : `Add ${entityLabel}`} description={category ? `Update this ${entityLabel} and its catalog details.` : entityLabel === 'brand' ? 'Create a brand for your product catalog.' : entityLabel === 'unit' ? 'Create a unit for product measurement.' : 'Create a category and optionally place it under a parent category.'}>
            <form onSubmit={submit}>
                <div className="max-h-[calc(100vh-13rem)] space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
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
                <div className="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                    {Object.keys(form.errors).length > 0 && <span className="mr-auto text-xs font-medium text-red-600">Fix the highlighted fields and try again.</span>}
                    <Button type="button" variant="secondary" onClick={close} disabled={form.processing || inlineProcessing}>Cancel</Button>
                    <Button type="submit" icon={Save} loading={form.processing || inlineProcessing} className="!border-violet-600 !bg-violet-600 hover:!bg-violet-700 focus-visible:!ring-violet-500">{category ? `Update ${entityLabel}` : `Save ${entityLabel}`}</Button>
                </div>
            </form>
        </Dialog>
    );
}
