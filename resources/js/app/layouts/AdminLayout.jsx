import { Head, Link, usePage } from '@inertiajs/react';
import { Bell, CheckCircle2, ChevronDown, CircleHelp, CreditCard, DollarSign, Grid2X2, Languages, LogOut, Menu, Moon, Search, Settings, Store, Sun, UserRound, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import IconButton from '@/app/design-system/components/IconButton';
import { adminNavigation } from '@/app/navigation/adminNavigation';
import { cn } from '@/app/utils/cn';

function isCurrent(url, href) {
    return url === href || (href !== '/dashboard' && url.startsWith(`${href}/`));
}

function Navigation({ url, onNavigate, orderCount = 0 }) {
    const [expanded, setExpanded] = useState(() => adminNavigation.find((item) => item.children?.some((child) => isCurrent(url, child.href)))?.label || null);

    useEffect(() => {
        const activeSection = adminNavigation.find((item) => item.children?.some((child) => isCurrent(url, child.href)));
        if (activeSection) setExpanded(activeSection.label);
    }, [url]);

    return (
        <nav className="space-y-1 px-3" aria-label="Admin navigation">
            {adminNavigation.map((item) => {
                const Icon = item.icon;
                const activeChildHref = item.children
                    ?.filter((child) => isCurrent(url, child.href))
                    .sort((first, second) => second.href.length - first.href.length)[0]?.href;
                const childActive = Boolean(activeChildHref);

                if (item.children) {
                    const isExpanded = expanded === item.label;
                    return (
                        <div key={item.label} className="py-1">
                            <button type="button" onClick={() => setExpanded(isExpanded ? null : item.label)} className={cn('flex h-10 w-full items-center gap-3 rounded-md px-3 text-left text-sm font-semibold transition-colors hover:bg-violet-50 hover:text-[#6c5ce7]', childActive ? 'text-[#6c5ce7]' : 'text-slate-600')}>
                                <Icon className="size-5" aria-hidden="true" />
                                <span className="flex-1">{item.label}</span>
                                <ChevronDown className={cn('size-4 transition-transform', isExpanded && 'rotate-180')} aria-hidden="true" />
                            </button>
                            {isExpanded && <div className="ml-5 mt-1 space-y-1 border-l border-slate-200 pl-3">
                                {item.children.map((child) => {
                                    const ChildIcon = child.icon;
                                    const active = child.href === activeChildHref;
                                    return (
                                        <Link key={child.href} href={child.href} onClick={() => { setExpanded(item.label); onNavigate?.(); }} className={cn('flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium transition-colors', active ? 'bg-violet-100 text-[#6c5ce7]' : 'text-slate-600 hover:bg-violet-50 hover:text-[#6c5ce7]')}>
                                            <ChildIcon className="size-4" aria-hidden="true" />
                                            {child.label}{child.href === '/admin/orders' && orderCount > 0 && <span className="ml-auto rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-4 text-white">{orderCount > 99 ? '99+' : orderCount}</span>}
                                        </Link>
                                    );
                                })}
                            </div>}
                        </div>
                    );
                }

                const active = isCurrent(url, item.href);
                return (
                    <Link key={item.href} href={item.href} onClick={onNavigate} className={cn('flex h-10 items-center gap-3 rounded-md px-3 text-sm font-semibold transition-colors', active ? 'bg-violet-100 text-[#6c5ce7]' : 'text-slate-600 hover:bg-violet-50 hover:text-[#6c5ce7]')}>
                        <Icon className="size-5" aria-hidden="true" />
                        {item.label}{item.href === '/admin/orders' && orderCount > 0 && <span className="ml-auto rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-4 text-white">{orderCount > 99 ? '99+' : orderCount}</span>}
                    </Link>
                );
            })}
        </nav>
    );
}

function Sidebar({ url, onNavigate, website, orderCount = 0 }) {
    return (
        <div className="flex h-full flex-col bg-white dark:bg-slate-900">
            <Link href="/dashboard" className="flex h-16 items-center gap-3 border-b border-violet-100 px-5 dark:border-slate-700" onClick={onNavigate}>
                {website?.logo ? <img src={website.logo} alt={website.name || 'Website logo'} className="h-10 max-w-[190px] object-contain object-left" /> : <span className="text-base font-bold tracking-tight text-[#4a495a] dark:text-white">{website?.name || 'Commerce'} Admin</span>}
            </Link>
            <div className="flex-1 overflow-y-auto py-4"><Navigation url={url} onNavigate={onNavigate} orderCount={orderCount} /></div>
            <div className="border-t border-violet-100 p-3 dark:border-slate-700">
                <Link href="/" className="flex h-10 items-center gap-3 rounded-md px-3 text-sm font-semibold text-slate-600 hover:bg-violet-50 hover:text-[#6c5ce7] dark:text-slate-300 dark:hover:bg-slate-800">
                    <Store className="size-5" /> View storefront
                </Link>
            </div>
        </div>
    );
}

function OrderNotifications({ darkMode, onCountChange }) {
    const [open, setOpen] = useState(false);
    const [orders, setOrders] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const load = async () => {
        const response = await fetch(route('orders.notifications'), { headers: { Accept: 'application/json' } });
        if (!response.ok) return;
        const result = await response.json();
        setOrders(result.orders || []); setUnreadCount(result.unreadCount || 0); onCountChange(result.unreadCount || 0);
    };
    useEffect(() => { load(); const timer = window.setInterval(load, 15000); return () => window.clearInterval(timer); }, []);
    const view = async order => {
        if (!order.viewed) {
            await fetch(route('orders.view', order.id), { method: 'POST', keepalive: true, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
            setOrders(current => current.map(item => item.id === order.id ? { ...item, viewed: true } : item));
            setUnreadCount(current => Math.max(0, current - 1)); onCountChange(Math.max(0, unreadCount - 1));
        }
        setOpen(false);
    };
    const unread = orders.filter(order => !order.viewed);
    const viewed = orders.filter(order => order.viewed);
    const item = order => <Link key={order.id} href={route('orders.show', order.id)} onClick={() => view(order)} className={cn('block border-b px-4 py-3 last:border-0 hover:bg-violet-50 dark:border-slate-700 dark:hover:bg-slate-800', !order.viewed && 'bg-violet-50/70 dark:bg-violet-950/30')}><div className="flex items-start justify-between gap-3"><span className="font-semibold text-slate-900 dark:text-white">New order {order.number}</span>{!order.viewed && <i className="mt-1 size-2 rounded-full bg-rose-500" />}</div><p className="mt-1 text-xs text-slate-600 dark:text-slate-300">{order.customer} · ৳{Number(order.total).toLocaleString('en-BD')}</p>{order.hasStockShortage && <p className="mt-1 rounded bg-rose-100 px-2 py-1 text-xs font-bold text-rose-700">Stock unavailable — action required</p>}<p className="mt-1 text-[11px] text-slate-400">{order.createdAt}</p></Link>;
    return <div className="relative"><button type="button" onClick={() => { setOpen(value => !value); if (!open) load(); }} aria-label="Notifications" aria-expanded={open} className="relative rounded-md p-2 hover:bg-slate-100 dark:hover:bg-slate-800"><Bell className="size-5" />{unreadCount > 0 && <span className="absolute right-0 top-0 grid min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-4 text-white ring-2 ring-white dark:ring-slate-900">{unreadCount > 99 ? '99+' : unreadCount}</span>}</button>{open && <section className={cn('absolute right-0 mt-3 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-xl border shadow-xl', darkMode ? 'border-slate-700 bg-slate-900' : 'border-slate-200 bg-white')}><div className="flex items-center justify-between border-b px-4 py-3 dark:border-slate-700"><b className="text-sm">Order notifications</b><span className="text-xs text-slate-500">{unreadCount} unread</span></div><div className="max-h-[60vh] overflow-y-auto">{unread.length > 0 && <><p className="px-4 pb-1 pt-3 text-xs font-bold uppercase tracking-wide text-violet-600">Unviewed</p>{unread.map(item)}</>}{viewed.length > 0 && <><p className="px-4 pb-1 pt-3 text-xs font-bold uppercase tracking-wide text-slate-400">Viewed</p>{viewed.map(item)}</>}{orders.length === 0 && <p className="p-6 text-center text-sm text-slate-500">No order notifications yet.</p>}</div><Link href="/admin/orders" onClick={() => setOpen(false)} className="block border-t px-4 py-3 text-center text-sm font-semibold text-violet-700 hover:bg-violet-50 dark:border-slate-700 dark:hover:bg-slate-800">View all orders</Link></section>}</div>;
}

export default function AdminLayout({ children }) {
    const { auth, flash, website } = usePage().props;
    const currentUrl = usePage().url;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [profileOpen, setProfileOpen] = useState(false);
    const [darkMode, setDarkMode] = useState(() => localStorage.getItem('admin-theme') === 'dark');
    const [unreadOrderCount, setUnreadOrderCount] = useState(0);

    useEffect(() => setMobileOpen(false), [currentUrl]);
    useEffect(() => {
        document.documentElement.classList.toggle('dark', darkMode);
        localStorage.setItem('admin-theme', darkMode ? 'dark' : 'light');
    }, [darkMode]);

    return (
        <div className={cn('min-h-screen transition-colors', darkMode ? 'dark bg-slate-950 text-slate-100' : 'bg-[#f8f7fa] text-slate-900')}>
            <Head>{website?.favicon && <link rel="icon" href={website.favicon} />}</Head>
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 bg-[#f8f7fa] dark:bg-slate-950 lg:block"><Sidebar url={currentUrl} website={website} orderCount={unreadOrderCount} /></aside>

            {mobileOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <button className="absolute inset-0 bg-slate-950/40" aria-label="Close navigation" onClick={() => setMobileOpen(false)} />
                    <aside className="relative h-full w-[min(20rem,86vw)] bg-[#f8f7fa] shadow-xl dark:bg-slate-950">
                        <IconButton icon={X} label="Close navigation" onClick={() => setMobileOpen(false)} className="absolute right-2 top-3 z-10" />
                        <Sidebar url={currentUrl} website={website} orderCount={unreadOrderCount} onNavigate={() => setMobileOpen(false)} />
                    </aside>
                </div>
            )}

            <div className="lg:pl-64">
                <header className={cn('sticky top-0 z-20 p-0', darkMode ? 'bg-slate-950' : 'bg-[#f8f7fa]')}>
                    <div className={cn('flex h-16 w-full items-center gap-3 border-b px-5', darkMode ? 'border-slate-700 bg-slate-900' : 'border-violet-100 bg-white')}>
                        <IconButton icon={Menu} label="Open navigation" className="lg:hidden" onClick={() => setMobileOpen(true)} />
                        <div className="relative hidden max-w-xl flex-1 md:block"><Search className="pointer-events-none absolute left-0 top-1/2 size-5 -translate-y-1/2 text-slate-500" /><input type="search" placeholder="Search [CTRL + K]" className={cn('h-10 w-full border-0 bg-transparent pl-10 pr-3 text-sm focus:ring-0', darkMode ? 'text-white placeholder:text-slate-500' : 'text-slate-700 placeholder:text-slate-400')} /></div>
                        <div className="ml-auto flex items-center gap-3 text-slate-600 dark:text-slate-300"><button aria-label="Language" className="hidden rounded-md p-2 hover:bg-slate-100 sm:block dark:hover:bg-slate-800"><Languages className="size-5" /></button><button aria-label="Toggle colour mode" onClick={() => setDarkMode((value) => !value)} className="rounded-md p-2 hover:bg-slate-100 dark:hover:bg-slate-800">{darkMode ? <Moon className="size-5" /> : <Sun className="size-5" />}</button><button aria-label="Apps" className="hidden rounded-md p-2 hover:bg-slate-100 sm:block dark:hover:bg-slate-800"><Grid2X2 className="size-5" /></button><OrderNotifications darkMode={darkMode} onCountChange={setUnreadOrderCount} /></div>
                        <div className="relative">
                            <button type="button" aria-expanded={profileOpen} onClick={() => setProfileOpen((open) => !open)} className="flex items-center gap-2 rounded-full p-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"><span className="flex size-9 items-center justify-center rounded-full bg-violet-200 text-sm font-bold text-violet-700 ring-4 ring-violet-100">{auth.user.name.charAt(0).toUpperCase()}</span><ChevronDown className="hidden size-4 sm:block" /></button>
                        {profileOpen && (
                            <div className="absolute -right-5 mt-3 w-56 overflow-hidden rounded-lg border border-slate-100 bg-white py-2 shadow-[0_8px_26px_rgba(44,32,66,.18)] dark:border-slate-700 dark:bg-slate-900">
                                <div className="flex items-center gap-3 px-5 pb-3 pt-1"><span className="relative flex size-10 items-center justify-center rounded-full bg-violet-200 font-bold text-violet-700"><span>{auth.user.name.charAt(0).toUpperCase()}</span><i className="absolute bottom-0 right-0 size-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900" /></span><span className="min-w-0"><b className="block truncate text-sm text-slate-700 dark:text-slate-100">{auth.user.name}</b><small className="block truncate text-slate-400">Admin</small></span></div>
                                <div className="border-y border-slate-100 py-1 dark:border-slate-700"><Link href="/profile" className="flex h-10 items-center gap-3 px-6 text-sm text-slate-600 hover:bg-violet-50 hover:text-[#6c5ce7] dark:text-slate-300 dark:hover:bg-slate-800"><UserRound className="size-5" /> My Profile</Link><Link href="/admin/settings/website" className="flex h-10 items-center gap-3 px-6 text-sm text-slate-600 hover:bg-violet-50 hover:text-[#6c5ce7] dark:text-slate-300 dark:hover:bg-slate-800"><Settings className="size-5" /> Settings</Link><a href="#billing" className="flex h-10 items-center gap-3 px-6 text-sm text-slate-600 hover:bg-violet-50 hover:text-[#6c5ce7] dark:text-slate-300 dark:hover:bg-slate-800"><CreditCard className="size-5" /> Billing <span className="ml-auto rounded bg-rose-500 px-2 py-0.5 text-xs font-bold text-white">4</span></a></div>
                                <div className="border-b border-slate-100 py-1 dark:border-slate-700"><a href="#pricing" className="flex h-10 items-center gap-3 px-6 text-sm text-slate-600 hover:bg-violet-50 hover:text-[#6c5ce7] dark:text-slate-300 dark:hover:bg-slate-800"><DollarSign className="size-5" /> Pricing</a><a href="#faq" className="flex h-10 items-center gap-3 px-6 text-sm text-slate-600 hover:bg-violet-50 hover:text-[#6c5ce7] dark:text-slate-300 dark:hover:bg-slate-800"><CircleHelp className="size-5" /> FAQ</a></div>
                                <div className="px-4 pt-2"><Link href="/logout" method="post" as="button" className="flex h-9 w-full items-center justify-center gap-2 rounded bg-[#ff4c59] text-sm font-semibold text-white shadow-sm hover:bg-[#ef3d4b]"><span>Logout</span><LogOut className="size-4" /></Link></div>
                            </div>
                        )}
                        </div>
                    </div>
                </header>
                <main className="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    {flash?.success && (
                        <div className="mb-5 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">
                            <CheckCircle2 className="size-5 shrink-0" aria-hidden="true" />
                            {flash.success}
                        </div>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}
