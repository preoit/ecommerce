import { Link } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import ProductCardActions from './ProductCardActions';
import ProductCardMedia from './ProductCardMedia';

const money = (value) => `৳${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;

export default function ListingProductCard({ product, viewMode = 'grid', fallbackBrand = 'Product', showStock = false, onRemove }) {
    const list = viewMode === 'list';
    const href = route('storefront.products.show', product.slug);
    const details = <Link href={href} className={`block min-w-0 p-3 ${list ? 'self-center' : 'flex-1'}`}>
        <p className="truncate text-[13px] font-semibold text-slate-500 sm:text-sm">{product.brand || fallbackBrand}</p>
        <h3 className="mt-1 line-clamp-2 min-h-12 text-[15px] font-medium leading-6 text-slate-900 transition group-hover:text-violet-700 sm:text-base">{product.title}</h3>
        <div className="mt-2 flex flex-wrap items-center gap-2">
            <b className="text-[17px] text-violet-700 sm:text-lg">{money(product.price)}</b>
            {product.discount > 0 && <span className="text-[13px] text-slate-400 line-through">{money(product.regularPrice)}</span>}
        </div>
        {showStock && <span className={`mt-2 inline-block text-[13px] font-semibold ${product.stockStatus === 'Out of Stock' ? 'text-rose-600' : 'text-emerald-600'}`}>{product.stockStatus}</span>}
    </Link>;

    return <article className={`group relative min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-violet-200 hover:shadow-lg ${list ? 'grid grid-cols-[112px_minmax(0,1fr)] sm:grid-cols-[170px_minmax(0,1fr)_210px] sm:items-center' : 'flex flex-col hover:-translate-y-1'}`}>
        <ProductCardMedia product={product} showActions={!list} showWishlist={!onRemove} imageFit={onRemove ? 'contain' : 'cover'} className={list ? 'rounded-l-xl' : ''}/>
        {onRemove && <button type="button" onClick={() => onRemove(product)} className="absolute right-2 top-2 z-20 grid size-11 place-items-center rounded-full border border-rose-100 bg-white/90 text-rose-600 shadow-sm backdrop-blur-md transition hover:bg-rose-600 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-violet-600" aria-label={`Remove ${product.title} from wishlist`}><Trash2 className="size-4" aria-hidden="true"/></button>}
        {details}
        {list && <div className="col-span-2 px-3 pb-3 sm:col-span-1 sm:p-3"><ProductCardActions product={product}/></div>}
    </article>;
}
