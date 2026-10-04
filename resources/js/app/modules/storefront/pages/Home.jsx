import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, ShoppingBag } from 'lucide-react';
import { useEffect, useState } from 'react';
import ProductCardMedia from '@/app/components/ProductCardMedia';
import Seo from '@/app/components/Seo';
import StorefrontLayout from '@/app/layouts/StorefrontLayout';

function PrimaryHeroSlider({ images, href }) {
    const [active, setActive] = useState(0);
    useEffect(() => {
        if (images.length < 2) return undefined;
        const timer = window.setInterval(() => setActive(current => (current + 1) % images.length), 4500);
        return () => window.clearInterval(timer);
    }, [images.length]);
    useEffect(() => { if (active >= images.length) setActive(0); }, [active, images.length]);

    return <div className="group relative overflow-hidden rounded-lg bg-slate-100">
        <a href={href} className="block h-full"><img key={images[active]} src={images[active]} alt={`Featured promotion ${active + 1}`} className="aspect-[16/9] h-full w-full animate-[heroFade_.35s_ease-out] object-contain transition-transform duration-500 group-hover:scale-[1.015] sm:aspect-[2.5/1]" fetchPriority={active === 0 ? 'high' : 'auto'} /></a>
        {images.length > 1 && <div className="absolute bottom-4 left-5 flex gap-2">{images.map((image, index) => <button type="button" key={image} onClick={() => setActive(index)} className={`size-2.5 rounded-full border border-white ${active === index ? 'bg-violet-600' : 'bg-white/90'}`} aria-label={`Show slide ${index + 1}`} />)}</div>}
    </div>;
}

function ProductCard({ product }) {
    const href = route('storefront.products.show', product.slug);

    return <article className="group flex h-full snap-start flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-[0_16px_40px_rgba(30,41,59,0.10)]">
        <ProductCardMedia product={product}/>
        <div className="flex flex-1 flex-col p-3 sm:p-4">
            <p className="text-[13px] font-semibold uppercase tracking-wide text-slate-500 sm:text-sm">{product.brand || product.category}</p>
            <h3 className="mt-1.5 line-clamp-2 min-h-11 text-base font-medium leading-[1.45] text-slate-900 transition group-hover:text-violet-700 sm:text-[17px]"><Link href={href}>{product.name}</Link></h3>
            <div className="mt-3 flex flex-wrap items-baseline gap-2"><span className="text-lg font-extrabold text-violet-700 sm:text-xl">৳{Number(product.price).toLocaleString('en-BD')}</span>{product.discount > 0 && <span className="text-[13px] font-semibold text-slate-400 line-through">৳{Number(product.regularPrice).toLocaleString('en-BD')}</span>}</div>
        </div>
    </article>;
}

function ProductSection({ title, href, products, index = 0, latest = false }) {
    return <section id={index === 0 ? 'products' : undefined} className={index % 2 ? 'bg-slate-50/70' : 'bg-white'}>
        <div className="mx-auto max-w-[1280px] px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
            <header className="flex items-end justify-between gap-4">
                <div className="flex min-w-0 items-start gap-3"><span className="mt-1 grid size-10 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700"><ShoppingBag className="size-5" /></span><div className="min-w-0"><p className="text-xs font-bold uppercase tracking-[0.16em] text-violet-600">{latest ? 'Products' : 'Shop by category'}</p><h2 className="mt-1 truncate text-2xl font-extrabold text-slate-950">{title}</h2></div></div>
                <Link href={href} className="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg border border-violet-200 px-3 text-sm font-bold text-violet-700 transition hover:bg-violet-600 hover:text-white sm:px-4">View all<ArrowRight className="size-4" /></Link>
            </header>
            <div className="mt-7 grid snap-x snap-mandatory grid-flow-col gap-4 overflow-x-auto pb-3 [grid-auto-columns:minmax(245px,82vw)] sm:grid-flow-row sm:grid-cols-2 sm:overflow-visible sm:pb-0 md:grid-cols-3 lg:grid-cols-4 lg:gap-6">{products.map(product => <ProductCard key={product.id} product={product} />)}</div>
        </div>
    </section>;
}

export default function Home({ featuredProducts = [], categorySections = [] }) {
    const { website } = usePage().props;
    const primaryBanners = (website?.heroPrimaryImages?.length ? website.heroPrimaryImages : [website?.heroPrimaryImage]).filter(Boolean);
    const secondaryBanner = website?.heroSecondaryImage || null;
    const hasHero = primaryBanners.length > 0 || Boolean(secondaryBanner);
    const sections = categorySections.length
        ? categorySections
        : (featuredProducts.length ? [{ id: 'latest', name: 'Latest products', slug: null, products: featuredProducts, latest: true }] : []);

    return <StorefrontLayout>
        <Seo title={website?.seoTitle || 'Everyday essentials'} description={website?.seoDescription || 'Shop carefully selected everyday products with reliable delivery across Bangladesh.'} image={website?.seoImage} schema={{ '@context': 'https://schema.org', '@type': 'Organization', name: website?.name || 'Commerce', url: typeof window === 'undefined' ? '' : window.location.origin }} />
        {hasHero && <section className="bg-white"><div className={`mx-auto grid max-w-[1280px] gap-4 px-4 py-5 sm:px-6 lg:px-8 ${primaryBanners.length && secondaryBanner ? 'lg:grid-cols-[2fr_1fr]' : 'grid-cols-1'}`}>{primaryBanners.length > 0 && <PrimaryHeroSlider images={primaryBanners} href={website?.heroPrimaryLink || '#products'} />}{secondaryBanner && <a href={website?.heroSecondaryLink || '#products'} className="group block overflow-hidden rounded-lg bg-slate-100"><img src={secondaryBanner} alt="Secondary promotion" className="aspect-[16/9] h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.015] sm:aspect-[2.5/1] lg:aspect-[1.2/1]" fetchPriority="high" /></a>}</div></section>}
        {sections.length ? sections.map((section, index) => <ProductSection key={section.id} title={section.name} href={section.slug ? `/${section.slug}` : route('storefront.products.index')} products={section.products} index={index} latest={section.latest} />) : <section id="products" className="mx-auto max-w-[1280px] px-4 py-16 text-center sm:px-6 lg:px-8"><div className="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-sm font-semibold text-slate-500">No published products are available.</div></section>}
    </StorefrontLayout>;
}
