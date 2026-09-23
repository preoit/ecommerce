import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle, Truck } from 'lucide-react';
import { useState } from 'react';
import AdminLayout from '@/app/layouts/AdminLayout';
import { api, Field, button, panel } from '../ui';

const credentialLabels = {
    api_key: 'API Key',
    token: 'API Token',
    secret_key: 'Secret Key',
    client_id: 'Client ID',
    client_secret: 'Client Secret',
    merchant_id: 'Merchant ID',
    store_id: 'Store ID',
};

const labelFor = (key) => credentialLabels[key] || key.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
const titleCase = (value) => value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

function CourierMark({ courier }) {
    if (courier.logo) return <img src={courier.logo} alt={`${courier.name} logo`} className="size-12 rounded-xl border border-slate-200 bg-white object-contain p-1.5" />;
    return <span className="grid size-12 place-items-center rounded-xl bg-violet-100 font-bold text-violet-700 dark:bg-violet-950 dark:text-violet-200" aria-hidden="true">{courier.name?.charAt(0) || <Truck className="size-5" />}</span>;
}

function CourierCard({ courier, statuses }) {
    const emptyCredentials = Object.fromEntries(courier.fields.map((key) => [key, '']));
    const form = useForm({ active: courier.active, sandbox_mode: courier.sandbox_mode, api_url: courier.api_url, credentials: emptyCredentials, status_mapping: courier.status_mapping || {} });
    const [message, setMessage] = useState(null);
    const [testing, setTesting] = useState(false);
    const statusLabel = form.data.active ? 'Active' : 'Inactive';

    const toggleActive = () => {
        if (form.data.active && !window.confirm(`Are you sure you want to deactivate ${courier.name}?`)) return;
        form.setData('active', !form.data.active);
        setMessage(null);
    };

    const testConnection = async () => {
        setTesting(true);
        setMessage(null);
        try {
            const result = await api(route('couriers.test', courier.id), {});
            setMessage({ type: 'success', text: result.message });
        } catch (error) {
            setMessage({ type: 'error', text: error.message });
        } finally {
            setTesting(false);
        }
    };

    return <form className={panel} onSubmit={(event) => {
        event.preventDefault();
        form.patch(route('couriers.save', courier.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.setData('credentials', emptyCredentials);
                setMessage({ type: 'success', text: 'Courier settings updated successfully.' });
            },
        });
    }}>
        <header className="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div className="flex min-w-0 items-center gap-3"><CourierMark courier={courier} /><div className="min-w-0"><h2 className="truncate text-lg font-semibold">{courier.name}</h2><div className="mt-1 flex flex-wrap items-center gap-2"><span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ${form.data.active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300'}`}><span className={`size-1.5 rounded-full ${form.data.active ? 'bg-emerald-500' : 'bg-slate-400'}`} />{statusLabel}</span><span className="text-xs text-slate-500">{courier.tested_at ? 'Connection tested' : 'Not tested yet'}</span></div></div></div>
            <button type="button" role="switch" aria-checked={form.data.active} aria-label={`${statusLabel} ${courier.name}`} onClick={toggleActive} className="inline-flex items-center gap-2 rounded-lg px-1 py-1 text-sm font-medium text-slate-600 dark:text-slate-300"><span className={`relative h-6 w-11 rounded-full transition-colors ${form.data.active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'}`}><span className={`absolute top-0.5 size-5 rounded-full bg-white shadow-sm transition-transform ${form.data.active ? 'translate-x-5' : 'translate-x-0.5'}`} /></span>{statusLabel}</button>
        </header>

        <div className="grid gap-4 sm:grid-cols-2">
            {courier.fields.map((key) => <Field key={key} label={labelFor(key)}><input type="password" autoComplete="new-password" value={form.data.credentials[key]} placeholder={courier.configured.includes(key) ? 'Configured — leave blank to retain' : 'Not configured'} onChange={(event) => form.setData('credentials', { ...form.data.credentials, [key]: event.target.value })} /></Field>)}
            <Field label="Mode"><select value={form.data.sandbox_mode ? 'sandbox' : 'live'} onChange={(event) => { const sandbox = event.target.value === 'sandbox'; form.setData({ ...form.data, sandbox_mode: sandbox, api_url: sandbox ? courier.sandboxUrl : courier.liveUrl }); }}><option value="live">Live</option>{courier.sandboxUrl && <option value="sandbox">Sandbox</option>}</select></Field>
            <Field label="API URL"><input type="url" value={form.data.api_url} onChange={(event) => form.setData('api_url', event.target.value)} /></Field>
        </div>
        {!courier.sandboxUrl && <p className="mt-3 text-xs text-slate-500">Sandbox mode is not available for this provider.</p>}

        <details className="mt-5 rounded-lg border border-slate-200 p-4 dark:border-slate-700"><summary className="cursor-pointer font-medium">Courier → order status mapping</summary><p className="my-3 text-xs text-slate-500">Leave a status unmapped to review it manually. Delivery status never changes payment status.</p><div className="grid gap-3 sm:grid-cols-2">{statuses.map((status) => <Field label={titleCase(status)} key={status}><select value={form.data.status_mapping[status] || ''} onChange={(event) => form.setData('status_mapping', { ...form.data.status_mapping, [status]: event.target.value })}><option value="">Do not change order</option>{['confirmed', 'processing', 'ready_to_ship', 'shipped', 'completed', 'returned', 'cancelled'].map((orderStatus) => <option key={orderStatus} value={orderStatus}>{titleCase(orderStatus)}</option>)}</select></Field>)}</div></details>

        {Object.values(form.errors).map((error, index) => <p key={index} role="alert" className="mt-2 text-sm text-rose-600">{error}</p>)}
        {message && <p role={message.type === 'error' ? 'alert' : 'status'} className={`mt-4 flex items-center gap-2 rounded-lg px-3 py-2 text-sm ${message.type === 'error' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'}`}>{message.type === 'success' && <CheckCircle2 className="size-4" />}{message.text}</p>}
        <div className="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap"><button disabled={form.processing} className={`${button} justify-center`}>{form.processing ? 'Saving…' : 'Save Settings'}</button><button type="button" disabled={testing || form.processing} className="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:hover:bg-slate-800" onClick={testConnection}>{testing && <LoaderCircle className="size-4 animate-spin" />}{testing ? 'Testing…' : 'Test saved connection'}</button></div>
    </form>;
}

export default function Settings({ couriers, statuses }) {
    return <AdminLayout><Head title="Courier Integration" /><div className="space-y-6"><header><p className="text-sm font-medium text-violet-600">Settings</p><h1 className="mt-1">Courier Integration</h1><p className="mt-2 max-w-2xl text-sm text-slate-500">Configure provider credentials, connection mode, and order status mapping. Credentials remain encrypted and are never returned to the browser.</p><Link className="mt-3 inline-flex text-sm font-semibold text-violet-600" href={route('couriers.index')}>View courier dashboard →</Link></header><div className="grid items-start gap-5 xl:grid-cols-2">{couriers.map((courier) => <CourierCard key={courier.id} courier={courier} statuses={statuses} />)}</div></div></AdminLayout>;
}
