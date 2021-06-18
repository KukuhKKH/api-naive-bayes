<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreprosessingData extends Model
{
    protected $fillable = ['raw_data_id', 'result'];

    public function raw_data() {
        return $this->belongsTo(RawData::class);
    }
}
