import {
    BadgePercent,
    BarChart3,
    Boxes,
    CirclePlus,
    FolderTree,
    FolderOpen,
    Globe2,
    MessagesSquare,
    Gauge,
    Package,
    PanelsTopLeft,
    GalleryHorizontalEnd,
    Ruler,
    Settings,
    ShieldCheck,
    ShoppingBag,
    Star,
    Tags,
    UserCog,
    Users,
} from 'lucide-react';

export const adminNavigation = [
    { label: 'Dashboard', href: '/dashboard', icon: Gauge, permission: 'dashboard.view' },
    {
        label: 'Inventories',
        icon: Boxes,
        children: [
            { label: 'Products', href: '/admin/inventories/products', icon: Package, permission: 'products.view' },
            { label: 'Add Products', href: '/admin/inventories/products/create', icon: CirclePlus, permission: 'products.create' },
            { label: 'Categories', href: '/admin/inventories/categories', icon: FolderTree, permission: 'categories.view' },
            { label: 'Brands', href: '/admin/inventories/brands', icon: Tags, permission: 'brands.view' },
            { label: 'Units', href: '/admin/inventories/units', icon: Ruler, permission: 'units.view' },
        ],
    },
    { label: 'Orders', href: '/admin/orders', icon: ShoppingBag, permission: 'orders.view' },
    { label: 'Courier Dashboard', href: '/admin/couriers', icon: Package, permission: 'courier.view' },
    {
        label: 'Blog', icon: PanelsTopLeft,
        children: [
            { label: 'Categories', href: '/admin/blog/categories', icon: FolderTree, permission: 'blog.view' },
            { label: 'All Blogs', href: '/admin/blog', icon: PanelsTopLeft, permission: 'blog.view' },
            { label: 'Add New Blog', href: '/admin/blog/create', icon: CirclePlus, permission: 'blog.create' },
        ],
    },
    { label: 'Customers', href: '/admin/customers', icon: Users, permission: 'customers.view' },
    { label: 'Coupons', href: '/admin/coupons', icon: BadgePercent, permission: 'coupons.view' },
    { label: 'Reviews', href: '/admin/reviews', icon: Star, permission: 'reviews.view' },
    { label: 'Reports', href: '/admin/reports', icon: BarChart3, permission: 'reports.view' },
    { label: 'File Manager', href: '/admin/file-manager', icon: FolderOpen, permission: 'file_manager.view' },
    {
        label: 'Website Design',
        icon: PanelsTopLeft,
        children: [
            { label: 'Hero Section', href: '/admin/website-design/hero-section', icon: GalleryHorizontalEnd, permission: 'website_design.view' },
            { label: 'Footer', href: '/admin/website-design/footer', icon: PanelsTopLeft, permission: 'website_design.view' },
        ],
    },
    {
        label: 'Settings',
        icon: Settings,
        children: [
            { label: 'Website Settings', href: '/admin/settings/website', icon: Globe2, permission: 'settings.view' },
            { label: 'Email & SMS', href: '/admin/settings/communication', icon: MessagesSquare, permission: 'settings.view' },
            { label: 'Courier Integration', href: '/admin/couriers/settings', icon: Package, permission: 'courier.view' },
        ],
    },
    {
        label: 'User Management',
        icon: UserCog,
        children: [
            { label: 'Users', href: '/admin/users', icon: Users, permission: 'users.view' },
            { label: 'Roles & Permissions', href: '/admin/roles', icon: ShieldCheck, permission: 'roles.view' },
        ],
    },
];
