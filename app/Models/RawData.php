<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawData extends Model
{
    protected $fillable = ['author', 'text', 'platform'];

    public function preprosessing() {
        return $this->hasOne(PreprosessingData::class, 'raw_data_id');
    }
}
