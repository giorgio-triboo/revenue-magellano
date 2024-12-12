<?php
// app/Models/Role.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Role extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function isAdmin()
    {
        return $this->code === 'admin';
    }

    public function isPublisher()
    {
        return $this->code === 'publisher';
    }
}
