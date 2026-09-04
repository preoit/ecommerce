import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/app/utils/cn';

export default function Pagination({ links, from, to, total }) {
    if (!links || links.length <= 3) return null;

    return (
        <div className="overflow-x-auto border-t border-slate-200 px-4 py-5 sm:px-5">
            <p className="sr-only">Showing {from} to {to} of {total}</p>
            <nav className="mx-auto flex w-max items-center gap-2" aria-label="Pagination">
                {links.map((link, index) => {
                    const isPrevious = index === 0;
                    const isNext = index === links.length - 1;
                    const content = isPrevious ? <ChevronLeft className="size-4" /> : isNext ? <ChevronRight className="size-4" /> : link.label;
                    return link.url ? (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url}
                            preserveScroll
                            className={cn(
                                'inline-flex size-9 shrink-0 items-center justify-center rounded-md border text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2',
                                link.active ? 'border-violet-600 bg-violet-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-violet-500 hover:bg-violet-50 hover:text-violet-700',
                            )}
                            aria-label={isPrevious ? 'Previous page' : isNext ? 'Next page' : `Page ${link.label}`}
                            aria-current={link.active ? 'page' : undefined}
                        >
                            {content}
                        </Link>
                    ) : (
                        <span
                            key={`${link.label}-${index}`}
                            className={cn(
                                'inline-flex size-9 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-sm text-slate-400',
                                (isPrevious || isNext) && 'cursor-not-allowed opacity-50',
                            )}
                            aria-hidden="true"
                        >
                            {content}
                        </span>
                    );
                })}
            </nav>
        </div>
    );
}
