<?php
declare(strict_types=1);

namespace SiteRuntime;

/** Where the renderer reads site content from: the site database, or in-memory demo data. */
interface DataSource
{
    /** @return array<string, mixed> decoded settings (theme, site info, ...) */
    public function settings(): array;

    /** @return list<array> all pages ordered by sort_order */
    public function pages(): array;

    public function pageBySlug(string $slug): ?array;

    public function homePage(): ?array;

    /** @return list<array> sections of a page ordered by sort_order, with data/style decoded */
    public function sections(int $pageId): array;

    /** @return list<array> custom menu items for 'header' or 'footer' (empty = automatic from pages) */
    public function menuItems(string $location): array;

    /** @return array<string, string> media path => alt text */
    public function mediaAlt(): array;
}
