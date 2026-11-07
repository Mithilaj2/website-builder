<?php
declare(strict_types=1);

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;

final class AuthController
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect('');
        }
        View::render('auth/login', ['title' => 'Sign in'], 'auth');
    }

    public function login(Request $request): void
    {
        $v = Validator::make($request->all(), ['email' => 'required|email|max:190', 'password' => 'required|max:200']);
        if ($v->fails()) {
            Response::fail('Please enter your email and password.', $v->errors());
        }
        $result = Auth::attempt($request->str('email'), (string) ($_POST['password'] ?? ''), $request->ip());
        if (!$result['ok']) {
            Response::fail($result['message'], [], str_starts_with($result['message'], 'Too many') ? 429 : 422);
        }
        Response::ok($result['message'], ['redirect' => url('')]);
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Response::ok('You have been logged out.', ['redirect' => url('login')]);
    }
}
