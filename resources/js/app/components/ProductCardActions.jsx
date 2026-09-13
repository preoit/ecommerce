import { Link, router } from '@inertiajs/react';
import { Eye, ShoppingBag } from 'lucide-react';
import { useRef, useState } from 'react';

export default function ProductCardActions({ product }) {
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState('');
    const locked = useRef(false);
    const href = route('storefront.products.show', product.slug);
    const orderNow = async () => {
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
            router.visit(route('storefront.checkout'));
        } catch (error) { setMessage(error.message || 'Connection failed. Please try again.'); }
        finally { locked.current = false; setBusy(false); }
    };
    return <div className="product-card-actions mt-4">
        <div className="product-card-action-slot">
            <Link href={href} className="product-card-view"><Eye size={15}/>View details</Link>
            <button type="button" disabled={busy} onClick={orderNow} className="product-card-buy"><ShoppingBag size={15}/><span>{busy ? 'Please wait…' : 'Order Now'}</span></button>
        </div>
        {message && <p role="status" className="mt-2 text-xs leading-5 text-violet-700 dark:text-violet-300">{message}</p>}
    </div>;
}
