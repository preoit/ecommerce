import { useState } from 'react';
import MediaLibraryPicker from '@/app/components/MediaLibraryPicker';

export default function CategoryImageUpload({ onChange }) {
    const [image, setImage] = useState(null);
    const select = (file) => { setImage(file); onChange(file?.path ?? null); };
    return <div className="rounded-lg border border-slate-200 p-3"><MediaLibraryPicker value={image} onChange={select} label="Open Media Library" />{image && <button type="button" onClick={() => select(null)} className="mt-3 text-xs font-semibold text-rose-600">Remove selected image</button>}</div>;
}
