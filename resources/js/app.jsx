import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = document.querySelector('meta[name="website-name"]')?.content || import.meta.env.VITE_APP_NAME || 'Commerce';
const pages = import.meta.glob([
    './Pages/**/*.jsx',
    './app/modules/**/pages/*.jsx',
]);

createInertiaApp({
    title: (title) => `${title} | ${appName}`,
    resolve: (name) => {
        const pagePath = name.startsWith('app/')
            ? `./${name}.jsx`
            : `./Pages/${name}.jsx`;

        return resolvePageComponent(pagePath, pages);
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
