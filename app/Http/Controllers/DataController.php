<?php

namespace App\Http\Controllers;

use App\Imports\DataImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DataController extends Controller
{
    public function import(Request $request) {
        // $this->validate($request, [
        //     'excel' => 'required|mimes:xls,xlsx,csv,txt'
        // ]);
        if ($request->hasFile('excel')) {
            $excel = $request->file('excel');
            $name = time().'.'.$excel->getClientOriginalExtension();
            $destinationPath = storage_path('app/public/uploads/excel');
            if(file_exists($destinationPath.$name)) {
                unlink($destinationPath);
            }
            $new_file = $excel->move($destinationPath, $name);
            Excel::import(new DataImport, $new_file);
            return response()->json([
                'status' => true,
                'message' => [
                    'head' => "Success",
                    'body' => 'File Uploaded'
                ]
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => [
                'head' => "Error",
                'body' => 'File not found'
            ]
        ], 500);
    }
}
