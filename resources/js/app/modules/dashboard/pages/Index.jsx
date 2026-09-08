import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Archive, BadgeDollarSign, Boxes, ChevronRight, ClipboardList, PackageCheck, ShoppingBag, UsersRound } from 'lucide-react';
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

function Card({ children, className = '' }) {
    return <section className={cn('rounded-lg border border-violet-100 bg-white shadow-[0_3px_12px_rgba(44,32,66,.08)] dark:border-slate-700 dark:bg-slate-900', className)}>{children}</section>;
}

function MetricCard({ metric }) {
    const Icon = iconMap[metric.icon] || PackageCheck;

    return (
        <Link href={metric.href || '#'} className="group block rounded-lg border border-violet-100 bg-white p-4 shadow-[0_3px_12px_rgba(44,32,66,.08)] transition hover:-translate-y-0.5 hover:border-violet-300 hover:shadow-[0_8px_24px_rgba(44,32,66,.12)] dark:border-slate-700 dark:bg-slate-900">
            <div className="flex items-start justify-between gap-3">
                <span className="grid size-11 shrink-0 place-items-center rounded-md bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-200"><Icon className="size-5" /></span>
                <ChevronRight className="size-5 text-slate-300 transition group-hover:translate-x-1 group-hover:text-violet-500" />
            </div>
            <p className="mt-4 text-sm font-semibold text-slate-500 dark:text-slate-400">{metric.label}</p>
            <div className="mt-1 flex flex-wrap items-end gap-x-3 gap-y-1">
                <b className="text-2xl font-bold text-slate-950 dark:text-white">{metric.value}</b>
                <span className="text-xs font-bold text-violet-600 dark:text-violet-300">{metric.hint}</span>
            </div>
        </Link>
    );
}

function EmptyState({ children }) {
    return <div className="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{children}</div>;
}

export default function Dashboard({ summary = [], orderStatus = [], recentOrders = [], stockAlerts = [], catalog = {} }) {
    const activeOrders = orderStatus.filter(item => ['pending', 'processing', 'shipped'].includes(item.key)).reduce((total, item) => total + item.count, 0);

    return (
        <AdminLayout>
            <Head title="Dashboard" />
            <main className="dashboard-content -mx-4 -my-6 min-h-screen bg-[#f8f7fa] px-4 py-5 font-sans text-[#172033] transition-colors dark:bg-slate-950 dark:text-slate-200 sm:-mx-6 sm:px-6 lg:-mx-8 lg:-my-8 lg:px-8 lg:py-8">
                <div className="mx-auto max-w-[1400px] space-y-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-sm font-bold text-violet-600">Ecommerce overview</p>
                            <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">Dashboard</h1>
                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Orders, sales, catalog and stock status in one place.</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href="/admin/orders" className="rounded-md border border-violet-200 bg-white px-4 py-2 text-sm font-bold text-violet-700 hover:bg-violet-50 dark:border-slate-700 dark:bg-slate-900 dark:text-violet-200">View orders</Link>
                            <Link href="/admin/inventories/products/create" className="rounded-md bg-violet-600 px-4 py-2 text-sm font-bold text-white shadow-sm shadow-violet-200 hover:bg-violet-700">Add product</Link>
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
                                <span className="rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 dark:bg-violet-950 dark:text-violet-200">{activeOrders} active</span>
                            </div>
                            {recentOrders.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-[720px] w-full text-left text-sm">
                                        <thead className="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-300">
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
                                                <tr key={order.id} className={cn(!order.viewed && 'bg-violet-50/70 dark:bg-violet-950/30')}>
                                                    <td className="px-5 py-4"><Link href={order.href} className="font-bold text-violet-700 hover:underline dark:text-violet-300">{order.number}</Link>{!order.viewed && <span className="ml-2 rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">New</span>}{order.hasStockShortage && <span className="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">Stock</span>}</td>
                                                    <td className="px-5 py-4"><b className="block text-slate-800 dark:text-slate-100">{order.customer}</b><small className="text-slate-500">{order.phone}</small></td>
                                                    <td className="px-5 py-4 font-bold text-slate-950 dark:text-white">{order.total}</td>
                                                    <td className="px-5 py-4"><span className={cn('rounded-full px-2.5 py-1 text-xs font-bold ring-1', statusColors[order.statusKey] || statusColors.returned)}>{order.status}</span></td>
                                                    <td className="px-5 py-4 text-slate-500">{order.date}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : <div className="p-5"><EmptyState>No orders yet.</EmptyState></div>}
                        </Card>

                        <div className="space-y-6">
                            <Card className="p-5">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <h2 className="text-lg font-bold text-slate-950 dark:text-white">Order pipeline</h2>
                                        <p className="text-sm text-slate-500 dark:text-slate-400">Current fulfilment status.</p>
                                    </div>
                                    <ClipboardList className="size-6 text-violet-600" />
                                </div>
                                <div className="mt-5 space-y-3">
                                    {orderStatus.map(status => <div key={status.key} className="flex items-center justify-between rounded-md border border-slate-100 px-3 py-2 dark:border-slate-700"><span className="text-sm font-semibold text-slate-600 dark:text-slate-300">{status.label}</span><b className="text-lg text-slate-950 dark:text-white">{status.count}</b></div>)}
                                </div>
                            </Card>

                            <Card className="p-5">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <h2 className="text-lg font-bold text-slate-950 dark:text-white">Catalog health</h2>
                                        <p className="text-sm text-slate-500 dark:text-slate-400">Inventory setup summary.</p>
                                    </div>
                                    <Archive className="size-6 text-violet-600" />
                                </div>
                                <div className="mt-5 grid grid-cols-2 gap-3">
                                    {Object.entries({ Categories: catalog.categories || 0, Brands: catalog.brands || 0, Drafts: catalog.drafts || 0, 'Out of stock': catalog.outOfStock || 0 }).map(([label, value]) => <div key={label} className="rounded-md bg-slate-50 p-3 dark:bg-slate-800"><b className="block text-xl text-slate-950 dark:text-white">{value}</b><span className="text-xs font-semibold text-slate-500 dark:text-slate-400">{label}</span></div>)}
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
                        {stockAlerts.length > 0 ? <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">{stockAlerts.map(product => <Link key={product.id} href={product.href} className="flex items-start gap-3 rounded-lg border border-amber-100 bg-amber-50/60 p-4 hover:border-amber-300 dark:border-amber-900 dark:bg-amber-950/20"><span className="grid size-10 shrink-0 place-items-center rounded-md bg-white text-amber-600 dark:bg-slate-900"><AlertTriangle className="size-5" /></span><span className="min-w-0 flex-1"><b className="block truncate text-sm text-slate-950 dark:text-white">{product.title}</b><small className="block text-slate-500">{product.sku}</small><span className="mt-2 inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-200 dark:bg-slate-900">{product.status}: {product.stock}</span></span></Link>)}</div> : <EmptyState>No low-stock product right now.</EmptyState>}
                    </Card>
                </div>
            </main>
        </AdminLayout>
    );
}