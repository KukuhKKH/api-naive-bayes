<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NaiveBayesController extends Controller
{
    public function index() {
        $training = $this->training();
        $this->testing($training);
    }

    public function training() {
        $data = [];
        return $data;
    }

    public function testing($data) {
        return true;
    }
}
