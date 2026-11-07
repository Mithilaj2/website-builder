<?php
declare(strict_types=1);

namespace App\Modules\Clients;

use App\Core\Activity;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;

final class ClientController
{
    private const SORTS = ['business_name' => 'c.business_name', 'contact_person' => 'c.contact_person', 'websites' => 'websites', 'created_at' => 'c.created_at', 'status' => 'c.status'];

    public function index(Request $request): void
    {
        View::render('clients/index', [
            'title' => 'Clients',
            'active' => 'clients',
            'scripts' => ['assets/js/pages/clients.js'],
        ]);
    }

    public function list(Request $request): void
    {
        $where = ['1=1'];
        $params = [];
        if ($q = $request->str('q')) {
            $where[] = '(c.business_name LIKE :q1 OR c.contact_person LIKE :q2 OR c.email LIKE :q3 OR c.phone LIKE :q4)';
            foreach (['q1', 'q2', 'q3', 'q4'] as $k) {
                $params[$k] = Database::like($q);
            }
        }
        if (in_array($request->str('status'), ['active', 'inactive'], true)) {
            $where[] = 'c.status = :status';
            $params['status'] = $request->str('status');
        }
        $sort = self::SORTS[$request->str('sort')] ?? 'c.created_at';
        $dir = $request->str('dir') === 'asc' ? 'ASC' : 'DESC';
        Response::json(Database::paginate(
            'c.id, c.business_name, c.contact_person, c.email, c.phone, c.status, c.created_at,
             (SELECT COUNT(*) FROM wb_websites w WHERE w.client_id = c.id) AS websites',
            'FROM wb_clients c WHERE ' . implode(' AND ', $where),
            $params,
            "$sort $dir, c.id DESC",
            $request->int('page', 1),
            $request->int('per_page', 15)
        ));
    }

    public function show(Request $request): void
    {
        $client = $this->find($request->params['id']);
        $websites = Database::fetchAll(
            'SELECT w.id, w.name, w.domain, w.site_code, w.status, w.folder_path, w.table_prefix, w.created_at,
                    cat.name AS category, t.name AS template
             FROM wb_websites w JOIN wb_categories cat ON cat.id = w.category_id LEFT JOIN wb_templates t ON t.id = w.template_id
             WHERE w.client_id = ? ORDER BY w.created_at DESC',
            [$client['id']]
        );
        $activity = Database::fetchAll(
            'SELECT a.action, a.details, a.created_at, u.name AS user FROM wb_activity_log a LEFT JOIN wb_users u ON u.id = a.user_id
             WHERE a.entity_type = \'client\' AND a.entity_id = ? ORDER BY a.id DESC LIMIT 20',
            [$client['id']]
        );
        View::render('clients/show', [
            'title' => $client['business_name'],
            'active' => 'clients',
            'client' => $client,
            'websites' => $websites,
            'activity' => $activity,
            'scripts' => ['assets/js/pages/clients.js'],
        ]);
    }

    public function json(Request $request): void
    {
        Response::json(['ok' => true, 'data' => $this->find($request->params['id'])]);
    }

    public function store(Request $request): void
    {
        $data = $this->validated($request);
        $data['created_by'] = \App\Core\Auth::id();
        $data['created_at'] = $data['updated_at'] = now();
        $id = Database::insert('wb_clients', $data);
        Activity::log('client.created', 'client', $id, $data['business_name']);
        Response::ok('Client added.', ['id' => $id]);
    }

    public function update(Request $request): void
    {
        $client = $this->find($request->params['id']);
        $data = $this->validated($request);
        $data['updated_at'] = now();
        Database::update('wb_clients', $data, (int) $client['id']);
        Activity::log('client.updated', 'client', (int) $client['id'], $data['business_name']);
        Response::ok('Client updated.');
    }

    public function toggleStatus(Request $request): void
    {
        $client = $this->find($request->params['id']);
        $new = $client['status'] === 'active' ? 'inactive' : 'active';
        Database::update('wb_clients', ['status' => $new, 'updated_at' => now()], (int) $client['id']);
        Activity::log('client.' . ($new === 'active' ? 'activated' : 'deactivated'), 'client', (int) $client['id'], $client['business_name']);
        Response::ok('Client ' . ($new === 'active' ? 'activated.' : 'deactivated.'));
    }

    public function destroy(Request $request): void
    {
        $client = $this->find($request->params['id']);
        $sites = (int) Database::value('SELECT COUNT(*) FROM wb_websites WHERE client_id = ?', [$client['id']]);
        if ($sites > 0) {
            Response::fail("This client has $sites website(s). Archive or delete the websites first, or deactivate the client instead.");
        }
        Database::delete('wb_clients', (int) $client['id']);
        Activity::log('client.deleted', 'client', (int) $client['id'], $client['business_name']);
        Response::ok('Client deleted.', ['redirect' => $request->str('from') === 'show' ? url('clients') : null]);
    }

    private function validated(Request $request): array
    {
        $v = Validator::make($request->all(), [
            'business_name' => 'required|max:190',
            'contact_person' => 'max:120',
            'email' => 'email|max:190',
            'phone' => 'phone',
            'address' => 'max:1000',
            'notes' => 'max:5000',
            'status' => 'required|in:active,inactive',
        ], ['business_name' => 'Client / business name']);
        if ($v->fails()) {
            Response::fail('Please correct the highlighted fields.', $v->errors());
        }
        $out = [];
        foreach (['business_name', 'contact_person', 'email', 'phone', 'address', 'notes', 'status'] as $k) {
            $out[$k] = $request->str($k) !== '' ? $request->str($k) : null;
        }
        $out['email'] = $out['email'] ? strtolower($out['email']) : null;
        return $out;
    }

    private function find(int $id): array
    {
        $c = Database::fetch('SELECT * FROM wb_clients WHERE id = ?', [$id]);
        if (!$c) {
            Response::abort(404, 'Client not found.');
        }
        return $c;
    }
}
