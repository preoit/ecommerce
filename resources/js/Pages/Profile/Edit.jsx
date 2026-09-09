import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import CustomerShell from '@/app/modules/customers/components/CustomerShell';
import { Head, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

function ProfileContent({ mustVerifyEmail, status }) {
    return <div className="space-y-5">
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <UpdateProfileInformationForm mustVerifyEmail={mustVerifyEmail} status={status} />
        </section>
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <UpdatePasswordForm />
        </section>
        <section className="rounded-2xl border border-rose-100 bg-white p-5 shadow-sm sm:p-7">
            <DeleteUserForm />
        </section>
    </div>;
}

export default function Edit({ mustVerifyEmail, status }) {
    const { auth } = usePage().props;
    const content = <ProfileContent mustVerifyEmail={mustVerifyEmail} status={status} />;
    if (!auth.user.is_admin) return <CustomerShell title="Profile Settings"><Head title="Profile Settings" />{content}</CustomerShell>;
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Profile</h2>}><Head title="Profile" /><div className="mx-auto max-w-4xl py-10 sm:px-6 lg:px-8">{content}</div></AuthenticatedLayout>;
}