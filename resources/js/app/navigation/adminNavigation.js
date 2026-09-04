import {
    BadgePercent,
    BarChart3,
    Boxes,
    CirclePlus,
    FolderTree,
    FolderOpen,
    Globe2,
    Gauge,
    Package,
    PanelsTopLeft,
    GalleryHorizontalEnd,
    Ruler,
    Settings,
    ShoppingBag,
    Star,
    Tags,
    Users,
} from 'lucide-react';

export const adminNavigation = [
    { label: 'Dashboard', href: '/dashboard', icon: Gauge },
    {
        label: 'Inventories',
        icon: Boxes,
        children: [
            { label: 'Products', href: '/admin/inventories/products', icon: Package },
            { label: 'Add Products', href: '/admin/inventories/products/create', icon: CirclePlus },
            { label: 'Categories', href: '/admin/inventories/categories', icon: FolderTree },
            { label: 'Brands', href: '/admin/inventories/brands', icon: Tags },
            { label: 'Units', href: '/admin/inventories/units', icon: Ruler },
        ],
    },
    { label: 'Orders', href: '/admin/orders', icon: ShoppingBag },
    { label: 'Customers', href: '/admin/customers', icon: Users },
    { label: 'Coupons', href: '/admin/coupons', icon: BadgePercent },
    { label: 'Reviews', href: '/admin/reviews', icon: Star },
    { label: 'Reports', href: '/admin/reports', icon: BarChart3 },
    { label: 'File Manager', href: '/admin/file-manager', icon: FolderOpen },
    {
        label: 'Website Design',
        icon: PanelsTopLeft,
        children: [
            { label: 'Hero Section', href: '/admin/website-design/hero-section', icon: GalleryHorizontalEnd },
            { label: 'Footer', href: '/admin/website-design/footer', icon: PanelsTopLeft },
        ],
    },
    {
        label: 'Settings',
        icon: Settings,
        children: [
            { label: 'Website Settings', href: '/admin/settings/website', icon: Globe2 },
        ],
    },
];
