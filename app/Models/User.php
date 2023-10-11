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
 * @property string $verification_token
 * @property bool $is_verified
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @package App\Models
 */
class User extends Model
{
	protected $table = 'users';
	public $incrementing = false;

	protected $casts = [
		'id' => 'int',
		'is_verified' => 'bool'
	];

	protected $hidden = [
		'password',
		'verification_token'
	];

	protected $fillable = [
		'email',
		'password',
		'name',
		'verification_token',
		'is_verified'
	];
}
