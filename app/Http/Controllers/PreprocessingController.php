<?php

namespace App\Http\Controllers;

use App\Models\RawData;
use App\Models\StopRemoval;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PreprosessingData;
use App\Models\Word;
use App\Traits\StringConvertion;
use Illuminate\Support\Facades\DB;
use Sastrawi\Stemmer\StemmerFactory;

class PreprocessingController extends Controller
{
    use StringConvertion; // memanggil trait

    public function start() {
        DB::beginTransaction();
        try {
            $data = RawData::all();
            $case_folding = $this->case_folding($data);
            $cleansing = $this->cleansing($case_folding);
            $stopRemoval = $this->stop_removal($cleansing);
            // $tokenizing = $this->tokenizing($stopRemoval);
            $stemming = $this->stemming($stopRemoval);
            $word = [];
            PreprosessingData::query()->truncate(); // Hapus all data
            foreach ($cleansing as $key => $value) {
                PreprosessingData::create([
                    'raw_data_id' => $value['id'],
                    'result' => $value['text']
                ]); // Insert ke databse
                $_word = explode(" ", $value['text']); // membuat array dari teks
                $result_word = $this->get_data_unique($_word); // cek kata yang sama
                array_push($word, $result_word); // push ke arr baru
            }
            Word::query()->truncate(); // hapus data di db
            Word::create([
                'word' => $word
            ]); // insert ke db
            DB::commit(); // lakukan query
            return response()->json([
                'status' => true,
                'message' => [
                    'head' => "Success",
                    'body' => 'Preprosessing Data'
                ]
            ], 200); // pesan berhasil
        } catch(\Exception $e) {
            DB::rollBack(); // kembalikan query jika ada yang gagal
            return response()->json([
                'status' => false,
                'message' => [
                    'head' => "Error",
                    'body' => $e->getMessage()
                ]
            ], 500); // pesan error
        }
    }

    public function case_folding($data) {
        $result = [];
        foreach ($data as $key => $value) {
            $temp = [];
            $temp['id'] = $value->id;
            $temp['text'] = trim(preg_replace('/\s+/', ' ', Str::lower($value->text)), ' '); // huruf kecil
            array_push($result, $temp); // push ke array
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
            $clear_character = str_replace('/\s+/', ' ', $clear_character);
            $temp['id'] = $value['id'];
            $temp['text'] = trim($clear_character, ' ');
            array_push($result, $temp);
        }
        return $result;
    }

    public function tokenizing($data) {
        $result = [];
        foreach ($data as $key => $value) {
            $temp = [];
            $temp['id'] = $value['id'];
            $temp['text'] = explode(" ",$value['text']);
            array_push($result, $temp);
        }

        return $result;
    }

    public function stop_removal($data) {
        $stopRemoval = StopRemoval::all();
        $singleData = $result = [];
        foreach ($stopRemoval as $key => $value) {
            $singleData[] = $value->word;
        }

        foreach ($data as $key => $value) {
            $temp = [];
            $temp['id'] = $value['id'];
            $removal = preg_replace('/\b('.implode('|',$singleData).')\b/','',$value['text']);
            $removal = preg_replace('/\s+/', ' ', $removal);
            $temp['text'] = $removal;
            array_push($result, $temp);
        }
        return $result;
    }

    public function stemming($data) {
        $stemmerFactory = new StemmerFactory();
        $stemmer  = $stemmerFactory->createStemmer();
        $result = [];

        foreach ($data as $key => $value) {
            $temp = [];
            $temp['id'] = $value['id'];
            $temp['text'] = $stemmer->stem($value['text']);
            array_push($result, $temp);
        }
        return $result;
    }
}
