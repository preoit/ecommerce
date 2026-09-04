import { Head, useForm } from '@inertiajs/react';
import { GalleryHorizontalEnd, Save } from 'lucide-react';
import { useState } from 'react';
import MediaLibraryPicker from '@/app/components/MediaLibraryPicker';
import Button from '@/app/design-system/components/Button';
import FormField from '@/app/design-system/components/FormField';
import AdminLayout from '@/app/layouts/AdminLayout';

function HeroImageField({ title, description, value, onChange, onRemove, multiple = false }) {
    return (
        <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="mb-4 flex items-start justify-between gap-3">
                <div><h2 className="font-bold text-slate-900">{title}</h2><p className="mt-1 text-sm text-slate-500">{description}</p></div>
                {value && <button type="button" onClick={onRemove} className="text-sm font-semibold text-red-600 hover:text-red-700">Remove</button>}
            </div>
            <MediaLibraryPicker value={value} onChange={onChange} multiple={multiple} label={`Choose ${title.toLowerCase()}`} />
        </section>
    );
}

export default function HeroSectionEdit({ hero }) {
    const [primaryImages, setPrimaryImages] = useState(hero.primaryImages || []);
    const [secondaryImage, setSecondaryImage] = useState(hero.secondaryImage);
    const { data, setData, patch, processing, errors } = useForm({
        hero_primary_image_path: hero.primaryImages?.[0]?.path || '',
        hero_primary_image_paths: (hero.primaryImages || []).map((image) => image.path),
        hero_primary_link: hero.primaryLink || '',
        hero_secondary_image_path: hero.secondaryImage?.path || '',
        hero_secondary_link: hero.secondaryLink || '',
    });

    const submit = (event) => { event.preventDefault(); patch('/admin/website-design/hero-section', { preserveScroll: true }); };

    return (
        <AdminLayout>
            <Head title="Hero Section" />
            <div className="mx-auto max-w-6xl">
                <div className="mb-6 flex items-center gap-3"><span className="grid size-11 place-items-center rounded-xl bg-violet-100 text-violet-700"><GalleryHorizontalEnd className="size-5" /></span><div><p className="text-sm text-violet-600">Website Design</p><h1 className="text-2xl font-bold">Hero Section</h1></div></div>
                <form onSubmit={submit} className="space-y-6">
                    <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="font-bold text-slate-900">Two-column hero layout</h2>
                        <p className="mt-1 text-sm text-slate-500">The primary banner uses two-thirds of the row and the secondary banner uses one-third.</p>
                        <div className="mt-5 grid h-44 grid-cols-3 gap-3 rounded-lg bg-slate-50 p-3">
                            <div className="col-span-2 overflow-hidden rounded-lg border border-dashed border-violet-300 bg-violet-50">{primaryImages.length ? <img src={primaryImages[0].url} alt="Primary banner preview" className="h-full w-full object-cover" /> : <span className="grid h-full place-items-center text-sm text-violet-600">Primary slider</span>}</div>
                            <div className="overflow-hidden rounded-lg border border-dashed border-violet-300 bg-violet-50">{secondaryImage ? <img src={secondaryImage.url} alt="Secondary banner preview" className="h-full w-full object-cover" /> : <span className="grid h-full place-items-center text-sm text-violet-600">Secondary banner</span>}</div>
                        </div>
                    </section>
                    <div className="grid gap-6 lg:grid-cols-2">
                        <div className="space-y-4"><HeroImageField title="Primary slider" description="Select up to 8 images. Recommended ratio: approximately 2.5:1." value={primaryImages} multiple onChange={(files) => { setPrimaryImages(files); setData((current) => ({ ...current, hero_primary_image_paths: files.map((file) => file.path), hero_primary_image_path: files[0]?.path || '' })); }} onRemove={() => { setPrimaryImages([]); setData((current) => ({ ...current, hero_primary_image_paths: [], hero_primary_image_path: '' })); }} /><FormField label="Primary slider link" error={errors.hero_primary_link}><input value={data.hero_primary_link} onChange={(event) => setData('hero_primary_link', event.target.value)} placeholder="/category-slug or https://..." className="mt-2 h-10 w-full rounded-md border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500" /></FormField></div>
                        <div className="space-y-4"><HeroImageField title="Secondary banner" description="Recommended ratio: approximately 1.2:1." value={secondaryImage} onChange={(file) => { setSecondaryImage(file); setData('hero_secondary_image_path', file.path); }} onRemove={() => { setSecondaryImage(null); setData('hero_secondary_image_path', ''); }} /><FormField label="Secondary banner link" error={errors.hero_secondary_link}><input value={data.hero_secondary_link} onChange={(event) => setData('hero_secondary_link', event.target.value)} placeholder="/category-slug or https://..." className="mt-2 h-10 w-full rounded-md border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500" /></FormField></div>
                    </div>
                    <div className="flex justify-end"><Button type="submit" icon={Save} loading={processing} className="!border-violet-600 !bg-violet-600 hover:!bg-violet-700">Save hero section</Button></div>
                </form>
            </div>
        </AdminLayout>
    );
}
