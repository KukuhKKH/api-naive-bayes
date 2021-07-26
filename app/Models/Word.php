<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Word extends Model
{
    protected $fillable = ['word'];

    /**
     * Set the word.
     *
     * @param  string  $value
     * @return void
     */
    public function setWordAttribute($value)
    {
        $this->attributes['word'] = serialize($value);
    }
}
