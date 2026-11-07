<?php
declare(strict_types=1);

namespace SiteRuntime;

/** In-memory content, used for template previews (demo content without a database). */
final class ArraySource implements DataSource
{
    /**
     * @param array $settings
     * @param list<array> $pages each with id, slug, is_home, ...
     * @param array<int, list<array>> $sections keyed by page id
     */
    public function __construct(private array $settings, private array $pages, private array $sections)
    {
    }

    public static function fromSeed(array $seed): self
    {
        $pages = [];
        $sections = [];
        foreach ($seed['pages'] as $i => $p) {
            $id = $i + 1;
            $pages[] = $p + ['id' => $id];
            $sections[$id] = [];
            foreach ($p['sections'] as $j => $s) {
                $sections[$id][] = $s + ['id' => $id * 100 + $j, 'page_id' => $id];
            }
        }
        return new self($seed['settings'], $pages, $sections);
    }

    public function settings(): array
    {
        return $this->settings;
    }

    public function pages(): array
    {
        return $this->pages;
    }

    public function pageBySlug(string $slug): ?array
    {
        foreach ($this->pages as $p) {
            if ($p['slug'] === $slug) {
                return $p;
            }
        }
        return null;
    }

    public function homePage(): ?array
    {
        foreach ($this->pages as $p) {
            if (!empty($p['is_home'])) {
                return $p;
            }
        }
        return null;
    }

    public function sections(int $pageId): array
    {
        return $this->sections[$pageId] ?? [];
    }

    public function mediaAlt(): array
    {
        return [];
    }
}
