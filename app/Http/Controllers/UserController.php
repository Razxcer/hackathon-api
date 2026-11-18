<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    // Список всех пользователей (для модератора и админа)
    public function index()
    {
        return response()->json(User::select('id', 'name', 'email', 'role', 'created_at')->get());
    }

    // Модератор переводит гостя в работники
    public function promoteToWorker(User $user)
    {
        if ($user->role !== 'guest') {
            return response()->json(['message' => 'Можно повысить только пользователя с ролью guest'], 422);
        }

        $user->update(['role' => 'worker']);

        return response()->json(['message' => 'Пользователь назначен работником', 'user' => $user]);
    }

    // Админ назначает любую роль
    public function changeRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => 'required|in:guest,worker,moderator,admin',
        ]);

        $user->update(['role' => $data['role']]);

        return response()->json(['message' => 'Роль обновлена', 'user' => $user]);
    }
}
