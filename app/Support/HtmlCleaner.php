<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Cleans the HTML written in the post editor so it is safe to show to everyone.
 * Only simple formatting survives (paragraphs, bold, lists, headings, quotes, links);
 * scripts, styles, frames, forms, event handlers and unsafe links are removed.
 */
final class HtmlCleaner
{
    private const ALLOWED = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a', 'hr'];

    /** Removed together with everything inside them. */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'noscript', 'template', 'svg', 'math', 'form', 'input',
        'textarea', 'select', 'button', 'link', 'meta', 'base', 'head', 'title', 'audio', 'video', 'canvas', 'frame', 'frameset', 'applet'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return '';
        }

        self::walk($body);

        $out = '';

        foreach ($body->childNodes as $child) {
            $out .= $document->saveHTML($child);
        }

        return trim($out);
    }

    /** Plain text version, for short previews. */
    public static function text(?string $html): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    private static function walk(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $parent->removeChild($child);          // comments, processing instructions, CDATA

                continue;
            }

            $name = strtolower($child->nodeName);

            if (in_array($name, self::DROP, true)) {
                $parent->removeChild($child);

                continue;
            }

            if ($name === 'div') {                     // browsers make <div> when Enter is pressed: treat it as a paragraph
                $paragraph = $child->ownerDocument->createElement('p');

                while ($child->firstChild) {
                    $paragraph->appendChild($child->firstChild);
                }

                $parent->replaceChild($paragraph, $child);
                $child = $paragraph;
                $name = 'p';
            }

            if (! in_array($name, self::ALLOWED, true)) {
                self::walk($child);                    // clean the inside first, then keep only its content

                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }

                $parent->removeChild($child);

                continue;
            }

            self::cleanAttributes($child, $name);
            self::walk($child);
        }
    }

    private static function cleanAttributes(DOMElement $element, string $name): void
    {
        $keep = [];

        if ($name === 'a') {
            $href = self::safeHref($element->getAttribute('href'));

            if ($href !== null) {
                $keep['href'] = $href;

                if (preg_match('#^https?://#i', $href)) {
                    $keep['rel'] = 'noopener noreferrer';
                }
            }

            $title = trim($element->getAttribute('title'));

            if ($title !== '') {
                $keep['title'] = mb_substr($title, 0, 200);
            }
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttributeNode($attribute);
        }

        foreach ($keep as $key => $value) {
            $element->setAttribute($key, $value);
        }
    }

    private static function safeHref(string $href): ?string
    {
        $href = trim($href);
        $probe = strtolower(preg_replace('/[\x00-\x20]+/', '', $href));

        if ($probe === '') {
            return null;
        }

        foreach (['http://', 'https://', 'mailto:', 'tel:'] as $scheme) {
            if (str_starts_with($probe, $scheme)) {
                return $href;
            }
        }

        if (str_starts_with($probe, '#') || (str_starts_with($probe, '/') && ! str_starts_with($probe, '//'))) {
            return $href;
        }

        return null;                                   // javascript:, data:, vbscript: and anything else
    }
}
