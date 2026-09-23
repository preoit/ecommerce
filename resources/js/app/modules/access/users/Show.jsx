import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Check, Clock3, Mail, Pencil, Phone, ShieldCheck, Trash2, UserRound } from 'lucide-react';
import AdminLayout from '@/app/layouts/AdminLayout';
import { panel, StatusBadge } from '@/app/modules/access/components';

const detailItems = [
    { key: 'email', label: 'Email address', icon: Mail },
    { key: 'phone', label: 'Phone number', icon: Phone },
    { key: 'lastLogin', label: 'Last login', icon: Clock3 },
    { key: 'createdAt', label: 'Account created', icon: CalendarDays },
];

export default function UserShow({ adminUser }) {
    const permissions = usePage().props.auth.permissions || [];
    const can = permission => permissions.includes('*') || permissions.includes(permission);
    const remove = () => window.confirm(`Delete "${adminUser.name}"?`) && router.delete(route('admin-users.destroy', adminUser.id));

    return <AdminLayout><Head title={adminUser.name} /><div className="mx-auto max-w-6xl space-y-6">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><Link href={route('admin-users.index')} className="inline-flex items-center gap-1.5 text-sm font-bold text-violet-600 transition hover:text-violet-800"><ArrowLeft className="size-4" />Back to users</Link><h1 className="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white">User details</h1><p className="mt-2 text-sm text-slate-500">Admin account information, assigned roles and effective access.</p></div>
            <div className="flex flex-wrap gap-2">{can('users.update') && <Link href={route('admin-users.edit', adminUser.id)} className="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-violet-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-violet-700"><Pencil className="size-4" />Edit user</Link>}{can('users.delete') && !adminUser.superAdmin && <button type="button" onClick={remove} className="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-4 text-sm font-bold text-rose-600 transition hover:bg-rose-50"><Trash2 className="size-4" />Delete</button>}</div>
        </div>

        <section className={`${panel} overflow-hidden`}>
            <div className="h-24 bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600" />
            <div className="px-5 pb-6 sm:px-7"><div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div className="flex min-w-0 items-end gap-4"><span className="-mt-10 grid size-20 shrink-0 place-items-center rounded-2xl border-4 border-white bg-violet-100 text-3xl font-black uppercase text-violet-700 shadow-sm dark:border-slate-900">{adminUser.name.charAt(0)}</span><div className="min-w-0 pb-1"><div className="flex flex-wrap items-center gap-2"><h2 className="truncate text-2xl font-black text-slate-950 dark:text-white">{adminUser.name}</h2>{adminUser.superAdmin && <span className="inline-flex items-center gap-1 rounded-full bg-violet-100 px-2.5 py-1 text-xs font-bold text-violet-700"><ShieldCheck className="size-3.5" />Super Admin</span>}</div><p className="mt-1 truncate text-sm text-slate-500">{adminUser.email}</p></div></div>
                <div className="sm:pb-1"><StatusBadge active={adminUser.active} /></div>
            </div></div>
        </section>

        <div className="grid gap-6 lg:grid-cols-[1.05fr_1.45fr]">
            <section className={`${panel} overflow-hidden`}>
                <div className="border-b border-slate-200 px-5 py-4 dark:border-slate-700"><div className="flex items-center gap-3"><span className="grid size-10 place-items-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"><UserRound className="size-5" /></span><div><h2 className="font-black text-slate-900 dark:text-white">Account information</h2><p className="mt-0.5 text-xs text-slate-500">Contact and account activity details</p></div></div></div>
                <dl className="divide-y divide-slate-100 dark:divide-slate-800">{detailItems.map(({ key, label, icon: Icon }) => <div key={key} className="flex items-start gap-3 px-5 py-4"><Icon className="mt-0.5 size-4 shrink-0 text-slate-400" /><div className="min-w-0"><dt className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</dt><dd className="mt-1 break-words text-sm font-bold text-slate-800 dark:text-slate-100">{adminUser[key] || (key === 'lastLogin' ? 'Never signed in' : 'Not provided')}</dd></div></div>)}</dl>
            </section>

            <section className={`${panel} overflow-hidden`}>
                <div className="border-b border-slate-200 px-5 py-4 dark:border-slate-700"><div className="flex items-center gap-3"><span className="grid size-10 place-items-center rounded-xl bg-violet-100 text-violet-700"><ShieldCheck className="size-5" /></span><div><h2 className="font-black text-slate-900 dark:text-white">Roles & permissions</h2><p className="mt-0.5 text-xs text-slate-500">Combined access granted to this account</p></div></div></div>
                <div className="p-5"><h3 className="text-xs font-black uppercase tracking-[.12em] text-slate-400">Assigned roles</h3><div className="mt-3 flex flex-wrap gap-2">{adminUser.roles.length ? adminUser.roles.map(role => <span key={role} className="inline-flex items-center gap-1.5 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-bold text-violet-700"><ShieldCheck className="size-4" />{role}</span>) : <span className="text-sm text-slate-500">No roles assigned</span>}</div>
                    <div className="my-5 border-t border-slate-200 dark:border-slate-700" />
                    <div className="flex items-center justify-between gap-3"><h3 className="text-xs font-black uppercase tracking-[.12em] text-slate-400">Effective permissions</h3><span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{adminUser.superAdmin ? 'Full access' : `${adminUser.permissions.length} granted`}</span></div>
                    {adminUser.superAdmin ? <div className="mt-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4"><span className="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700"><Check className="size-5" /></span><div><p className="text-sm font-black text-emerald-800">All permissions enabled</p><p className="mt-1 text-xs leading-5 text-emerald-700">This Super Admin can access and manage every administration feature.</p></div></div> : <div className="mt-4 grid gap-2 sm:grid-cols-2">{adminUser.permissions.map(item => <div key={item} className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-600 dark:border-slate-700 dark:text-slate-300"><Check className="size-4 shrink-0 text-emerald-500" /><span className="capitalize">{item.replace('.', ' / ').replaceAll('_', ' ')}</span></div>)}</div>}
                </div>
            </section>
        </div>
    </div></AdminLayout>;
}
