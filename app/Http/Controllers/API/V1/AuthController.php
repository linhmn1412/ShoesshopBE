<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Customer;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
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
     
            $user = User::create([
                'fullname' => $request->fullname,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'id_role' => 3
            ]);
            if($user){
                Customer::create([
                    'id_customer' => $user->id_user,
                    'point' => 0,
                ]);
            }
    
        
        

        return response()->json(['message' => 'Đăng ký tài khoản thành công'], 201);
    }

    public function getAllStaffs (Request $request){
        $user = $request->user();
        if($user->id_role === 1){
            $staffs = User::join('staff','staff.id_staff','=','id_user')->paginate(6);
            return response()->json($staffs);
        }
    }

    public function createStaff (Request $request)
    {
        $admin = $request->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required',
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
        if($admin->id_role === 1){
            $user = User::create([
                'fullname' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'id_role' => 2
            ]);
            if($user){
                Staff::create([
                    'id_staff' => $user->id_user,
                    'status' => true,
                ]);
            }
            return response()->json(['message' => 'Đăng ký tài khoản thành công'], 200);
        }
        return response()->json(['message' => 'Nguời dùng không có quyền truy cập'], 202);
    }

    public function updateStaff (Request $request, $id) {
        $user = $request->user();
        if($user->id_role === 1){
            Staff::where('id_staff', $id)->update([
                'salary' => $request->salary,
                'status' => $request->status
            ]);
            return response()->json(['message' => 'Cập nhật tài khoản nhân viên thành công'], 200);
        }
        
    }
    
}
