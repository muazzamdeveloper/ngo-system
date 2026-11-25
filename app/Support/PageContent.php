<?php

namespace App\Support;

use App\Models\PageBlock;
use App\Services\SiteMedia;

/** Reads the editable blocks of config-defined pages: saved value first, then the config default. */
class PageContent
{
    public static function definition(string $page): ?array
    {
        return config("site_pages.{$page}");
    }

    public static function exists(string $page): bool
    {
        return self::definition($page) !== null;
    }

    public static function text(string $page, string $block): string
    {
        $saved = PageBlock::query()->where(['page_key' => $page, 'block_key' => $block])->value('value');

        return (string) ($saved ?? self::definition($page)['blocks'][$block]['default'] ?? '');
    }

    /** Rich text is cleaned again on output, so only the allowed tags reach the page. */
    public static function html(string $page, string $block): string
    {
        return HtmlCleaner::clean(self::text($page, $block));
    }

    public static function image(string $page, string $block): ?string
    {
        $path = PageBlock::query()->where(['page_key' => $page, 'block_key' => $block])->value('image_path');

        if ($path) {
            return SiteMedia::url($path);
        }

        // Demo photo shipped in public/ until the admin uploads one.
        $default = self::definition($page)['blocks'][$block]['default'] ?? null;

        return $default ? asset($default) : null;
    }

    /** Ordered list of [key => definition] for the public page. */
    public static function blocks(string $page): array
    {
        return self::definition($page)['blocks'] ?? [];
    }

    /**
     * Blocks grouped into the page's sections, in the order the page declares them.
     * Each section: key, label, layout ('text', 'split' or 'cards'), image side and its blocks.
     * A block without a 'section' key goes into a section called 'content'.
     *
     * @return list<array{key: string, label: string, layout: string, image: string, blocks: array}>
     */
    public static function sections(string $page): array
    {
        $definition = self::definition($page) ?? [];
        $declared = $definition['sections'] ?? [];
        $grouped = [];

        foreach (self::blocks($page) as $key => $block) {
            $section = (string) ($block['section'] ?? 'content');
            $grouped[$section][$key] = $block;
        }

        $order = array_values(array_unique(array_merge(array_keys($declared), array_keys($grouped))));
        $sections = [];

        foreach ($order as $sectionKey) {
            if (! isset($grouped[$sectionKey])) {
                continue;
            }
            $meta = $declared[$sectionKey] ?? [];
            $sections[] = [
                'key' => $sectionKey,
                'label' => (string) ($meta['label'] ?? ''),
                'layout' => (string) ($meta['layout'] ?? 'text'),
                'image' => (string) ($meta['image'] ?? 'right'),
                'blocks' => $grouped[$sectionKey],
            ];
        }

        return $sections;
    }
}
