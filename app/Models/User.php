<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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
 * 
 * @property EmailResetToken $email_reset_token
 * @property VerificationAccountToken $verification_account_token
 *
 * @package App\Models
 */
class User extends Model
{
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
}
