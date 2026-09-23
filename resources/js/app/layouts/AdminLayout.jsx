import { Head, Link, router, usePage } from '@inertiajs/react';
import { Bell, CheckCircle2, ChevronDown, LogOut, Menu, Moon, Search, Settings, ShoppingBag, Store, Sun, UserRound, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import IconButton from '@/app/design-system/components/IconButton';
import { adminNavigation } from '@/app/navigation/adminNavigation';
import { cn } from '@/app/utils/cn';
import '../../../css/admin.css';

function isCurrent(url, href) {
    return url === href || (href !== '/dashboard' && url.startsWith(`${href}/`));
}

const searchableDestinations = adminNavigation.flatMap((item) => item.children
    ? item.children.map((child) => ({ ...child, context: item.label }))
    : [{ ...item, context: 'Admin' }]);

function AdminSearch({ inputRef }) {
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState(false);
    const root = useRef(null);
    const results = query.trim() === '' ? searchableDestinations.slice(0, 6) : searchableDestinations.filter((item) => `${item.label} ${item.context}`.toLowerCase().includes(query.trim().toLowerCase())).slice(0, 8);
    const visit = (item) => {
        setOpen(false);
        setQuery('');
        router.visit(item.href);
    };

    useEffect(() => {
        const outside = (event) => { if (!root.current?.contains(event.target)) setOpen(false); };
        document.addEventListener('pointerdown', outside);
        return () => document.removeEventListener('pointerdown', outside);
    }, []);

    return <div ref={root} className="relative hidden w-full max-w-lg md:block">
        <form onSubmit={(event) => { event.preventDefault(); if (results[0]) visit(results[0]); }}>
            <Search className="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-slate-400" />
            <input ref={inputRef} type="search" value={query} onFocus={() => setOpen(true)} onChange={(event) => { setQuery(event.target.value); setOpen(true); }} onKeyDown={(event) => { if (event.key === 'Escape') { setOpen(false); event.currentTarget.blur(); } }} aria-label="Search admin panel" placeholder="Search admin pages" className="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-20 text-sm text-slate-700 shadow-none outline-none transition placeholder:text-slate-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-violet-500 dark:focus:ring-violet-950" />
            <kbd className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-400 dark:border-slate-600 dark:bg-slate-900">Ctrl K</kbd>
        </form>
        {open && <div className="absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">{results.length ? results.map((item) => <button key={item.href} type="button" onClick={() => visit(item)} className="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left hover:bg-violet-50 dark:hover:bg-slate-800"><span className="text-sm font-semibold text-slate-800 dark:text-slate-100">{item.label}</span><span className="text-xs text-slate-400">{item.context}</span></button>) : <p className="px-3 py-5 text-center text-sm text-slate-500">No admin page found.</p>}</div>}
    </div>;
}

function Navigation({ url, onNavigate, orderCount = 0 }) {
    const [expanded, setExpanded] = useState(() => adminNavigation.find((item) => item.children?.some((child) => isCurrent(url, child.href)))?.label || null);

    useEffect(() => {
        const activeSection = adminNavigation.find((item) => item.children?.some((child) => isCurrent(url, child.href)));
        if (activeSection) setExpanded(activeSection.label);
    }, [url]);

    return (
        <nav className="space-y-0.5 px-2.5" aria-label="Admin navigation">
            {adminNavigation.map((item) => {
                const Icon = item.icon;
                const activeChildHref = item.children
                    ?.filter((child) => isCurrent(url, child.href))
                    .sort((first, second) => second.href.length - first.href.length)[0]?.href;
                const childActive = Boolean(activeChildHref);

                if (item.children) {
                    const isExpanded = expanded === item.label;
                    return (
                        <div key={item.label} className="py-0.5">
                            <button type="button" onClick={() => setExpanded(isExpanded ? null : item.label)} className={cn('flex min-h-9 w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-[13px] font-semibold leading-5 transition-colors hover:bg-slate-100 hover:text-slate-950 dark:hover:bg-slate-800 dark:hover:text-white', childActive ? 'bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300' : 'text-slate-600 dark:text-slate-300')}>
                                <Icon className="size-[18px] shrink-0" strokeWidth={1.8} aria-hidden="true" />
                                <span className="flex-1">{item.label}</span>
                                <ChevronDown className={cn('size-3.5 shrink-0 transition-transform', isExpanded && 'rotate-180')} aria-hidden="true" />
                            </button>
                            {isExpanded && <div className="ml-[18px] mt-1 space-y-0.5 border-l border-slate-200 pl-2.5 dark:border-slate-700">
                                {item.children.map((child) => {
                                    const ChildIcon = child.icon;
                                    const active = child.href === activeChildHref;
                                    return (
                                        <Link key={child.href} href={child.href} onClick={() => { setExpanded(item.label); onNavigate?.(); }} className={cn('flex min-h-8 items-center gap-2 rounded-lg px-2.5 py-1.5 text-[13px] font-medium leading-5 transition-colors', active ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white')}>
                                            <ChildIcon className="size-4 shrink-0" strokeWidth={1.8} aria-hidden="true" />
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
                    <Link key={item.href} href={item.href} onClick={onNavigate} className={cn('flex min-h-9 items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-semibold leading-5 transition-colors', active ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white')}>
                        <Icon className="size-[18px] shrink-0" strokeWidth={1.8} aria-hidden="true" />
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
            <div className="flex-1 overflow-y-auto py-3"><Navigation url={url} onNavigate={onNavigate} orderCount={orderCount} /></div>
            <div className="border-t border-violet-100 p-3 dark:border-slate-700">
                <Link href="/" className="flex min-h-9 items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-semibold leading-5 text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                    <Store className="size-[18px]" strokeWidth={1.8} /> View storefront
                </Link>
            </div>
        </div>
    );
}

function OrderNotifications({ darkMode, onCountChange, toastEnabled = false }) {
    const [open,setOpen]=useState(false),[orders,setOrders]=useState([]),[unreadCount,setUnreadCount]=useState(0),[loading,setLoading]=useState(true),[error,setError]=useState(''),[toastOrders,setToastOrders]=useState([]);
    const root=useRef(null),trigger=useRef(null),request=useRef(null),latest=useRef(null),toastTimer=useRef(null),toastFlag=useRef(toastEnabled);
    const {url}=usePage();
    toastFlag.current=toastEnabled;
    const load=async()=>{
        request.current?.abort();
        const controller=new AbortController();request.current=controller;
        setLoading(true);
        try {
            const response=await fetch(route('orders.notifications'),{headers:{Accept:'application/json'},signal:controller.signal,cache:'no-store'});
            if(!response.ok)throw new Error('Could not refresh notifications. Please try again.');
            const data=await response.json();
            if(controller.signal.aborted)return;
            const next=data.orders||[];
            if(latest.current!==null&&toastFlag.current){
                const fresh=next.filter(o=>!o.viewed&&Number(o.id)>latest.current).slice(0,3);
                if(fresh.length){setToastOrders(fresh);clearTimeout(toastTimer.current);toastTimer.current=setTimeout(()=>setToastOrders([]),10000);}
            }
            latest.current=Math.max(latest.current||0,...next.map(o=>Number(o.id)),0);
            setOrders(next);setUnreadCount(data.unreadCount||0);onCountChange(data.unreadCount||0);setError('');
        }catch(e){if(e.name!=='AbortError')setError(e.message);}
        finally{if(!controller.signal.aborted)setLoading(false);}
    };
    useEffect(()=>{
        const refresh=()=>{if(document.visibilityState==='visible')load();};
        const timer=setInterval(refresh,30000);
        document.addEventListener('visibilitychange',refresh);
        return()=>{request.current?.abort();clearInterval(timer);clearTimeout(toastTimer.current);document.removeEventListener('visibilitychange',refresh);};
    },[]);
    useEffect(()=>{setOpen(false);setToastOrders([]);load();},[url]);
    useEffect(()=>{
        if(!open)return;
        const outside=e=>{if(!root.current?.contains(e.target))setOpen(false);};
        const escape=e=>{if(e.key==='Escape'){setOpen(false);trigger.current?.focus();}};
        document.addEventListener('pointerdown',outside);document.addEventListener('keydown',escape);
        return()=>{document.removeEventListener('pointerdown',outside);document.removeEventListener('keydown',escape);};
    },[open]);
    const visit=()=>{setOpen(false);setToastOrders([]);};
    return <div ref={root} className="relative">
        <button ref={trigger} type="button" aria-label={`Order notifications, ${unreadCount} unread`} aria-expanded={open} aria-controls="order-notifications" onClick={()=>{setOpen(v=>!v);if(!open)load();}} className="relative rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-800"><Bell className="size-5"/>{unreadCount>0&&<span className="absolute -right-1 -top-1 rounded-full bg-rose-500 px-1.5 text-[10px] font-bold leading-4 text-white">{unreadCount>99?'99+':unreadCount}</span>}</button>
        {open&&<section id="order-notifications" aria-label="Order notifications" className="fixed left-3 right-3 top-[4.5rem] z-50 flex max-h-[calc(100dvh-5.5rem)] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900 sm:absolute sm:left-auto sm:right-0 sm:top-full sm:mt-3 sm:w-96">
            <header className="flex shrink-0 items-center justify-between gap-2 border-b border-slate-200 p-4 dark:border-slate-700"><div><h2 className="text-sm font-semibold">Order notifications</h2><p className="mt-1 text-xs text-slate-500">{unreadCount} unread · Latest 20 orders</p></div><button type="button" aria-label="Close notifications" onClick={()=>{setOpen(false);trigger.current?.focus();}} className="rounded p-2 hover:bg-slate-100 dark:hover:bg-slate-800"><X className="size-4"/></button></header>
            {error&&<div role="alert" className="p-3 text-xs text-rose-600">{error}<button type="button" onClick={load} className="ml-2 underline">Retry</button></div>}
            <div className="min-h-0 overflow-y-auto overscroll-contain" aria-busy={loading}>
                {loading&&!orders.length?<p className="p-8 text-center text-sm text-slate-500">Loading notifications…</p>:!orders.length&&!error?<p className="p-8 text-center text-sm text-slate-500">No orders yet.</p>:orders.map(order=><Link key={order.id} href={route('orders.show',order.id)} onClick={visit} className={cn('block border-b border-slate-100 p-4 transition-colors last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800',!order.viewed&&'bg-violet-50/50 dark:bg-violet-950/20')}>
                    <div className="flex items-center justify-between gap-2"><b className="text-sm text-violet-700">{order.number}</b><span className="text-[11px] text-slate-500">{order.status}</span></div>
                    <div className="mt-2 flex items-start justify-between gap-3"><span className="min-w-0 break-words text-sm text-slate-800">{order.customer}</span><b className="shrink-0 text-sm text-slate-800">৳{Number(order.total).toLocaleString('en-BD')}</b></div>
                    <p className="mt-1 text-xs text-slate-500">{order.phone}</p>
                    {order.hasStockShortage&&<p className="mt-2 text-xs text-amber-700">Stock shortage — review required</p>}
                    <div className="mt-2 flex justify-between gap-2 text-[11px] text-slate-500"><time title={order.date}>{order.createdAt}</time>{!order.viewed&&<span className="font-semibold text-violet-600">Unread</span>}</div>
                </Link>)}
            </div>
            <Link href={route('orders.index')} onClick={visit} className="shrink-0 border-t border-slate-200 p-3 text-center text-sm font-semibold text-violet-700 dark:border-slate-700">View all orders</Link>
        </section>}
        {!!toastOrders.length&&!open&&<div className="fixed left-3 right-3 top-20 z-40 space-y-2 sm:left-auto sm:right-4 sm:w-96" aria-live="polite">{toastOrders.map(order=><div key={order.id} className="flex items-start gap-3 rounded-xl border border-violet-200 bg-white p-4 shadow-lg dark:border-slate-700 dark:bg-slate-900"><Link href={route('orders.show',order.id)} onClick={visit} className="min-w-0 flex-1"><span className="text-xs text-violet-600">New order received</span><b className="mt-1 block break-words text-sm text-slate-800">{order.number} · {order.customer}</b><p className="mt-1 text-xs text-slate-500">৳{Number(order.total).toLocaleString('en-BD')} · {order.status}</p></Link><button type="button" aria-label="Dismiss notification" onClick={()=>setToastOrders(current=>current.filter(o=>o.id!==order.id))} className="p-1"><X className="size-4"/></button></div>)}</div>}
    </div>;
}

export default function AdminLayout({ children, showFlash = true }) {
    const { auth, flash, website } = usePage().props;
    const currentUrl = usePage().url;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [profileOpen, setProfileOpen] = useState(false);
    const [darkMode, setDarkMode] = useState(() => localStorage.getItem('admin-theme') === 'dark');
    const [unreadOrderCount, setUnreadOrderCount] = useState(0);
    const [flashMessage, setFlashMessage] = useState(flash?.success || null);
    const [flashVisible, setFlashVisible] = useState(Boolean(flash?.success));
    const searchRef = useRef(null);

    useEffect(() => setMobileOpen(false), [currentUrl]);
    useEffect(() => {
        const focusSearch = event => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                searchRef.current?.focus();
            }
        };
        window.addEventListener('keydown', focusSearch);
        return () => window.removeEventListener('keydown', focusSearch);
    }, []);
    useEffect(() => {
        document.body.classList.add('admin-ui');
        return () => document.body.classList.remove('admin-ui');
    }, []);
    useEffect(() => {
        document.documentElement.classList.toggle('dark', darkMode);
        localStorage.setItem('admin-theme', darkMode ? 'dark' : 'light');
    }, [darkMode]);
    useEffect(() => {
        if (!flash?.success) return;
        setFlashMessage(flash.success);
        setFlashVisible(true);
        const fadeTimer = window.setTimeout(() => setFlashVisible(false), 4500);
        const removeTimer = window.setTimeout(() => setFlashMessage(null), 5000);
        return () => { window.clearTimeout(fadeTimer); window.clearTimeout(removeTimer); };
    }, [flash?.success]);

    return (
        <div data-admin-panel className={cn('min-h-screen transition-colors', darkMode ? 'dark bg-slate-950 text-slate-100' : 'bg-[#f8f7fa] text-slate-900')}>
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
                        <AdminSearch inputRef={searchRef} />
                        <div className="ml-auto flex items-center gap-2 text-slate-600 dark:text-slate-300"><button type="button" aria-label="Toggle colour mode" onClick={() => setDarkMode((value) => !value)} className="rounded-md p-2 hover:bg-slate-100 dark:hover:bg-slate-800">{darkMode ? <Sun className="size-5" /> : <Moon className="size-5" />}</button><OrderNotifications darkMode={darkMode} onCountChange={setUnreadOrderCount} toastEnabled /></div>
                        <div className="relative">
                            <button type="button" aria-expanded={profileOpen} onClick={() => setProfileOpen((open) => !open)} className="flex items-center gap-2 rounded-full p-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"><span className="flex size-9 items-center justify-center rounded-full bg-violet-200 text-sm font-bold text-violet-700 ring-4 ring-violet-100">{auth.user.name.charAt(0).toUpperCase()}</span><ChevronDown className="hidden size-4 sm:block" /></button>
                        {profileOpen && (
                            <div className="absolute -right-5 mt-3 w-56 overflow-hidden rounded-lg border border-slate-100 bg-white py-2 shadow-[0_8px_26px_rgba(44,32,66,.18)] dark:border-slate-700 dark:bg-slate-900">
                                <div className="flex items-center gap-3 px-5 pb-3 pt-1"><span className="relative flex size-10 items-center justify-center rounded-full bg-violet-200 font-bold text-violet-700"><span>{auth.user.name.charAt(0).toUpperCase()}</span><i className="absolute bottom-0 right-0 size-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900" /></span><span className="min-w-0"><b className="block truncate text-sm text-slate-700 dark:text-slate-100">{auth.user.name}</b><small className="block truncate text-slate-400">Admin</small></span></div>
                                <div className="border-y border-slate-100 py-1 dark:border-slate-700"><Link href="/admin/profile" className="flex h-10 items-center gap-3 px-6 text-sm text-slate-600 hover:bg-violet-50 hover:text-[#6c5ce7] dark:text-slate-300 dark:hover:bg-slate-800"><UserRound className="size-5" /> My Profile</Link><Link href="/admin/settings/website" className="flex h-10 items-center gap-3 px-6 text-sm text-slate-600 hover:bg-violet-50 hover:text-[#6c5ce7] dark:text-slate-300 dark:hover:bg-slate-800"><Settings className="size-5" /> Settings</Link></div>
                                <div className="px-4 pt-2"><Link href="/logout" method="post" as="button" className="flex h-9 w-full items-center justify-center gap-2 rounded bg-[#ff4c59] text-sm font-semibold text-white shadow-sm hover:bg-[#ef3d4b]"><span>Logout</span><LogOut className="size-4" /></Link></div>
                            </div>
                        )}
                        </div>
                    </div>
                </header>
                <main className="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    {showFlash && flashMessage && (
                        <div className={cn('relative z-10 mb-5 flex w-full items-center gap-3 rounded-xl border border-emerald-300 border-l-4 border-l-emerald-500 bg-emerald-50 px-4 py-3 shadow-[0_6px_18px_rgba(5,150,105,.10)] transition-all duration-500 dark:border-emerald-900 dark:border-l-emerald-500 dark:bg-emerald-950/30', flashVisible ? 'translate-y-0 opacity-100' : '-translate-y-2 opacity-0')} role="status" aria-live="polite">
                            <span className="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:ring-emerald-800"><CheckCircle2 className="size-5" aria-hidden="true" /></span>
                            <div className="min-w-0 flex-1"><b className="block text-[11px] font-extrabold uppercase tracking-[.12em] text-emerald-700 dark:text-emerald-300">Success</b><p className="mt-0.5 text-sm font-semibold text-slate-700 dark:text-slate-200">{flashMessage}</p></div>
                            <button type="button" onClick={() => setFlashMessage(null)} className="grid size-8 shrink-0 place-items-center rounded-lg text-emerald-700 transition-colors hover:bg-emerald-100" aria-label="Dismiss message"><X className="size-4" /></button>
                        </div>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}
