import { Link } from '@inertiajs/react';
import { House, Image } from 'lucide-react';
import Seo from '@/app/components/Seo';
import StorefrontLayout from '@/app/layouts/StorefrontLayout';

const money = (value) => `৳${Number(value || 0).toLocaleString('en-BD')}`;

export default function CategoryShow({ category, products }) {
    const plainDescription = category.short_description || category.description?.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() || `Shop ${category.name} products.`;

    return (
        <StorefrontLayout>
            <Seo
                title={category.name}
                description={plainDescription.slice(0, 160)}
                image={category.image_url}
                schema={{
                    '@context': 'https://schema.org',
                    '@type': 'CollectionPage',
                    name: category.name,
                    description: plainDescription,
                    url: typeof window === 'undefined' ? '' : window.location.href,
                }}
            />
            <div className="border-b border-slate-200 bg-slate-50">
                <div className="mx-auto max-w-[1280px] px-4 py-8 sm:px-6 lg:px-8">
                    <nav className="flex items-center gap-3 text-sm font-medium text-slate-600" aria-label="Breadcrumb">
                        <Link href="/" className="text-slate-500 transition-colors hover:text-violet-700" aria-label="Home"><House className="size-4" /></Link>
                        <span className="text-slate-400" aria-hidden="true">/</span>
                        {category.parent && <><Link href={`/${category.parent.slug}`} className="transition-colors hover:text-violet-700">{category.parent.name}</Link><span className="text-slate-400" aria-hidden="true">/</span></>}
                        <span className="text-violet-700" aria-current="page">{category.name}</span>
                    </nav>
                    <div className="mt-5">
                        <h1 className="text-[22px] font-medium leading-7 text-violet-700">{category.name}</h1>
                        {category.short_description && <p className="mt-2 max-w-none text-sm font-normal leading-6 text-slate-700">{category.short_description}</p>}
                    </div>
                </div>
            </div>
            <section className="mx-auto min-h-64 max-w-[1280px] px-4 py-10 sm:px-6 lg:px-8">
                {category.children.length > 0 && <div><h2 className="text-xl font-bold text-slate-950">Browse subcategories</h2><div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">{category.children.map((child) => <Link key={child.id} href={route('storefront.categories.show', child.slug)} className="rounded-md border border-slate-200 p-4 font-semibold text-slate-800 hover:border-brand-300 hover:text-brand-700">{child.name}</Link>)}</div></div>}
                <div className={category.children.length > 0 ? 'mt-10' : ''}>
                    <div className="flex items-end justify-between gap-4"><div><p className="text-sm font-semibold text-violet-600">{category.name}</p><h2 className="mt-1 text-2xl font-bold text-slate-950">Products</h2></div><p className="text-sm font-medium text-slate-500">{products.total} products</p></div>
                    {products.data.length > 0 ? <div className="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">{products.data.map((product) => <Link key={product.id} href={route('storefront.products.show',product.slug)} className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 transition hover:-translate-y-1 hover:border-violet-200 hover:shadow-lg"><div className="relative aspect-square overflow-hidden rounded-xl bg-slate-50">{product.image ? <img src={product.image} alt={product.title} width="420" height="420" loading="lazy" className="h-full w-full object-contain p-4 transition duration-300 group-hover:scale-105"/> : <span className="grid h-full place-items-center text-slate-300"><Image className="size-9"/></span>}{product.discount > 0 && <span className="absolute left-2 top-2 rounded-full bg-violet-600 px-2 py-1 text-[11px] font-bold text-white">-{product.discount}%</span>}</div><p className="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{product.brand || category.name}</p><h3 className="mt-1 line-clamp-2 min-h-10 text-sm font-bold text-slate-900 group-hover:text-violet-700">{product.title}</h3><div className="mt-2 flex items-center gap-2"><b className="text-violet-700">{money(product.price)}</b>{product.discount > 0 && <span className="text-xs text-slate-400 line-through">{money(product.regularPrice)}</span>}</div><span className={`mt-2 text-xs font-semibold ${product.stockStatus === 'Out of Stock' ? 'text-rose-600' : 'text-emerald-600'}`}>{product.stockStatus}</span></Link>)}</div> : <div className="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 py-16 text-center text-sm font-medium text-slate-500">No products found in this category.</div>}
                    {products.links.length > 3 && <nav className="mt-8 flex flex-wrap justify-center gap-2" aria-label="Product pagination">{products.links.map((link,index) => <Link key={index} href={link.url || '#'} preserveScroll className={`rounded-lg border px-3 py-2 text-sm ${link.active ? 'border-violet-600 bg-violet-600 text-white' : 'border-slate-200 bg-white text-slate-600'} ${!link.url ? 'pointer-events-none opacity-40' : ''}`} dangerouslySetInnerHTML={{__html:link.label}} />)}</nav>}
                </div>
                {category.description && <div className="rich-text-content mt-10 border-t border-slate-200 pt-8 text-sm leading-7 text-slate-700" dangerouslySetInnerHTML={{ __html: category.description }} />}
            </section>
        </StorefrontLayout>
    );
}
