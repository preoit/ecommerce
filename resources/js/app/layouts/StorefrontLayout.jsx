import { Head, Link, usePage } from '@inertiajs/react';
import { Apple, ArrowUp, ChevronDown, Mail, Menu, MessageCircle, Music2, Phone, Play, Search, ShoppingCart, UserRound, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const footerGroups = [
    { title: 'Let Us Help You', links: ['Account Info', 'Your Orders', 'Returns Policies', 'Shipping Rates'] },
    { title: 'Make Money with Us', links: ['Sell on our store', 'Sell Your Services', 'Become an Affiliate'] },
    { title: 'Get to Know Us', links: ['Careers', 'About Us', 'Customer Reviews'] },
    { title: 'Our Stores', links: ['New York', 'London', 'Los Angeles'] },
];
const socialNetworks = ['Facebook', 'X', 'Instagram', 'YouTube', 'TikTok', 'WhatsApp'];

function Brand({ website }) {
    return <Link href="/" className="flex shrink-0 items-center text-xl font-black tracking-[-0.06em] text-[#202631]" aria-label={`${website?.name || 'Store'} home`}>
        {website?.logo ? <img src={website.logo} alt={website.name || 'Store'} className="h-16 w-[120px] object-contain object-left" /> : <span className="text-2xl">{website?.name || 'Store'}</span>}
    </Link>;
}

function SearchBar() {
    return <form className="flex h-12 min-w-0 flex-1 items-stretch rounded-full border-2 border-transparent bg-[#f1f3f6] p-0.5 transition-colors hover:border-violet-500 focus-within:border-violet-500 lg:max-w-[760px]" role="search" onSubmit={event => event.preventDefault()}>
        <input type="search" placeholder="Search phones, beauty, home & more..." className="min-w-0 flex-1 border-0 bg-transparent px-4 text-sm text-slate-800 placeholder:text-slate-500 focus:ring-0" />
        <button type="submit" className="inline-flex aspect-square h-full shrink-0 items-center justify-center rounded-full bg-transparent text-[#172231] transition-colors hover:bg-slate-200" aria-label="Search"><Search className="size-5" /></button>
    </form>;
}

function HeaderAction({ href = '/', icon: Icon, label, count = 0 }) {
    const { cartCount: initialCartCount = 0 } = usePage().props;
    const [liveCount, setLiveCount] = useState(initialCartCount);
    useEffect(() => { setLiveCount(initialCartCount); }, [initialCartCount]);
    useEffect(() => { if (label !== 'Cart') return; const update = event => setLiveCount(event.detail?.count || 0); window.addEventListener('cart:updated', update); return () => window.removeEventListener('cart:updated', update); }, [label]);
    const displayCount = label === 'Cart' ? liveCount : count;
    return <Link href={label === 'Cart' ? route('storefront.cart') : href} className="group relative flex min-w-[54px] flex-col items-center justify-center gap-0.5 text-[#1d2837]" aria-label={label}><span className="relative"><Icon className="size-5 stroke-[1.7] transition-transform group-hover:-translate-y-0.5" />{displayCount > 0 && <span className="absolute -right-3 -top-2 grid min-w-4 place-items-center rounded-full bg-violet-600 px-1 text-[10px] font-bold leading-4 text-white">{displayCount > 99 ? '99+' : displayCount}</span>}</span><span className="text-sm font-medium text-slate-600 group-hover:text-slate-950">{label}</span></Link>;
}

function SocialBrandIcon({ name }) {
    const className = 'size-4';
    if (name === 'Facebook') return <svg viewBox="0 0 24 24" className={className} fill="currentColor"><path d="M13.8 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.3-1.5 1.6-1.5H17V3.6c-.3 0-1.3-.1-2.4-.1-2.4 0-4.1 1.5-4.1 4.2v2.2H7.8V13h2.7v8h3.3Z" /></svg>;
    if (name === 'X') return <svg viewBox="0 0 24 24" className={className} fill="currentColor"><path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3l-5-6.5L6.2 22H3l7.3-8.4L2.6 2H9l4.5 6 5.4-6Zm-1.1 18h1.7L8.1 3.9H6.3L17.8 20Z" /></svg>;
    if (name === 'Instagram') return <svg viewBox="0 0 24 24" className={className} fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="3" width="18" height="18" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none" /></svg>;
    if (name === 'YouTube') return <svg viewBox="0 0 24 24" className={className} fill="currentColor"><path d="M22 12c0-3.1-.4-5.2-1.2-6-.8-.8-2.7-1.1-6.8-1.1S8 5.2 7.2 6C6.4 6.8 6 8.9 6 12s.4 5.2 1.2 6c.8.8 2.7 1.1 6.8 1.1s6-.3 6.8-1.1c.8-.8 1.2-2.9 1.2-6ZM11 16.5v-9l7 4.5-7 4.5Z" /></svg>;
    return name === 'TikTok' ? <Music2 className={className} /> : <MessageCircle className={className} />;
}

function FooterLinkGroup({ group }) {
    return <div>
        <h2 className="text-sm font-bold text-slate-950">{group.title}</h2>
        {group.content ? <div className="rich-text-content mt-3 text-sm leading-6 text-slate-600" dangerouslySetInnerHTML={{ __html: group.content }} /> : <ul className="mt-3 space-y-2.5">{(group.links || []).map((link, index) => {
            const [label, href] = String(link).split('|').map(value => value.trim());
            return <li key={`${label}-${index}`}><a href={href || '#'} className="inline-flex text-sm leading-5 text-slate-600 transition hover:translate-x-0.5 hover:text-violet-700 hover:underline hover:underline-offset-4">{label}</a></li>;
        })}</ul>}
    </div>;
}

function StoreFooter({ website }) {
    const config = website?.footer || {};
    const groups = config.link_groups?.length ? config.link_groups : footerGroups;
    const payments = config.payment_methods?.length ? config.payment_methods : ['Visa', 'Mastercard', 'bKash', 'Nagad', 'NexusPay'];
    const brand = website?.name || 'Commerce';
    const [scrollProgress, setScrollProgress] = useState(0);

    useEffect(() => {
        const updateProgress = () => {
            const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
            setScrollProgress(scrollableHeight > 0 ? Math.min(window.scrollY / scrollableHeight, 1) : 0);
        };
        updateProgress();
        window.addEventListener('scroll', updateProgress, { passive: true });
        window.addEventListener('resize', updateProgress);
        return () => {
            window.removeEventListener('scroll', updateProgress);
            window.removeEventListener('resize', updateProgress);
        };
    }, []);
    return <footer className="relative overflow-hidden border-t border-slate-100 bg-[#f8f8f8] text-slate-700">
        <div aria-hidden="true" className="pointer-events-none absolute inset-x-0 top-14 text-center text-[clamp(3.5rem,7vw,6rem)] font-black leading-none tracking-[-0.08em] text-slate-200">Follow Us@{brand}</div>
        <div aria-hidden="true" className="absolute left-1/2 top-[126px] h-px w-[min(80%,1025px)] -translate-x-1/2 bg-slate-300/70" />
        <div className="relative mx-auto max-w-[1280px] px-4 pb-5 pt-44 sm:px-6 lg:px-8">
            <div className="grid gap-x-10 gap-y-8 sm:grid-cols-2 lg:grid-cols-[1.9fr_repeat(4,1fr)]">
                <div className="lg:row-span-2"><Brand website={website} /><p className="mt-4 max-w-xs text-sm leading-6 text-slate-600">{config.description || 'Discover quality products, great value and dependable service—selected for everyday life.'}</p><p className="mt-6 text-sm font-bold text-slate-950">Download our app</p><div className="mt-3 flex gap-3"><a href={config.app_store_url || '#'} className="flex h-10 items-center gap-1.5 rounded bg-black px-2.5 text-white"><Apple className="size-6 fill-white" /><span className="leading-none"><span className="block text-[8px]">Download on the</span><span className="block text-base">App Store</span></span></a><a href={config.google_play_url || '#'} className="flex h-10 items-center gap-1.5 rounded bg-black px-2.5 text-white"><Play className="size-5 fill-emerald-400 text-emerald-400" /><span className="leading-none"><span className="block text-[8px]">GET IT ON</span><span className="block text-base">Google Play</span></span></a></div><p className="mt-7 text-sm text-slate-600">Subscribe and get discount 20% Off</p><form className="mt-3 flex h-11 max-w-[300px] overflow-hidden rounded-md border border-slate-200 bg-white p-1 shadow-sm" onSubmit={event => event.preventDefault()}><Mail className="my-auto ml-3 mr-2 size-4 shrink-0 text-slate-400" /><input className="min-w-0 flex-1 border-0 px-1 text-sm focus:ring-0" placeholder="Enter email" /><button className="h-full min-w-[88px] rounded bg-violet-600 px-3 text-xs font-bold text-white transition hover:bg-violet-700">Subscribe</button></form><div className="mt-8 flex items-center gap-3"><Phone className="size-7 text-violet-600" /><div><p className="text-xs text-slate-500">Need help? Call now!</p><a href={`tel:${config.phone || ''}`} className="font-bold text-slate-950">{config.phone || '+880 0000 000 000'}</a></div></div></div>
                {groups.map((group, index) => <FooterLinkGroup key={`${group.title}-${index}`} group={group} />)}
            </div>
            <div className="mt-12 flex flex-col gap-4 border-t border-slate-100 pt-5 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between"><div className="flex items-center gap-2"><span className="mr-1">Follow us:</span>{socialNetworks.map(name => <a key={name} href={config.social_links?.[name] || '#'} aria-label={name} className="inline-grid size-8 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:border-violet-200 hover:bg-violet-600 hover:text-white"><SocialBrandIcon name={name} /></a>)}</div><p>Copyright © {new Date().getFullYear()} © <span className="font-semibold text-slate-800">{config.copyright_name || 'iTTiBA International'}</span>. All rights reserved.</p><div className="flex gap-1.5">{config.payment_images?.length ? config.payment_images.map(path => <img key={path} src={`/storage/${path}`} alt="Accepted payment method" className="h-10 w-16 rounded-md border border-slate-200 bg-white object-contain p-1" />) : payments.map(method => <span key={method} className="rounded border border-slate-300 bg-white px-2 py-1 text-xs font-bold text-slate-700">{method}</span>)}</div></div>
        </div>
        <button type="button" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })} style={{ background: `conic-gradient(#8b5cf6 ${scrollProgress * 360}deg, #dbeafe ${scrollProgress * 360}deg)` }} className="fixed bottom-5 right-5 z-40 grid size-11 place-items-center rounded-full p-0.5 shadow-sm transition hover:-translate-y-0.5" aria-label="Back to top"><span className="grid size-full place-items-center rounded-full bg-white text-violet-600 transition-colors hover:bg-violet-600 hover:text-white"><ArrowUp className="size-5" /></span></button>
    </footer>;
}

