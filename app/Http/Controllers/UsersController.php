<?php

namespace App\Http\Controllers;

use App\Exceptions\PasswordMismatchException;
use App\Exceptions\TokenExpiredException;
use App\Exceptions\TokenHasBeenUsedException;
use App\Http\Requests\ChangeEmailRequest;
use App\Http\Requests\ChangePasswordUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\GetUser;
use App\Http\Resources\StoreUser;
use App\Http\Responses\ApiResponse;
use App\Mail\ChangeEmailMail;
use App\Mail\VerifyAccountMail;
use App\Models\EmailResetToken;
use App\Models\User;
use App\Models\VerificationAccountToken;
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
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Session\TokenMismatchException;
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
        DB::beginTransaction();
        try {
            $data = $request -> all();
            $data['password'] = Hash::make($data['password']);
            $verificationToken = Str::random(71);

            $user = new StoreUser(User::create($data));
            VerificationAccountToken::create([
                'user_id' => $user -> id,
                'token' => Hash::make($verificationToken),
                'expiration' => Carbon::now() -> addDays(2)
            ]);

            Mail::to($request -> email) -> send(new VerifyAccountMail($verificationToken, $request -> email));

            DB::commit();

            return ApiResponse::success(
                'Account created succesfully, you need verify it to access.',
                JsonResponse::HTTP_CREATED,
                $user
            );
        } catch (Exception $error){
            DB::rollBack();
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
                $token = Str::random(71);
                $emailResetTokenData = [
                    'user_id' => $user -> id,
                    'token' => Hash::make($token),
                    'new_email' => $request -> email,
                    'date_expiration' => Carbon::now() -> addDays(2)
                ];

                if(!EmailResetToken::where('user_id', $user -> id) -> exists()){
                    EmailResetToken::create($emailResetTokenData);
                } else {
                    $actualEmailResetToken = EmailResetToken::where('user_id', $user -> id) -> first();
                    
                    $actualEmailResetToken -> token = Hash::make($emailResetTokenData['token']);
                    $actualEmailResetToken -> new_email = $emailResetTokenData['new_email'];
                    $actualEmailResetToken -> is_used = FALSE;
                    $actualEmailResetToken -> date_expiration = Carbon::now() -> addDays(2);
                    $actualEmailResetToken -> save();
                }

                Mail::to($request -> email) -> send(new ChangeEmailMail($token));

                $response_return = ApiResponse::success(
                    'To change the email address an authorization token was sent to the new email address.',
                    JsonResponse::HTTP_ACCEPTED,
                );
            }

            $user -> name = $request -> name ? $request -> name : $user -> name;
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
                JsonResponse::HTTP_CONFLICT,
                errors: [$error -> getMessage()]
            );
        } catch(Exception $error){
            DB::rollBack();
            return ApiResponse::fail(
                'An error has occurred, try again or contact the administrator.',
                errors: [$error -> getMessage()]
            );
        }
    }

    /**
     * Disable specified user from database.
     */
    public function disableAccount(string $id)
    {
        try{
            if(!is_numeric($id)){ throw new InvalidArgument('The ID must be numeric.'); }

            $user = User::withTrashed() -> findOrFail($id);

            if($user -> trashed()){ 
                return ApiResponse::fail(
                    'The account is already disabled.',
                    JsonResponse::HTTP_CONFLICT,
                ); 
            }
            else { $user -> delete(); }

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
     * Enable account disabled
     */
    public function enableAccount(string $id){
        try {
            if(!is_numeric($id)){ throw new InvalidArgument('The ID must be numeric.'); }

            $user = User::withTrashed() -> findOrFail($id);

            if($user -> trashed()){
                $user -> restore();
            } else {
                return ApiResponse::fail(
                    'The account is already enabled.',
                    JsonResponse::HTTP_CONFLICT,
                ); 
            }

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
     * Delete user
     */
    public function destroy(string $id){
        try {
            if(!is_numeric($id)){ throw new InvalidArgument('The ID must be numeric.'); }

            $user = User::withTrashed() -> findOrFail($id);

            $user -> forceDelete();

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

            $verificationToken = VerificationAccountToken::findOrFail($id);
            $email = User::find($id) -> select('email') -> first();
            $token = Str::random(71);

            Mail::to($email -> email) -> send(new VerifyAccountMail($token, $email -> email));

            $verificationToken -> update([
                'token' => Hash::make($token),
                'expiration' => Carbon::now() -> addDays(2)
            ]);

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

    /**
     * Change the email of a User
     */
    public function changeEmail(ChangeEmailRequest $request){
        $token = $request -> query('token');

        DB::beginTransaction();
        try {
            $user = User::where('email', $request -> previous_email) -> first();
            $emailTokenRecord = EmailResetToken::findOrFail($user -> id);
            if(!Hash::check($token, $emailTokenRecord -> token)){
                throw new TokenMismatchException();
            }
            if($emailTokenRecord -> is_used){
                throw new TokenHasBeenUsedException();
            }
            if(Carbon::now()->gt($emailTokenRecord -> date_expiration)){
                throw new TokenExpiredException();
            }
            if(!Hash::check($request -> password, $user -> password)){
                throw new PasswordMismatchException();  
            }
            
            $user -> email = $emailTokenRecord -> new_email;
            $user -> save();
            $emailTokenRecord -> is_used = TRUE;
            $emailTokenRecord -> save();

            DB::commit();

            return response() -> noContent();
        } catch (ModelNotFoundException $error) {
            DB::rollBack();
            return ApiResponse::fail(
                'The user has not requested a change of email address.',
                JsonResponse::HTTP_NOT_FOUND,
                [$error -> getMessage()]
            );
        } catch(TokenMismatchException $error) {
            DB::rollBack();
            return ApiResponse::fail(
                'Incorrect token',
                JsonResponse::HTTP_BAD_REQUEST
            );
        } catch(TokenHasBeenUsedException $error) {
            DB::rollBack();
            return ApiResponse::fail(
                'The token has been used, generate another one.',
                JsonResponse::HTTP_FORBIDDEN
            );
        } catch(TokenExpiredException $error) {
            DB::rollBack();
            return ApiResponse::fail(
                'The token has expired, generate another one.',
                JsonResponse::HTTP_FORBIDDEN
            );
        } catch(PasswordMismatchException $error) {
            DB::rollBack();
            return ApiResponse::fail(
                'The password is incorrect.',
                JsonResponse::HTTP_FORBIDDEN
            );
        } catch(Exception $error){
            DB::rollBack();
            return ApiResponse::fail(
                'An error has occurred, try again or contact the administrator.',
                errors: [$error -> getMessage()]
            );
        }
    }


    /**
     * Verify acocount's user
     */
    public function verifyAccount(Request $request) {
        $emailUser = $request -> query('email');
        $token = $request -> query('token');
        if(!$emailUser || !$token){
            return ApiResponse::fail(
                'The email and token are required.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        DB::beginTransaction();

        try {
            $user = User::where('email', $emailUser) -> select('id') -> firstOrFail();
            $verifyAccountRecord = VerificationAccountToken::findOrFail($user -> id);
            
            if(!Hash::check($token, $verifyAccountRecord -> token)){
                throw new TokenMismatchException();
            }
            if($verifyAccountRecord -> is_used){
                throw new TokenHasBeenUsedException();
            }
            if(Carbon::now()->gt($verifyAccountRecord -> expiration)){
                throw new TokenExpiredException();
            }

            $user -> is_verified = TRUE;
            $user -> save();
            $verifyAccountRecord -> is_used = TRUE;
            $verifyAccountRecord -> save();

            DB::commit();

            return ApiResponse::success('Account verified successfully', JsonResponse::HTTP_OK);
        } catch(ModelNotFoundException $error){
            DB::rollBack();
            return ApiResponse::fail(
                'User not found.',
                JsonResponse::HTTP_NOT_FOUND,
                [$error -> getMessage()]
            );
        } catch(TokenMismatchException $error) {
            DB::rollBack();
            return ApiResponse::fail(
                'Incorrect token',
                JsonResponse::HTTP_BAD_REQUEST
            );
        } catch(TokenHasBeenUsedException $error) {
            DB::rollBack();
            return ApiResponse::fail(
                'The token has been used, generate another one.',
                JsonResponse::HTTP_FORBIDDEN
            );
        } catch(TokenExpiredException $error) {
            DB::rollBack();
            return ApiResponse::fail(
                'The token has expired, generate another one.',
                JsonResponse::HTTP_FORBIDDEN
            );
        } catch (Exception $error) {
            DB::rollBack();
            return ApiResponse::fail(
                'An error has occurred, try again or contact the administrator.',
                errors: [$error -> getMessage()]
            );
        }
    }
}
