import { Head, Link, router, usePage } from '@inertiajs/react';
import { KeyRound, Plus, ShieldCheck, Users } from 'lucide-react';
import AdminLayout from '@/app/layouts/AdminLayout';
import { panel, TableActions } from '@/app/modules/access/components';

const allowed = (permissions, name) => permissions.includes('*') || permissions.includes(name);

export default function RoleIndex({ roles }) {
    const permissions = usePage().props.auth.permissions || [];
    const remove = (role) => window.confirm(`Delete role "${role.name}"?`) && router.delete(route('roles.destroy', role.id), { preserveScroll: true });

    return <AdminLayout><Head title="Roles & Permissions" /><div className="space-y-6">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p className="text-sm font-bold text-violet-600">Access control</p><h1 className="mt-1 text-3xl font-black text-slate-950 dark:text-white">Roles & Permissions</h1><p className="mt-2 text-sm text-slate-500">Control exactly which features and actions each administrator can access.</p></div>
            {allowed(permissions, 'roles.create') && <Link href={route('roles.create')} className="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-violet-600 px-4 text-sm font-bold text-white hover:bg-violet-700"><Plus className="size-4" />Create role</Link>}
        </div>
        <section className={`${panel} overflow-hidden`}>
            <div className="overflow-x-auto"><table className="w-full min-w-[760px]"><thead className="bg-slate-50 dark:bg-slate-800/60"><tr>{['Role', 'Users', 'Permissions', 'Type', 'Actions'].map(item => <th key={item} className="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">{item}</th>)}</tr></thead>
                <tbody className="divide-y divide-slate-200 dark:divide-slate-700">{roles.map(role => <tr key={role.id} className="hover:bg-violet-50/40 dark:hover:bg-slate-800/50"><td className="px-5 py-4"><Link href={route('roles.show', role.id)} className="font-bold text-slate-900 hover:text-violet-700 dark:text-white">{role.name}</Link><p className="mt-1 max-w-md text-xs text-slate-500">{role.description || 'No description provided.'}</p></td><td className="px-5 py-4"><span className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200"><Users className="size-4 text-slate-400" />{role.users}</span></td><td className="px-5 py-4"><span className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200"><KeyRound className="size-4 text-slate-400" />{role.permissions}</span></td><td className="px-5 py-4">{role.system ? <span className="inline-flex items-center gap-1 rounded-full bg-violet-100 px-2.5 py-1 text-xs font-bold text-violet-700"><ShieldCheck className="size-3.5" />System</span> : <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Custom</span>}</td><td className="px-5 py-4"><TableActions view={route('roles.show', role.id)} edit={route('roles.edit', role.id)} canEdit={allowed(permissions, 'roles.update') && !role.system} canDelete={allowed(permissions, 'roles.delete') && !role.system && role.users === 0} onDelete={() => remove(role)} /></td></tr>)}</tbody>
            </table></div>
            {!roles.length && <p className="py-16 text-center text-sm text-slate-500">No roles found.</p>}
        </section>
    </div></AdminLayout>;
}
