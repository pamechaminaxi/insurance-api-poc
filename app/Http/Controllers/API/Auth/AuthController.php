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

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {

        try {
            $data = $this->authService->register($request);
            return ApiResponse::success('User registered', $data, 201);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $data = $this->authService->login($request);
            
            ActivityLog::log('Login', auth()->user());

            return ApiResponse::success('Login successful', $data);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(),401);
        }
    }

    public function logout()
    {
        try {
            $this->authService->logout(auth()->user());
            return ApiResponse::success('Logged out successfully');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function profile()
    {
        try {
            return ApiResponse::success('User profile', auth()->user());
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $data = $this->authService->forgotPassword($request);

            return ApiResponse::success('Reset token generated', $data);

        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

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
