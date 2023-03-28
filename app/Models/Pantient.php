<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pantient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'photo', 
        'name',
        'mon',
        'birthday',
        'cpf',
        'cns',
        'address_id'
    ];   

    /**
     * Get the Pantient associated with the Address
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Pantient(): HasOne
    {
        return $this->hasOne(Pantient::class, 'address_id', 'id');
    }


}
