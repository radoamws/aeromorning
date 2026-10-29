<?php
/**
 * Final clean-up of the article text OpenAI returns, before it is stored
 * and published. Added 2026-10-29 after two kinds of broken posts:
 *
 *  - Layout: OpenAI copies the email's HTML almost verbatim, including the
 *    hard line breaks of the plain-text wrap (~70 columns) and the Outlook /
 *    Yahoo markup (class="ydp…MsoNormal", style="…", <span lang>, layout
 *    <table cellspacing>). wpautop turns every one of those line breaks into
 *    <br>, so the article rendered as a narrow column on the left half of the
 *    content area. The HTML is rebuilt from a whitelist of tags, without
 *    attributes, with whitespace collapsed inside text.
 *  - Encoding: asked for "well escaped" JSON, the model sometimes escapes
 *    accented letters byte by byte as "\u0000e9" ("é" = U+00E9) or
 *    "\u0000e2\u000080\u000093" ("–" = UTF-8 E2 80 93). json_decode() turns
 *    those into a NUL byte followed by hex digits ("d\0e9voile"). The bytes
 *    are reassembled here.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AMNP_Content
{
    /** Kept tags (with the attributes they may keep); b/i are renamed. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'strong' => [], 'em' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [], 'caption' => [],
        'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'a' => ['href'],
    ];
    private const RENAMED = ['b' => 'strong', 'i' => 'em', 'h1' => 'h2', 'strike' => 's', 'del' => 's'];
    /** Dropped with everything inside. */
    private const REMOVED = ['script', 'style', 'head', 'title', 'meta', 'link', 'img', 'picture', 'svg',
        'iframe', 'object', 'embed', 'video', 'audio', 'form', 'input', 'button', 'select', 'textarea', 'xml'];
    private const BLOCKS = ['p', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption'];

    /** Titles, meta description, focus keyphrase: plain text on one line. */
    public static function cleanText(string $text): string
    {
        $text = html_entity_decode(wp_strip_all_tags(self::repairEncoding($text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    public static function cleanHtml(string $html): string
    {
        $html = trim(self::repairEncoding($html));
        if ($html === '') {
            return '';
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div id="amnp-root">' . $html . '</div>', LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('amnp-root');
        if (!$root) {
            return wp_kses_post($html);
        }

        $out = self::renderChildren($root);

        // Empty inline wrappers (<strong></strong>), then empty blocks.
        do {
            $before = $out;
            $out = preg_replace('#<(strong|em|u|s|sub|sup|a)>(?:\s|&nbsp;|<br>)*</\1>#u', '', $out) ?? $out;
            $out = preg_replace('#<(p|h[2-6]|li|blockquote)>(?:\s|&nbsp;|<br>)*</\1>#u', '', $out) ?? $out;
        } while ($out !== $before);

        // No <br> or spaces at the edges of a block.
        $out = preg_replace('#(<(?:p|h[2-6]|li|td|th)>)(?:\s|<br>)+#u', '$1', $out) ?? $out;
        $out = preg_replace('#(?:\s|<br>)+(</(?:p|h[2-6]|li|td|th)>)#u', '$1', $out) ?? $out;

        // One block per line, never a line break inside running text.
        $out = preg_replace('#\s*(</?(?:' . implode('|', self::BLOCKS) . ')>)\s*#u', '$1', $out) ?? $out;
        // Outlook wraps every table cell in a single paragraph.
        $out = preg_replace('#<(td|th)([^>]*)><p>((?:(?!</?p>).)*)</p></\1>#su', '<$1$2>$3</$1>', $out) ?? $out;
        $out = preg_replace('#(</(?:p|h[2-6]|ul|ol|blockquote|table)>)#', "$1\n", $out) ?? $out;
        $out = preg_replace('#(<(?:ul|ol|table)>)#', "$1\n", $out) ?? $out;
        $out = preg_replace('#(</(?:li|tr|thead|tbody|tfoot|caption)>)#', "$1\n", $out) ?? $out;

        return trim($out);
    }

    /**
     * NUL + two hex digits, possibly repeated, is a byte sequence the model
     * escaped one byte at a time: UTF-8 when valid ("–"), else Latin-1 ("é").
     * Escapes the model wrote as literal text ("dévoile") are decoded too.
     */
    public static function repairEncoding(string $text): string
    {
        $text = preg_replace_callback('/(?:\x00[0-9a-fA-F]{2})+/', static function (array $m): string {
            $bytes = hex2bin(str_replace("\x00", '', $m[0]));
            if ($bytes !== false && mb_check_encoding($bytes, 'UTF-8')) {
                return $bytes;
            }
            $chars = '';
            foreach (str_split($bytes ?: '') as $byte) {
                $chars .= mb_chr(ord($byte), 'UTF-8');
            }
            return $chars;
        }, $text) ?? $text;

        $text = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', static function (array $m): string {
            $char = mb_chr((int) hexdec($m[1]), 'UTF-8');
            return $char === false ? '' : $char;
        }, $text) ?? $text;

        // Any control character left (keeps tab and line breaks).
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? $text;
    }

    private static function renderChildren(\DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= self::render($child);
        }
        return $out;
    }

    private static function render(\DOMNode $node): string
    {
        if ($node instanceof \DOMText) {
            // Hard-wrapped source lines become plain spaces.
            $text = preg_replace('/[ \t\r\n\f]+/', ' ', $node->nodeValue ?? '') ?? '';
            return htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
        }
        if (!$node instanceof \DOMElement) {
            return ''; // comments, processing instructions
        }

        $tag = strtolower($node->localName ?? $node->nodeName);
        if (strpos($node->nodeName, ':') !== false || in_array($tag, self::REMOVED, true)) {
            return ''; // Office/VML namespaced tags (o:p, v:shape) and non-text content
        }
        $tag = self::RENAMED[$tag] ?? $tag;

        $inner = self::renderChildren($node);

        if (!isset(self::ALLOWED[$tag])) {
            // div/span/font/section...: keep the content. A div holding only
            // inline content was a paragraph in the email.
            if (in_array($tag, ['div', 'section', 'article', 'center', 'header', 'footer'], true)
                && !preg_match('#<(?:' . implode('|', self::BLOCKS) . ')>#', $inner)) {
                return '<p>' . $inner . '</p>';
            }
            return $inner;
        }

        if ($tag === 'br') {
            return '<br>';
        }

        $attrs = '';
        foreach (self::ALLOWED[$tag] as $name) {
            $value = trim((string) $node->getAttribute($name));
            if ($value === '') {
                continue;
            }
            if ($name === 'href') {
                $value = esc_url($value, ['http', 'https', 'mailto']);
                if ($value === '') {
                    return $inner; // javascript: and the like: keep the text only
                }
            } elseif (!ctype_digit($value)) {
                continue;
            }
            $attrs .= ' ' . $name . '="' . esc_attr($value) . '"';
        }
        if ($tag === 'a') {
            if ($attrs === '') {
                return $inner; // anchors without a link
            }
            $attrs .= ' target="_blank" rel="noopener"';
        }

        return "<{$tag}{$attrs}>{$inner}</{$tag}>";
    }
}
