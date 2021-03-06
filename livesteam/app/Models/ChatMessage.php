<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class ChatMessage extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $table      = 'chat_message';
    protected $primaryKey = 'id';

    public static $select = [
        'id',
        'room_id as roomId',
        'uid',
        'status',
        'created_at as createdAt',
        'updated_at as updatedAt',
        'content'
    ];
    const  STATUS_NORMAL = 1; //正常
    const  STATUS_BACK = 2; //撤回
}
