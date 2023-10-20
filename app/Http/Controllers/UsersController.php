<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\GetUser;
use App\Http\Resources\StoreUser;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Doctrine\Common\Cache\Psr6\InvalidArgument;
use Exception;
use Faker\Core\Number;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created User in Database.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $data = $request -> all();
            $data['password'] = Hash::make($data['password']);
            $data['verification_token'] = Str::random(60);

            $user = new StoreUser(User::create($data));
            return ApiResponse::success(
                'User created correctly.',
                JsonResponse::HTTP_CREATED,
                $user
            );
        } catch (Exception $error){
            return ApiResponse::fail(
                'An error has occurred, try again or contact the administrator.',
                errors: [$error -> getMessage()]
            );
        }
    }

    /**
     * Display the specified User.
     */
    public function show(string $id): JsonResponse
    {
        try{
            if(!is_numeric($id)){ throw new InvalidArgument('The ID must be numeric.'); }

            $user = new GetUser(User::findOrFail($id));

            return ApiResponse::success(data: $user);
        } catch(InvalidArgument $error){
            return ApiResponse::fail(
                'Validation error.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                [$error -> getMessage()]
            );
        } catch(ModelNotFoundException $error){
            return ApiResponse::fail(
                'User not found.',
                JsonResponse::HTTP_NOT_FOUND,
                [$error -> getMessage()]
            );
        } catch(Exception $error){
            return ApiResponse::fail(
                'An error has occurred, try again or contact the administrator.',
                errors: [$error -> getMessage()]
            );
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        
    }

    /**
     * Remove a specified user from database.
     */
    public function destroy(string $id): JsonResponse
    {
        try{
            if(!is_numeric($id)){ throw new InvalidArgument('The ID must be numeric.'); }

            $user = User::findOrFail($id);
            $user -> delete();
            return response() -> noContent();
        } catch(InvalidArgument $error){
            return ApiResponse::fail(
                'Validation error.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                [$error -> getMessage()]
            );
        } catch(ModelNotFoundException $error){
            return ApiResponse::fail(
                'User not found.',
                JsonResponse::HTTP_NOT_FOUND,
                [$error -> getMessage()]
            );
        } catch(Exception $error){
            return ApiResponse::fail(
                'An error has occurred, try again or contact the administrator.',
                errors: [$error -> getMessage()]
            );
        }
    }

    /**
     * Regenerate verification token of a user
     */
    public function regenerateVerificationToken(string $id): JsonResponse
    {
        try{
            if(!is_numeric($id)){ throw new InvalidArgument('The ID must be numeric.'); }

            $user = User::findOrFail($id);
            $user -> update(['verification_token' => Str::random(60)]);

            return response() -> noContent();
        } catch(InvalidArgument $error){
            return ApiResponse::fail(
                'Validation error.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                [$error -> getMessage()]
            );
        } catch(ModelNotFoundException $error){
            return ApiResponse::fail(
                'User not found.',
                JsonResponse::HTTP_NOT_FOUND,
                [$error -> getMessage()]
            );
        } catch(Exception $error){
            return ApiResponse::fail(
                'An error has occurred, try again or contact the administrator.',
                errors: [$error -> getMessage()]
            );
        }
    }

    /**
     * Change a user's password to a new one
     */
    public function changePassword(ChangePasswordUserRequest $request, string $id): JsonResponse    
    {
        try{
            if(!is_numeric($id)){ throw new InvalidArgument('The ID must be numeric'); }

            $user = User::findOrFail($id);

            if(!Hash::check($request -> old_password, $user -> password)){
                throw new AuthorizationException('The old password does not match the one provided.');
            } 

            $user -> update(['password' => Hash::make($request -> new_password)]);
            return response() -> noContent();
        } catch(InvalidArgument $error){
            return ApiResponse::fail(
                'Validation error',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                [$error -> getMessage()]
            );
        } catch(ModelNotFoundException $error){
            return ApiResponse::fail(
                'Not found',
                JsonResponse::HTTP_NOT_FOUND,
                [$error -> getMessage()]
            );
        } catch(AuthorizationException $error){
            return ApiResponse::fail(
                'Unauthorized',
                JsonResponse::HTTP_UNAUTHORIZED,
                errors: [$error -> getMessage()]
            );
        } catch(Exception $error) {
            return ApiResponse::fail(
                'An error has occurred, try again or contact the administrator.',
                errors: [$error -> getMessage()]
            );
        }
    }
}
