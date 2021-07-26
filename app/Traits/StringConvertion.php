<?php

namespace App\Traits;

trait StringConvertion {
    public function getLabel($label) {
        if($label == 0) {
            return 'Negatif';
        } else if($label == 1) {
            return 'Positif';
        } else if($label == 2) {
            return 'Netral';
        } else {
            return 'Tidak ditemukan';
        }
    }

    public function get_data_unique($array) {
        $word = [];
        foreach ($array as $k => $value) {
            if(empty($word)) {
                array_push($word, $value);
            } else {
                if(!in_array($value, $word)) {
                    array_push($word, $value);
                }
            }
        }
        return $word;
    }

    public function sumTotalFlag($data) {
        $result = 0;
        foreach ($data as $key => $value) {
            $result += $value;
        }
        return $result;
    }
}
