<?php
class Helpers_Html {

    // Tags allowed in rich-text content (Jodit terms editors). Everything else is unwrapped.
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ol', 'ul', 'li',
        'h1', 'h2', 'h3', 'h4', 'blockquote', 'hr', 'span', 'div',
        'table', 'thead', 'tbody', 'tr', 'td', 'th',
    ];

    // Tags removed together with their content.
    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
        'button', 'svg', 'math', 'link', 'meta', 'head', 'title', 'base',
    ];

    /**
     * Sanitize rich-text HTML against an allowlist. Strips scripts, event
     * handlers and unknown tags (keeping their text). Returns '' for
     * visually empty content (e.g. Jodit's "<p><br></p>").
     */
    public static function sanitize(?string $html): string {
        $html = trim((string) $html);
        if ($html === '' || self::isEmpty($html)) {
            return '';
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"?><html><body>' . $html . '</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }

        self::cleanNode($body);

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        return trim($out);
    }

    /**
     * True when HTML has no visible text content (empty editors produce
     * markup like "<p><br></p>" or "&nbsp;").
     */
    public static function isEmpty(?string $html): bool {
        $text = strip_tags((string) $html);
        $text = str_replace(["\u{00A0}", '&nbsp;'], ' ', $text);
        return trim($text) === '';
    }

    private static function cleanNode(DOMNode $node): void {
        // Snapshot child list — we mutate while iterating
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                self::cleanNode($child);

                $tag = strtolower($child->nodeName);
                if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                    $node->removeChild($child);
                } elseif (!in_array($tag, self::ALLOWED_TAGS, true)) {
                    // Unwrap: keep (already cleaned) children, drop the tag itself
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                } else {
                    self::cleanAttributes($child);
                }
            } elseif ($child instanceof DOMComment) {
                $node->removeChild($child);
            }
        }
    }

    private static function cleanAttributes(DOMElement $el): void {
        $allowed = ['style', 'colspan', 'rowspan'];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            if (!in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
            } elseif ($name === 'style' && preg_match('/url\s*\(|expression|javascript/i', $attr->value)) {
                $el->removeAttribute($attr->name);
            }
        }
    }
}
?>
