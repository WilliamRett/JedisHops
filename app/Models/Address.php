<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'address', 
        'city',
        'state',
        'country',
        'cep',
    ]; 
    

    /**
     * Get all of the comments for the Address
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Pantients(): HasMany
    {
        return $this->hasMany(Pantient::class, 'foreign_key', 'local_key');
    }
    
}
