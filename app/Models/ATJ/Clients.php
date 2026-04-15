<?php

namespace App\Models\ATJ;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clients extends Model
{
    //
    use HasFactory;
    protected $fillable = [];
    protected $guarded = [];
    protected $table = 'atj_clients';

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}
