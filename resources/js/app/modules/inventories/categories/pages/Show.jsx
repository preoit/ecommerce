import { Head, Link, usePage } from '@inertiajs/react';
import { House, Image } from 'lucide-react';
import ListingHero from '@/app/components/ListingHero';
import Seo from '@/app/components/Seo';
import ProductListingFilters from '@/app/components/ProductListingFilters';
import ProductCardActions from '@/app/components/ProductCardActions';
import StorefrontLayout from '@/app/layouts/StorefrontLayout';

const money = (value) => `৳${Number(value || 0).toLocaleString('en-BD')}`;

export default function CategoryShow({ category, products, brands = [], selectedBrand = '', filters = {}, priceBounds }) {
    const { website } = usePage().props;
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
                <ListingHero title={category.name} description={plainDescription} brandNames={brands.filter(brand => !selectedBrand || brand.slug === selectedBrand).map(brand => brand.name)} label={category.parent?.name || 'Categories'} parent={category.parent ? { name: category.parent.name, href: categoryUrl(category.parent.slug) } : null}>
                    {brands.length > 0 && <><span className="mr-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Browse</span>
                    <Link href={categoryUrl(category.slug)} data={{ ...filters, brand: undefined }} preserveScroll className="rounded-full border border-slate-200 px-4 py-2 text-sm text-violet-600">All Brands</Link>
                    {brands.map(brand => <Link key={brand.id} href={categoryUrl(category.slug)} data={{ ...filters, brand: brand.slug }} preserveScroll className={`rounded-full border px-4 py-2 text-sm ${selectedBrand === brand.slug ? 'border-violet-500 bg-violet-50 text-violet-700' : 'border-slate-200 text-slate-600 dark:text-slate-300'}`}>{brand.name}</Link>)}</>}
                </ListingHero>
            </div>
            <section className="mx-auto min-h-64 max-w-[1280px] px-4 py-10 sm:px-6 lg:px-8">
                {category.children.length > 0 && <div><h2 className="text-xs font-semibold uppercase tracking-wide text-slate-500">Browse subcategories</h2><div className="mt-3 flex flex-wrap gap-2">{category.children.map((child) => <Link key={child.id} href={categoryUrl(child.slug)} className="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-800 hover:border-brand-300 hover:text-brand-700">{child.name}</Link>)}</div></div>}
                <div className={`grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)] ${category.children.length > 0 ? 'mt-10' : ''}`}>
                    <aside><ProductListingFilters url={categoryUrl(category.slug)} filters={filters} priceBounds={priceBounds} /></aside><div className="min-w-0">
                    <div className="flex items-end justify-between gap-4"><div><p className="text-sm font-semibold text-violet-600">{category.name}</p><h2 className="mt-1 text-2xl font-bold text-slate-950">Products</h2></div><p className="text-sm font-medium text-slate-500">{products.total} products</p></div>
                    {products.data.length > 0 ? <div className="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3">{products.data.map(product => <article key={product.id} className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-1 hover:border-violet-200 hover:shadow-lg"><Link href={route('storefront.products.show', product.slug)}><div className="relative aspect-square overflow-hidden rounded-xl bg-slate-50">{product.image ? <img src={product.image} alt={product.title} width="420" height="420" loading="lazy" className="h-full w-full object-cover transition duration-300 group-hover:scale-105"/> : <span className="grid h-full place-items-center text-slate-300"><Image className="size-9"/></span>}</div><div className="flex flex-1 flex-col p-3"><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{product.brand || category.name}</p><h3 className="mt-1 line-clamp-2 min-h-10 text-sm font-medium text-slate-900 group-hover:text-violet-700">{product.title}</h3><div className="mt-2 flex flex-wrap items-center gap-2"><b className="text-violet-700">{money(product.price)}</b>{product.discount > 0 && <><span className="text-xs text-slate-400 line-through">{money(product.regularPrice)}</span><span className="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">-{product.discount}%</span></>}</div>{website?.showStockToCustomers && <span className={`mt-2 text-xs font-semibold ${product.stockStatus === 'Out of Stock' ? 'text-rose-600' : 'text-emerald-600'}`}>{product.stockStatus}</span>}</div></Link><div className="mt-auto px-3 pb-3"><ProductCardActions product={product}/></div></article>)}</div> : <div className="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 py-16 text-center text-sm font-medium text-slate-500">No products found in this category.</div>}
                    {products.links.length > 3 && <nav className="mt-8 flex flex-wrap justify-center gap-2" aria-label="Product pagination">{products.links.map((link,index) => <Link key={index} href={link.url || '#'} preserveScroll className={`rounded-lg border px-3 py-2 text-sm ${link.active ? 'border-violet-600 bg-violet-600 text-white' : 'border-slate-200 bg-white text-slate-600'} ${!link.url ? 'pointer-events-none opacity-40' : ''}`} dangerouslySetInnerHTML={{__html:link.label}} />)}</nav>}
                </div>
                </div>
                {category.description && <div className="rich-text-content mt-10 border-t border-slate-200 pt-8 text-sm leading-7 text-slate-700" dangerouslySetInnerHTML={{ __html: category.description }} />}
            </section>
        </StorefrontLayout>
    );
}
