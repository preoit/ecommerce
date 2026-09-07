import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, CircleDollarSign, MapPin, Package, Phone, Mail, TriangleAlert } from 'lucide-react';
import AdminLayout from '@/app/layouts/AdminLayout';

const money = value => `৳${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;
const orderSteps = [
    ['pending', 'Pending'],
    ['confirmed', 'Confirmed'],
    ['processing', 'Processing'],
    ['ready_to_ship', 'Ready To Ship'],
    ['shipped', 'Shipped'],
    ['completed', 'Completed'],
];
const exceptionStatuses = [['cancelled', 'Cancelled'], ['returned', 'Returned']];

export default function OrderShow({ order }) {
    const { data, setData, patch, processing, recentlySuccessful, errors } = useForm({ status: order.statusKey });
    const selectedStep = orderSteps.findIndex(([value]) => value === data.status);
    const currentStep = orderSteps.findIndex(([value]) => value === order.statusKey);
    const isException = exceptionStatuses.some(([value]) => value === data.status);
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
                        <p className="mt-1 text-xs text-slate-500">Select the next stage, then save the change.</p>
                        <ol className="mt-5">
                            {orderSteps.map(([value, label], index) => {
                                const reached = !isException && index <= selectedStep;
                                const saved = currentStep >= 0 && index <= currentStep;
                                const selected = data.status === value;
                                return <li key={value} className="relative flex min-h-16 gap-3 last:min-h-0">
                                    {index < orderSteps.length - 1 && <span aria-hidden="true" className={reached ? 'absolute left-[15px] top-8 h-[calc(100%-1rem)] w-0.5 bg-violet-500' : 'absolute left-[15px] top-8 h-[calc(100%-1rem)] w-0.5 bg-slate-200 dark:bg-slate-700'} />}
                                    <button type="button" onClick={() => setData('status', value)} aria-current={selected ? 'step' : undefined} className={selected || reached ? 'relative z-10 grid size-8 shrink-0 place-items-center rounded-full bg-violet-600 text-white ring-4 ring-violet-100' : 'relative z-10 grid size-8 shrink-0 place-items-center rounded-full border-2 border-slate-300 bg-white text-xs font-bold text-slate-500 dark:bg-slate-900'}>
                                        {reached ? <Check className="size-4" /> : index + 1}
                                    </button>
                                    <button type="button" onClick={() => setData('status', value)} className="min-w-0 pb-5 text-left">
                                        <span className={selected ? 'block text-sm font-bold text-violet-700' : 'block text-sm font-semibold text-slate-700 dark:text-slate-200'}>{label}</span>
                                        <span className="mt-0.5 block text-xs text-slate-400">{saved ? 'Completed' : selected ? 'Selected' : 'Not completed'}</span>
                                    </button>
                                </li>;
                            })}
                        </ol>
                        <div className="mt-5 border-t border-slate-200 pt-4 dark:border-slate-700">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Exception status</p>
                            <div className="mt-2 grid grid-cols-2 gap-2">{exceptionStatuses.map(([value, label]) => <button type="button" key={value} onClick={() => setData('status', value)} className={data.status === value ? 'rounded-lg border border-rose-500 bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700' : 'rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-500 hover:border-rose-300 hover:text-rose-600'}>{label}</button>)}</div>
                        </div>
                        {errors.status && <p className="mt-3 text-sm text-rose-600">{errors.status}</p>}
                        {recentlySuccessful && <p className="mt-3 text-sm font-semibold text-emerald-600">Order status updated.</p>}
                        <button disabled={processing || data.status === order.statusKey} className="mt-4 h-11 w-full rounded-xl bg-violet-600 font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">{processing ? 'Saving...' : 'Save status'}</button>
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