<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Helpers\ApiResponse;
use Exception;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\ActivityLog;


class AuthController extends Controller
{
    protected $authService;

    // Inject dependencies through constructor method for better testability and maintainability. 
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    //register user method with validation [RegisterRequest] using service
    public function register(RegisterRequest $request)
    {

        try {
            $data = $this->authService->register($request);
            return ApiResponse::success('User registered', $data, 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    //login method with validation [LoginRequest] using service and login user
    public function login(LoginRequest $request)
    {
        try {
            $data = $this->authService->login($request);

            ActivityLog::log("User logged in successfully (Email: " . auth()->user()->email . ")");

            return ApiResponse::success('Login successful', $data);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(),401);
        }
    }

    //logout method using service
    public function logout()
    {
        try {
            $this->authService->logout(auth()->user());
            return ApiResponse::success('Logged out successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    //get user profile method using service
    public function profile()
    {
        try {
            return ApiResponse::success('User profile', auth()->user());
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    //forgot password method with validation [ForgotPasswordRequest] using service and return reset token
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $data = $this->authService->forgotPassword($request);

            return ApiResponse::success('Reset token generated', $data);

        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    //reset password method with validation [ResetPasswordRequest] using service and reset password
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $this->authService->resetPassword($request);

            return ApiResponse::success('Password reset successfully');

        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
