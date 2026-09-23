import { Head, Link, router } from '@inertiajs/react';
import { BadgeDollarSign, Eye, Image, Pencil, Plus, Search, Trash2, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import IconButton from '@/app/design-system/components/IconButton';
import AdminLayout from '@/app/layouts/AdminLayout';

const money = (value) => `৳${Number(value || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}`;
const actionClass = 'size-11 rounded-lg border-0 bg-slate-100 text-slate-600 hover:bg-violet-100 hover:text-violet-700 xl:size-8';

function ProductActions({ product, onView, onDelete }) {
    return <div className="flex items-center justify-end gap-1.5"><IconButton icon={Eye} iconClassName="!size-4" label={`View ${product.title}`} onClick={() => onView(product)} className={actionClass} /><IconButton icon={Pencil} iconClassName="!size-4" label={`Edit ${product.title}`} onClick={() => { window.location.href = `${route('inventories.products.create')}?edit=${product.id}`; }} className={actionClass} /><IconButton icon={Trash2} iconClassName="!size-4" label={`Delete ${product.title}`} onClick={() => onDelete(product)} className={`${actionClass} hover:!bg-red-50 hover:!text-red-600`} /></div>;
}

function ProductPrice({ product }) {
    return (
        <div className="flex flex-col items-start gap-0.5 whitespace-nowrap">
            <b className="text-slate-900">{money(product.salePrice || product.price)}</b>
            {Boolean(product.salePrice) && <span className="text-xs text-slate-400 line-through">{money(product.price)}</span>}
        </div>
    );
}

function ProductStatus({ status }) {
    return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${status === 'Published' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}`}>{status}</span>;
}

function ProductImage({ product }) {
    return product.image
        ? <img src={product.image} alt="" loading="lazy" className="size-16 shrink-0 rounded-lg object-cover xl:size-12" />
        : <span className="grid size-16 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400 xl:size-12"><Image className="size-5" aria-hidden="true" /></span>;
}

function ViewModal({ product, onClose }) {
    useEffect(() => {
        if (!product) return;
        const details = document.querySelector('.fixed.inset-0.z-50 dl');
        if (!details || details.dataset.fullProductInfo) return;
        const values = [
            ['Brand', product.brand], ['Barcode', product.barcode], ['Model', product.model], ['Manufacturer', product.manufacturer], ['Country of origin', product.countryOfOrigin], ['Weight', product.weight], ['Dimensions', product.dimensions], ['Warranty', product.warranty], ['Estimated delivery', product.estimatedDelivery], ['Inside Dhaka delivery', product.deliveryInsideDhaka !== null && product.deliveryInsideDhaka !== undefined ? money(product.deliveryInsideDhaka) : null], ['Outside Dhaka delivery', product.deliveryOutsideDhaka !== null && product.deliveryOutsideDhaka !== undefined ? money(product.deliveryOutsideDhaka) : null], ['Payment methods', product.paymentMethods], ['SEO title', product.seoTitle], ['Meta description', product.metaDescription], ['Short description', product.shortDescription], ['Description', product.description?.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()], ['Return policy', product.returnPolicy], ['Replacement policy', product.replacementPolicy],
        ].filter(([, value]) => value);
        values.forEach(([label, value]) => { const item = document.createElement('div'); item.innerHTML = `<dt class="text-slate-500">${label}</dt><dd class="mt-1 font-semibold">${value}</dd>`; details.append(item); });
        details.dataset.fullProductInfo = 'true';
    }, [product]);
    if (!product) return null;
    return <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/45 p-4" onMouseDown={(event) => event.target === event.currentTarget && onClose()}><div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl"><div className="flex justify-between"><div><p className="text-sm font-semibold text-violet-600">Product details</p><h2 className="mt-1 text-xl font-bold">{product.title}</h2></div><IconButton icon={X} label="Close" onClick={onClose} /></div><dl className="mt-6 grid grid-cols-2 gap-4 text-sm">{[['SKU',product.sku||'—'],['Status',product.status],['Price',money(product.salePrice||product.price)],['Stock',`${product.stock} ${product.unit||''}`],['Categories',product.categories.join(', ')||'—'],['Visibility',product.visibility]].map(([label,value])=><div key={label}><dt className="text-slate-500">{label}</dt><dd className="mt-1 font-semibold">{value}</dd></div>)}</dl>{product.status === 'Published' && <a href={route('storefront.products.show',product.slug)} target="_blank" rel="noreferrer" className="mt-6 inline-flex rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white">Open storefront page</a>}</div></div>;
}

function EditModal({ product, onClose }) {
    const [form, setForm] = useState({ title:product.title, regular_price:product.price, sale_price:product.salePrice||'', stock_quantity:product.stock, status:product.status, visibility:product.visibility });
    const field = (key) => (event) => setForm({...form,[key]:event.target.value});
    const save = (event) => { event.preventDefault(); router.patch(route('inventories.products.update',product.id),form,{preserveScroll:true,onSuccess:onClose}); };
    const input = 'mt-2 h-11 w-full rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500';
    return <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/45 p-4" onMouseDown={(event) => event.target === event.currentTarget && onClose()}><form onSubmit={save} className="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl"><div className="flex justify-between"><div><p className="text-sm font-semibold text-violet-600">Edit product</p><h2 className="mt-1 text-xl font-bold">{product.title}</h2></div><IconButton icon={X} label="Close" onClick={onClose} /></div><div className="mt-6 grid gap-4 sm:grid-cols-2"><label className="text-sm font-semibold sm:col-span-2">Product title<input required value={form.title} onChange={field('title')} className={input}/></label><label className="text-sm font-semibold">Regular price<input required type="number" min="0" step="0.01" value={form.regular_price} onChange={field('regular_price')} className={input}/></label><label className="text-sm font-semibold">Sale price<input type="number" min="0" step="0.01" value={form.sale_price} onChange={field('sale_price')} className={input}/></label><label className="text-sm font-semibold">Stock<input required type="number" min="0" value={form.stock_quantity} onChange={field('stock_quantity')} className={input}/></label><label className="text-sm font-semibold">Status<select value={form.status} onChange={field('status')} className={input}>{['Draft','Published','Active','Inactive','Discontinued'].map(item=><option key={item}>{item}</option>)}</select></label><label className="text-sm font-semibold">Visibility<select value={form.visibility} onChange={field('visibility')} className={input}><option>Public</option><option>Private</option></select></label></div><div className="mt-6 flex justify-end gap-2"><button type="button" onClick={onClose} className="rounded-lg border px-4 py-2 text-sm font-semibold">Cancel</button><button className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white">Save changes</button></div></form></div>;
}

export default function ProductsIndex({ products=[] }) {
    const openStorefront = (product) => window.open(route('storefront.products.show', product.slug), '_blank', 'noopener,noreferrer');
    const [query,setQuery]=useState(''); const [editing,setEditing]=useState(null); const viewing = null; const setViewing = openStorefront;
    const visible=useMemo(()=>products.filter(product=>`${product.title} ${product.sku||''} ${product.categories.join(' ')}`.toLowerCase().includes(query.toLowerCase())),[products,query]);
    const remove=(product)=>window.confirm(`Delete "${product.title}"? This action cannot be undone.`)&&router.delete(route('inventories.products.destroy',product.id),{preserveScroll:true});
    return (
        <AdminLayout>
            <Head title="Products" />
            <main className="-mx-4 -my-6 min-h-screen bg-[#f8f7fa] p-4 text-slate-800 sm:-mx-6 sm:p-6 lg:-mx-8 lg:-my-8 lg:p-8">
                <div className="mx-auto min-w-0 max-w-[1450px]">
                    <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-violet-600">Inventories</p>
                            <h1 className="mt-1 text-2xl font-bold text-slate-950">Products</h1>
                            <p className="mt-1 text-sm text-slate-500">Manage published products, prices and stock.</p>
                        </div>
                        <Link href={route('inventories.products.create')} className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white">
                            <Plus className="size-4 shrink-0" aria-hidden="true" />Add new product
                        </Link>
                    </div>
                    <section aria-label="Product inventory" className="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="shrink-0 text-sm text-slate-500" role="status" aria-live="polite">{visible.length} {visible.length === 1 ? 'product' : 'products'}{query ? ` of ${products.length}` : ''}</p>
                            <label className="relative block w-full sm:max-w-sm">
                                <span className="sr-only">Search products</span>
                                <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                                <input type="search" value={query} onChange={event => setQuery(event.target.value)} placeholder="Search products" className="h-11 w-full min-w-0 rounded-lg border-slate-300 pl-10 text-sm" />
                            </label>
                        </div>

                        {/* Cards keep all inventory actions accessible without horizontal scrolling. */}
                        {visible.length > 0 && <ul className="grid min-w-0 gap-4 p-3 sm:grid-cols-2 sm:p-4 xl:hidden">
                            {visible.map(product => <li key={product.id} className="flex min-w-0 flex-col rounded-xl border border-slate-200 p-4">
                                <div className="flex min-w-0 items-start gap-3">
                                    <ProductImage product={product} />
                                    <div className="min-w-0 flex-1">
                                        <button type="button" onClick={() => openStorefront(product)} className="text-left text-sm font-semibold leading-5 text-violet-600 [overflow-wrap:anywhere] hover:underline">{product.title}</button>
                                        <p className="mt-1 truncate text-xs text-slate-500" title={`/${product.slug}`}>/{product.slug}</p>
                                        <p className="mt-2 break-all text-xs text-slate-500">SKU: <span className="text-slate-800">{product.sku || '—'}</span></p>
                                    </div>
                                </div>
                                <dl className="my-4 grid min-w-0 grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)_minmax(0,1fr)] gap-2 text-sm">
                                    <div className="min-w-0">
                                        <dt className="text-xs text-slate-500">Categories</dt>
                                        <dd className="mt-1 [overflow-wrap:anywhere]">{product.categories.join(', ') || '—'}</dd>
                                    </div>
                                    <div className="min-w-0">
                                        <dt className="mb-1 inline-flex items-center gap-1 text-xs text-slate-500"><BadgeDollarSign className="size-4 text-violet-600" aria-hidden="true" />Price</dt>
                                        <dd><ProductPrice product={product} /></dd>
                                    </div>
                                    <div className="min-w-0">
                                        <dt className="mb-1 text-xs text-slate-500">Stock</dt>
                                        <dd className="break-words font-semibold">{product.stock} {product.unit || ''}</dd>
                                    </div>
                                </dl>
                                <div className="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-3">
                                    <ProductStatus status={product.status} />
                                    <ProductActions product={product} onView={openStorefront} onDelete={remove} />
                                </div>
                            </li>)}
                        </ul>}

                        <div role="region" aria-label="Products table, scroll horizontally to see all columns" tabIndex={0} className="hidden max-w-full overflow-x-auto focus-visible:outline focus-visible:outline-2 focus-visible:outline-violet-500 xl:block">
                            <table className="w-full min-w-[1040px]">
                                <thead><tr>{['Image', 'Product', 'SKU', 'Categories', 'Price', 'Stock', 'Status', 'Actions'].map(title => <th key={title} scope="col" className="px-4 py-3 text-left">
                                    {title === 'Price' ? <span className="inline-flex items-center gap-1.5"><BadgeDollarSign className="size-4 text-violet-600" aria-hidden="true" />Price</span> : title === 'Actions' ? <span className="sr-only">Actions</span> : title}
                                </th>)}</tr></thead>
                                <tbody className="divide-y divide-slate-200">{visible.map(product => <tr key={product.id} className="hover:bg-violet-50/30">
                                    <td className="w-20 px-4 py-3"><ProductImage product={product} /></td>
                                    <td className="min-w-[240px] max-w-sm px-4 py-3">
                                        <button type="button" onClick={() => openStorefront(product)} className="text-left font-semibold text-violet-600 [overflow-wrap:anywhere] hover:underline">{product.title}</button>
                                        <p className="mt-1 break-all text-xs text-slate-500">/{product.slug}</p>
                                    </td>
                                    <td className="max-w-32 break-all px-4 py-3">{product.sku || '—'}</td>
                                    <td className="max-w-40 px-4 py-3 [overflow-wrap:anywhere]">{product.categories.join(', ') || '—'}</td>
                                    <td className="w-32 px-4 py-3"><ProductPrice product={product} /></td>
                                    <td className="px-4 py-3">{product.stock} {product.unit || ''}</td>
                                    <td className="px-4 py-3"><ProductStatus status={product.status} /></td>
                                    <td className="px-4 py-3"><ProductActions product={product} onView={openStorefront} onDelete={remove} /></td>
                                </tr>)}</tbody>
                            </table>
                        </div>
                        {!visible.length && <div className="px-4 py-16 text-center text-sm text-slate-500">No products found.</div>}
                    </section>
                </div>
            </main>
            <ViewModal product={viewing} onClose={() => setViewing(null)} />
            {editing && <EditModal key={editing.id} product={editing} onClose={() => setEditing(null)} />}
        </AdminLayout>
    );
}
