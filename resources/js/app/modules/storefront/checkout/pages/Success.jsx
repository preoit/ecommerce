import { Head, Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import CheckoutLayout from '@/app/layouts/CheckoutLayout';

export default function OrderSuccess({ order }) {
    return <CheckoutLayout><Head title="Order confirmed" /><section className="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-8 text-center"><CheckCircle2 className="mx-auto size-14 text-emerald-500" /><h1 className="mt-5 text-3xl font-bold">Order confirmed</h1><p className="mt-3 text-slate-600">Thank you, {order.customer_name}. Your order <b>{order.order_number}</b> has been placed successfully.</p><p className="mt-2 text-sm text-slate-500">We will contact you at {order.phone} to confirm delivery.</p><Link href="/" className="mt-7 inline-flex rounded-xl bg-violet-600 px-5 py-3 font-semibold text-white">Continue shopping</Link></section></CheckoutLayout>;
}
