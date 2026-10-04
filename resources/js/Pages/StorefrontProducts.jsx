import { Head, Link, router, usePage } from '@inertiajs/react';
import { Grid2X2, List, SearchX, SlidersHorizontal, X } from 'lucide-react';
import { useState } from 'react';
import ListingHero from '@/app/components/ListingHero';
import ListingProductCard from '@/app/components/ListingProductCard';
import ProductListingFilters from '@/app/components/ProductListingFilters';
import Seo from '@/app/components/Seo';
import StorefrontLayout from '@/app/layouts/StorefrontLayout';

export default function StorefrontProducts({ products, filters = {}, categories = [], brands = [], listingSeo = null, priceBounds }) {
    const { website } = usePage().props;
    const [viewMode, setViewMode] = useState(() => typeof window !== 'undefined' ? (window.localStorage.getItem('product-listing-view') || 'grid') : 'grid');
    const changeView = mode => {
        setViewMode(mode);
        window.localStorage.setItem('product-listing-view', mode);
    };
    const apply = (key, value) => router.get(route('storefront.products.index'), { ...filters, page: undefined, [key]: value || undefined }, { preserveState: true, replace: true });
    const hasFilters = filters.search || filters.category || filters.brand || filters.min_price || filters.max_price || Number(filters.in_stock) === 1;
    const entityFiltered = filters.category || filters.brand;
    const entity = brands.find(item => item.slug === filters.brand) || categories.find(item => item.slug === filters.category);
    const listingTitle = filters.search ? `Search results for “${filters.search}”` : (entity?.name || 'All Products');
    const listingDescription = (listingSeo?.short_description || listingSeo?.meta_description || '').replace(/<[^>]*>/g, ' ').trim();
    const seoTitle = filters.search ? `Search results for ${filters.search}` : (entityFiltered ? (listingSeo?.seo_title || listingTitle) : 'All Products');
    const seoDescription = entityFiltered ? (listingSeo?.meta_description || undefined) : 'Shop available products at competitive prices with reliable delivery across Bangladesh.';
    const canonical = listingSeo?.canonical_url || route('storefront.products.index');
    const robots = filters.search ? 'noindex,follow' : (listingSeo?.meta_robots || (entityFiltered ? 'index,follow' : undefined));

    return <StorefrontLayout>
        <Seo title={seoTitle} description={seoDescription} />
        <Head><link rel="canonical" href={canonical} />{robots && <meta name="robots" content={robots} />}{listingSeo?.og_title && <meta property="og:title" content={listingSeo.og_title} />}{listingSeo?.og_description && <meta property="og:description" content={listingSeo.og_description} />}</Head>
        <main className="bg-[#fafafa]"><div className="mx-auto min-h-[65vh] max-w-[1280px] px-4 py-8 sm:px-6 lg:px-8">
            {entityFiltered && <ListingHero title={entity?.name || 'Products'} description={listingDescription} brandNames={brands.filter(brand => brand.slug === filters.brand).map(brand => brand.name)} label={filters.brand ? 'Brands' : 'Categories'}><span className="text-xs font-semibold uppercase tracking-wide text-slate-400">Browse</span>{brands.map(item => <Link key={item.id} href={route('storefront.products.index')} data={{ ...filters, brand: item.slug, page: undefined }} className={`rounded-full border px-4 py-2 text-sm ${filters.brand === item.slug ? 'border-violet-500 text-violet-700' : 'border-slate-200 text-slate-500'}`}>{item.name}</Link>)}</ListingHero>}
            <div className="mt-7 grid gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">
                <div className="space-y-4"><ProductListingFilters url={route('storefront.products.index')} filters={filters} priceBounds={priceBounds} /><aside className="h-fit rounded-xl border border-slate-200 bg-white p-5"><h2 className="flex items-center gap-2 font-bold"><SlidersHorizontal className="size-4" />Filters</h2><label className="mt-5 block text-sm font-semibold">Category<select value={filters.category || ''} onChange={event => apply('category', event.target.value)} className="mt-2 w-full rounded-lg border-slate-300 text-sm"><option value="">All categories</option>{categories.map(item => <option key={item.id} value={item.slug}>{item.name}</option>)}</select></label><label className="mt-5 block text-sm font-semibold">Brand<select value={filters.brand || ''} onChange={event => apply('brand', event.target.value)} className="mt-2 w-full rounded-lg border-slate-300 text-sm"><option value="">All brands</option>{brands.map(item => <option key={item.id} value={item.slug}>{item.name}</option>)}</select></label>{hasFilters && <Link href={route('storefront.products.index')} className="mt-5 block text-sm font-semibold text-rose-600">Clear all filters</Link>}</aside></div>
                <section className="min-w-0">
                    <div className="mb-6 flex items-end justify-between gap-4 border-b border-slate-200 pb-4"><div className="min-w-0"><h1 className="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-[2rem]">{listingTitle}</h1><div className="mt-1 flex flex-wrap items-center gap-3"><p className="text-sm font-medium text-slate-500">{products.total} {products.total === 1 ? 'product' : 'products'} found</p>{filters.search && <button type="button" onClick={() => apply('search', '')} className="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-bold text-violet-700 transition hover:bg-violet-100"><X className="size-3.5" />Clear search</button>}</div></div><div className="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm" role="group" aria-label="Product view"><button type="button" onClick={() => changeView('grid')} aria-pressed={viewMode === 'grid'} title="Grid view" className={`grid size-9 place-items-center rounded-lg transition ${viewMode === 'grid' ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-500 hover:bg-violet-50 hover:text-violet-700'}`}><Grid2X2 className="size-4" /></button><button type="button" onClick={() => changeView('list')} aria-pressed={viewMode === 'list'} title="List view" className={`grid size-9 place-items-center rounded-lg transition ${viewMode === 'list' ? 'bg-violet-600 text-white shadow-sm' : 'text-slate-500 hover:bg-violet-50 hover:text-violet-700'}`}><List className="size-4" /></button></div></div>
                    <div className={viewMode === 'grid' ? 'grid grid-cols-2 gap-x-4 gap-y-7 md:grid-cols-3' : 'space-y-4'}>{products.data.map(product => <ListingProductCard key={product.id} product={product} viewMode={viewMode} showStock={website?.showStockToCustomers} />)}</div>
                    {!products.data.length && <div className="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center"><span className="mx-auto grid size-14 place-items-center rounded-full bg-violet-50 text-violet-600"><SearchX className="size-6" /></span><h2 className="mt-4 text-xl font-bold text-slate-900">No products found</h2><p className="mx-auto mt-2 max-w-md text-sm text-slate-500">{filters.search ? <>We could not find a product matching “{filters.search}”. Try another product name, SKU, brand or category.</> : 'Try changing or clearing the selected filters.'}</p>{hasFilters && <Link href={route('storefront.products.index')} className="mt-5 inline-flex h-10 items-center rounded-lg bg-violet-600 px-4 text-sm font-bold text-white hover:bg-violet-700">Clear filters</Link>}</div>}
                    {products.links.length > 3 && <nav className="mt-8 flex flex-wrap justify-center gap-2" aria-label="Product pagination">{products.links.map((link, index) => <Link key={index} href={link.url || '#'} preserveScroll preserveState className={`rounded-lg border px-3 py-2 text-sm ${link.active ? 'border-violet-600 bg-violet-600 text-white' : 'border-slate-200 bg-white text-slate-600'} ${!link.url ? 'pointer-events-none opacity-40' : ''}`} dangerouslySetInnerHTML={{ __html: link.label }} />)}</nav>}
                </section>
            </div>
        </div></main>
    </StorefrontLayout>;
}
