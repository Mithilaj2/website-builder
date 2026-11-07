<?php
declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

final class DashboardController
{
    private const DEV_STATUSES = "'draft','config_pending','generating','generated','testing','ready','failed'";

    public function index(Request $request): void
    {
        $stats = Database::fetch(
            'SELECT
                (SELECT COUNT(*) FROM wb_clients) AS clients,
                (SELECT COUNT(*) FROM wb_websites) AS websites,
                (SELECT COUNT(*) FROM wb_templates) AS templates,
                (SELECT COUNT(*) FROM wb_templates WHERE status = \'active\') AS templates_active,
                (SELECT COUNT(*) FROM wb_categories) AS categories,
                (SELECT COUNT(*) FROM wb_categories WHERE status = \'active\') AS categories_active,
                (SELECT COUNT(*) FROM wb_websites WHERE status = \'deployed\') AS active_sites,
                (SELECT COUNT(*) FROM wb_websites WHERE status = \'archived\') AS inactive_sites,
                (SELECT COUNT(*) FROM wb_websites WHERE status IN (' . self::DEV_STATUSES . ')) AS dev_sites,
                (SELECT COUNT(*) FROM wb_generation_runs WHERE type IN (\'generate\',\'regenerate\')) AS generations'
        ) ?? [];

        $byCategory = Database::fetchAll(
            'SELECT c.name, c.slug, c.status,
                    (SELECT COUNT(*) FROM wb_templates t WHERE t.category_id = c.id) AS templates,
                    (SELECT COUNT(*) FROM wb_templates t WHERE t.category_id = c.id AND t.status = \'active\') AS active_templates,
                    (SELECT COUNT(*) FROM wb_websites w WHERE w.category_id = c.id) AS websites
             FROM wb_categories c ORDER BY c.sort_order, c.name'
        );

        View::render('dashboard/index', [
            'title' => 'Dashboard',
            'active' => 'dashboard',
            'stats' => array_map('intval', $stats),
            'byCategory' => $byCategory,
            'scripts' => ['assets/js/pages/dashboard.js'],
        ]);
    }

    /** Recently created websites, with search / status filter / pagination. */
    public function recent(Request $request): void
    {
        $where = ['1=1'];
        $params = [];
        if ($q = $request->str('q')) {
            $where[] = '(w.name LIKE :q OR c.business_name LIKE :q2 OR w.domain LIKE :q3)';
            $params += ['q' => Database::like($q), 'q2' => Database::like($q), 'q3' => Database::like($q)];
        }
        $status = $request->str('status');
        if ($status !== '' && in_array($status, ['draft', 'config_pending', 'generating', 'generated', 'testing', 'ready', 'deployed', 'archived', 'failed'], true)) {
            $where[] = 'w.status = :status';
            $params['status'] = $status;
        }
        $result = Database::paginate(
            'w.id, w.name, w.domain, w.status, w.created_at, c.business_name AS client, cat.name AS category, t.name AS template',
            'FROM wb_websites w JOIN wb_clients c ON c.id = w.client_id JOIN wb_categories cat ON cat.id = w.category_id LEFT JOIN wb_templates t ON t.id = w.template_id WHERE ' . implode(' AND ', $where),
            $params,
            'w.created_at DESC, w.id DESC',
            $request->int('page', 1),
            $request->int('per_page', 10)
        );
        Response::json($result);
    }
}
