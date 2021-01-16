<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class RoomInfo extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $table      = 'roominfo';
    protected $primaryKey = 'id';

    public static $select = [
        'id',
        'name',
        'status',
        'created_at as createdAt',
        'updated_at as updatedAt',
        'owner_uid as ownerUid',
        'people_num as peopleNum',
        'single_chat_uid as singleChatUid',
    ];
    const  STATUS_NORMAL = 1;

}
