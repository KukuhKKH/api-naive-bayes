<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clasification extends Model
{
    protected $fillable = ['raw_data_id', 'dataset', 'predict'];

    public function raw_data() {
        return $this->belongsTo(RawData::class);
    }
}
