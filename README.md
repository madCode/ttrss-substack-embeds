# Substack embeds for tt-rss

A [tt-rss](https://github.com/tt-rss/tt-rss) plugin, `af_substack_embeds`, that shows Substack Notes
embedded in posts as quotes.

Substack posts can embed a Substack Note, the way a blog embeds a post from a social network. In the
feed, the embed is an empty element whose `data-attrs` attribute holds the Note as JSON, which
Substack's page script fills in. tt-rss removes `data-*` attributes when it sanitizes an article, so
the Note shows as nothing, and the sentence introducing it ("…as you might expect:") is left
hanging. This plugin turns each embedded Note into a quote of its text and author as the article is
stored. Other `data-attrs` embeds are left alone.

## Install

1. Clone this repository into your tt-rss installation as `plugins.local/af_substack_embeds`.
2. Enable it in Preferences › Plugins, under the user plugins, for each user who wants it.

Articles still in their feed are redone at the feed's next update: tt-rss counts its enabled plugins
when it decides whether an article changed. Older ones keep their empty embed.

## Example

From a post on countercraft.substack.com:

    <p>…gross-out comedy you might expect from <em>South Park</em>: </p>
    <div class="comment" data-attrs="{&quot;comment&quot;:{&quot;body&quot;:&quot;I just got to
    the part in Don Quixote…&quot;,&quot;name&quot;:&quot;Lincoln Michel&quot;, …}}"></div>

becomes

    <p>…gross-out comedy you might expect from <em>South Park</em>: </p>
    <blockquote><p>I just got to the part in Don Quixote…</p><p>— Lincoln Michel</p></blockquote>

## Tests

    php tests/run.php

They run the filter on its own, with stand-ins for tt-rss's plugin classes, on an excerpt of that
post and on edge cases: a Note inside a paragraph, several in one paragraph, other embeds, escaping.

## Also here

`drafts/ISSUE-footnotes.md` is a draft issue for tt-rss about footnotes, which tt-rss breaks in a
related way. It isn't posted yet.

## License

GPL v3 or later, like tt-rss. See [LICENSE](LICENSE).
