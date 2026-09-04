import { CheckCircle2, Download, Plus } from 'lucide-react';
import Seo from '@/app/components/Seo';
import Button from '@/app/design-system/components/Button';
import PageHeader from '@/app/design-system/components/PageHeader';
import AdminLayout from '@/app/layouts/AdminLayout';

const actionIcons = { plus: Plus, download: Download };

export default function ModuleOverview({ eyebrow, title, description, actions, features }) {
    const actionButtons = actions.map((action) => <Button key={action.label} href={action.href} icon={actionIcons[action.icon]}>{action.label}</Button>);

    return (
        <AdminLayout>
            <Seo title={title} description={description} />
            <PageHeader eyebrow={eyebrow} title={title} description={description} actions={actionButtons} />
            <div className="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <section className="min-h-80 rounded-lg border border-dashed border-slate-300 bg-white p-6 sm:p-10">
                    <div className="mx-auto flex max-w-md flex-col items-center justify-center py-12 text-center">
                        <span className="flex size-12 items-center justify-center rounded-md bg-brand-50 text-brand-700"><CheckCircle2 className="size-6" /></span>
                        <h2 className="mt-4 text-lg font-bold text-slate-950">{title} module is ready</h2>
                        <p className="mt-2 text-sm leading-6 text-slate-600">The route, service layer and responsive page boundary are in place. CRUD workflows can now be added without changing the application architecture.</p>
                    </div>
                </section>
                <aside>
                    <h2 className="text-sm font-bold uppercase text-slate-700">Module scope</h2>
                    <ul className="mt-3 divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white px-4 shadow-sm">{features.map((feature) => <li key={feature} className="flex items-center gap-3 py-3 text-sm font-medium text-slate-700"><CheckCircle2 className="size-4 shrink-0 text-brand-600" />{feature}</li>)}</ul>
                </aside>
            </div>
        </AdminLayout>
    );
}
