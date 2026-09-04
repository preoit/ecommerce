import { Head, usePage } from '@inertiajs/react';

export default function Seo({ title, description, image, type = 'website', schema }) {
    const { url, props } = usePage();
    const website = props.website || {};
    const appName = website.name || import.meta.env.VITE_APP_NAME || 'Commerce';
    const resolvedTitle = title || website.seoTitle || appName;
    const resolvedDescription = description || website.seoDescription || '';
    const origin = typeof window === 'undefined' ? '' : window.location.origin;
    const canonical = `${origin}${url.split('?')[0]}`;

    return (
        <Head title={resolvedTitle}>
            <meta name="description" content={resolvedDescription} />
            <link rel="canonical" href={canonical} />
            <meta property="og:type" content={type} />
            <meta property="og:title" content={`${resolvedTitle} | ${appName}`} />
            <meta property="og:description" content={resolvedDescription} />
            <meta property="og:url" content={canonical} />
            {(image || website.seoImage) && <meta property="og:image" content={image || website.seoImage} />}
            <meta name="twitter:card" content={image || website.seoImage ? 'summary_large_image' : 'summary'} />
            {schema && <script type="application/ld+json">{JSON.stringify(schema)}</script>}
        </Head>
    );
}
