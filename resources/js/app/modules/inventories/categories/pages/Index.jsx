import { Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import Seo from '@/app/components/Seo';
import AdminLayout from '@/app/layouts/AdminLayout';
import CategoryTable from '../components/CategoryTable';
import CategoryViewModal from '../components/CategoryViewModal';

export default function CategoryIndex({ categories, filters, resource = 'categories', entityLabel = 'category' }) {
    const [viewingCategory, setViewingCategory] = useState(null);
    const [search, setSearch] = useState(filters.search || '');

    const submitSearch = (event) => {
        event.preventDefault();
        router.get(route(`inventories.${resource}.index`), search ? { search } : {}, { preserveState: true, replace: true });
    };

    const deleteCategory = (category) => {
        if (!window.confirm(`Delete "${category.name}"? This action cannot be undone.`)) return;
        router.delete(route(`inventories.${resource}.destroy`, category.id), { preserveScroll: true });
    };

    return (
        <AdminLayout>
            <Seo title={entityLabel === 'brand' ? 'Brands' : 'Categories'} description={`Organize your product ${entityLabel}s.`} />
            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div><p className="text-sm font-medium text-violet-600">Products</p><h1 className="mt-1 text-2xl font-bold text-slate-950">Product {entityLabel}s</h1></div>
                <Link href={route(`inventories.${resource}.create`)} className="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700"><Plus className="size-4" />Add new {entityLabel}</Link>
            </div>
            <CategoryTable categories={categories} entityLabel={entityLabel} search={search} onSearchChange={setSearch} onSearchSubmit={submitSearch} onView={setViewingCategory} onEdit={(item) => router.get(route(`inventories.${resource}.edit`, item.id))} onDelete={deleteCategory} />
            <CategoryViewModal category={viewingCategory} entityLabel={entityLabel} onClose={() => setViewingCategory(null)} />
        </AdminLayout>
    );
}
