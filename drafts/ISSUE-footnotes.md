**Title:** Footnotes and other in-page links open the site's front page

**What happens**

Links within an article, like footnote references (`<a href="#footnote-4">4</a>`), open the feed's site in a new tab instead of jumping to the footnote. The footnote text is there at the end of the article, but nothing links to it.

This affects every Substack feed with footnotes, and other blogs that use footnotes (WordPress, Ghost, Markdown generators). It shows the same way in the web UI and through the API, since both go through `Sanitizer::sanitize()`.

**To reproduce**

1. Subscribe to `https://countercraft.substack.com/feed`.
2. Open "The Roots of the Novel Are Parody, Pastiche, and Play" (2026-09-30).
3. Click the footnote number after "his contemporary Shakespeare."

The feed has `<a id="footnote-anchor-4" href="#footnote-4">4</a>`, and the footnote at the end is `<a id="footnote-4" href="#footnote-anchor-4">4</a>`. tt-rss shows the reference as `<a href="https://countercraft.substack.com#footnote-4" target="_blank" rel="noopener noreferrer">4</a>`. The footnote itself has no id.

**Expected**

The reference jumps to the footnote, and the footnote's number jumps back.

**Why** (as of aeff35e)

- `Sanitizer::sanitize()` passes every `a[href]` through `UrlHelper::rewrite_relative()` against the site URL (`classes/Sanitizer.php`, around line 146). A fragment-only href has no path, so it becomes `https://site#footnote-4`. The link also gets `target="_blank"`.
- `id` is in `$disallowed_attributes` (`classes/Sanitizer.php`, around line 279), so the targets are gone anyway.

Stripping ids makes sense: articles share one page in the web UI, so their ids would clash with each other and with tt-rss's own.

**A possible fix**

Keep in-article links working, but scoped to the article:
- When `$article_id` is known, keep an element's `id` as `a{article_id}-{id}`. Rewrite a fragment-only `href="#x"` to `#a{article_id}-x`, without `target="_blank"`, and only when an element in the same article has that id.
- Leave every other link as it is now.

That keeps ids unique on the page, and footnotes work in the web UI and for API clients. I'm happy to send a pull request if this direction sounds right.
