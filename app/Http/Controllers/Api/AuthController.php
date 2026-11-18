<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ],
        [
            // Сообщения для имени (name)
            'name.required'     => 'Пожалуйста, укажите ваше имя.',
            'name.string'       => 'Имя должно быть строкой.',
            'name.max'          => 'Имя не должно превышать 255 символов.',

            // Сообщения для почты (email)
            'email.required'    => 'Поле email обязательно для заполнения.',
            'email.email'       => 'Указан некорректный адрес почты.',
            'email.unique'      => 'Этот email уже занят.',

            // Сообщения для пароля (password)
            'password.required' => 'Поле пароля обязательно для заполнения.',
            'password.min'      => 'Пароль должен содержать минимум :min символов.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Неверный логин или пароль'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message'=> 'Выход выполнен']);
    }
}
