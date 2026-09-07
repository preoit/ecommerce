import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CircleDollarSign, MapPin, Package, Phone, Mail, TriangleAlert } from 'lucide-react';
import AdminLayout from '@/app/layouts/AdminLayout';

const money = value => `৳${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;
const statuses = [
    ['pending', 'Pending'],
    ['confirmed', 'Confirmed'],
    ['processing', 'Processing'],
    ['ready_to_ship', 'Ready To Ship'],
    ['shipped', 'Shipped'],
    ['completed', 'Completed'],
    ['cancelled', 'Cancelled'],
    ['returned', 'Returned'],
];

export default function OrderShow({ order }) {
    const { data, setData, patch, processing, recentlySuccessful, errors } = useForm({ status: order.statusKey });
    const updateStatus = event => {
        event.preventDefault();
        patch(route('orders.status.update', order.id), { preserveScroll: true });
    };

    return <AdminLayout>
        <Head title={`Order ${order.number}`} />
        <main className="mx-auto max-w-6xl space-y-6">
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <Link href={route('orders.index')} className="inline-flex items-center gap-2 text-sm font-semibold text-violet-700"><ArrowLeft className="size-4" />Back to orders</Link>
                    <h1 className="mt-2 text-2xl font-bold text-slate-950 dark:text-white">Order {order.number}</h1>
                    <p className="mt-1 text-sm text-slate-500">{order.date}</p>
                </div>
                <span className="w-fit rounded-full bg-violet-100 px-4 py-2 text-sm font-bold text-violet-700">{order.status}</span>
            </div>

            {order.hasStockShortage && <div className="flex gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800"><TriangleAlert className="mt-0.5 size-5 shrink-0" /><div><b>Stock unavailable — action required</b><p className="mt-1 text-sm">One or more products need stock before this order can be fulfilled.</p></div></div>}

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    <div className="border-b border-slate-200 p-5 dark:border-slate-800"><h2 className="flex items-center gap-2 text-lg font-bold"><Package className="size-5 text-violet-600" />Ordered products</h2></div>
                    <div className="divide-y divide-slate-200 dark:divide-slate-800">
                        {order.items.map((item, index) => <article key={`${item.sku || item.title}-${index}`} className="grid gap-3 p-5 sm:grid-cols-[1fr_auto]">
                            <div><h3 className="font-bold">{item.title}</h3><p className="mt-1 text-sm text-slate-500">SKU: {item.sku || 'N/A'} · {money(item.unitPrice)} × {item.quantity}</p>{item.stockShortageQuantity > 0 && <p className="mt-2 text-sm font-bold text-rose-600">Stock shortage: {item.stockShortageQuantity}</p>}</div>
                            <b>{money(item.lineTotal)}</b>
                        </article>)}
                    </div>
                    <div className="space-y-2 border-t border-slate-200 bg-slate-50 p-5 text-sm dark:border-slate-800 dark:bg-slate-950/40">
                        <div className="flex justify-between"><span>Subtotal</span><b>{money(order.subtotal)}</b></div>
                        <div className="flex justify-between"><span>Delivery</span><b>{money(order.shippingTotal)}</b></div>
                        <div className="flex justify-between border-t border-slate-200 pt-3 text-lg dark:border-slate-700"><span>Total</span><b className="text-violet-700">{money(order.total)}</b></div>
                    </div>
                </section>

                <aside className="space-y-6">
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                        <h2 className="font-bold">Customer & delivery</h2>
                        <p className="mt-4 font-semibold">{order.customerName}</p>
                        <a href={`tel:${order.phone}`} className="mt-3 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><Phone className="size-4 text-violet-600" />{order.phone}</a>
                        {order.email && <a href={`mailto:${order.email}`} className="mt-2 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><Mail className="size-4 text-violet-600" />{order.email}</a>}
                        <p className="mt-3 flex items-start gap-2 text-sm leading-6 text-slate-600 dark:text-slate-300"><MapPin className="mt-1 size-4 shrink-0 text-violet-600" />{order.address}, {order.city}</p>
                        {order.note && <div className="mt-4 rounded-xl bg-slate-50 p-3 text-sm dark:bg-slate-800"><b>Order note</b><p className="mt-1 text-slate-600 dark:text-slate-300">{order.note}</p></div>}
                    </section>

                    <form onSubmit={updateStatus} className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                        <h2 className="font-bold">Update order status</h2>
                        <select value={data.status} onChange={event => setData('status', event.target.value)} className="mt-4 w-full rounded-xl border-slate-300 dark:bg-slate-800">
                            {statuses.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                        </select>
                        {errors.status && <p className="mt-2 text-sm text-rose-600">{errors.status}</p>}
                        {recentlySuccessful && <p className="mt-2 text-sm font-semibold text-emerald-600">Order status updated.</p>}
                        <button disabled={processing} className="mt-4 h-11 w-full rounded-xl bg-violet-600 font-bold text-white disabled:opacity-60">{processing ? 'Saving...' : 'Save status'}</button>
                    </form>

                    <section className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                        <h2 className="flex items-center gap-2 font-bold"><CircleDollarSign className="size-5 text-violet-600" />Payment</h2>
                        <dl className="mt-3 space-y-2 text-sm"><div className="flex justify-between"><dt className="text-slate-500">Method</dt><dd className="font-semibold uppercase">{order.paymentMethod}</dd></div><div className="flex justify-between"><dt className="text-slate-500">Status</dt><dd className="font-semibold capitalize">{order.paymentStatus}</dd></div></dl>
                    </section>
                </aside>
            </div>
        </main>
    </AdminLayout>;
}