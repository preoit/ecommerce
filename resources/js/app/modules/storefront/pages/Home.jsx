import { Link, usePage } from '@inertiajs/react';
import { Eye, Heart, Image as ImageIcon, ShoppingBag } from 'lucide-react';
import { useEffect, useState } from 'react';
import Seo from '@/app/components/Seo';
import StorefrontLayout from '@/app/layouts/StorefrontLayout';

function PrimaryHeroSlider({ images, href }) {
    const [active, setActive] = useState(0);
    useEffect(() => {
        if (images.length < 2) return undefined;
        const timer = window.setInterval(() => setActive((current) => (current + 1) % images.length), 4500);
        return () => window.clearInterval(timer);
    }, [images.length]);
    useEffect(() => { if (active >= images.length) setActive(0); }, [active, images.length]);
    return <div className="group relative overflow-hidden rounded-lg bg-slate-100"><a href={href} className="block h-full"><img key={images[active]} src={images[active]} alt={`Featured promotion ${active + 1}`} className="aspect-[16/9] h-full w-full animate-[heroFade_.35s_ease-out] object-cover sm:aspect-[2.5/1] transition-transform duration-500 group-hover:scale-[1.015]" fetchPriority={active === 0 ? 'high' : 'auto'} /></a>{images.length > 1 && <div className="absolute bottom-4 left-5 flex gap-2">{images.map((image, index) => <button type="button" key={image} onClick={() => setActive(index)} className={`size-2.5 rounded-full border border-white ${active === index ? 'bg-violet-600' : 'bg-white/90'}`} aria-label={`Show slide ${index + 1}`} />)}</div>}</div>;
}

function ProductCard({ product }) {
    const href = route('storefront.products.show', product.slug);
    return <article className="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-[0_16px_40px_rgba(30,41,59,0.10)]">
        <Link href={href} className="relative block aspect-square overflow-hidden bg-slate-50">
            {product.image ? <img src={product.image} alt={product.name} width="520" height="520" loading="lazy" className="h-full w-full object-cover transition duration-500 group-hover:scale-105" /> : <span className="grid h-full place-items-center text-slate-300"><ImageIcon className="size-12" strokeWidth={1.4} /></span>}
            <div className="absolute left-3 top-3 flex flex-col items-start gap-2">{product.isNewArrival && <span className="rounded-full bg-slate-950 px-2.5 py-1 text-[11px] font-bold text-white">New</span>}</div>
            <span className="absolute right-3 top-3 grid size-9 place-items-center rounded-full border border-slate-200 bg-white/95 text-slate-600 shadow-sm transition hover:bg-violet-600 hover:text-white sm:translate-y-1 sm:opacity-0 sm:group-hover:translate-y-0 sm:group-hover:opacity-100"><Heart className="size-4" /></span>
        </Link>
        <div className="flex flex-1 flex-col p-3 sm:p-4"><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{product.brand || product.category}</p><h3 className="mt-1.5 line-clamp-2 min-h-11 text-[15px] font-bold leading-[1.45] text-slate-900 transition group-hover:text-violet-700"><Link href={href}>{product.name}</Link></h3><div className="mt-3 flex flex-wrap items-baseline gap-2"><span className="text-lg font-extrabold text-violet-700">৳{Number(product.price).toLocaleString('en-BD')}</span>{product.discount > 0 && <><span className="text-xs font-semibold text-slate-400 line-through">৳{Number(product.regularPrice).toLocaleString('en-BD')}</span><span className="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">-{product.discount}%</span></>}</div><Link href={href} className="mt-4 inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-slate-950 px-2 text-xs font-bold sm:gap-2 sm:px-4 sm:text-sm text-white transition hover:bg-violet-600"><Eye className="size-4" /> View details</Link></div>
    </article>;
}

export default function Home({ featuredProducts = [] }) {
    const { website } = usePage().props;
    const primaryBanners = (website?.heroPrimaryImages?.length ? website.heroPrimaryImages : [website?.heroPrimaryImage]).filter(Boolean);
    const secondaryBanner = website?.heroSecondaryImage || null;
    const hasHero = primaryBanners.length > 0 || Boolean(secondaryBanner);
    return <StorefrontLayout>
        <Seo title={website?.seoTitle || 'Everyday essentials'} description={website?.seoDescription || 'Shop carefully selected everyday products with reliable delivery across Bangladesh.'} image={website?.seoImage} schema={{ '@context': 'https://schema.org', '@type': 'Organization', name: website?.name || 'Commerce', url: typeof window === 'undefined' ? '' : window.location.origin }} />
        {hasHero && <section className="bg-white"><div className={`mx-auto grid max-w-[1280px] gap-4 px-4 py-5 sm:px-6 lg:px-8 ${primaryBanners.length && secondaryBanner ? 'lg:grid-cols-[2fr_1fr]' : 'grid-cols-1'}`}>{primaryBanners.length > 0 && <PrimaryHeroSlider images={primaryBanners} href={website?.heroPrimaryLink || '#products'} />}{secondaryBanner && <a href={website?.heroSecondaryLink || '#products'} className="group block overflow-hidden rounded-lg bg-slate-100"><img src={secondaryBanner} alt="Secondary promotion" className="aspect-[16/9] h-full w-full object-cover sm:aspect-[2.5/1] lg:aspect-[1.2/1] transition-transform duration-500 group-hover:scale-[1.015]" fetchPriority="high" /></a>}</div></section>}
        <section id="products" className="mx-auto max-w-[1280px] px-4 py-12 sm:px-6 lg:px-8"><div className="flex items-end justify-between gap-4"><div className="flex items-start gap-3"><span className="mt-1 grid size-10 place-items-center rounded-xl bg-violet-100 text-violet-700"><ShoppingBag className="size-5" /></span><div><p className="text-xs font-bold uppercase tracking-[0.16em] text-violet-600">Products</p><h2 className="mt-1 text-2xl font-extrabold text-slate-950">Latest products</h2></div></div><Link href={route('storefront.products.index')} className="rounded-lg border border-violet-200 px-4 py-2 text-sm font-bold text-violet-700 transition hover:bg-violet-600 hover:text-white">View all</Link></div>{featuredProducts.length > 0 ? <div className="mt-7 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 lg:gap-6">{featuredProducts.map((product) => <ProductCard key={product.id} product={product} />)}</div> : <div className="mt-7 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center text-sm font-semibold text-slate-500">No published products are available.</div>}</section>
    </StorefrontLayout>;
}
