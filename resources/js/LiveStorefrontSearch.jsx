import { router } from '@inertiajs/react';
import { LoaderCircle, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

const money = (value) => `৳${Number(value || 0).toLocaleString('en-BD', { maximumFractionDigits: 2 })}`;
const searchFormSelector = 'header[data-storefront-header] form[role="search"]';

export default function LiveStorefrontSearch() {
    const [form, setForm] = useState(null);
    const [query, setQuery] = useState('');
    const [suggestions, setSuggestions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const cache = useRef(new Map());
    const abortController = useRef(null);

    useEffect(() => {
        const locateForm = () => setForm((current) => {
            const next = document.querySelector(searchFormSelector);
            return current === next ? current : next;
        });
        locateForm();
        const observer = new MutationObserver(locateForm);
        observer.observe(document.body, { childList: true, subtree: true });
        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        if (!form) return undefined;
        const input = form.querySelector('input[type="search"]');
        if (!input) return undefined;

        form.classList.add('relative');
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-controls', 'storefront-search-suggestions');
        setQuery(input.value || '');

        const onInput = (event) => {
            setQuery(event.target.value);
            setActiveIndex(-1);
            setOpen(event.target.value.trim().length >= 2);
        };
        const onFocus = () => query.trim().length >= 2 && setOpen(true);
        input.addEventListener('input', onInput);
        input.addEventListener('focus', onFocus);
        return () => {
            input.removeEventListener('input', onInput);
            input.removeEventListener('focus', onFocus);
        };
    }, [form, query]);

    useEffect(() => {
        const term = query.trim();
        abortController.current?.abort();

        if (term.length < 2) {
            setSuggestions([]);
            setLoading(false);
            setOpen(false);
            return undefined;
        }

        const cacheKey = term.toLocaleLowerCase();
        if (cache.current.has(cacheKey)) {
            setSuggestions(cache.current.get(cacheKey));
            setLoading(false);
            return undefined;
        }

        setLoading(true);
        const timer = window.setTimeout(async () => {
            const controller = new AbortController();
            abortController.current = controller;
            try {
                const response = await fetch(`/products/search-suggestions?q=${encodeURIComponent(term)}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('Search request failed');
                const payload = await response.json();
                const results = Array.isArray(payload.suggestions) ? payload.suggestions : [];
                cache.current.set(cacheKey, results);
                setSuggestions(results);
            } catch (error) {
                if (error.name !== 'AbortError') setSuggestions([]);
            } finally {
                if (!controller.signal.aborted) setLoading(false);
            }
        }, 350);

        return () => window.clearTimeout(timer);
    }, [query]);

    useEffect(() => {
        if (!form) return undefined;
        const input = form.querySelector('input[type="search"]');
        const visit = (suggestion) => {
            setOpen(false);
            router.visit(`/${encodeURIComponent(suggestion.slug)}`);
        };
        const onKeyDown = (event) => {
            if (!open || query.trim().length < 2) return;
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                setActiveIndex((current) => Math.min(current + 1, suggestions.length - 1));
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                setActiveIndex((current) => Math.max(current - 1, 0));
            } else if (event.key === 'Enter' && activeIndex >= 0 && suggestions[activeIndex]) {
                event.preventDefault();
                visit(suggestions[activeIndex]);
            } else if (event.key === 'Escape') {
                setOpen(false);
                input?.blur();
            }
        };
        const onOutsideClick = (event) => {
            if (!form.contains(event.target)) setOpen(false);
        };
        input?.addEventListener('keydown', onKeyDown);
        document.addEventListener('pointerdown', onOutsideClick);
        return () => {
            input?.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('pointerdown', onOutsideClick);
        };
    }, [activeIndex, form, open, query, suggestions]);

    if (!form || !open || query.trim().length < 2) return null;

    const openProduct = (suggestion) => {
        setOpen(false);
        router.visit(`/${encodeURIComponent(suggestion.slug)}`);
    };
    const showAll = () => {
        setOpen(false);
        router.get('/products', { search: query.trim() }, { preserveState: false });
    };

    return createPortal(
        <div id="storefront-search-suggestions" role="listbox" className="absolute inset-x-0 top-[calc(100%+0.55rem)] z-[90] overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-[0_22px_55px_rgba(15,23,42,.18)] dark:border-slate-700 dark:bg-slate-900">
            {loading ? <div className="flex items-center justify-center gap-2 px-5 py-8 text-sm font-semibold text-slate-500"><LoaderCircle className="size-5 animate-spin text-violet-600" />Searching products…</div> : suggestions.length ? <div className="max-h-[420px] overflow-y-auto p-2">
                {suggestions.map((suggestion, index) => <button key={suggestion.id} type="button" role="option" aria-selected={activeIndex === index} onMouseEnter={() => setActiveIndex(index)} onClick={() => openProduct(suggestion)} className={`flex w-full items-center gap-3 rounded-xl p-3 text-left transition ${activeIndex === index ? 'bg-violet-50 dark:bg-violet-950/50' : 'hover:bg-slate-50 dark:hover:bg-slate-800'}`}>
                    <span className="grid size-14 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-100 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">{suggestion.image ? <img src={suggestion.image} alt="" className="h-full w-full object-contain p-1" /> : <Search className="size-5 text-slate-300" />}</span>
                    <span className="min-w-0 flex-1"><strong className="line-clamp-1 block text-sm text-slate-900 dark:text-white">{suggestion.title}</strong><span className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">{suggestion.sku && <span>SKU: {suggestion.sku}</span>}{suggestion.brand && <span>{suggestion.brand}</span>}</span></span>
                    <strong className="shrink-0 text-sm text-violet-700 dark:text-violet-300">{money(suggestion.price)}</strong>
                </button>)}
            </div> : <div className="px-5 py-8 text-center"><p className="text-sm font-bold text-slate-800 dark:text-slate-100">No matching products</p><p className="mt-1 text-xs text-slate-500">Try another name, SKU, brand or category.</p></div>}
            {!loading && <button type="button" onClick={showAll} className="flex w-full items-center justify-center gap-2 border-t border-slate-100 bg-slate-50 px-4 py-3 text-sm font-bold text-violet-700 transition hover:bg-violet-50 dark:border-slate-700 dark:bg-slate-800 dark:text-violet-300"><Search className="size-4" />View all results for “{query.trim()}”</button>}
        </div>,
        form,
    );
}
