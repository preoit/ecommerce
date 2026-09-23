import { Head, Link, useForm } from '@inertiajs/react';
import { Check, ChevronLeft, ShieldCheck } from 'lucide-react';
import AdminLayout from '@/app/layouts/AdminLayout';
import { ErrorText, input, panel } from '@/app/modules/access/components';

export default function RoleForm({ role, permissionGroups }) {
    const editing = Boolean(role);
    const { data, setData, post, put, processing, errors } = useForm({ name: role?.name || '', description: role?.description || '', permissions: role?.permissions || [] });
    const allIds = permissionGroups.flatMap(group => group.permissions.map(permission => permission.id)).filter(Boolean);
    const selected = (id) => data.permissions.includes(id);
    const toggle = (id) => setData('permissions', selected(id) ? data.permissions.filter(value => value !== id) : [...data.permissions, id]);
    const toggleGroup = (ids) => {
        const valid = ids.filter(Boolean), allSelected = valid.every(selected);
        setData('permissions', allSelected ? data.permissions.filter(id => !valid.includes(id)) : [...new Set([...data.permissions, ...valid])]);
    };
    const submit = event => { event.preventDefault(); editing ? put(route('roles.update', role.id)) : post(route('roles.store')); };

    return <AdminLayout><Head title={editing ? `Edit ${role.name}` : 'Create Role'} /><form onSubmit={submit} className="space-y-6">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><Link href={route('roles.index')} className="inline-flex items-center gap-1 text-sm font-bold text-violet-600"><ChevronLeft className="size-4" />Roles & Permissions</Link><h1 className="mt-2 text-3xl font-black text-slate-950 dark:text-white">{editing ? 'Edit role' : 'Create role'}</h1><p className="mt-2 text-sm text-slate-500">Choose the features and actions this role can use.</p></div><button disabled={processing} className="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-violet-600 px-5 text-sm font-bold text-white hover:bg-violet-700 disabled:opacity-50"><Check className="size-4" />{processing ? 'Saving...' : 'Save role'}</button></div>
        <section className={`${panel} grid gap-5 p-5 lg:grid-cols-2`}><label className="text-sm font-bold text-slate-700 dark:text-slate-200">Role name<input value={data.name} onChange={event => setData('name', event.target.value)} className={input} placeholder="Example: Inventory Manager" required /><ErrorText>{errors.name}</ErrorText></label><label className="text-sm font-bold text-slate-700 dark:text-slate-200">Description<textarea value={data.description} onChange={event => setData('description', event.target.value)} className={`${input} min-h-24 py-3`} placeholder="Describe this role's responsibility" /><ErrorText>{errors.description}</ErrorText></label></section>
        <section className={`${panel} overflow-hidden`}><div className="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700"><div><h2 className="flex items-center gap-2 text-lg font-black text-slate-900 dark:text-white"><ShieldCheck className="size-5 text-violet-600" />Feature permissions</h2><p className="mt-1 text-sm text-slate-500">{data.permissions.length} of {allIds.length} permissions selected</p></div><button type="button" onClick={() => toggleGroup(allIds)} className="h-10 rounded-lg border border-violet-300 px-4 text-sm font-bold text-violet-700 hover:bg-violet-50">{allIds.every(selected) ? 'Clear all' : 'Select all'}</button></div><ErrorText>{errors.permissions}</ErrorText>
            <div className="grid gap-px bg-slate-200 dark:bg-slate-700 md:grid-cols-2">{permissionGroups.map(group => { const ids = group.permissions.map(permission => permission.id).filter(Boolean); return <div key={group.module} className="bg-white p-5 dark:bg-slate-900"><div className="mb-4 flex items-center justify-between"><h3 className="font-black text-slate-900 dark:text-white">{group.label}</h3><button type="button" onClick={() => toggleGroup(ids)} className="text-xs font-bold text-violet-600 hover:underline">{ids.every(selected) ? 'Clear' : 'Select all'}</button></div><div className="grid grid-cols-2 gap-2">{group.permissions.map(permission => <label key={permission.name} className={`flex cursor-pointer items-center gap-2 rounded-lg border p-3 text-sm font-semibold transition ${selected(permission.id) ? 'border-violet-300 bg-violet-50 text-violet-700 dark:bg-violet-950/30' : 'border-slate-200 text-slate-600 hover:border-violet-200 dark:border-slate-700 dark:text-slate-300'}`}><input type="checkbox" checked={selected(permission.id)} onChange={() => toggle(permission.id)} className="rounded border-slate-300 text-violet-600 focus:ring-violet-500" />{permission.label}</label>)}</div></div>; })}</div>
        </section>
    </form></AdminLayout>;
}
