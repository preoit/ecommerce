import { Head, Link } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { CalendarDays, Check, ChevronRight, Clock3, Copy, House, Share2, UserRound } from 'lucide-react';
import StorefrontLayout from '@/app/layouts/StorefrontLayout';
import { imageUrl, date } from './shared';

const relatedDate = (value) => value
    ? new Date(value).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
    : '';

const headingSlug = (value) => value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\u0980-\u09ff]+/g, '-')
    .replace(/^-+|-+$/g, '');

function prepareArticleContent(html = '') {
    if (!html || typeof document === 'undefined') return { html, headings: [] };

    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    const usedIds = new Set();
    const headings = [...wrapper.querySelectorAll('h2, h3')].map((heading, index) => {
        const text = heading.textContent?.trim() || `Section ${index + 1}`;
        const baseId = heading.id || headingSlug(text) || `section-${index + 1}`;
        let id = baseId;
        let suffix = 2;
        while (usedIds.has(id)) id = `${baseId}-${suffix++}`;
        usedIds.add(id);
        heading.id = id;
        heading.style.scrollMarginTop = '7rem';
        return { id, text, level: heading.tagName.toLowerCase() };
    });

    return { html: wrapper.innerHTML, headings };
}

function TableOfContents({ headings, activeId, mobile = false }) {
    const links = <nav aria-label="Table of contents" className={mobile ? 'mt-4 border-t border-slate-200 pt-4 dark:border-slate-700' : 'mt-4'}>
        <ol className="space-y-1.5">{headings.map((heading) => <li key={heading.id} className={heading.level === 'h3' ? 'pl-3' : ''}>
            <a href={`#${heading.id}`} className={`block border-l-2 py-1.5 pl-3 text-sm leading-5 transition ${activeId === heading.id
                ? 'border-violet-600 font-semibold text-violet-700 dark:text-violet-300'
                : 'border-transparent text-slate-500 hover:border-violet-200 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
            }`}>{heading.text}</a>
        </li>)}</ol>
    </nav>;

    if (mobile) {
        return <details className="mb-7 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900 xl:hidden">
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-slate-950 dark:text-white">
                <span>Table of Contents</span>
                <span className="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-bold text-violet-700 dark:bg-violet-950 dark:text-violet-300">{headings.length} sections</span>
            </summary>
            {links}
        </details>;
    }

    return <div className="max-h-[calc(100vh-8rem)] overflow-y-auto pr-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <p className="text-xs font-bold uppercase tracking-[0.14em] text-violet-600 dark:text-violet-300">In this article</p>
        <h2 className="mt-1 text-lg font-semibold text-slate-950 dark:text-white">Table of Contents</h2>
        {links}
    </div>;
}

function SocialShare({ title, url }) {
    const [copied, setCopied] = useState(false);
    const encodedUrl = encodeURIComponent(url);
    const encodedText = encodeURIComponent(title);
    const shares = [
        { label: 'Facebook', short: 'f', href: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}` },
        { label: 'X', short: 'X', href: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedText}` },
        { label: 'LinkedIn', short: 'in', href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}` },
        { label: 'WhatsApp', short: 'wa', href: `https://wa.me/?text=${encodedText}%20${encodedUrl}` },
    ];

    const copyLink = async () => {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 1800);
        } catch {
            setCopied(false);
        }
    };

    return <div className="flex flex-wrap items-center gap-2" aria-label="Share this article">
        <span className="mr-1 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400"><Share2 className="size-4" />Share</span>
        {shares.map((share) => <a key={share.label} href={share.href} target="_blank" rel="noopener noreferrer" aria-label={`Share on ${share.label}`} title={`Share on ${share.label}`} className="grid size-9 place-items-center rounded-full border border-slate-200 bg-white text-xs font-extrabold text-slate-600 transition hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-violet-700 dark:hover:bg-violet-950">{share.short}</a>)}
        <button type="button" onClick={copyLink} aria-label="Copy article link" title={copied ? 'Link copied' : 'Copy link'} className={`grid size-9 place-items-center rounded-full border transition focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 ${copied ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300'}`}>
            {copied ? <Check className="size-4" /> : <Copy className="size-4" />}
        </button>
    </div>;
}

