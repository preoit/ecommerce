import { Link, router } from '@inertiajs/react';
import { Eye, ShoppingCart } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export default function ProductCardActions({ product, overlay = false }) {
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState(false);
    const locked = useRef(false);
    const href = route('storefront.products.show', product.slug);

    useEffect(() => {
        if (!message) return undefined;
        const timer = window.setTimeout(() => setMessage(''), 4000);
        return () => window.clearTimeout(timer);
    }, [message]);

    const addToCart = async () => {
        if (locked.current) return;
        locked.current = true;
        setBusy(true);
        setMessage('');
        setError(false);
        try {
            const response = await fetch(route('storefront.products.cart', product.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ quantity: Number(product.minQuantity || 1), increment: true }),
            });
            const result = await response.json();
            if (!response.ok) {
                if (result.message === 'Please select a product option.') {
                    router.visit(href);
                    return;
                }
                throw new Error(result.message || 'Could not add this product. Please try again.');
            }
            window.dispatchEvent(new CustomEvent('cart:updated', { detail: { count: result.count } }));
            setMessage(result.message || 'Added to cart.');
        } catch (issue) {
            setError(true);
            setMessage(issue.message || 'Connection failed. Please try again.');
        } finally {
            locked.current = false;
            setBusy(false);
        }
    };

    return <div className={`product-card-actions ${overlay ? 'product-card-actions--overlay' : 'product-card-actions--inline'}`}>
        <div className="product-card-action-slot">
            <button type="button" disabled={busy} onClick={addToCart} className="product-card-buy">
                <ShoppingCart size={16} strokeWidth={1.8} aria-hidden="true"/>
                <span>{busy ? 'Adding…' : 'Add to Cart'}</span>
            </button>
            <Link href={href} className="product-card-view" aria-label={`View ${product.title || product.name || 'product'} details`} title="View details">
                <Eye size={18} strokeWidth={1.8} aria-hidden="true"/>
            </Link>
        </div>
        {message && <p role={error ? 'alert' : 'status'} className={`product-card-message ${error ? 'product-card-message--error' : ''}`}>{message}</p>}
    </div>;
}