export default function StorefrontLayout({ children }) {
    const { auth, website, storefrontCategories = [], cartCount = 0 } = usePage().props;
    const [menuOpen, setMenuOpen] = useState(false);
    useEffect(() => {
        if (!menuOpen) return undefined;
        const previousOverflow = document.body.style.overflow;
        const drawer = document.querySelector('header ~ div[class~="lg:hidden"]');
        drawer?.setAttribute('data-mobile-menu', 'true');
        const actions = document.createElement('div');
        actions.setAttribute('data-mobile-menu-actions', 'true');
        const makeLink = (label, href, className) => {
            const link = document.createElement('a');
            link.textContent = label;
            link.href = href;
            link.className = className;
            return link;
        };
        const trackLink = makeLink('Track Order', auth?.user ? '/dashboard' : '/login', 'mobile-menu-secondary-action');
        const loginLink = makeLink(auth?.user ? 'Account' : 'Login', auth?.user ? '/dashboard' : '/login', 'mobile-menu-secondary-action');
        const cartLink = makeLink('View cart', route('storefront.cart'), 'mobile-menu-cart-action');
        const count = document.createElement('span');
        count.textContent = `${cartCount} ${cartCount === 1 ? 'item' : 'items'}`;
        cartLink.appendChild(count);
        actions.append(trackLink, loginLink, cartLink);
        drawer?.appendChild(actions);
        const categoryCleanups = [];
        drawer?.querySelectorAll('nav > div').forEach((item) => {
            const categoryLink = item.querySelector(':scope > a');
            const submenu = item.querySelector(':scope > div');
            if (!categoryLink || !submenu) return;
            item.classList.add('mobile-category-with-children');
            categoryLink.setAttribute('aria-expanded', 'false');
            const toggleSubmenu = (event) => {
                event.preventDefault();
                const isOpen = item.classList.toggle('is-open');
                categoryLink.setAttribute('aria-expanded', String(isOpen));
            };
            categoryLink.addEventListener('click', toggleSubmenu);
            categoryCleanups.push(() => categoryLink.removeEventListener('click', toggleSubmenu));
        });
        const closeOnEscape = (event) => { if (event.key === 'Escape') setMenuOpen(false); };
        const closeOnNavigation = (event) => {
            const link = event.target.closest('[data-mobile-menu] a');
            if (link && !link.parentElement?.classList.contains('mobile-category-with-children')) setMenuOpen(false);
        };
        const closeOutside = (event) => {
            if (!drawer || drawer.contains(event.target) || event.target.closest('button[aria-label="Close menu"]')) return;
            event.preventDefault();
            setMenuOpen(false);
        };
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', closeOnEscape);
        document.addEventListener('click', closeOnNavigation);
        document.addEventListener('pointerdown', closeOutside, true);
        return () => {
            document.body.style.overflow = previousOverflow;
            document.removeEventListener('keydown', closeOnEscape);
            document.removeEventListener('click', closeOnNavigation);
            document.removeEventListener('pointerdown', closeOutside, true);
            categoryCleanups.forEach((cleanup) => cleanup());
        };
    }, [menuOpen, auth?.user, cartCount]);
    return <div className="min-h-screen bg-white text-slate-950"><Head>{website?.favicon && <link rel="icon" href={website.favicon} />}</Head><header className="border-b border-slate-200 bg-white"><div className="mx-auto flex h-[76px] max-w-[1280px] items-center gap-5 px-4 sm:px-6 lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(400px,700px)_minmax(0,1fr)] lg:gap-8 lg:px-8"><button type="button" onClick={() => setMenuOpen(open => !open)} className="grid size-10 shrink-0 place-items-center rounded-lg text-slate-800 hover:bg-slate-100 lg:hidden" aria-label={menuOpen ? 'Close menu' : 'Open menu'}>{menuOpen ? <X className="size-5" /> : <Menu className="size-5" />}</button><Brand website={website} /><SearchBar /><div className="ml-auto hidden shrink-0 items-center gap-2 md:flex lg:justify-self-end"><HeaderAction href={auth?.user ? '/dashboard' : '/login'} icon={UserRound} label={auth?.user ? 'Account' : 'Sign in'} /><HeaderAction icon={ShoppingCart} label="Cart" /></div></div></header><div className="sticky top-0 z-50 hidden border-b border-slate-200 bg-white shadow-sm lg:block"><div className="mx-auto flex h-[54px] max-w-[1280px] items-center gap-8 px-8"><nav className="flex min-w-0 items-stretch gap-8 whitespace-nowrap text-base font-semibold text-slate-800">{storefrontCategories.map(category => <div key={category.id} className="group relative flex items-center"><Link href={`/${category.slug}`} className="flex h-[54px] items-center gap-1.5 border-b-2 border-transparent transition-colors hover:border-violet-600 hover:text-violet-700">{category.name}{category.children.length > 0 && <ChevronDown className="size-4 stroke-2" />}</Link>{category.children.length > 0 && <div className="invisible absolute left-0 top-full min-w-60 translate-y-2 rounded-lg border border-slate-200 bg-white p-2 opacity-0 shadow-xl transition-all group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">{category.children.map(child => <Link key={child.id} href={`/${child.slug}`} className="block rounded-md px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-violet-50 hover:text-violet-700">{child.name}</Link>)}</div>}</div>)}</nav></div></div>{menuOpen && <div className="border-t border-slate-200 p-4 lg:hidden"><nav className="grid gap-1 sm:grid-cols-2">{storefrontCategories.map(category => <div key={category.id}><Link href={`/${category.slug}`} className="block rounded-lg px-3 py-2.5 text-base font-semibold text-slate-800 hover:bg-violet-50 hover:text-violet-700">{category.name}</Link>{category.children.length > 0 && <div className="ml-4 border-l-2 border-violet-100 pl-2">{category.children.map(child => <Link key={child.id} href={`/${child.slug}`} className="block rounded-md px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-violet-50 hover:text-violet-700">— {child.name}</Link>)}</div>}</div>)}</nav></div>}<main>{children}</main><StoreFooter website={website} /></div>;
}
