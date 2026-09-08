import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Archive, BadgeCheck, BadgeDollarSign, Boxes, ChevronRight, ClipboardList, FileClock, FolderTree, PackageCheck, PackageX, Plus, ShoppingBag, UsersRound } from 'lucide-react';
import AdminLayout from '@/app/layouts/AdminLayout';
import { cn } from '@/app/utils/cn';

const iconMap = {
    revenue: BadgeDollarSign,
    orders: ShoppingBag,
    products: Boxes,
    customers: UsersRound,
};

const statusColors = {
    pending: 'bg-amber-50 text-amber-700 ring-amber-200',
    processing: 'bg-sky-50 text-sky-700 ring-sky-200',
    shipped: 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    delivered: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    cancelled: 'bg-rose-50 text-rose-700 ring-rose-200',
    returned: 'bg-slate-100 text-slate-700 ring-slate-200',
};

const pipelineColors = {
    pending: { dot: 'bg-amber-500', bar: 'bg-amber-500', text: 'text-amber-700 dark:text-amber-300', surface: 'border-amber-100 bg-amber-50/60 dark:border-amber-900/70 dark:bg-amber-950/20' },
    processing: { dot: 'bg-sky-500', bar: 'bg-sky-500', text: 'text-sky-700 dark:text-sky-300', surface: 'border-sky-100 bg-sky-50/60 dark:border-sky-900/70 dark:bg-sky-950/20' },
    shipped: { dot: 'bg-indigo-500', bar: 'bg-indigo-500', text: 'text-indigo-700 dark:text-indigo-300', surface: 'border-indigo-100 bg-indigo-50/60 dark:border-indigo-900/70 dark:bg-indigo-950/20' },
    delivered: { dot: 'bg-emerald-500', bar: 'bg-emerald-500', text: 'text-emerald-700 dark:text-emerald-300', surface: 'border-emerald-100 bg-emerald-50/60 dark:border-emerald-900/70 dark:bg-emerald-950/20' },
    cancelled: { dot: 'bg-rose-500', bar: 'bg-rose-500', text: 'text-rose-700 dark:text-rose-300', surface: 'border-rose-100 bg-rose-50/60 dark:border-rose-900/70 dark:bg-rose-950/20' },
    returned: { dot: 'bg-slate-500', bar: 'bg-slate-500', text: 'text-slate-700 dark:text-slate-300', surface: 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/60' },
};

function Card({ children, className = '' }) {
    return <section className={cn('rounded-lg border border-violet-100/80 bg-white shadow-[0_1px_2px_rgba(15,23,42,.04),0_10px_30px_rgba(67,40,116,.08)] dark:border-slate-700 dark:bg-slate-900 dark:shadow-[0_1px_2px_rgba(0,0,0,.24),0_14px_34px_rgba(0,0,0,.20)]', className)}>{children}</section>;
}

function MetricCard({ metric }) {
    const Icon = iconMap[metric.icon] || PackageCheck;

    return (
        <Link href={metric.href || '#'} className="group relative block min-h-40 overflow-hidden rounded-lg border border-violet-100/80 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,.04),0_8px_24px_rgba(67,40,116,.075)] transition duration-200 hover:-translate-y-0.5 hover:border-violet-300 hover:shadow-[0_2px_5px_rgba(15,23,42,.06),0_16px_34px_rgba(91,33,182,.14)] dark:border-slate-700 dark:bg-slate-900 dark:shadow-[0_1px_2px_rgba(0,0,0,.24),0_12px_28px_rgba(0,0,0,.18)] dark:hover:shadow-[0_2px_5px_rgba(0,0,0,.28),0_18px_38px_rgba(0,0,0,.26)]">
            <span aria-hidden="true" className="absolute -right-8 -top-10 size-28 rounded-full bg-violet-50/80 transition duration-300 group-hover:scale-110 dark:bg-violet-950/25" />
            <div className="relative flex items-center justify-between gap-3">
                <div className="flex min-w-0 items-center gap-3">
                    <span className="grid size-10 shrink-0 place-items-center rounded-lg bg-violet-100 text-violet-700 ring-1 ring-violet-200/70 dark:bg-violet-950 dark:text-violet-200 dark:ring-violet-800"><Icon className="size-5" /></span>
                    <p className="truncate text-sm font-bold text-slate-600 dark:text-slate-300">{metric.label}</p>
                </div>
                <span className="grid size-8 shrink-0 place-items-center rounded-full border border-violet-100 bg-white/80 text-slate-400 transition duration-200 group-hover:border-violet-300 group-hover:bg-violet-600 group-hover:text-white dark:border-slate-700 dark:bg-slate-900">
                    <ChevronRight className="size-4 transition group-hover:translate-x-0.5" />
                </span>
            </div>
            <div className="relative mt-5">
                <b className="block break-words text-[clamp(1.45rem,1.7vw,1.8rem)] font-extrabold leading-none tracking-tight text-slate-950 dark:text-white">{metric.value}</b>
                <span className="mt-3 inline-flex max-w-full items-center gap-2 rounded-full bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700 ring-1 ring-inset ring-violet-100 dark:bg-violet-950/60 dark:text-violet-200 dark:ring-violet-800">
                    <span className="size-1.5 shrink-0 rounded-full bg-violet-500" />
                    <span className="truncate">{metric.hint}</span>
                </span>
            </div>
        </Link>
    );
}

function EmptyState({ children }) {
    return <div className="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{children}</div>;
}

export default function Dashboard({ summary = [], orderStatus = [], recentOrders = [], stockAlerts = [], catalog = {} }) {
    const activeOrders = orderStatus.filter(item => ['pending', 'processing', 'shipped'].includes(item.key)).reduce((total, item) => total + item.count, 0);
    const totalOrders = orderStatus.reduce((total, item) => total + item.count, 0);
    const catalogItems = [
        { key: 'categories', label: 'Categories', value: catalog.categories || 0, Icon: FolderTree, surface: 'border-violet-100 bg-violet-50/60 dark:border-violet-900/70 dark:bg-violet-950/20', icon: 'bg-white text-violet-600 ring-violet-100 dark:bg-slate-900 dark:text-violet-300 dark:ring-violet-900', valueColor: 'text-violet-700 dark:text-violet-300' },
        { key: 'brands', label: 'Brands', value: catalog.brands || 0, Icon: BadgeCheck, surface: 'border-cyan-100 bg-cyan-50/60 dark:border-cyan-900/70 dark:bg-cyan-950/20', icon: 'bg-white text-cyan-600 ring-cyan-100 dark:bg-slate-900 dark:text-cyan-300 dark:ring-cyan-900', valueColor: 'text-cyan-700 dark:text-cyan-300' },
        { key: 'drafts', label: 'Draft products', value: catalog.drafts || 0, Icon: FileClock, surface: 'border-amber-100 bg-amber-50/60 dark:border-amber-900/70 dark:bg-amber-950/20', icon: 'bg-white text-amber-600 ring-amber-100 dark:bg-slate-900 dark:text-amber-300 dark:ring-amber-900', valueColor: 'text-amber-700 dark:text-amber-300' },
        { key: 'outOfStock', label: 'Out of stock', value: catalog.outOfStock || 0, Icon: PackageX, surface: 'border-rose-100 bg-rose-50/60 dark:border-rose-900/70 dark:bg-rose-950/20', icon: 'bg-white text-rose-600 ring-rose-100 dark:bg-slate-900 dark:text-rose-300 dark:ring-rose-900', valueColor: 'text-rose-700 dark:text-rose-300' },
    ];

    return (
        <AdminLayout>
            <Head title="Dashboard" />
            <main className="dashboard-content -mx-4 -my-6 min-h-screen bg-[#f8f7fa] px-4 py-5 font-sans text-[#172033] transition-colors dark:bg-slate-950 dark:text-slate-200 sm:-mx-6 sm:px-6 lg:-mx-8 lg:-my-8 lg:px-8 lg:py-8">
                <div className="w-full space-y-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-sm font-bold text-violet-600">Ecommerce overview</p>
                            <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">Dashboard</h1>
                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Orders, sales, catalog and stock status in one place.</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href="/admin/orders" className="group inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-slate-800 to-slate-950 px-4 py-2.5 text-sm font-bold text-white shadow-[0_6px_16px_rgba(15,23,42,.18)] transition duration-200 hover:-translate-y-0.5 hover:from-slate-700 hover:to-slate-900 hover:shadow-[0_10px_22px_rgba(15,23,42,.24)]"><ShoppingBag className="size-4 transition group-hover:-rotate-6" />View orders</Link>
                            <Link href="/admin/inventories/products/create" className="group inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-violet-600 to-purple-600 px-4 py-2.5 text-sm font-bold text-white shadow-[0_6px_18px_rgba(124,58,237,.28)] transition duration-200 hover:-translate-y-0.5 hover:from-violet-700 hover:to-purple-700 hover:shadow-[0_10px_24px_rgba(124,58,237,.34)]"><Plus className="size-4 transition group-hover:rotate-90" />Add product</Link>
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {summary.map(metric => <MetricCard key={metric.label} metric={metric} />)}
                    </div>

                    <div className="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.65fr)]">
                        <Card className="overflow-hidden">
                            <div className="flex flex-col gap-2 border-b border-violet-100 px-5 py-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 className="text-lg font-bold text-slate-950 dark:text-white">Recent orders</h2>
                                    <p className="text-sm text-slate-500 dark:text-slate-400">Latest customer orders and stock warnings.</p>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 dark:bg-violet-950 dark:text-violet-200">{activeOrders} active</span>
                                    <Link href="/admin/orders" className="inline-flex items-center gap-1 rounded-md border border-violet-200 bg-white px-3 py-1.5 text-xs font-bold text-violet-700 transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-700 dark:bg-slate-900 dark:text-violet-200">View all orders<ChevronRight className="size-3.5" /></Link>
                                </div>
                            </div>
                            {recentOrders.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-[720px] w-full text-left text-sm">
                                        <thead className="bg-slate-50/90 text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                            <tr>
                                                <th className="px-5 py-3">Order</th>
                                                <th className="px-5 py-3">Customer</th>
                                                <th className="px-5 py-3">Total</th>
                                                <th className="px-5 py-3">Status</th>
                                                <th className="px-5 py-3">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                            {recentOrders.map(order => (
                                                <tr key={order.id} className={cn('transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/60', !order.viewed && 'bg-violet-50/70 dark:bg-violet-950/30')}>
                                                    <td className="px-5 py-3.5"><Link href={order.href} className="font-bold text-violet-700 hover:underline dark:text-violet-300">{order.number}</Link>{!order.viewed && <span className="ml-2 rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">New</span>}{order.hasStockShortage && <span className="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">Stock</span>}</td>
                                                    <td className="px-5 py-3.5">
                                                        <div className="flex items-center gap-2.5">
                                                            <span className="grid size-8 shrink-0 place-items-center rounded-full bg-violet-100 text-xs font-extrabold uppercase text-violet-700 dark:bg-violet-950 dark:text-violet-200">{order.customer?.charAt(0) || 'C'}</span>
                                                            <span className="min-w-0"><b className="block max-w-44 truncate text-slate-800 dark:text-slate-100">{order.customer}</b><small className="text-slate-500">{order.phone}</small></span>
                                                        </div>
                                                    </td>
                                                    <td className="whitespace-nowrap px-5 py-3.5 font-bold text-slate-950 dark:text-white">{order.total}</td>
                                                    <td className="px-5 py-3.5"><span className={cn('rounded-full px-2.5 py-1 text-xs font-bold ring-1', statusColors[order.statusKey] || statusColors.returned)}>{order.status}</span></td>
                                                    <td className="whitespace-nowrap px-5 py-3.5 text-slate-500">{order.date}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : <div className="p-5"><EmptyState>No orders yet. New customer orders will appear here.</EmptyState></div>}
                        </Card>

                        <div className="space-y-6">
                            <Card className="p-5">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <h2 className="text-lg font-bold text-slate-950 dark:text-white">Order pipeline</h2>
                                        <p className="text-sm text-slate-500 dark:text-slate-400">Current fulfilment status.</p>
                                    </div>
                                    <div className="text-right">
                                        <span className="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 dark:bg-violet-950 dark:text-violet-200"><ClipboardList className="size-3.5" />{totalOrders} total</span>
                                    </div>
                                </div>
                                <div className="mt-5 space-y-2.5">
                                    {orderStatus.map(status => {
                                        const colors = pipelineColors[status.key] || pipelineColors.returned;
                                        const percentage = totalOrders > 0 ? Math.round((status.count / totalOrders) * 100) : 0;

                                        return (
                                            <div key={status.key} className={cn('rounded-lg border px-3.5 py-3 transition hover:-translate-y-px hover:shadow-sm', colors.surface)}>
                                                <div className="flex items-center justify-between gap-3">
                                                    <div className="flex min-w-0 items-center gap-2.5">
                                                        <span className={cn('size-2.5 shrink-0 rounded-full ring-4 ring-white dark:ring-slate-900', colors.dot)} />
                                                        <span className="truncate text-sm font-bold text-slate-700 dark:text-slate-200">{status.label}</span>
                                                    </div>
                                                    <div className="flex shrink-0 items-baseline gap-2">
                                                        <span className={cn('text-xs font-bold', colors.text)}>{percentage}%</span>
                                                        <b className="min-w-6 text-right text-lg leading-none text-slate-950 dark:text-white">{status.count}</b>
                                                    </div>
                                                </div>
                                                <div className="mt-2.5 h-1.5 overflow-hidden rounded-full bg-white/90 ring-1 ring-black/5 dark:bg-slate-900/80 dark:ring-white/10">
                                                    <span className={cn('block h-full rounded-full transition-all duration-500', colors.bar)} style={{ width: `${percentage}%` }} />
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </Card>

                            <Card className="p-5">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <h2 className="text-lg font-bold text-slate-950 dark:text-white">Catalog health</h2>
                                        <p className="text-sm text-slate-500 dark:text-slate-400">Inventory setup summary.</p>
                                    </div>
                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 dark:bg-violet-950 dark:text-violet-200"><Archive className="size-3.5" />4 areas</span>
                                </div>
                                <div className="mt-5 grid grid-cols-2 gap-3">
                                    {catalogItems.map(({ key, label, value, Icon, surface, icon, valueColor }) => (
                                        <div key={key} className={cn('group relative min-h-28 overflow-hidden rounded-lg border p-3.5 transition duration-200 hover:-translate-y-0.5 hover:shadow-md', surface)}>
                                            <span aria-hidden="true" className="absolute -bottom-7 -right-6 size-20 rounded-full bg-white/40 dark:bg-white/5" />
                                            <div className="relative flex items-start justify-between gap-2">
                                                <span className={cn('grid size-9 shrink-0 place-items-center rounded-lg ring-1 shadow-sm', icon)}><Icon className="size-4.5" /></span>
                                                <b className={cn('text-2xl font-extrabold leading-none tracking-tight', valueColor)}>{value}</b>
                                            </div>
                                            <p className="relative mt-4 text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">{label}</p>
                                        </div>
                                    ))}
                                </div>
                            </Card>
                        </div>
                    </div>

                    <Card className="p-5">
                        <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-lg font-bold text-slate-950 dark:text-white">Stock alerts</h2>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Products at or below low-stock threshold.</p>
                            </div>
                            <Link href="/admin/inventories/products" className="text-sm font-bold text-violet-700 hover:underline dark:text-violet-300">Manage products</Link>
                        </div>
                        {stockAlerts.length > 0 ? <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">{stockAlerts.map(product => <Link key={product.id} href={product.href} className="flex items-start gap-3 rounded-lg border border-amber-100 bg-amber-50/60 p-4 shadow-[0_1px_3px_rgba(120,53,15,.05),0_7px_18px_rgba(120,53,15,.06)] transition duration-200 hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-[0_2px_5px_rgba(120,53,15,.08),0_12px_26px_rgba(120,53,15,.11)] dark:border-amber-900 dark:bg-amber-950/20 dark:shadow-[0_1px_3px_rgba(0,0,0,.2)]"><span className="grid size-10 shrink-0 place-items-center rounded-md bg-white text-amber-600 shadow-[0_1px_3px_rgba(120,53,15,.08)] dark:bg-slate-900"><AlertTriangle className="size-5" /></span><span className="min-w-0 flex-1"><b className="block truncate text-sm text-slate-950 dark:text-white">{product.title}</b><small className="block text-slate-500">{product.sku}</small><span className="mt-2 inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-200 dark:bg-slate-900">{product.status}: {product.stock}</span></span></Link>)}</div> : <EmptyState>No low-stock product right now.</EmptyState>}
                    </Card>
                </div>
            </main>
        </AdminLayout>
    );
}