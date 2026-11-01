# mikowl.org
A repository made in an attempt to contain the millennial art infusion we all love. This will contain all current and future work to mikowl.org

### Adding images
Drop a file into `img/mikowl/` and it shows up in the gallery. Its dimensions are read automatically, so the grid reserves space for it before it loads.

Describe it in `img/descriptions.json`, keyed by its file name: a short line saying what the picture shows, like `"Shining.jpg": "Mikowl leering through the splintered door in the “Here's Johnny!” scene from The Shining"`. That's its alt text, which screen readers announce and search engines (Google Images especially) rely on to understand the picture. Until it has one, it gets a generic "Mikowl: <file name>".

Then run `php bin/optimize-images.php` and commit `img/web/` along with it. That saves a much lighter WebP copy of each image (resized to 1200px wide at most), which the gallery serves instead of the original. Originals in `img/mikowl/` are never modified, and any image without a copy is simply served as is. If you replace an image, run the script again so the copy stays current. It works best with Google's WebP tools installed (`brew install webp`), which keep color profiles intact and are needed for animated GIFs. The script also lists any image that still needs a description.

### Analytics
Analytics are off. To turn on Google Analytics 4, paste your measurement ID (it looks like `G-XXXXXXXXXX`) into `$ga4_id` at the top of `index.php`.

### Search and sharing
The settings at the top of `index.php` control how the page shows up in search results and link previews:

- `$site_url` is the page's canonical address. The page also answers at `www.mikowl.org` and `/index.php`; the canonical link tells search engines those are all this one page.
- `$title` and `$description` are the headline and summary shown in search results and link previews.
- `$share_image` is the picture in link previews (Facebook, X, iMessage, Slack...). Keep it a JPEG about 1200px wide and under 300 KB, since WhatsApp skips bigger ones. To use a different picture, run `sips -s format jpeg -s formatOptions 80 --resampleWidth 1200 img/mikowl/<file> --out img/share.jpg` and update `$share_alt` to describe it.

The page also carries structured data that gives search engines the site's name, marks the page as an image gallery, and pairs each image with its description.

### Cache busting
CSS, JS, and images are linked through `asset()` (see `inc/asset.php`), which adds a fingerprint of the file's contents to the URL, e.g. `css/main.css?v=3f2a9c1b`. Change a file and its URL changes with it, so visitors' browsers fetch the new version instead of a stale cached copy. A file that hasn't changed keeps its URL, even through a deploy that rewrites every file, so it stays cached and its URL stays steady for Google Images. Nothing needs to be bumped by hand; just link new files with `<?php echo asset('path/to/file'); ?>`.

### Long-term Roadmap
1. Dump PHP and Render site using JS
2. Create server-side build step so changes can be deployed from the git repository
3. Build out photo service API to be used in a number of ways
