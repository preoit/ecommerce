import CategoryForm from '../components/CategoryFormModal';

export default function CategoryFormPage({ category = null, parentOptions = [], resource = 'categories', entityLabel = 'category' }) {
    return <CategoryForm page open category={category} parentOptions={parentOptions} resource={resource} entityLabel={entityLabel} />;
}
