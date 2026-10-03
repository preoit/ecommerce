import { Link, router, usePage } from '@inertiajs/react';
import { Heart, Image as ImageIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import ProductCardActions from './ProductCardActions';

export default function ProductCardMedia({ product, showActions = true, showWishlist = true, imageFit = 'cover', className = '' }) {
    const { auth, storefrontWishlistIds = [] } = usePage().props;
    const [wishlisted, setWishlisted] = useState(storefrontWishlistIds.includes(Number(product.id)));
    const [wishlistBusy, setWishlistBusy] = useState(false);
    const [wishlistError, setWishlistError] = useState('');
    const href = route('storefront.products.show', product.slug);
    const title = product.title || product.name || 'Product';

    useEffect(() => {
        setWishlisted(storefrontWishlistIds.includes(Number(product.id)));
    }, [storefrontWishlistIds, product.id]);

    const toggleWishlist = async () => {
        if (!auth?.user) {
            router.visit(route('customer.login'));
            return;
        }
        if (wishlistBusy) return;
        setWishlistBusy(true);
        setWishlistError('');
        try {
            const response = await fetch(route('storefront.products.wishlist', product.id), {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Could not update wishlist.');
            setWishlisted(Boolean(result.active));
        } catch (issue) {
            setWishlistError(issue.message || 'Could not update wishlist.');
        } finally {
            setWishlistBusy(false);
        }
    };

    return <div className={`product-card-media ${className}`}>
        <Link href={href} className="product-card-media-link" aria-label={`View ${title} details`}>
            {product.image
                ? <img src={product.image} alt={title} loading="lazy" width="520" height="520" className={`product-card-media-image ${imageFit === 'contain' ? 'object-contain' : 'object-cover'}`}/>
                : <span className="grid h-full place-items-center text-slate-300"><ImageIcon className="size-10" strokeWidth={1.4}/></span>}
        </Link>
        <div className="product-card-badges">
            {product.discount > 0 && <span className="product-card-discount">-{product.discount}%</span>}
            {product.isNewArrival && <span className="product-card-new">New</span>}
        </div>
        {showWishlist && <button type="button" onClick={toggleWishlist} disabled={wishlistBusy} aria-label={`${wishlisted ? 'Remove' : 'Add'} ${title} ${wishlisted ? 'from' : 'to'} wishlist`} aria-pressed={wishlisted} className={`product-card-wishlist ${wishlisted ? 'product-card-wishlist--active' : ''}`}>
            <Heart size={18} strokeWidth={1.8} className={wishlisted ? 'fill-current' : ''} aria-hidden="true"/>
        </button>}
        {wishlistError && <p role="alert" className="product-card-wishlist-error">{wishlistError}</p>}
        {showActions && <ProductCardActions product={product} overlay/>}
    </div>;
}
