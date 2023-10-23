<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\GetUser;
use App\Http\Resources\StoreUser;
use App\Http\Responses\ApiResponse;
use App\Mail\ChangeEmailMail;
use App\Models\EmailResetToken;
use App\Models\User;
use Doctrine\Common\Cache\Psr6\InvalidArgument;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

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
    public function store(StoreUserRequest $request)
    {
        try {
            $data = $request -> all();
            $data['password'] = Hash::make($data['password']);
            $data['verification_token'] = Str::random(120);

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
    public function show(string $id)
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
     * Update the specified User in Database.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        $response_return = response() -> noContent();

        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);

            if($user -> email != $request -> email){
                $token = Str::random(120);
                $emailResetTokenData = [
                    'user_id' => $user -> id,
                    'token' => $token,
                    'new_email' => $request -> email,
                    'date_expiration' => Carbon::now() -> addDays(2)
                ];

                if(!EmailResetToken::where('user_id', $user -> id) -> exists()){
                    EmailResetToken::create([
                        'user_id' => $user -> id,
                        'token' => $token,
                        'new_email' => $request -> email,
                        'date_expiration' => Carbon::now() -> addDays(2)
                    ]);
                } else {
                    $actualEmailResetToken = EmailResetToken::where('user_id', $user -> id) -> first();
                    
                    $actualEmailResetToken -> token = $emailResetTokenData['token'];
                    $actualEmailResetToken -> new_email = $emailResetTokenData['new_email'];
                    $actualEmailResetToken -> is_used = TRUE;
                    $actualEmailResetToken -> date_expiration = Carbon::now() -> addDays(2);
                    $actualEmailResetToken -> save();
                }

                Mail::to($request -> email) -> send(new ChangeEmailMail());
                $response_return = ApiResponse::success(
                    'To change the email address an authorization token was sent to the new email address.',
                    JsonResponse::HTTP_ACCEPTED,
                );
            }

            $user -> name = $request -> name;
            $user -> save();
            DB::commit();
            return $response_return;
        } catch(ModelNotFoundException $error){
            DB::rollBack();
            return ApiResponse::fail(
                'User not found.',
                JsonResponse::HTTP_NOT_FOUND,
                [$error -> getMessage()]
            );
        } catch(UniqueConstraintViolationException $error) {
            DB::rollBack();
            return ApiResponse::fail(
                'The selected email has already been taken by someone else to change it as theirs.',
                errors: [$error -> getMessage()]
            );
        } catch(Exception $error){
            dd(get_class($error));
            DB::rollBack();
            return ApiResponse::fail(
                'An error has occurred, try again or contact the administrator.',
                errors: [$error -> getMessage()]
            );
        }
    }

    /**
     * Remove a specified user from database.
     */
    public function destroy(string $id)
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
    public function regenerateVerificationToken(string $id)
    {
        try{
            if(!is_numeric($id)){ throw new InvalidArgument('The ID must be numeric.'); }

            $user = User::findOrFail($id);
            $user -> update(['verification_token' => Str::random(120)]);

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
    public function changePassword(ChangePasswordUserRequest $request, string $id)    
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
