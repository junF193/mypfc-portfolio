<?php
namespace App\Http\Controllers;

use League\Csv\Reader;
use League\Csv\Writer;

class CsvImportController extends Controller
{
    public function processCsv()
    {
        $inputPath = storage_path('app/food_data_20230428.csv');
        $outputPath = storage_path('app/corrected.csv');

        // ファイル内容を読み込み、CP932からUTF-8に変換
        $content = file_get_contents($inputPath);
        $utf8Content = mb_convert_encoding($content, 'UTF-8', 'CP932');
        
        // 改行文字を統一
        $utf8Content = str_replace(["\r\n", "\r"], "\n", $utf8Content);
        
        // 一時ファイルに保存
        $tempPath = storage_path('app/temp_utf8.csv');
        file_put_contents($tempPath, $utf8Content);

        // CSVを読み込む
        $csv = Reader::createFromPath($tempPath, 'r');
        $csv->setHeaderOffset(null);
        $csv->setDelimiter(',');

        // 新しいヘッダーを定義
        $headers = [
            '食品群', '食品番号', '索引番号', '食品名', '廃棄率', 'エネルギー(kJ)', 'エネルギー(kcal)', '水分',
            'アミノ酸組成によるたんぱく質', 'たんぱく質', '脂肪酸のトリアシルグリセロール当量', 'コレステロール', '脂質',
            '利用可能炭水化物(単糖当量)', '利用可能炭水化物(質量計)', '差引き法による利用可能炭水化物', '食物繊維総量',
            '糖アルコール', '炭水化物', '有機酸', '灰分', 'ナトリウム', 'カリウム', 'カルシウム', 'マグネシウム',
            'リン', '鉄', '亜鉛', '銅', 'マンガン', 'ヨウ素', 'セレン', 'クロム', 'モリブデン', 'レチノール',
            'αカロテン', 'βカロテン', 'βクリプトキサンチン', 'βカロテン当量', 'レチノール活性当量', 'ビタミンD',
            'αトコフェロール', 'βトコフェロール', 'γトコフェロール', 'δトコフェロール', 'ビタミンK', 'ビタミンB1',
            'ビタミンB2', 'ナイアシン', 'ナイアシン当量', 'ビタミンB6', 'ビタミンB12', '葉酸', 'パントテン酸',
            'ビオチン', 'ビタミンC', 'アルコール', '食塩相当量', '備考'
        ];

        // 新しいCSVライターを作成
        $writer = Writer::createFromPath($outputPath, 'w+');
        $writer->setOutputBOM(Writer::BOM_UTF8);
        $writer->insertOne($headers);

        $processedRows = 0;
        $dataStarted = false;
        
        // データ行を処理
        foreach ($csv->getRecords() as $index => $record) {
            // データ開始行を探す（食品群の番号から始まる行）
            if (!$dataStarted) {
                // 最初の列が数字（食品群番号）で始まる行を探す
                if (!empty($record[0]) && is_numeric($record[0])) {
                    $dataStarted = true;
                } else {
                    continue; // ヘッダー行をスキップ
                }
            }
            
            // 空行をスキップ
            if (empty(array_filter($record))) {
                continue;
            }

            // 列数をヘッダーと一致させる
            $record = array_pad($record, count($headers), '');
            $record = array_slice($record, 0, count($headers));

            // 特殊文字の処理
            $record = array_map(function ($value) {
                $value = trim($value); // 前後の空白を除去
                
                if ($value === '*' || $value === '-' || $value === '') {
                    return '\N'; // MySQLのNULL値
                }
                if (strpos($value, '(Tr)') !== false) {
                    return 'Tr';
                }
                if ($value === '0') {  // 数値の0はそのまま
                    return '0';
                }
                
                return $value;
            }, $record);

            // 修正した行を書き込む
            $writer->insertOne($record);
            $processedRows++;
        }

        // 一時ファイルを削除
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }

        return response()->json([
            'message' => 'CSV file processed successfully',
            'processed_rows' => $processedRows,
            'encoding' => 'CP932 -> UTF-8'
        ]);
    }
}