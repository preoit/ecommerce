import { Link, router } from '@inertiajs/react';
import { Eye, ShoppingBag, ShoppingCart } from 'lucide-react';
import { useRef, useState } from 'react';

export default function ProductCardActions({ product }) {
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState('');
    const locked = useRef(false);
    const href = route('storefront.products.show', product.slug);
    const add = async (checkout) => {
        if (locked.current) return;
        locked.current = true;
        setBusy(true);
        setMessage('');
        try {
            const response = await fetch(route('storefront.products.cart', product.id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ quantity: Number(product.minQuantity || 1), increment: true }),
            });
            const result = await response.json();
            if (!response.ok) {
                if (result.message === 'Please select a product option.') { router.visit(href); return; }
                throw new Error(result.message || 'Could not add this product. Please try again.');
            }
            window.dispatchEvent(new CustomEvent('cart:updated', { detail: { count: result.count } }));
            if (checkout) router.visit(route('storefront.checkout'));
            else setMessage('Added to cart');
        } catch (error) { setMessage(error.message || 'Connection failed. Please try again.'); }
        finally { locked.current = false; setBusy(false); }
    };
    return <div className="product-card-actions mt-4">
        <div className="product-card-action-slot">
            <Link href={href} className="product-card-view"><Eye size={15}/>View details</Link>
            <div className="product-card-purchase">
                <button type="button" disabled={busy} onClick={() => add(true)} className="product-card-buy"><ShoppingBag size={15}/><span>Buy Now</span></button>
                <button type="button" disabled={busy} onClick={() => add(false)} className="product-card-add"><ShoppingCart size={15}/><span>{busy ? 'Adding…' : 'Add to Cart'}</span></button>
            </div>
        </div>
        {message && <p role="status" className="mt-2 text-xs leading-5 text-violet-700 dark:text-violet-300">{message}</p>}
    </div>;
}
