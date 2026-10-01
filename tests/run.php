<?php
// Minimal stand-ins for tt-rss's plugin classes, enough to run the filter.
class Plugin {}
class PluginHost { const HOOK_ARTICLE_FILTER = "hook_article_filter"; }
require __DIR__ . "/../init.php";

$f = new Af_Substack_Embeds();
$fail = 0;
function check($what, $ok) { global $fail; echo ($ok ? "ok   " : "FAIL ") . $what . "\n"; if (!$ok) $fail++; }

// An excerpt of a real Countercraft post, as its feed carries it.
$real = file_get_contents(__DIR__ . "/countercraft-excerpt.html");
$out = $f->hook_article_filter(["content" => $real])["content"];
check("the Note becomes a quote", str_contains($out, "<blockquote><p>I just got to the part in Don Quixote"));
check("with its author", str_contains($out, "<p>— Lincoln Michel</p></blockquote>"));
check("right after the sentence introducing it", preg_match('~South Park</em>: </p>\s*<blockquote>~', $out) === 1);
check("the rest of the post survives", str_contains($out, "One might reply that this humor is less like Stooges"));
check("no data-attrs left on the Note", !str_contains($out, "commentId"));

$attrs = fn($name) => htmlspecialchars(json_encode(["comment" => ["body" => "Said by $name.\nSecond line.", "name" => $name]]));
$two = '<p>As I wrote: <span data-attrs="' . $attrs("A") . '"></span> and <span data-attrs="' . $attrs("B") . '"></span></p><p>After.</p>';
$out = $f->hook_article_filter(["content" => $two])["content"];
check("not inside the paragraph", str_contains($out, "and </p><blockquote>"));
check("several keep their order", strpos($out, "— A") < strpos($out, "— B"));
check("a line per paragraph", str_contains($out, "<p>Said by A.</p><p>Second line.</p>"));
check("text is escaped", str_contains($f->hook_article_filter(["content" => '<div data-attrs="' . htmlspecialchars(json_encode(["comment" => ["body" => "<script>x</script>"]])) . '"></div>'])["content"], "&lt;script&gt;"));

$other = '<div data-attrs="{&quot;src&quot;:&quot;x&quot;}"></div><p>comment</p>';
check("other embeds are left alone", $f->hook_article_filter(["content" => $other])["content"] === $other);
$plain = "<p>No embeds here, just a comment.</p>";
check("an article without embeds is untouched", $f->hook_article_filter(["content" => $plain])["content"] === $plain);
check("unicode survives", str_contains($f->hook_article_filter(["content" => '<p>é</p><div data-attrs="' . $attrs("Zoë") . '"></div>'])["content"], "— Zoë"));
exit($fail ? 1 : 0);
