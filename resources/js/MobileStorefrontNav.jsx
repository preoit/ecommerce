import { router } from '@inertiajs/react';
import { Grid2X2, Home, Search, ShoppingBag, UserRound } from 'lucide-react';
import { useEffect, useState } from 'react';

const currentPath = () => window.location.pathname.replace(/\/$/, '') || '/';

export default function MobileStorefrontNav() {
    const [storefront, setStorefront] = useState(false);
    const [productDetails, setProductDetails] = useState(false);
    const [path, setPath] = useState(currentPath);
    const [cartCount, setCartCount] = useState(0);
    const [menuOpen, setMenuOpen] = useState(false);

    useEffect(() => {
        const syncPage = () => {
            const header = document.querySelector('header[data-storefront-header]');
            setStorefront(Boolean(header));
            setProductDetails(Boolean(document.querySelector('.product-details-layout')));
            setMenuOpen(Boolean(document.querySelector('[data-mobile-menu]')));
            setPath(currentPath());
            const badge = header?.querySelector('button[aria-label="Open shopping cart"] span');
            if (badge) setCartCount(Number.parseInt(badge.textContent, 10) || 0);
        };
        syncPage();
        const observer = new MutationObserver(syncPage);
        observer.observe(document.body, { childList: true, subtree: true });
        const stop = router.on('finish', syncPage);
        window.addEventListener('popstate', syncPage);
        return () => {
            observer.disconnect();
            stop();
            window.removeEventListener('popstate', syncPage);
        };
    }, []);

    useEffect(() => {
        const updateCart = (event) => setCartCount(Number(event.detail?.count || 0));
        window.addEventListener('cart:updated', updateCart);
        return () => window.removeEventListener('cart:updated', updateCart);
    }, []);

    const visible = storefront && !productDetails;
    useEffect(() => {
        document.body.classList.toggle('has-mobile-storefront-nav', visible);
        return () => document.body.classList.remove('has-mobile-storefront-nav');
    }, [visible]);

    if (!visible) return null;

    const visit = (href) => router.visit(href, { preserveScroll: false });
    const openCategories = () => document.querySelector('header[data-storefront-header] button[aria-label$="menu"]')?.click();
    const openSearch = () => {
        const input = document.querySelector('header[data-storefront-header] input[type="search"]');
        input?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.setTimeout(() => input?.focus(), 220);
    };
    const openCart = () => document.querySelector('header[data-storefront-header] button[aria-label="Open shopping cart"]')?.click();
    const accountHref = document.querySelector('header[data-storefront-header] .mobile-account-action')?.getAttribute('href') || '/customer/login';
    const items = [
        { label: 'Home', icon: Home, active: path === '/', action: () => visit('/') },
        { label: 'Categories', icon: Grid2X2, active: menuOpen, action: openCategories },
        { label: 'Search', icon: Search, active: false, action: openSearch, featured: true },
        { label: 'Cart', icon: ShoppingBag, active: path === '/cart' || path === '/checkout', action: openCart, count: cartCount },
        { label: 'Account', icon: UserRound, active: path.startsWith('/account') || path.startsWith('/customer'), action: () => visit(accountHref) },
    ];

    return <nav className="mobile-storefront-nav" aria-label="Mobile storefront navigation">
        {items.map(({ label, icon: Icon, active, action, featured, count }) => <button key={label} type="button" onClick={action} aria-label={label} aria-current={active ? 'page' : undefined} className={`${active ? 'is-active' : ''} ${featured ? 'is-featured' : ''}`}>
            <span className="mobile-storefront-nav-icon"><Icon aria-hidden="true" />{count > 0 && <small>{count > 99 ? '99+' : count}</small>}</span>
            <span>{label}</span>
        </button>)}
    </nav>;
}
