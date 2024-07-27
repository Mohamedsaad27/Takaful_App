<?php

namespace App\Http\Controllers;

use App\Models\Company\Company;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{
    use ApiResponseTrait;
    public function register(Request $request){
        try {
            $validator = Validator::make($request->all(),[
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'nick_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'required|string|in:user,company',
                'phone' => 'required', 'string', 'max:15|unique:users,phone',
            ]);
            if ($validator->fails()){
                return response()->json($validator->errors(), 422);
            }
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'nick_name' => $request->nick_name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role'=>$request->role,
                'phone'=>$request->phone,
            ]);
            if ($request->role == 'company')
            {
                Company::create([
                    'user_id' => $user->id
                ]);
            }
            $token = JWTAuth::fromUser($user);
            $user['token'] = $token;
            return  $this->successResponse($user,'User Registered Successfully',201);
        }catch (QueryException $exception) {
            if ($exception->errorInfo[1] === 1062) {
                return $this->errorResponse(['message' => 'enter valid phone number'], 409);
            }
            return $this->errorResponse(['message' => $exception->getMessage()], 500);
        }catch (\Exception $exception){
            return $this->errorResponse(['message'=>$exception->getMessage()],500);
        }
    }
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required','email'],
                'password' => ['required','string'],
            ]);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(),422);
            }

            if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
                return $this->errorResponse('Invalid email or Password',401);
            }
            $user = auth()->user();
            if($user->role == 'company'){
                $user['data'] = $user->company;
            }
            unset($user['data']);
            return $this->successResponse([
                'user' => $user,
                'token' => $token,
            ], 'User logged in successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(),500);
        }
    }
    public function logout(Request $request){
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->successResponse(null, 'User successfully logged out', 200);
        }catch (\Exception $exception){
            return $this->errorResponse(['message'=>$exception->getMessage()],500);
        }
    }

}