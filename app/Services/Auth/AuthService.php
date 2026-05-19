<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\PasswordReset;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthService
{
    // register user method
    public function register($request)
    {
        $role = Role::where('name', $request->role)->firstOrFail();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id
        ]);

        $token = $user->createToken('API Token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    // login user method
    public function login($request)
    {
        if (!Auth::attempt($request->only('email','password'))) {
            throw new \Exception('Invalid credentials');
        }

        $user = Auth::user();
        $token = $user->createToken('API Token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    // logout user method
    public function logout($user)
    {
        $user->tokens()->delete();
    }

    // forgot password method
    public function forgotPassword($request)
    {
        // generate token
        $token = Str::random(60);

        // delete old tokens
        PasswordReset::where('email', $request->email)->delete();

        // store new token
        PasswordReset::create([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        return [
            'email' => $request->email,
            'reset_token' => $token
        ];
    }

    // reset password method
    public function resetPassword($request)
    {
        $record = PasswordReset::where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            throw new \Exception('Invalid token');
        }

        if ($record->created_at->addMinutes(60)->isPast()) {
            throw new \Exception('Token expired');
        }

        // update password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // delete token after use
        PasswordReset::where('email', $request->email)->delete();

        return true;
    }


}
