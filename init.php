<?php
/*
 * af_substack_embeds, a tt-rss plugin. Licensed under the GNU GPL v3 or later; see LICENSE.
 */

/**
 * Substack posts can embed a Substack Note, the way a blog embeds a post from a social network.
 * In the feed the embed is an empty element whose `data-attrs` JSON holds the Note; Substack's
 * page script fills it in. tt-rss drops `data-*` attributes when it sanitizes, so the embed shows
 * as nothing and the sentence introducing it is left hanging. This turns each embed into a
 * blockquote of the Note's text and author while the article is being stored.
 */
class Af_Substack_Embeds extends Plugin {

	function about() {
		return [null,
			"Show Substack Notes embedded in posts as quotes",
			"madCode"];
	}

	function init($host) {
		$host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
	}

	function hook_article_filter($article) {
		$content = $article["content"] ?? "";

		// Cheap check first: the embed's JSON names the Note "comment", HTML-escaped in the attribute.
		if (!str_contains($content, "data-attrs") || !str_contains($content, "comment"))
			return $article;

		$doc = new DOMDocument();

		if (!@$doc->loadHTML('<?xml encoding="UTF-8"><html><body>' . $content . '</body></html>'))
			return $article;

		$xpath = new DOMXPath($doc);
		$changed = false;

		// The last quote placed after each paragraph, so several from one paragraph keep their order.
		$placed_after = new SplObjectStorage();

		/** @var DOMElement $embed */
		foreach (iterator_to_array($xpath->query("//*[@data-attrs]")) as $embed) {
			$attrs = json_decode($embed->getAttribute("data-attrs"), true);
			$note = is_array($attrs) && is_array($attrs["comment"] ?? null) ? $attrs["comment"] : null;
			$body = is_string($note["body"] ?? null) ? trim($note["body"]) : "";

			if (!$body)
				continue;

			$quote = $doc->createElement("blockquote");

			foreach (preg_split("/\n+/", $body) as $line) {
				if (trim($line) === "")
					continue;

				$p = $doc->createElement("p");
				$p->appendChild($doc->createTextNode(trim($line)));
				$quote->appendChild($p);
			}

			$name = is_string($note["name"] ?? null) ? trim($note["name"]) : "";

			if ($name) {
				$p = $doc->createElement("p");
				$p->appendChild($doc->createTextNode("— " . $name));
				$quote->appendChild($p);
			}

			// A blockquote can't sit inside a paragraph: one embedded in one goes after it.
			$paragraph = null;

			for ($node = $embed->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
				if ($node->tagName == "p") {
					$paragraph = $node;
					break;
				}
			}

			if ($paragraph) {
				$after = $placed_after->contains($paragraph) ? $placed_after[$paragraph] : $paragraph;
				$embed->parentNode->removeChild($embed);
				$after->parentNode->insertBefore($quote, $after->nextSibling);
				$placed_after[$paragraph] = $quote;
			} else {
				$embed->parentNode->replaceChild($quote, $embed);
			}

			$changed = true;
		}

		if ($changed) {
			$html = "";

			foreach ($doc->getElementsByTagName("body")->item(0)->childNodes as $child)
				$html .= $doc->saveHTML($child);

			$article["content"] = $html;
		}

		return $article;
	}

	function api_version() {
		return 2;
	}
}
