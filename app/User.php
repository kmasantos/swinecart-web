<?php

namespace App;

use App\Role;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the roles that the user has
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Assign Role to a User
     */
    public function assign($role)
    {
        if(is_string($role)){
            return $this->roles()->attach(
                Role::whereTitle($role)->firstOrFail()
            );
        }

        return $this->roles()->save($role);
    }

    /**
     * Check User if it has a certain role
     */
    public function hasRole($role)
    {
        if(is_string($role)){
            return $this->roles->contains('title',$role);
        }

        return !! $role->intersect($this->roles)->count();
    }



}
