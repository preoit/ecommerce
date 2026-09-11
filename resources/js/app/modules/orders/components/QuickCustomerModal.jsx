import { MapPin, Save, UserRound, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const fieldClass = 'mt-2 h-11 w-full rounded-xl border-slate-300 bg-slate-50 text-sm focus:border-violet-500 focus:bg-white focus:ring-violet-500';
const empty = { name: '', phone: '', email: '', city: 'Dhaka', address_label: 'Home', address: '' };

export default function QuickCustomerModal({ open, customer = null, onClose, onSaved }) {
    const [data, setData] = useState(empty), [errors, setErrors] = useState({}), [saving, setSaving] = useState(false);
    useEffect(() => {
        if (!open) return undefined;
        setData(customer ? { name: customer.name||'', phone: customer.phone||'', email: customer.email||'', city: customer.city||'Dhaka', address_label: customer.addressLabel||'Home', address: customer.address||'' } : empty); setErrors({});
        const close = event => event.key === 'Escape' && onClose(); document.addEventListener('keydown', close); return () => document.removeEventListener('keydown', close);
    }, [open, customer, onClose]);
    if (!open) return null;
    const set = (key, value) => setData(current => ({ ...current, [key]: value }));
    const submit = async event => {
        event.preventDefault(); setSaving(true); setErrors({});
        try {
            const response = await fetch(customer ? route('orders.customers.update', customer.id) : route('orders.customers.store'), { method: customer ? 'PATCH' : 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: JSON.stringify(data) });
            const result = await response.json(); if (!response.ok) { setErrors(result.errors || { form: result.message || 'Customer could not be saved.' }); return; } onSaved(result.customer); onClose();
        } catch { setErrors({ form: 'Customer could not be saved. Please try again.' }); } finally { setSaving(false); }
    };
    return <div className="fixed inset-0 z-[80] grid place-items-center bg-slate-950/45 p-4 backdrop-blur-sm" onMouseDown={event=>event.target===event.currentTarget&&onClose()}><section role="dialog" aria-modal="true" aria-labelledby="quick-customer-title" className="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
        <header className="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white/95 px-6 py-5 backdrop-blur"><div className="flex items-center gap-3"><span className="grid size-11 place-items-center rounded-2xl bg-violet-100 text-violet-700"><UserRound className="size-5"/></span><div><h2 id="quick-customer-title" className="text-lg font-black text-slate-950">{customer?'Edit customer':'Add new customer'}</h2><p className="text-xs text-slate-500">Save customer and delivery address details.</p></div></div><button type="button" onClick={onClose} className="grid size-10 place-items-center rounded-xl text-slate-500 hover:bg-slate-100" aria-label="Close"><X className="size-5"/></button></header>
        <form onSubmit={submit} className="p-6"><div className="grid gap-4 sm:grid-cols-2"><Field label="Customer name" value={data.name} error={errors.name} onChange={value=>set('name',value)} required/><Field label="Phone number" value={data.phone} error={errors.phone} onChange={value=>set('phone',value)} required type="tel"/><Field label="Email (optional)" value={data.email} error={errors.email} onChange={value=>set('email',value)} type="email"/><Field label="City" value={data.city} error={errors.city} onChange={value=>set('city',value)} required/><Field label="Address name" value={data.address_label} error={errors.address_label} onChange={value=>set('address_label',value)} required placeholder="Home, Office…"/><label className="text-sm font-bold sm:col-span-2"><span className="flex items-center gap-2"><MapPin className="size-4 text-violet-500"/>Full delivery address</span><textarea rows="3" required value={data.address} onChange={event=>set('address',event.target.value)} className="mt-2 w-full rounded-xl border-slate-300 bg-slate-50 text-sm focus:border-violet-500 focus:bg-white focus:ring-violet-500"/>{errors.address&&<small className="mt-1 block text-rose-600">{errors.address}</small>}</label></div>{errors.form&&<p className="mt-4 text-sm font-semibold text-rose-600">{errors.form}</p>}<footer className="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5"><button type="button" onClick={onClose} className="h-11 rounded-xl border border-slate-200 px-5 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</button><button disabled={saving} className="inline-flex h-11 items-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-bold text-white hover:bg-violet-700 disabled:opacity-50"><Save className="size-4"/>{saving?'Saving…':customer?'Update customer':'Save customer'}</button></footer></form>
    </section></div>;
}

function Field({ label, value, onChange, error, ...props }) { return <label className="text-sm font-bold">{label}<input {...props} value={value} onChange={event=>onChange(event.target.value)} className={fieldClass}/>{error&&<small className="mt-1 block text-rose-600">{error}</small>}</label>; }
