<?php
declare(strict_types=1);

namespace App\Modules\Users;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;

/** Every logged-in user can edit their own name/email and change their password. */
final class ProfileController
{
    public function index(Request $request): void
    {
        $user = Database::fetch('SELECT id, name, email, role, last_login_at, last_login_ip, created_at FROM wb_users WHERE id = ?', [Auth::id()]);
        View::render('users/profile', ['title' => 'My profile', 'active' => '', 'user' => $user]);
    }

    public function update(Request $request): void
    {
        $id = (int) Auth::id();
        $input = $request->all();
        $input['email'] = strtolower($request->str('email'));
        $v = Validator::make($input, ['name' => 'required|max:120', 'email' => "required|email|max:190|unique:wb_users,email,$id"]);
        if ($v->fails()) {
            Response::fail('Please correct the highlighted fields.', $v->errors());
        }
        Database::update('wb_users', ['name' => $request->str('name'), 'email' => $input['email'], 'updated_at' => now()], $id);
        Activity::log('profile.updated', 'user', $id);
        Response::ok('Profile updated.');
    }

    public function password(Request $request): void
    {
        $id = (int) Auth::id();
        $current = (string) ($_POST['current_password'] ?? '');
        $hash = (string) Database::value('SELECT password_hash FROM wb_users WHERE id = ?', [$id]);
        if (!password_verify($current, $hash)) {
            Response::fail('Your current password is incorrect.', ['current_password' => 'Current password is incorrect.']);
        }
        $input = ['password' => (string) ($_POST['password'] ?? ''), 'password_confirmation' => (string) ($_POST['password_confirmation'] ?? '')];
        $v = Validator::make($input, ['password' => 'required|password|confirmed'], ['password' => 'New password']);
        if ($v->fails()) {
            Response::fail('Please correct the highlighted fields.', $v->errors());
        }
        Database::update('wb_users', ['password_hash' => password_hash($input['password'], PASSWORD_DEFAULT), 'updated_at' => now()], $id);
        Session::regenerate();
        Activity::log('profile.password_changed', 'user', $id);
        Response::ok('Password changed.');
    }
}
