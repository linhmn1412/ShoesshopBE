<?php

namespace App\Http\Controllers\API\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends RoutingController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

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

    
}
