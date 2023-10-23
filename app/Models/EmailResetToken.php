<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EmailResetToken
 * 
 * @property int $user_id
 * @property string $token
 * @property string $new_email
 * @property bool|null $is_used
 * @property Carbon $date_expiration
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property User $user
 *
 * @package App\Models
 */
class EmailResetToken extends Model
{
	protected $table = 'email_reset_tokens';
	protected $primaryKey = 'user_id';
	public $incrementing = false;

	protected $casts = [
		'user_id' => 'int',
		'is_used' => 'bool',
		'date_expiration' => 'datetime'
	];

	protected $hidden = [
		'token'
	];

	protected $fillable = [
		'user_id',
		'token',
		'new_email',
		'is_used',
		'date_expiration'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
