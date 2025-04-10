<?php

namespace App\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthController
{

    /**
     * Handles user registration by validating the provided request data,
     * creating a user record, and returning a success response.
     *
     * @param Request $request The HTTP request instance containing user input.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response with a success message and user data.
     * @throws \Exception If any error occurs during user creation or validation.
     *
     */
    public function register(Request $request)
    {
        try {
            $data = $request->validate([
                'username' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|same:password',
            ]);


            // Create user
            $user = User::create([
                'name' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            return response()->json([
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user
                ]
            ], 200);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Authenticates a user by validating the provided request data,
     * checking credentials, and generating an authentication token.
     *
     * @param Request $request The HTTP request instance containing login credentials.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response with a success message, user data, and a new authentication token.
     * @throws AuthenticationException If the provided credentials are invalid.
     * @throws \Exception If any other error occurs during authentication.
     *
     */
    public function login(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|string|email|max:255',
                'password' => 'string|min:8',
            ]);
            $user = User::where('email', $data['email'])->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                throw new AuthenticationException();
            }

            // Delete all existing tokens for the user
            $user->tokens()->delete();

            return response()->json([
                'message' => 'User logged in successfully',
                'data' => [
                    'user' => $user,
                    'token' => $user->createToken('auth_token')->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_in' => config('sanctum.expiration') * 60]
            ], 200);

        } catch (\Exception $e) {
            throw $e;
        }
    }

}
