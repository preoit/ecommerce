import AdminLayout from '@/app/layouts/AdminLayout';

export default function AuthenticatedLayout({ header, children }) {
    return (
        <AdminLayout>
            {header && (
                <div className="mb-6 border-b border-slate-200 pb-5">
                    {header}
                </div>
            )}
            {children}
        </AdminLayout>
    );
}
