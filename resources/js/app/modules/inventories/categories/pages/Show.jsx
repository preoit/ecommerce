import { Head, Link, usePage } from '@inertiajs/react';
import { Grid2X2, List } from 'lucide-react';
import { useState } from 'react';
import ListingHero from '@/app/components/ListingHero';
import Seo from '@/app/components/Seo';
import ProductListingFilters from '@/app/components/ProductListingFilters';
import SubcategoryFilters from '@/app/components/SubcategoryFilters';
import ListingProductCard from '@/app/components/ListingProductCard';
import StorefrontLayout from '@/app/layouts/StorefrontLayout';


export default function CategoryShow({ category, products, brands = [], selectedBrand = '', filters = {}, priceBounds, subcategories = [] }) {
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
            </div>
            <section className="mx-auto min-h-64 max-w-[1280px] px-4 py-10 sm:px-6 lg:px-8">
                <div className="grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
                    <aside className="space-y-3"><SubcategoryFilters category={category} subcategories={subcategories} total={products.total} filters={filters} /><ProductListingFilters url={categoryUrl(category.slug)} filters={filters} priceBounds={priceBounds} /></aside><div className="min-w-0">
                    <div className="mb-6 flex items-end justify-between gap-4 border-b border-slate-200 pb-4"><div className="min-w-0"><h2 className="truncate text-3xl font-extrabold tracking-tight text-slate-950 sm:text-[2rem]">{category.name}</h2><p className="mt-1 text-sm font-medium text-slate-500">{products.total} {products.total === 1 ? 'product' : 'products'} available</p></div><div className="inline-flex shrink-0 rounded-xl border border-slate-200 bg-white p-1 shadow-sm" role="group" aria-label="Product view"><button type="button" onClick={() => changeView('grid')} aria-pressed={viewMode === 'grid'} title="Grid view" className={`grid size-9 place-items-center rounded-lg transition ${viewMode === 'grid' ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-500 hover:bg-violet-50 hover:text-violet-700'}`}><Grid2X2 className="size-4" /></button><button type="button" onClick={() => changeView('list')} aria-pressed={viewMode === 'list'} title="List view" className={`grid size-9 place-items-center rounded-lg transition ${viewMode === 'list' ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-500 hover:bg-violet-50 hover:text-violet-700'}`}><List className="size-4" /></button></div></div>
                    {products.data.length > 0 ? <div className={viewMode === 'grid' ? 'grid grid-cols-2 gap-4 md:grid-cols-3' : 'space-y-4'}>{products.data.map(product => <ListingProductCard key={product.id} product={product} viewMode={viewMode} fallbackBrand={category.name} showStock={website?.showStockToCustomers}/>)}</div> : <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 py-16 text-center text-sm font-medium text-slate-500">No products found in this category.</div>}
                    {products.links.length > 3 && <nav className="mt-8 flex flex-wrap justify-center gap-2" aria-label="Product pagination">{products.links.map((link,index) => <Link key={index} href={link.url || '#'} preserveScroll className={`rounded-lg border px-3 py-2 text-sm ${link.active ? 'border-violet-600 bg-violet-600 text-white' : 'border-slate-200 bg-white text-slate-600'} ${!link.url ? 'pointer-events-none opacity-40' : ''}`} dangerouslySetInnerHTML={{__html:link.label}} />)}</nav>}
                </div>
                </div>
                {category.description && <div className="rich-text-content mt-10 border-t border-slate-200 pt-8 text-sm leading-7 text-slate-700" dangerouslySetInnerHTML={{ __html: category.description }} />}
            </section>
        </StorefrontLayout>
    );
}
