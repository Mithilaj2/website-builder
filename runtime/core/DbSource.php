<?php
declare(strict_types=1);

namespace SiteRuntime;

final class DbSource implements DataSource
{
    private ?array $settings = null;
    private ?array $pages = null;
    private ?array $alts = null;

    public function __construct(private Db $db)
    {
    }

    public function db(): Db
    {
        return $this->db;
    }

    public function settings(): array
    {
        if ($this->settings === null) {
            $this->settings = [];
            foreach ($this->db->fetchAll('SELECT setting_key, setting_value FROM ' . $this->db->t('settings')) as $r) {
                $v = json_decode((string) $r['setting_value'], true);
                $this->settings[$r['setting_key']] = (json_last_error() === JSON_ERROR_NONE) ? $v : $r['setting_value'];
            }
        }
        return $this->settings;
    }

    public function pages(): array
    {
        return $this->pages ??= $this->db->fetchAll('SELECT * FROM ' . $this->db->t('pages') . ' ORDER BY sort_order, id');
    }

    public function pageBySlug(string $slug): ?array
    {
        foreach ($this->pages() as $p) {
            if ($p['slug'] === $slug) {
                return $p;
            }
        }
        return null;
    }

    public function homePage(): ?array
    {
        foreach ($this->pages() as $p) {
            if ((int) $p['is_home'] === 1) {
                return $p;
            }
        }
        return null;
    }

    public function sections(int $pageId): array
    {
        $rows = $this->db->fetchAll('SELECT * FROM ' . $this->db->t('sections') . ' WHERE page_id = ? ORDER BY sort_order, id', [$pageId]);
        foreach ($rows as &$r) {
            $r['data'] = json_decode((string) $r['data'], true) ?: [];
            $r['style'] = json_decode((string) $r['style'], true) ?: [];
        }
        return $rows;
    }

    public function mediaAlt(): array
    {
        if ($this->alts === null) {
            $this->alts = [];
            foreach ($this->db->fetchAll('SELECT path, alt_text FROM ' . $this->db->t('media')) as $m) {
                $this->alts[$m['path']] = (string) $m['alt_text'];
            }
        }
        return $this->alts;
    }

    public function menuItems(string $location): array
    {
        try {
            return $this->db->fetchAll('SELECT * FROM ' . $this->db->t('menu_items') . ' WHERE location = ? ORDER BY sort_order, id', [$location]);
        } catch (\PDOException) {
            return []; // table not migrated yet
        }
    }
}
