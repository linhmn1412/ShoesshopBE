<?php

namespace App\Http\Controllers\API\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends RoutingController
{
    public function login(Request $request)
    {
        $request->validate([
            'usernameOrEmailOrPhone' => 'required',
            'password' => 'required',
        ]);

        $credentials = $request->only(['usernameOrEmailOrPhone', 'password']);

        // Attempt to find the user by email, username, or phone number
        $user = User::where(function ($query) use ($credentials) {
            $query->where('email', $credentials['usernameOrEmailOrPhone'])
                  ->orWhere('username', $credentials['usernameOrEmailOrPhone'])
                  ->orWhere('phone_number', $credentials['usernameOrEmailOrPhone']);
        })->first();

        // If the user is not found or the password is incorrect, throw an exception
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'message' => ['Login failed'],
            ]);
        }

        // Authenticate the user and generate the access token
        $token = $user->createToken('API Token')->plainTextToken;
        return response()->json(['user' => $user, 'access_token' => $token]);
    }

    public function register (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required',
            'email' => 'required | email | unique:user',
            'phone_number' => ['required', 'numeric', 'digits:10', 'unique:user'],
            'username' => 'required | unique:user',
            'password' => 'required | min:6 ',
        ], [
            'email.unique' => 'Email đã tồn tại.',
            'username.unique' => 'Tên đăng nhập đã tồn tại.',
            'phone_number.unique'=> 'Số điện thoại đã tồn tại.',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 201);
        }
        User::create([
            'fullname' => $request->fullname,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'id_role' => 3
        ]);
        return response()->json(['message' => 'Đăng ký tài khoản thành công'], 201);
    }
    
}
