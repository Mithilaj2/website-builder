<?php
declare(strict_types=1);

namespace App\Modules\Activity;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

final class ActivityController
{
    private const TYPES = ['user', 'category', 'template', 'client', 'website'];

    public function index(Request $request): void
    {
        View::render('activity/index', [
            'title' => 'Activity Log',
            'active' => 'activity',
            'types' => self::TYPES,
            'users' => Database::fetchAll('SELECT id, name FROM wb_users ORDER BY name'),
            'scripts' => ['assets/js/pages/activity.js'],
        ]);
    }

    public function list(Request $request): void
    {
        $where = ['1=1'];
        $params = [];
        if ($q = $request->str('q')) {
            $where[] = '(a.action LIKE :q1 OR a.details LIKE :q2)';
            $params += ['q1' => Database::like($q), 'q2' => Database::like($q)];
        }
        if (in_array($request->str('type'), self::TYPES, true)) {
            $where[] = 'a.entity_type = :type';
            $params['type'] = $request->str('type');
        }
        if ($request->int('user_id') > 0) {
            $where[] = 'a.user_id = :uid';
            $params['uid'] = $request->int('user_id');
        }
        Response::json(Database::paginate(
            'a.id, a.action, a.entity_type, a.entity_id, a.details, a.ip, a.created_at, u.name AS user',
            'FROM wb_activity_log a LEFT JOIN wb_users u ON u.id = a.user_id WHERE ' . implode(' AND ', $where),
            $params,
            'a.id DESC',
            $request->int('page', 1),
            $request->int('per_page', 25)
        ));
    }
}
