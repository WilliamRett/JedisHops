<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'address',
        'neighborhood',
        'city',
        'state',
        'cep',
    ];


    /**
     * Get all of the comments for the Address
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Pantients(): HasMany
    {
        return $this->hasMany(Pantient::class, 'address_id', 'local_key');
    }
}
