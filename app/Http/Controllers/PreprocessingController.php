<?php

namespace App\Http\Controllers;

use App\Models\PreprosessingData;
use App\Models\RawData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PreprocessingController extends Controller
{
    public function start() {
        DB::beginTransaction();
        try {
            $data = RawData::all();
            $case_folding = $this->case_folding($data);
            $cleansing = $this->cleansing($case_folding);
            foreach ($cleansing as $key => $value) {
                PreprosessingData::create([
                    'raw_data_id' => $value['id'],
                    'result' => $value['text']
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => [
                    'head' => "Success",
                    'body' => 'Preprosessing Data'
                ]
            ], 200);
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => [
                    'head' => "Error",
                    'body' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function case_folding($data) {
        $result = [];
        foreach ($data as $key => $value) {
            $temp = [];
            $temp['id'] = $value->id;
            $temp['text'] = Str::lower($value->text);
            array_push($result, $temp);
        }
        return $result;
    }

    public function cleansing($data) {
        $result = [];
        foreach ($data as $key => $value) {
            $arr = explode(' ', $value['text']);
            $temp_url = [];
            $temp = [];
            // Clean URL
            foreach ($arr as $k => $v) {
                $regex_url = "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?).*$)@";
                $temp_url[] = preg_replace($regex_url, ' ', $v);
            }
            $result_clean_url = implode(" ",$temp_url);
            // Clear Number
            $clear_number = preg_replace('/[0-9]+/', '', $result_clean_url);
            // Clear Character
            $clear_character = str_replace(array('[\', \']'), '', $clear_number);
            $clear_character = preg_replace('/\[.*\]/U', '', $clear_character);
            $clear_character = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '-', $clear_character);
            $clear_character = htmlentities($clear_character, ENT_COMPAT, 'utf-8');
            $clear_character = preg_replace('/&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);/i', '\\1', $clear_character );
            $clear_character = preg_replace(array('/[^a-z0-9]/i', '/[-]+/') , '-', $clear_character);
            $clear_character = str_replace('rt', ' ', $clear_character);

            $clear_character = str_replace('-', ' ', $clear_character);
            $temp['id'] = $value['id'];
            $temp['text'] = trim($clear_character, ' ');
            array_push($result, $temp);
        }
        return $result;
    }
}
