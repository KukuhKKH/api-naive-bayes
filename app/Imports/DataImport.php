<?php

namespace App\Imports;

use App\Models\RawData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DataImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return User|null
     */
    public function model(array $row)
    {
        if(isset($row['label'])) {
            if($row['label'] == 'positif') {
                $flag = 1;
            } else if($row['label'] == 'netral') {
                $flag = 2;
            } else {
                $flag = 0;
            }
        } else {
            $flag = '';
        }
        return new RawData([
           'author'     => isset($row['author']) ? trim($row['author']) : '',
           'text'    => isset($row['text']) ? trim($row['text']) : '',
           'platform' => isset($row['platform']) ? trim($row['platform']) : '',
           'flag' => $flag
        ]);
    }
}
