import { router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import Seo from '@/app/components/Seo';
import Button from '@/app/design-system/components/Button';
import AdminLayout from '@/app/layouts/AdminLayout';
import CategoryFormModal from '../components/CategoryFormModal';
import CategoryTable from '../components/CategoryTable';
import CategoryViewModal from '../components/CategoryViewModal';

export default function CategoryIndex({ categories, parentOptions, filters, resource = 'categories', entityLabel = 'category' }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [viewingCategory, setViewingCategory] = useState(null);
    const [search, setSearch] = useState(filters.search || '');

    const submitSearch = (event) => {
        event.preventDefault();
        router.get(route(`inventories.${resource}.index`), search ? { search } : {}, { preserveState: true, replace: true });
    };

    const openCreateModal = () => {
        setEditingCategory(null);
        setModalOpen(true);
    };

    const openEditModal = (category) => {
        setEditingCategory(category);
        setModalOpen(true);
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
                <Button icon={Plus} onClick={openCreateModal} className="!border-violet-600 !bg-violet-600 hover:!bg-violet-700 focus-visible:!ring-violet-500">Add new {entityLabel}</Button>
            </div>
            <CategoryTable categories={categories} entityLabel={entityLabel} search={search} onSearchChange={setSearch} onSearchSubmit={submitSearch} onView={setViewingCategory} onEdit={openEditModal} onDelete={deleteCategory} />
            {modalOpen && <CategoryFormModal key={editingCategory?.id || 'new'} open onClose={() => setModalOpen(false)} parentOptions={parentOptions} category={editingCategory} resource={resource} entityLabel={entityLabel} />}
            <CategoryViewModal category={viewingCategory} entityLabel={entityLabel} onClose={() => setViewingCategory(null)} />
        </AdminLayout>
    );
}
