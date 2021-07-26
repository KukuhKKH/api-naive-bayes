<?php

namespace App\Http\Controllers;

use App\Imports\DataImport;
use App\Models\Clasification;
use App\Models\PreprosessingData;
use App\Models\RawData;
use App\Models\Setting;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DataController extends Controller
{
    public function import(Request $request) {
        // $this->validate($request, [
        //     'excel' => 'required|mimes:xls,xlsx,csv,txt'
        // ]);
        if ($request->hasFile('excel')) { // apakah ada file di upload
            $excel = $request->file('excel'); // inisialisasi file
            $name = time().'.'.$excel->getClientOriginalExtension(); // set nama file
            $destinationPath = storage_path('app/public/uploads/excel'); // path file
            if(file_exists($destinationPath.$name)) { // Apakah ada file
                unlink($destinationPath); // Hapus file
            }
            RawData::query()->truncate(); // hapus data di database
            $new_file = $excel->move($destinationPath, $name); // pindahkan file ke dir baru
            Excel::import(new DataImport, $new_file); // proses import
            return response()->json([ // pesan sukses
                'status' => true,
                'message' => [
                    'head' => "Success",
                    'body' => 'File Uploaded'
                ]
            ], 200);
        }
        return response()->json([ // pesan error
            'status' => false,
            'message' => [
                'head' => "Error",
                'body' => 'File not found'
            ]
        ], 500);
    }

    public function getDataRaw() {
        $rawData = RawData::all(); // Select * from raw_data
        return response()->json([
            'status' => true,
            'data' => $rawData
        ]);
    }

    public function getDataPreprosessing() {
        $preprosessing = PreprosessingData::with('raw_data')->get(); // Select * from preprosessing left join raw_data
        return response()->json([
            'status' => true,
            'data' => $preprosessing
        ]);
    }

    public function getClasification() {
        $clasification = Clasification::with('raw_data')->get();
        return response()->json([
            'status' => true,
            'data' => $clasification
        ]);
    }

    public function getAcuracyEtc() {
        $akurasi = Setting::where('key', 'akurasi')->first();
        $presisi = Setting::where('key', 'presisi')->first();
        $recall = Setting::where('key', 'recall')->first();
        return response()->json([
            'status' => true,
            'data' => [
                'akurasi' => $akurasi->value ?? 0,
                'presisi' => $presisi->value ?? 0,
                'recall' => $recall->value ?? 0
            ]
        ]);
    }
}
