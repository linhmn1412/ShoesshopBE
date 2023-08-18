<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Customer;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

    public function register(Request $request)
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
            'phone_number.unique' => 'Số điện thoại đã tồn tại.',
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
        if ($user) {
            Customer::create([
                'id_customer' => $user->id_user,
                'point' => 0,
            ]);
        }




        return response()->json(['message' => 'Đăng ký tài khoản thành công'], 201);
    }

    public function sendMail(Request $request)
    {
        $email = $request->email;

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy người dùng phù hợp'], 201);
        }
        $tmpPassword = Str::random(6); 
        $tmpPassword = preg_replace("/[^A-Za-z0-9]/", "", $tmpPassword); 
        User::where('email', $email)->update([
            'password' =>  Hash::make($tmpPassword),
        ]);
        $text = "
        Xin chào $user->fullname,

        Chúng tôi nhận được yêu cầu thiết lập lại mật khẩu cho tài khoản Shoe Shop của bạn.

        Mật khẩu mới của bạn là: $tmpPassword";
        Mail::raw($text, function (Message $message) use ($email) {
            $message->to($email)->subject('Thiết lập mật khẩu mới đăng nhập Shoe Shop');
        });

        return response()->json(['message' => 'Mật khẩu đã gửi đến email của bạn.'],200);
    }

    public function getAllStaffs (Request $request){
        $user = $request->user();
        if($user->id_role === 1){
            $staffs = User::join('staff','staff.id_staff','=','id_user')->paginate(6);
            return response()->json($staffs);
        }
    }
    public function createStaff(Request $request)
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
            'phone_number.unique' => 'Số điện thoại đã tồn tại.',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 201);
        }
        if ($admin->id_role === 1) {
            $user = User::create([
                'fullname' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'id_role' => 2
            ]);
            if ($user) {
                Staff::create([
                    'id_staff' => $user->id_user,
                    'status' => true,
                ]);
            }
            return response()->json(['message' => 'Đăng ký tài khoản thành công'], 200);
        }
        return response()->json(['message' => 'Nguời dùng không có quyền truy cập'], 202);
    }

    public function updateStaff(Request $request, $id)
    {
        $user = $request->user();
        if ($user->id_role === 1) {
            Staff::where('id_staff', $id)->update([
                'salary' => $request->salary,
                'status' => $request->status
            ]);
            return response()->json(['message' => 'Cập nhật tài khoản nhân viên thành công'], 200);
        }
    }

    public function updateAccount (Request $request)
    {
        $user = $request->user(); 

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', Rule::unique('user')->ignore($user->id_user, 'id_user')],
            'username' => ['required', Rule::unique('user')->ignore($user->id_user, 'id_user')],
            'phone_number' => ['required', 'numeric', 'digits:10', Rule::unique('user')->ignore($user->id_user, 'id_user')],
        ], [
            'email.unique' => 'Email đã tồn tại.',
            'username.unique' => 'Tên đăng nhập đã tồn tại.',
            'phone_number.unique' => 'Số điện thoại đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 201);
        }

        // Cập nhật thông tin tài khoản
        $user->email = $request->email;
        $user->username =trim( $request->username);
        $user->phone_number = trim($request->phone_number);
        $user->address = trim($request->address);
        $user->birth = $request->birth;
        $user->fullname = trim($request->fullname);
        $user->gender = $request->gender;

        $user->save();

        return response()->json(['message' => 'Thông tin tài khoản đã được cập nhật',
        "user" => $user],200);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user(); 

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'confirm_password.required' => 'Vui lòng xác nhận mật khẩu mới.',
            'confirm_password.same' => 'Mật khẩu xác nhận không khớp.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 201);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['errors' => ['current_password' => ['Mật khẩu hiện tại không chính xác.']]], 202);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Mật khẩu đã được thay đổi thành công',
    ],200);
    }
}
