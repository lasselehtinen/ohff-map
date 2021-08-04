<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Reference;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Create a new user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\UserResource
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'callsign' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = collect($validator->errors()->toArray())->map(function ($errors, $field) use ($request) {
                return [
                    'status' => '422',
                    'source' => ['pointer' => $request->url()],
                    'title' => 'Invalid Attribute',
                    'detail' => $errors[0],
                ];
            });

            return response()->json([
                'errors' => [
                    $errors->values()->toArray(),
                ]
            ], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'callsign' => $request->callsign,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return new UserResource($user);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\UserResource
     */
    public function show(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '403',
                        'source' => ['pointer' => $request->url()],
                        'title' => 'Forbidden',
                        'detail' => 'You are only allowed to view your own user information.',
                    ],
                ],
            ], 403);
        }

        return new UserResource($request->user());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\UserResource
     */
    public function update(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '403',
                        'source' => ['pointer' => $request->url()],
                        'title' => 'Forbidden',
                        'detail' => 'You are only allowed to edit your own user information.',
                    ],
                ],
            ], 403);
        }

        $request->user()->update($request->all());

        return new UserResource($request->user());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        return 'user deleted';
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  \App\Models\Reference  $reference
     * @return \Illuminate\Http\JsonResponse
     */
    public function userActivation(Request $request, User $user, Reference $reference)
    {
        if ($request->user()->id !== $user->id) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '403',
                        'source' => ['pointer' => $request->url()],
                        'title' => 'Forbidden',
                        'detail' => 'You are only allowed to edit your own user information.',
                    ],
                ],
            ], 403);
        }

        $user->activations()->attach($reference, ['activation_date' => '2021-01-01']);

        return response()->json([
            'status' => 'success',
            'message' => 'Reference marked as activated for user.',
        ]);
    }
}
