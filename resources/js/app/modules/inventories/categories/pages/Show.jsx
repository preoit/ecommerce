import { Head, Link, usePage } from '@inertiajs/react';
import { Grid2X2, Image, List } from 'lucide-react';
import { useState } from 'react';
import ListingHero from '@/app/components/ListingHero';
import Seo from '@/app/components/Seo';
import ProductListingFilters from '@/app/components/ProductListingFilters';
import ProductCardActions from '@/app/components/ProductCardActions';
import StorefrontLayout from '@/app/layouts/StorefrontLayout';

const money = (value) => `৳${Number(value || 0).toLocaleString('en-BD')}`;

export default function CategoryShow({ category, products, brands = [], selectedBrand = '', filters = {}, priceBounds }) {
    const { website } = usePage().props;
    const [viewMode, setViewMode] = useState(() => typeof window !== 'undefined' ? (window.localStorage.getItem('product-listing-view') || 'grid') : 'grid');
    const changeView = mode => {
        setViewMode(mode);
        window.localStorage.setItem('product-listing-view', mode);
    };
    const plainDescription = (category.short_description || category.description || `Shop ${category.name} products.`).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    const categoryUrl = (slug, query = '') => `/${slug}${query}`;
    const canonical = category.canonical_url || categoryUrl(category.slug);

    return (
        <StorefrontLayout>
            <Seo
                title={category.seo_title || undefined}
                description={category.meta_description || plainDescription.slice(0, 160)}
                image={category.image_url}
                schema={{
                    '@context': 'https://schema.org',
                    '@type': 'CollectionPage',
                    name: category.name,
                    description: plainDescription,
                    url: typeof window === 'undefined' ? '' : window.location.href,
                }}
            />
            <Head><link rel="canonical" href={canonical} /><meta name="robots" content={category.meta_robots || 'index,follow'} /><meta property="og:title" content={category.og_title || category.seo_title || ''} /><meta property="og:description" content={category.og_description || category.meta_description || plainDescription.slice(0, 160)} /><meta property="og:url" content={canonical} /></Head>
            <div className="mx-auto max-w-[1280px] px-4 pt-6 sm:px-6 lg:px-8">
                <ListingHero title={category.name} description={plainDescription} brandNames={brands.filter(brand => !selectedBrand || brand.slug === selectedBrand).map(brand => brand.name)} label={category.parent?.name || 'Categories'} parent={category.parent ? { name: category.parent.name, href: categoryUrl(category.parent.slug) } : null} />
                {category.children.length > 0 && <nav className="mt-6" aria-label="Browse subcategories"><h2 className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Browse subcategories</h2><div className="mt-3 flex flex-wrap gap-2">{category.children.map(child => <Link key={child.id} href={categoryUrl(child.slug)} className="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-800 transition-colors hover:border-violet-300 hover:text-violet-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-violet-500 dark:hover:text-violet-300">{child.name}</Link>)}</div></nav>}
            </div>
            <section className="mx-auto min-h-64 max-w-[1280px] px-4 py-10 sm:px-6 lg:px-8">
                <div className="grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
                    <aside><ProductListingFilters url={categoryUrl(category.slug)} filters={filters} priceBounds={priceBounds} /></aside><div className="min-w-0">
                    <div className="mb-6 flex items-end justify-between gap-4 border-b border-slate-200 pb-4"><div className="min-w-0"><h2 className="truncate text-3xl font-extrabold tracking-tight text-slate-950 sm:text-[2rem]">{category.name}</h2><p className="mt-1 text-sm font-medium text-slate-500">{products.total} {products.total === 1 ? 'product' : 'products'} available</p></div><div className="inline-flex shrink-0 rounded-xl border border-slate-200 bg-white p-1 shadow-sm" role="group" aria-label="Product view"><button type="button" onClick={() => changeView('grid')} aria-pressed={viewMode === 'grid'} title="Grid view" className={`grid size-9 place-items-center rounded-lg transition ${viewMode === 'grid' ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-500 hover:bg-violet-50 hover:text-violet-700'}`}><Grid2X2 className="size-4" /></button><button type="button" onClick={() => changeView('list')} aria-pressed={viewMode === 'list'} title="List view" className={`grid size-9 place-items-center rounded-lg transition ${viewMode === 'list' ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-500 hover:bg-violet-50 hover:text-violet-700'}`}><List className="size-4" /></button></div></div>
                    {products.data.length > 0 ? <div className={viewMode === 'grid' ? 'grid grid-cols-2 gap-4 md:grid-cols-3' : 'space-y-4'}>{products.data.map(product => <article key={product.id} className={`group overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-violet-200 hover:shadow-lg ${viewMode === 'list' ? 'sm:grid sm:grid-cols-[minmax(0,1fr)_210px] sm:items-center' : 'flex flex-col hover:-translate-y-1'}`}><Link href={route('storefront.products.show', product.slug)} className={viewMode === 'list' ? 'grid min-w-0 grid-cols-[112px_minmax(0,1fr)] items-center sm:grid-cols-[170px_minmax(0,1fr)]' : ''}><div className={`relative aspect-square overflow-hidden bg-slate-50 ${viewMode === 'grid' ? 'rounded-xl' : 'h-full rounded-l-xl'}`}>{product.image ? <img src={product.image} alt={product.title} width="420" height="420" loading="lazy" className="h-full w-full object-cover transition duration-300 group-hover:scale-105"/> : <span className="grid h-full place-items-center text-slate-300"><Image className="size-9"/></span>}</div><div className="flex flex-1 flex-col p-3"><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{product.brand || category.name}</p><h3 className="mt-1 line-clamp-2 min-h-10 text-sm font-medium text-slate-900 group-hover:text-violet-700">{product.title}</h3><div className="mt-2 flex flex-wrap items-center gap-2"><b className="text-violet-700">{money(product.price)}</b>{product.discount > 0 && <><span className="text-xs text-slate-400 line-through">{money(product.regularPrice)}</span><span className="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">-{product.discount}%</span></>}</div>{website?.showStockToCustomers && <span className={`mt-2 text-xs font-semibold ${product.stockStatus === 'Out of Stock' ? 'text-rose-600' : 'text-emerald-600'}`}>{product.stockStatus}</span>}</div></Link><div className={viewMode === 'grid' ? 'mt-auto px-3 pb-3' : 'px-3 pb-3 sm:p-4'}><ProductCardActions product={product}/></div></article>)}</div> : <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 py-16 text-center text-sm font-medium text-slate-500">No products found in this category.</div>}
                    {products.links.length > 3 && <nav className="mt-8 flex flex-wrap justify-center gap-2" aria-label="Product pagination">{products.links.map((link,index) => <Link key={index} href={link.url || '#'} preserveScroll className={`rounded-lg border px-3 py-2 text-sm ${link.active ? 'border-violet-600 bg-violet-600 text-white' : 'border-slate-200 bg-white text-slate-600'} ${!link.url ? 'pointer-events-none opacity-40' : ''}`} dangerouslySetInnerHTML={{__html:link.label}} />)}</nav>}
                </div>
                </div>
                {category.description && <div className="rich-text-content mt-10 border-t border-slate-200 pt-8 text-sm leading-7 text-slate-700" dangerouslySetInnerHTML={{ __html: category.description }} />}
            </section>
        </StorefrontLayout>
    );
}