function RelatedPost({ post }) {
    const href = route('blog.public.show', post.slug);
    const category = post.categories?.[0];

    return <article className="grid min-w-0 grid-cols-[96px_minmax(0,1fr)] gap-4 border-b border-slate-200 py-6 first:pt-3 last:border-b-0 last:pb-1 dark:border-slate-800 sm:grid-cols-[128px_minmax(0,1fr)] lg:grid-cols-[104px_minmax(0,1fr)]">
        <Link href={href} className="block aspect-square overflow-hidden rounded-2xl bg-slate-100 outline-none ring-violet-500 focus-visible:ring-2 focus-visible:ring-offset-2 dark:bg-slate-800">
            {post.image_path
                ? <img loading="lazy" src={imageUrl(post.image_path)} alt={post.title} className="h-full w-full object-cover transition duration-300 hover:scale-105" />
                : <span className="grid h-full w-full place-items-center bg-gradient-to-br from-violet-50 to-violet-100 text-2xl font-bold text-violet-300 dark:from-slate-800 dark:to-violet-950">iT</span>}
        </Link>
        <div className="flex min-w-0 flex-col items-start justify-center">
            {category && <Link href={`/${category.slug}`} className="mb-2 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 transition hover:bg-violet-100 hover:text-violet-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-violet-950 dark:hover:text-violet-300">{category.name}</Link>}
            <h3 className="text-base font-semibold leading-snug tracking-tight text-slate-950 dark:text-white">
                <Link href={href} className="outline-none transition hover:text-violet-700 focus-visible:text-violet-700 dark:hover:text-violet-300">{post.title}</Link>
            </h3>
            {post.published_at && <time dateTime={post.published_at} className="mt-2 text-xs font-medium text-slate-500 dark:text-slate-400">{relatedDate(post.published_at)}</time>}
        </div>
    </article>;
}

