<?php
declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Permissions;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;

/** User management — Super Admin only (permission users.manage). */
final class UserController
{
    private const SORTS = ['name' => 'name', 'email' => 'email', 'role' => 'role', 'last_login_at' => 'last_login_at', 'created_at' => 'created_at'];

    public function index(Request $request): void
    {
        View::render('users/index', [
            'title' => 'Users & Roles',
            'active' => 'users',
            'roles' => Permissions::ROLES,
            'scripts' => ['assets/js/pages/users.js'],
        ]);
    }

    public function list(Request $request): void
    {
        $where = ['1=1'];
        $params = [];
        if ($q = $request->str('q')) {
            $where[] = '(name LIKE :q1 OR email LIKE :q2)';
            $params = ['q1' => Database::like($q), 'q2' => Database::like($q)];
        }
        if (array_key_exists($request->str('role'), Permissions::ROLES)) {
            $where[] = 'role = :role';
            $params['role'] = $request->str('role');
        }
        if (in_array($request->str('status'), ['active', 'inactive'], true)) {
            $where[] = 'status = :status';
            $params['status'] = $request->str('status');
        }
        $sort = self::SORTS[$request->str('sort')] ?? 'created_at';
        $dir = $request->str('dir') === 'desc' ? 'DESC' : 'ASC';
        $result = Database::paginate(
            'id, name, email, role, status, last_login_at, last_login_ip, created_at',
            'FROM wb_users WHERE ' . implode(' AND ', $where),
            $params,
            "$sort $dir, id ASC",
            $request->int('page', 1),
            $request->int('per_page', 15)
        );
        $me = Auth::id();
        foreach ($result['rows'] as &$r) {
            $r['is_me'] = (int) $r['id'] === $me;
        }
        Response::json($result);
    }

    public function store(Request $request): void
    {
        $data = $this->validated($request, null, true);
        $data['password_hash'] = password_hash((string) $_POST['password'], PASSWORD_DEFAULT);
        $data['created_at'] = $data['updated_at'] = now();
        $id = Database::insert('wb_users', $data);
        Activity::log('user.created', 'user', $id, $data['email'] . ' (' . $data['role'] . ')');
        Response::ok('User created.');
    }

    public function update(Request $request): void
    {
        $user = $this->find($request->params['id']);
        $data = $this->validated($request, (int) $user['id'], false);

        if ((int) $user['id'] === Auth::id() && ($data['role'] !== 'super_admin' || $data['status'] !== 'active')) {
            Response::fail('You cannot remove your own Super Admin role or deactivate yourself.');
        }
        if ($user['role'] === 'super_admin' && ($data['role'] !== 'super_admin' || $data['status'] !== 'active') && $this->activeSuperAdmins() <= 1) {
            Response::fail('At least one active Super Admin must remain.');
        }
        if (!empty($_POST['password'])) {
            $data['password_hash'] = password_hash((string) $_POST['password'], PASSWORD_DEFAULT);
        }
        $data['updated_at'] = now();
        Database::update('wb_users', $data, (int) $user['id']);
        Activity::log('user.updated', 'user', (int) $user['id'], $data['email'] . ' (' . $data['role'] . ')' . (!empty($_POST['password']) ? ' + password reset' : ''));
        Response::ok('User updated.');
    }

    public function destroy(Request $request): void
    {
        $user = $this->find($request->params['id']);
        if ((int) $user['id'] === Auth::id()) {
            Response::fail('You cannot delete your own account.');
        }
        if ($user['role'] === 'super_admin' && $this->activeSuperAdmins() <= 1) {
            Response::fail('At least one active Super Admin must remain.');
        }
        Database::delete('wb_users', (int) $user['id']);
        Activity::log('user.deleted', 'user', (int) $user['id'], $user['email']);
        Response::ok('User deleted.');
    }

    private function validated(Request $request, ?int $ignoreId, bool $passwordRequired): array
    {
        $input = $request->all();
        $input['email'] = strtolower($request->str('email'));
        $input['password'] = (string) ($_POST['password'] ?? '');
        $input['password_confirmation'] = (string) ($_POST['password_confirmation'] ?? '');
        $v = Validator::make($input, [
            'name' => 'required|max:120',
            'email' => 'required|email|max:190|unique:wb_users,email' . ($ignoreId ? ",$ignoreId" : ''),
            'role' => 'required|in:' . implode(',', array_keys(Permissions::ROLES)),
            'status' => 'required|in:active,inactive',
            'password' => ($passwordRequired ? 'required|' : '') . 'password|confirmed',
        ]);
        if ($v->fails()) {
            Response::fail('Please correct the highlighted fields.', $v->errors());
        }
        return ['name' => $request->str('name'), 'email' => $input['email'], 'role' => $request->str('role'), 'status' => $request->str('status')];
    }

    private function activeSuperAdmins(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM wb_users WHERE role = 'super_admin' AND status = 'active'");
    }

    private function find(int $id): array
    {
        $u = Database::fetch('SELECT * FROM wb_users WHERE id = ?', [$id]);
        if (!$u) {
            Response::abort(404, 'User not found.');
        }
        return $u;
    }
}
