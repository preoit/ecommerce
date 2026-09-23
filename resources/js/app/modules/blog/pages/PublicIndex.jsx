import { Link } from '@inertiajs/react';
import { ArrowUpRight, BookOpen, CalendarDays, ChevronLeft, ChevronRight, Tag } from 'lucide-react';
import StorefrontLayout from '@/app/layouts/StorefrontLayout';
import Seo from '@/app/components/Seo';
import { imageUrl } from './shared';

const formatDate = (value) => value
    ? new Date(value).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
    : 'Not published';

const initials = (name = '') => name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase() || 'A';

function AuthorAvatar({ author, size = 'size-11' }) {
    if (author?.image_path) {
        return <img src={imageUrl(author.image_path)} alt={author.name} className={`${size} shrink-0 rounded-full object-cover ring-2 ring-violet-100 dark:ring-violet-900`} />;
    }

    return <span aria-hidden="true" className={`${size} grid shrink-0 place-items-center rounded-full bg-violet-100 text-xs font-extrabold text-violet-700 ring-2 ring-violet-50 dark:bg-violet-950 dark:text-violet-200 dark:ring-violet-900`}>{initials(author?.name)}</span>;
}

function BlogPagination({ links = [] }) {
    if (links.length <= 3) return null;

    return <nav aria-label="Blog pagination" className="mt-10 flex flex-wrap items-center justify-center gap-2.5">
        {links.map((link, index) => {
            const isPrevious = index === 0;
            const isNext = index === links.length - 1;
            const label = isPrevious ? 'Previous page' : isNext ? 'Next page' : `Page ${link.label}`;
            const content = isPrevious ? <ChevronLeft className="size-4" /> : isNext ? <ChevronRight className="size-4" /> : link.label;
            const classes = `grid size-11 place-items-center rounded-full border text-sm font-bold transition ${link.active
                ? 'border-violet-600 bg-violet-600 text-white shadow-sm shadow-violet-200 dark:shadow-none'
                : 'border-slate-200 bg-white text-slate-700 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-violet-700 dark:hover:bg-violet-950'
            }`;

            return link.url
                ? <Link key={`${link.label}-${index}`} href={link.url} aria-label={label} aria-current={link.active ? 'page' : undefined} className={classes}>{content}</Link>
                : <span key={`${link.label}-${index}`} aria-label={label} aria-disabled="true" className={`${classes} cursor-not-allowed opacity-40`}>{content}</span>;
        })}
    </nav>;
}

function BlogCard({ post }) {
    const href = route('blog.public.show', post.slug);
    const primaryCategory = post.categories?.[0];

    return <article className="group flex h-full min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-4 transition duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-xl hover:shadow-violet-100/60 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-violet-800 dark:hover:shadow-none sm:p-5">
        <div className="flex min-w-0 items-center justify-between gap-4">
            {post.author ? <Link href={route('blog.public.author', post.author.slug)} className="flex min-w-0 items-center gap-3 rounded-full outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2">
                <AuthorAvatar author={post.author} />
                <span className="min-w-0">
                    <strong className="block truncate text-sm font-bold text-slate-950 transition group-hover:text-violet-700 dark:text-white dark:group-hover:text-violet-300">{post.author.name}</strong>
                    <span className="mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400">{post.author.designation || 'Editorial team'}</span>
                </span>
            </Link> : <span />}
            <time dateTime={post.published_at || undefined} className="flex shrink-0 items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 sm:text-sm">
                <CalendarDays className="size-4" />
                {formatDate(post.published_at)}
            </time>
        </div>

        <Link href={href} className="mt-5 block overflow-hidden rounded-xl bg-slate-100 outline-none ring-violet-500 focus-visible:ring-2 focus-visible:ring-offset-2 dark:bg-slate-800">
            {post.image_path
                ? <img loading="lazy" src={imageUrl(post.image_path)} alt={post.title} className="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
                : <span className="grid aspect-[4/3] w-full place-items-center bg-gradient-to-br from-violet-50 via-slate-50 to-violet-100 text-violet-300 dark:from-slate-800 dark:via-slate-900 dark:to-violet-950 dark:text-violet-700"><BookOpen className="size-12" strokeWidth={1.5} /></span>}
        </Link>

        <div className="flex flex-1 flex-col px-1 pb-1 pt-5">
            {primaryCategory && <Link href={`/${primaryCategory.slug}`} className="inline-flex w-fit items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-violet-700 dark:text-slate-400 dark:hover:text-violet-300"><Tag className="size-4" />{primaryCategory.name}</Link>}
            <h2 className="mt-3 text-xl font-semibold leading-snug tracking-tight text-slate-950 dark:text-white sm:text-[1.35rem]">
                <Link href={href} className="outline-none transition hover:text-violet-700 focus-visible:text-violet-700 dark:hover:text-violet-300 dark:focus-visible:text-violet-300">{post.title}</Link>
            </h2>
            <Link href={href} className="mt-auto inline-flex w-fit items-center gap-1.5 pt-6 text-sm font-bold text-violet-700 transition hover:gap-2.5 hover:text-violet-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2 dark:text-violet-300 dark:hover:text-violet-200">Read article <ArrowUpRight className="size-4" /></Link>
        </div>
    </article>;
}

export default function PublicIndex({ posts, categories, category, author }) {
    return <StorefrontLayout>
        <Seo title={author ? `${author.name} — Blog` : category ? `${category.name} — Blog` : 'Blog'} description={author?.bio || 'Stories, practical guides and product advice.'} />
        <main className="bg-white text-slate-900 dark:bg-slate-950 dark:text-slate-100">
            <div className="mx-auto w-full max-w-[1280px] px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
                <nav aria-label="Blog categories" className="flex gap-2 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <Link href={route('blog.public.index')} className={`shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition ${!category && !author ? 'border-violet-600 bg-violet-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-violet-300 hover:text-violet-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300'}`}>All articles</Link>
                    {categories.map((item) => <Link key={item.id} href={`/${item.slug}`} className={`shrink-0 rounded-full border px-4 py-2 text-sm font-semibold transition ${category?.id === item.id ? 'border-violet-600 bg-violet-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-violet-300 hover:text-violet-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300'}`}>{item.name}</Link>)}
                </nav>

                <div className="mb-7 mt-8 flex items-end justify-between gap-4 border-b border-slate-200 pb-4 dark:border-slate-800">
                    <div><p className="text-xs font-bold uppercase tracking-[0.16em] text-violet-600 dark:text-violet-300">Latest insights</p><h1 className="mt-1 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{category?.name || (author ? `${author.name}'s articles` : 'All articles')}</h1></div>
                    <p className="shrink-0 text-sm text-slate-500 dark:text-slate-400">{posts.total ?? posts.data.length} {(posts.total ?? posts.data.length) === 1 ? 'article' : 'articles'}</p>
                </div>

                {posts.data.length > 0
                    ? <div className="grid items-stretch gap-6 md:grid-cols-2 lg:grid-cols-3">{posts.data.map((post) => <BlogCard key={post.id} post={post} />)}</div>
                    : <section className="grid min-h-72 place-items-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 text-center dark:border-slate-700 dark:bg-slate-900"><div><span className="mx-auto grid size-14 place-items-center rounded-full bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300"><BookOpen className="size-6" /></span><h2 className="mt-4 text-lg font-bold">No articles found</h2><p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Try another search or browse all published articles.</p><Link href={route('blog.public.index')} className="mt-5 inline-flex rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-violet-700">View all articles</Link></div></section>}

                <BlogPagination links={posts.links} />
            </div>
        </main>
    </StorefrontLayout>;
}