export default function PublicShow({ post, related }) {
    const seo = post.seo || {};
    const origin = window.location.origin;
    const canonical = seo.canonical || route('blog.public.show', post.slug);
    const shareUrl = canonical.startsWith('http') ? canonical : `${origin}${canonical.startsWith('/') ? '' : '/'}${canonical}`;
    const absoluteImage = (path) => path ? `${origin}${imageUrl(path)}` : null;
    const preparedContent = useMemo(() => prepareArticleContent(post.content || ''), [post.content]);
    const [activeHeading, setActiveHeading] = useState(preparedContent.headings[0]?.id || '');
    const wordCount = (post.content || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().split(' ').filter(Boolean).length;
    const readingMinutes = Math.max(1, Math.ceil(wordCount / 200));
    const primaryCategory = post.categories?.find((category) => category.parent_id) || post.categories?.[0];
    const breadcrumbCategories = [primaryCategory?.parent, primaryCategory].filter(Boolean).filter((category, index, items) => items.findIndex((item) => item.id === category.id) === index);
    const layoutColumns = preparedContent.headings.length > 0
        ? (related.length > 0 ? 'lg:grid-cols-[minmax(0,1fr)_320px] xl:grid-cols-[210px_minmax(0,1fr)_320px]' : 'xl:grid-cols-[210px_minmax(0,1fr)]')
        : (related.length > 0 ? 'lg:grid-cols-[minmax(0,1fr)_320px]' : '');

    useEffect(() => {
        if (!preparedContent.headings.length || typeof IntersectionObserver === 'undefined') return undefined;
        const elements = preparedContent.headings.map((heading) => document.getElementById(heading.id)).filter(Boolean);
        const observer = new IntersectionObserver((entries) => {
            const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
            if (visible[0]?.target.id) setActiveHeading(visible[0].target.id);
        }, { rootMargin: '-110px 0px -68% 0px', threshold: [0, 1] });
        elements.forEach((element) => observer.observe(element));
        return () => observer.disconnect();
    }, [preparedContent]);

    const schema = {
        '@context': 'https://schema.org',
        '@type': 'BlogPosting',
        headline: post.title,
        description: seo.description || post.excerpt,
        image: absoluteImage(post.image_path),
        datePublished: post.published_at,
        dateModified: post.updated_at,
        timeRequired: `PT${readingMinutes}M`,
        author: { '@type': 'Person', name: post.author.name, url: route('blog.public.author', post.author.slug) },
        mainEntityOfPage: canonical,
    };

    return <StorefrontLayout>
        <Head title={seo.title || post.title}>
            <meta name="description" content={seo.description || post.excerpt || ''} />
            <meta name="robots" content={seo.robots || 'index,follow'} />
            <link rel="canonical" href={canonical} />
            <meta property="og:type" content="article" />
            <meta property="og:title" content={seo.og_title || seo.title || post.title} />
            <meta property="og:description" content={seo.og_description || seo.description || post.excerpt || ''} />
            <meta property="og:url" content={canonical} />
            {(seo.og_image || post.image_path) && <meta property="og:image" content={absoluteImage(seo.og_image || post.image_path)} />}
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content={seo.og_title || post.title} />
            <meta name="twitter:description" content={seo.og_description || post.excerpt || ''} />
            {(seo.twitter_image || seo.og_image || post.image_path) && <meta name="twitter:image" content={absoluteImage(seo.twitter_image || seo.og_image || post.image_path)} />}
            <script type="application/ld+json">{JSON.stringify(schema).replace(/</g, '\u003c')}</script>
        </Head>

        <main className="mx-auto max-w-7xl px-4 py-8 text-slate-900 dark:text-slate-100 sm:px-6 lg:px-8 lg:py-10">
            <nav aria-label="Breadcrumb" className="overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <ol className="flex min-w-max items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400">
                    <li><Link href="/" aria-label="Home" className="grid size-8 place-items-center rounded-lg transition hover:bg-violet-50 hover:text-violet-700 dark:hover:bg-violet-950"><House className="size-4" /></Link></li>
                    {breadcrumbCategories.map((category) => <li key={category.id} className="flex items-center gap-2"><ChevronRight className="size-3.5 text-slate-300" /><Link href={`/${category.slug}`} className="transition hover:text-violet-700">{category.name}</Link></li>)}
                    <li className="flex min-w-0 items-center gap-2"><ChevronRight className="size-3.5 shrink-0 text-slate-300" /><span aria-current="page" className="max-w-[260px] truncate font-semibold text-slate-800 dark:text-slate-200 sm:max-w-lg">{post.title}</span></li>
                </ol>
            </nav>

            <header className="mt-7 border-b border-slate-200 pb-7 dark:border-slate-800">
                <h1 className="max-w-5xl text-3xl font-semibold leading-tight tracking-tight text-slate-950 dark:text-white sm:text-4xl lg:text-[2.65rem]">{post.title}</h1>
                {post.excerpt && <p className="mt-5 max-w-4xl text-lg leading-8 text-slate-600 dark:text-slate-300">{post.excerpt}</p>}
                <div className="mt-6 flex flex-col gap-4 border-t border-slate-100 pt-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500 dark:text-slate-400">
                        <Link href={route('blog.public.author', post.author.slug)} className="inline-flex items-center gap-2 font-semibold text-slate-700 transition hover:text-violet-700 dark:text-slate-200"><UserRound className="size-4 text-violet-600" />{post.author.name}</Link>
                        <span className="inline-flex items-center gap-2"><CalendarDays className="size-4 text-violet-600" />{date(post.published_at)}</span>
                        <span className="inline-flex items-center gap-2" title="Estimated reading time"><Clock3 className="size-4 text-violet-600" />Time to read: {readingMinutes} min</span>
                    </div>
                    <SocialShare title={post.title} url={shareUrl} />
                </div>
            </header>

            <div className={`mt-8 grid items-start gap-8 ${layoutColumns}`}>
                {preparedContent.headings.length > 0 && <aside className="sticky top-24 hidden min-w-0 xl:block"><TableOfContents headings={preparedContent.headings} activeId={activeHeading} /></aside>}

                <article className="min-w-0">
                    {preparedContent.headings.length > 0 && <TableOfContents headings={preparedContent.headings} activeId={activeHeading} mobile />}
                    {post.image_path && <img src={imageUrl(post.image_path)} alt={post.title} className="mb-8 max-h-[520px] w-full rounded-2xl object-cover" />}
                    <div className="product-description-content rich-text-content break-words" dangerouslySetInnerHTML={{ __html: preparedContent.html }} />
                    {post.gallery?.length > 0 && <div className="mt-8 grid gap-4 sm:grid-cols-2">{post.gallery.map((path, index) => <img key={`${path}-${index}`} loading="lazy" src={imageUrl(path)} alt={`${post.title} — image ${index + 1}`} className="w-full rounded-xl" />)}</div>}
                    <div className="mt-8 flex flex-wrap gap-2">{post.tags?.split(',').map((tag) => tag.trim()).filter(Boolean).map((tag, index) => <span key={index} className="rounded-full bg-slate-100 px-3 py-1 text-xs dark:bg-slate-800">{tag}</span>)}</div>
                    <section className="mt-8 flex gap-4 rounded-2xl border border-slate-200 p-5 dark:border-slate-700">
                        {post.author.image_path && <img src={imageUrl(post.author.image_path)} alt="" className="size-16 rounded-full object-cover" />}
                        <div><Link href={route('blog.public.author', post.author.slug)} className="font-bold text-violet-600">{post.author.name}</Link><p className="mt-1 text-xs text-slate-500">{post.author.designation}</p><p className="mt-3 whitespace-pre-line text-sm leading-6">{post.author.bio}</p><Link href={route('blog.public.author', post.author.slug)} className="mt-3 inline-block text-sm text-violet-600">View author profile →</Link></div>
                    </section>
                </article>

                {related.length > 0 && <aside className="min-w-0 lg:sticky lg:top-24">
                    <h2 className="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">Related posts</h2>
                    <div className="mt-3">{related.map((item) => <RelatedPost key={item.id} post={item} />)}</div>
                </aside>}
            </div>
        </main>
    </StorefrontLayout>;
}
