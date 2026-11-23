<?php
declare(strict_types=1);

namespace SiteRuntime\Listings;

use SiteRuntime\Renderer;

/**
 * Public listing pages, at the path the template chooses (e.g. /properties):
 *   GET /{path}          all listings, with filters (?c=category&location=&q=&min=&max=&a[key]=&sort=&p=)
 *   GET /{path}/{slug}   one listing, with an enquiry form (posted to the normal form handler)
 * Views come from the template (theme/listings/{index|detail}.php) when present, otherwise from core/Listings/views.
 */
final class ListingPage
{
    private const PER_PAGE = 12;

    /** @param array{url:callable(string):string, asset:callable(string):string, theme_dir:string} $ctx */
    public function __construct(private ListingService $svc, private Renderer $renderer, private array $ctx)
    {
    }

    public function handle(string $sub): void
    {
        $sub = trim($sub, '/');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') {
            $this->notFound();
            return;
        }
        if ($sub === '') {
            $this->index();
        } elseif (preg_match('/^[a-z0-9-]{1,160}$/', $sub)) {
            $this->detail($sub);
        } else {
            $this->notFound();
        }
    }

    /** Filters from the query string, cleaned (also used by templates to keep the search form filled in). */
    public function filters(): array
    {
        $cfg = $this->svc->config();
        $money = static function ($v): ?int {
            $v = str_replace([',', '₹', ' '], '', (string) $v);
            return preg_match('/^\d{1,12}$/', $v) ? (int) $v * 100 : null;
        };
        $attr = [];
        foreach ((array) ($_GET['a'] ?? []) as $k => $v) {
            $a = $cfg['attributes'][$k] ?? null;
            if ($a && $a['filter'] && is_string($v) && $v !== '') {
                if ($a['type'] === 'number' && preg_match('/^\d{1,6}$/', $v)) {
                    $attr[$k] = (int) $v;
                } elseif ($a['type'] === 'select' && in_array($v, $a['options'], true)) {
                    $attr[$k] = $v;
                } elseif ($a['type'] === 'toggle' && $v === '1') {
                    $attr[$k] = '1';
                }
            }
        }
        return [
            'c' => preg_match('/^[a-z0-9-]{1,90}$/', (string) ($_GET['c'] ?? '')) ? (string) $_GET['c'] : '',
            'q' => mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60),
            'location' => mb_substr(trim((string) ($_GET['location'] ?? '')), 0, 120),
            'min' => $money($_GET['min'] ?? ''), 'max' => $money($_GET['max'] ?? ''),
            'a' => $attr,
            'sort' => in_array($_GET['sort'] ?? '', ['new', 'price_asc', 'price_desc'], true) ? (string) $_GET['sort'] : '',
        ];
    }

    private function index(): void
    {
        $cfg = $this->svc->config();
        $f = $this->filters();
        $cat = null;
        if ($f['c'] !== '') {
            $cat = $this->svc->categoryBySlug($f['c']);
            if (!$cat) {
                $this->notFound();
                return;
            }
        }
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $r = $this->svc->search(['category' => $cat['id'] ?? null, 'q' => $f['q'], 'location' => $f['location'], 'min_price' => $f['min'], 'max_price' => $f['max'],
            'attr' => $f['a'], 'sort' => $f['sort'], 'limit' => self::PER_PAGE, 'offset' => ($page - 1) * self::PER_PAGE]);
        $pages = max(1, (int) ceil($r['total'] / self::PER_PAGE));
        $filtered = $f['q'] !== '' || $f['location'] !== '' || $f['min'] !== null || $f['max'] !== null || $f['a'] || $page > 1;
        $query = array_filter(['c' => $f['c'], 'q' => $f['q'], 'location' => $f['location'], 'min' => $f['min'] !== null ? $f['min'] / 100 : null,
            'max' => $f['max'] !== null ? $f['max'] / 100 : null, 'a' => $f['a'] ?: null, 'sort' => $f['sort']], static fn ($v) => $v !== null && $v !== '');
        $this->render('index', $cat['name'] ?? $cfg['plural'], [
            'items' => $r['rows'], 'total' => $r['total'], 'category' => $cat, 'categories' => $this->svc->categories(), 'locations' => $this->svc->locations(),
            'filters' => $f, 'query' => $query, 'pageNo' => min($page, $pages), 'pages' => $pages, 'config' => $cfg,
        ], (string) ($cat['description'] ?? ''), $filtered);
    }

    private function detail(string $slug): void
    {
        $item = $this->svc->bySlug($slug);
        if (!$item) {
            $this->notFound();
            return;
        }
        $similar = $this->svc->search(['category' => $item['category_id'], 'exclude' => $item['id'], 'include_closed' => false, 'limit' => 3])['rows'];
        $this->render('detail', (string) $item['title'], ['item' => $item, 'similar' => $similar, 'config' => $this->svc->config()],
            (string) ($item['short_description'] ?? ''), false, $this->svc->config()['path'] . '/' . $slug);
    }

    private function render(string $view, string $title, array $vars, string $description, bool $noindex, ?string $slug = null): void
    {
        $page = ['id' => 0, 'title' => $title, 'slug' => $slug ?? $this->svc->config()['path'], 'page_type' => 'listings', 'is_home' => 0, 'status' => 'active',
            'seo_title' => $title, 'seo_description' => mb_substr($description, 0, 300), 'seo_keywords' => '', 'robots' => $noindex ? 'noindex' : null];
        $v = $this->renderer->view($page);
        $custom = $this->ctx['theme_dir'] . '/listings/' . $view . '.php';
        $body = $v->capture(is_file($custom) ? $custom : __DIR__ . '/views/' . $view . '.php', $vars);
        $e = static fn ($s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $content = '<link rel="stylesheet" href="' . $e(($this->ctx['asset'])('listings/listings.css')) . '">' . $body
            . '<script src="' . $e(($this->ctx['asset'])('listings/listings.js')) . '"></script>';
        $out = $this->renderer->renderContent($page, $content, 200);
        http_response_code($out['status']);
        header('Content-Type: text/html; charset=utf-8');
        echo $out['html'];
    }

    private function notFound(): void
    {
        $page = ['id' => 0, 'title' => 'Not found', 'slug' => $this->svc->config()['path'], 'page_type' => 'listings', 'is_home' => 0, 'status' => 'active', 'seo_title' => 'Not found', 'seo_description' => '', 'seo_keywords' => '', 'robots' => 'noindex'];
        $cfg = $this->svc->config();
        $out = $this->renderer->renderContent($page, '<section class="wb-section" style="padding:6rem 1rem;text-align:center"><div class="container"><h1>Not found</h1><p>This ' . htmlspecialchars(strtolower($cfg['singular']), ENT_QUOTES, 'UTF-8')
            . ' is no longer available.</p><p><a href="' . htmlspecialchars(($this->ctx['url'])(''), ENT_QUOTES, 'UTF-8') . '">See all ' . htmlspecialchars(strtolower($cfg['plural']), ENT_QUOTES, 'UTF-8') . '</a></p></div></section>', 404);
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo $out['html'];
    }
}
