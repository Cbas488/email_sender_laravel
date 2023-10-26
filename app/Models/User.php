<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class User
 * 
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string $name
 * @property bool $is_verified
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property EmailResetToken $email_reset_token
 * @property VerificationAccountToken $verification_account_token
 *
 * @package App\Models
 */
class User extends Model
{
	use SoftDeletes;
	protected $table = 'users';

	protected $casts = [
		'is_verified' => 'bool'
	];

	protected $hidden = [
		'password'
	];

	protected $fillable = [
		'email',
		'password',
		'name',
		'is_verified'
	];

	public function email_reset_token()
	{
		return $this->hasOne(EmailResetToken::class);
	}

	public function verification_account_token()
	{
		return $this->hasOne(VerificationAccountToken::class);
	}

	public static function boot() {
		parent::boot();

		static::deleting(function(User $user){
			if($user -> email_reset_token){ $user -> email_reset_token -> delete(); }
			if($user -> verification_account_token){ $user -> verification_account_token -> delete(); }
		});
	}
}
