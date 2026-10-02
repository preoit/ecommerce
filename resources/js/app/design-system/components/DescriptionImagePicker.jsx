import { ImagePlus, Search, Upload, X } from 'lucide-react';
import { useRef, useState } from 'react';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

export default function DescriptionImagePicker({ onOpen, onSelect }) {
    const input = useRef(null);
    const [open, setOpen] = useState(false);
    const [media, setMedia] = useState([]);
    const [selected, setSelected] = useState(null);
    const [altText, setAltText] = useState('');
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState('');

    const choose = (file) => {
        setSelected(file);
        setAltText(file.altText || file.title || file.name || '');
    };

    const openPicker = async () => {
        onOpen?.();
        setOpen(true);
        setSelected(null);
        setAltText('');
        setSearch('');
        setError('');
        setLoading(true);
        try {
            const response = await fetch('/admin/file-manager/media', { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Could not load File Manager images.');
            const files = await response.json();
            setMedia(Object.values(files).filter((file) => file.mimeType?.startsWith('image/')));
        } catch (cause) {
            setError(cause.message || 'Could not load File Manager images.');
        } finally {
            setLoading(false);
        }
    };

    const upload = async (event) => {
        const file = event.target.files?.[0];
        if (!file) return;
        setError('');
        setUploading(true);
        const body = new FormData();
        body.append('file', file);
        try {
            const response = await fetch('/admin/settings/website/media', {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                body,
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.errors?.file?.[0] || result.message || 'Image upload failed.');
            setMedia((current) => [result, ...current]);
            choose(result);
        } catch (cause) {
            setError(cause.message || 'Image upload failed.');
        } finally {
            setUploading(false);
            event.target.value = '';
        }
    };

    const insert = () => {
        if (!selected) return;
        onSelect({ ...selected, altText: altText.trim() || selected.name || '' });
        setOpen(false);
    };

    const visible = media.filter((file) => (file.name + ' ' + (file.title || '') + ' ' + (file.altText || '')).toLowerCase().includes(search.toLowerCase()));

    return <>
        <button type="button" aria-label="Insert image from File Manager" title="Insert image" onMouseDown={(event) => event.preventDefault()} onClick={openPicker} className="grid size-8 shrink-0 place-items-center rounded-md text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900">
            <ImagePlus className="size-4" aria-hidden="true" />
        </button>
        {open && <div className="fixed inset-0 z-[110] grid place-items-center bg-slate-950/60 p-3 sm:p-5" onMouseDown={(event) => { if (event.target === event.currentTarget) setOpen(false); }}>
            <section role="dialog" aria-modal="true" aria-label="Insert description image" className="flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white text-slate-900 shadow-2xl">
                <div className="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 p-4 sm:p-5">
                    <div><h2 className="text-lg font-bold">Insert image</h2><p className="text-sm text-slate-500">Choose an image from File Manager or upload a new one.</p></div>
                    <div className="flex items-center gap-2">
                        <input ref={input} type="file" accept="image/*" className="hidden" onChange={upload} />
                        <button type="button" disabled={uploading} onClick={() => input.current?.click()} className="inline-flex min-h-10 items-center gap-2 rounded-lg bg-violet-600 px-3 text-sm font-semibold text-white disabled:opacity-50"><Upload className="size-4" />{uploading ? 'Uploading...' : 'Upload image'}</button>
                        <button type="button" aria-label="Close image picker" onClick={() => setOpen(false)} className="grid size-10 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><X className="size-5" /></button>
                    </div>
                </div>
                <div className="overflow-y-auto p-4 sm:p-5">
                    <label className="relative block"><Search className="absolute left-3 top-3 size-4 text-slate-400" /><span className="sr-only">Search images</span><input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search images" className="w-full rounded-lg border-slate-300 pl-10 text-sm focus:border-violet-500 focus:ring-violet-500" /></label>
                    {error && <p role="alert" className="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{error}</p>}
                    {loading ? <p role="status" className="py-12 text-center text-sm text-slate-500">Loading images...</p> : visible.length ? <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">{visible.map((file) => <button key={file.id} type="button" onClick={() => choose(file)} aria-pressed={selected?.id === file.id} className={'overflow-hidden rounded-xl border p-2 text-left transition ' + (selected?.id === file.id ? 'border-violet-600 ring-2 ring-violet-200' : 'border-slate-200 hover:border-violet-300')}><img src={file.url} alt={file.altText || file.name} loading="lazy" className="h-28 w-full rounded-lg bg-slate-50 object-contain" /><span className="mt-2 block truncate text-xs font-medium" title={file.name}>{file.name}</span></button>)}</div> : <p className="py-12 text-center text-sm text-slate-500">No images found. Upload one to add it to File Manager.</p>}
                    {selected && <label className="mt-5 block text-sm font-semibold">Image alt text<input value={altText} onChange={(event) => setAltText(event.target.value)} placeholder="Describe this image" className="mt-2 w-full rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500" /></label>}
                </div>
                <div className="flex justify-end gap-2 border-t border-slate-200 p-4 sm:p-5"><button type="button" onClick={() => setOpen(false)} className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Cancel</button><button type="button" disabled={!selected || uploading} onClick={insert} className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Insert image</button></div>
            </section>
        </div>}
    </>;
}
