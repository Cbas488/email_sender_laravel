<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class VerificationAccountToken
 * 
 * @property int $user_id
 * @property string $token
 * @property Carbon $expiration
 * @property bool|null $is_used
 * 
 * @property User $user
 *
 * @package App\Models
 */
class VerificationAccountToken extends Model
{
	protected $table = 'verification_account_tokens';
	protected $primaryKey = 'user_id';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'user_id' => 'int',
		'expiration' => 'datetime',
		'is_used' => 'bool'
	];

	protected $hidden = [
		'token'
	];

	protected $fillable = [
		'user_id',
		'token',
		'expiration',
		'is_used'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
