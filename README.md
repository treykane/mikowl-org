# mikowl.org
A repository made in an attempt to contain the millennial art infusion we all love. This will contain all current and future work to mikowl.org

### Adding images
Drop a file into `img/mikowl/` and it shows up in the gallery. Its dimensions are read automatically, so the grid reserves space for it before it loads.

Then run `php bin/optimize-images.php` and commit `img/web/` along with it. That saves a much lighter WebP copy of each image (resized to 1200px wide at most), which the gallery serves instead of the original. Originals in `img/mikowl/` are never modified, and any image without a copy is simply served as is. If you replace an image, run the script again so the copy stays current. It works best with Google's WebP tools installed (`brew install webp`), which keep color profiles intact and are needed for animated GIFs.

### Analytics
Analytics are off. To turn on Google Analytics 4, paste your measurement ID (it looks like `G-XXXXXXXXXX`) into `$ga4_id` at the top of `index.php`.

### Cache busting
CSS, JS, and images are linked through `asset()` (see `inc/asset.php`), which adds the file's last-modified time to the URL, e.g. `css/main.css?v=1730412345`. Change a file and its URL changes with it, so visitors' browsers fetch the new version instead of a stale cached copy. Nothing needs to be bumped by hand; just link new files with `<?php echo asset('path/to/file'); ?>`.

### Long-term Roadmap
1. Dump PHP and Render site using JS
2. Create server-side build step so changes can be deployed from the git repository
3. Build out photo service API to be used in a number of ways
