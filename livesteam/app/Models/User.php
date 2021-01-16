<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $table      = 'user';
    protected $primaryKey = 'id';


    public static $select = [
        'id',
        'username',
        'status',
        'created_at as createdAt',
        'updated_at as updatedAt',
        'avatar'
    ];
    const  STATUS_NORMAL = 1;

}
