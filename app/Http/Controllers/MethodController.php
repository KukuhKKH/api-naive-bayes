<?php

namespace App\Http\Controllers;

use App\Models\Clasification;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Traits\StringConvertion;
use App\Models\PreprosessingData;
use Phpml\Metric\ConfusionMatrix;

class MethodController extends Controller
{
    use StringConvertion; // memanggil trait

    public function index() {
        /**
         * 0 = Negatif
         * 1 = Positif
         * 2 = Netral
         */
        // Lek pengen dewe dewe ngawe where nang kene
        $dataset = PreprosessingData::with('raw_data')
                            // ->wherehas('raw_data', function($subquery) {
                                // $subquery->where('platform', 'twitter');
                                // $subquery->where('platform', 'youtube');
                            // })
                            // ->inRandomOrder()
                            ->limit(500)
                            ->get(); // select * from preprosessingdata limit 5 random
        $flagData = []; // Set kalimat flag
        $rawDataset = []; // Dataset multiple array
        $keyFlag = [];
        foreach ($dataset as $key => $value) {
            $rawDataset[] = $this->get_data_unique(explode(' ', $value->result)); // Mencari kata" yang sama
            if($value->raw_data->flag == 1) {
                $flagData[] = '1';
                $keyFlag['1'][] = $key;
            } else if($value->raw_data->flag == 2) {
                $flagData[] = '2';
                $keyFlag['2'][] = $key;
            } else if($value->raw_data->flag == 0) {
                $flagData[] = '0';
                $keyFlag['0'][] = $key;
            }
        }

        $resultDataset = []; // Singel array
        for ($i=0; $i < count($rawDataset); $i++) {
            $resultDataset = array_merge($resultDataset, $rawDataset[$i]); // Dataset push ke array baru
        }
        $resultDataset = $this->get_data_unique($resultDataset); // Filter jika ada kata yang sama

        $resutlTF = []; // Kata yang sama
        foreach ($rawDataset as $key => $value) { // pemobobotan semua datatraining
            $resutlTF[] = $this->word_weight($value, $resultDataset); // Pembobotan
        }

        $arrFlag = [ // Set variabel awal (Nx)
            '1' => 0,
            '0' => 0,
            '2' => 0,
        ];
        $normalisasi = $sumWordFlag = [
            '1' => [],
            '0' => [],
            '2' => [],
        ];
        foreach ($resutlTF as $key => $value) { // Menghitung total 1 / 0 / 2
            if($flagData[$key] == '1') {
                $arrFlag['1'] += $this->sumTotalFlag($value); // Menjumlahkan data sesuai key
                foreach ($value as $k => $val) { // Perulangan setiap dataset yang 1 jika ada yang sama
                    if($val >= 1) { // jika
                        if(empty($sumWordFlag['1'][$k])) {
                            $sumWordFlag['1'][$k] = 1; // Jika array tsb belum ada maka = 1
                        } else {
                            $sumWordFlag['1'][$k] += 1; // jika sudah ada maka + 1
                        }
                    } else {
                        if(empty($sumWordFlag['1'][$k])) {
                            $sumWordFlag['1'][$k] = 0; // jika da tsb belum ada dan tidak ada data yang sama dengan dataset
                        }
                    }
                }
            } else if ($flagData[$key] == '0') {
                $arrFlag['0'] +=  $this->sumTotalFlag($value); // Menjumlahkan data sesuai key
                foreach ($value as $k => $val) {
                    if($val >= 1) {
                        if(empty($sumWordFlag['0'][$k])) {
                            $sumWordFlag['0'][$k] = 1;
                        } else {
                            $sumWordFlag['0'][$k] += 1;
                        }
                    } else {
                        if(empty($sumWordFlag['0'][$k])) {
                            $sumWordFlag['0'][$k] = 0;
                        }
                    }
                }
            } else if ($flagData[$key] == '2') {
                $arrFlag['2'] +=  $this->sumTotalFlag($value); // Menjumlahkan data sesuai key
                foreach ($value as $k => $val) {
                    if($val >= 1) {
                        if(empty($sumWordFlag['2'][$k])) {
                            $sumWordFlag['2'][$k] = 1;
                        } else {
                            $sumWordFlag['2'][$k] += 1;
                        }
                    } else {
                        if(empty($sumWordFlag['2'][$k])) {
                            $sumWordFlag['2'][$k] = 0;
                        }
                    }
                }
            }
        }

        $totalDataset = count($resultDataset); // Total Dataset (n |Vocab|)

        foreach ($sumWordFlag as $k => $v) {
            $totalSameWord = array_sum($v);
            foreach ($v as $key => $value) {
                $normalisasi[$k][$key] = round(@(1+$value) / @($totalSameWord+$totalDataset), 4);
            }
        }

        $dataTestResult = $dataPredictResult = $dataNormalisasiUtama = [];
        $countData = 200;
        for ($i=0; $i < $countData; $i++) {
            // $rand_index = rand(0, 54);
            $rand_index = $i;
            $kata_kunci = $dataset[$rand_index]->result;
            $kata_kunci = explode(' ', $kata_kunci);
            $setting = Setting::all(); // Get bobot 1 / 0 / 2
            $finalResult = [];
            foreach ($normalisasi as $key => $value) {
                $finalResult[$key] = $setting[$key]->value;
                foreach ($value as $k => $v) {
                    if($this->searchValue($kata_kunci, $k)) {
                        $finalResult[$key] *= $v;
                    }
                }
            }
            $dataNormalisasiUtama[] = $dataset[$rand_index];
            $dataTestResult[] = $dataset[$rand_index]->raw_data->flag;
            $dataPredictResult[] = array_keys($finalResult, max($finalResult))[0];
        }

        Clasification::query()->truncate();
        foreach ($dataNormalisasiUtama as $key => $value) {
            Clasification::create([
                'raw_data_id' => $value->raw_data_id,
                'dataset' => $dataTestResult[$key],
                'predict' => $dataPredictResult[$key]
            ]);
        }

        $confusionMatrix = ConfusionMatrix::compute($dataTestResult, $dataPredictResult, [0, 1, 2]);

        $positifTP = $confusionMatrix[1][1];
        $positifTN = $confusionMatrix[1][0];;
        $positifFN = $confusionMatrix[1][2];;

        $negatifTP = $confusionMatrix[0][0];
        $negatifTN = $confusionMatrix[0][1];
        $negatifFN = $confusionMatrix[0][2];

        $netralTP = $confusionMatrix[2][2];
        $netralTN = $confusionMatrix[2][0];
        $netralFN = $confusionMatrix[2][1];

        $truePositif = $positifTP + $negatifTP + $netralTP; // Data True dan benar
        $trueNegative = $positifTN + $negatifTN + $netralTP; // Data Salah dan benar
        $falseNegative = $positifFN + $negatifFN + $netralFN; // Data Salah dan salah

        $akurasi = $truePositif / ($truePositif + $trueNegative + $falseNegative); // TruePositif / All Data
        $presisiPositif = @($positifTP / ($positifTP + $positifTN));
        $presisiNegatif = @($negatifTP / ($negatifTP + $negatifTN));
        $presisiNetral = @($netralTP / ($netralTP + $netralTN));
        $presisi = ($presisiPositif + $presisiNegatif + $presisiNetral) / 3; // All Presisi / Jumlah Kelas
        $recallPositif = @($positifTP / ($positifTP + $positifFN)); // True Positif / TP + FN
        $recallNegatif = @($negatifTP / ($negatifTP + $negatifFN)); // True Positif / TP + FN
        $recallNetral = @($netralTP / ($netralTP + $netralFN)); // True Positif / TP + FN
        $recall = ($recallPositif + $recallNegatif + $recallNetral) / 3; // All Recall / Jumlah Kelas

        Setting::updateOrCreate([
            'key' => 'akurasi'
        ], [
            'value' => $akurasi
        ]);
        Setting::updateOrCreate([
            'key' => 'presisi'
        ], [
            'value' => $presisi
        ]);
        Setting::updateOrCreate([
            'key' => 'recall'
        ], [
            'value' => $recall
        ]);

        return [
            'akurasi' => $akurasi,
            'presisi' => $presisi,
            'recall' => $recall,
            'confusionMatrix' => $confusionMatrix
        ];
    }

    public function searchValue($arr, $keyword) {
        foreach ($arr as $key => $value) {
            if ($value == $keyword) {
                return true;
            }
        }
        return false;
    }


    private function word_weight($data, $dataset) {
        $result = [];
        $index = 0;
        /** Berdasarkan datacrawl */
        // substr_count("Hello world. The world is nice","world")
        // $newDataset = implode(' ', $dataset);
        // foreach ($data as $key => $value) { // Get all dataset
        //     if(!empty($value)) { // Jaga" jika tidak terdpat kata
        //         $result[$value] = substr_count($newDataset, $value);
        //     } else {
        //         $result[$value] = 0;
        //     }
        // }
        /** Berdasarkan dataset */
        $newData = implode(' ', $data);
        foreach ($dataset as $key => $value) {
            if(!empty($value)) { // Jaga" jika tidak terdpat kata
                $result[$value] = substr_count($newData, $value);
            } else {
                $result[$value] = 0;
            }
        }
        return $result;
    }
}
