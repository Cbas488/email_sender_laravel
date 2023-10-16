<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\StoreUser;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Exception;
use Faker\Core\Number;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Exceptions\HttpResponseException;

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

    public function regenerateVerificationToken(Number $id){
        /*try{
            if(!is_numeric($id)){
                
            }
        } catch(){

        }*/
    }

    /**
     * Store a newly created User on Database.
     */
    public function store(StoreUserRequest $request)
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
                500,
                [$error -> getMessage()]
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
