<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Mowner extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'mowner';
    protected $primaryKey = 'nid';
    public $timestamps = false;

    protected $fillable = [
        'cemail',
        'cname',
        'cpassword',
        'ccompany',
        'dcreated',
    ];

    protected $hidden = [
        'cpassword',
    ];

    protected $casts = [
        'dcreated' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->cpassword;
    }

    // Role Checks (Full Access for Owner)
    public function isSuperAdmin() { return true; }
    public function isAdmin() { return true; }
    public function isHrd() { return true; }
    public function isSenior() { return true; }
    public function isActive() { return true; }
    public function notifEnabled() { return true; }
    public function isPayrollAccess() { return true; }

    // Accessors for field-based checks (e.g. $user->fadmin == 1)
    public function getFadminAttribute() { return 1; }
    public function getFsuperAttribute() { return 1; }
    public function getFhrdAttribute() { return 1; }
    public function getFseniorAttribute() { return 1; }
    public function getFactiveAttribute() { return 1; }
}
