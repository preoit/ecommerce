# Blog module

After pulling this release, run from the project directory:

```sh
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

The production frontend build is committed in `public/build`. If building on the server instead, run `npm ci` and `npm run build`.

Admin: **Blog → Categories / All Blogs / Add New Blog**. Create an author with **New author** in the blog editor; **Edit profile** updates that author's public profile. Categories can be created directly from the editor. Both actions preserve the current blog draft in the form. Select the new author/category after saving the modal.

Public URLs: `/blog`, `/blog/{slug}`, `/blog/category/{slug}`, `/blog/author/{slug}`. Add `/sitemap-blog.xml` to your search console sitemap submissions.

Only Published posts with a publish date at or before the current time are public. Future dates schedule visibility automatically without a queue job. Drafts and scheduled posts return 404 until published. Only indexable published posts enter the blog sitemap.

Changing a blog permalink keeps a 301 redirect from previous slugs. Category and author slugs should be kept stable once shared. Deleting a blog is permanent; the admin action asks for confirmation. Categories with child categories or linked posts cannot be deleted.
