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
        return new RawData([
           'author'     => isset($row['author']) ? trim($row['author']) : '',
           'text'    => isset($row['text']) ? trim($row['text']) : '',
           'platform' => isset($row['platform']) ? trim($row['platform']) : ''
        ]);
    }
}
