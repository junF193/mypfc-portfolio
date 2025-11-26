<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanFoodDataCommand extends Command
{
    protected $signature = 'food:clean {file_path}';
    protected $description = '食品標準成分表のCSVデータをクリーンアップしてMySQLにインポート';

    public function handle()
    {
        // PDOのエラーモードを警告からサイレントに変更
        DB::connection()->getPdo()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        
        $filePath = $this->argument('file_path');
        
        // ファイルパスの検索と解決
        $actualPath = $this->resolveFilePath($filePath);
        
        if (!$actualPath) {
            $this->error("ファイルが見つかりません: {$filePath}");
            $this->info("デバッグコマンドを実行してください:");
            $this->line("  php artisan debug:file-path {$filePath}");
            return 1;
        }

        $this->info("ファイルを発見: {$actualPath}");
        $this->info('CSVファイルを読み込み中...');
        
        $csvContent = file_get_contents($actualPath);
        $lines = explode("\n", $csvContent);
        
        // データの開始行を探す（数字で始まる行）
        $dataStartIndex = null;
        $sampleDataLines = [];
        
        foreach ($lines as $index => $line) {
            $trimmedLine = trim($line);
            if (empty($trimmedLine)) continue;
            
            // 数字（食品群）で始まる行を探す
            if (preg_match('/^\d+,/', $trimmedLine)) {
                if ($dataStartIndex === null) {
                    $dataStartIndex = $index;
                }
                // 最初の5行をサンプルとして保存
                if (count($sampleDataLines) < 5) {
                    $sampleDataLines[] = $trimmedLine;
                }
            }
        }
        
        if ($dataStartIndex === null) {
            $this->error('データ行が見つかりません');
            $this->info('ファイルの最初の10行:');
            for ($i = 0; $i < min(10, count($lines)); $i++) {
                $this->line(($i + 1) . ': ' . substr($lines[$i], 0, 100) . '...');
            }
            return 1;
        }
        
        $this->info("データ開始行: {$dataStartIndex}");
        $this->info('サンプルデータ行:');
        foreach ($sampleDataLines as $sample) {
            $this->line('  ' . substr($sample, 0, 100) . '...');
        }
        
        // 固定ヘッダーを使用（日本食品標準成分表の標準構造）
        $headers = $this->getStandardHeaders();
        $this->info('使用するカラム数: ' . count($headers));
        
        // データ行を処理
        $cleanedData = [];
        $rowCount = 0;
        $errorCount = 0;
        
        for ($i = $dataStartIndex; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            // 数字で始まる行のみ処理
            if (!preg_match('/^\d+,/', $line)) continue;
            
            try {
                $data = $this->parseDataRow($line, $headers);
                if ($data && !empty($data['food_name'])) {
                    $cleanedData[] = $data;
                    $rowCount++;
                    
                    if ($rowCount % 50 == 0) {
                        $this->info("処理済み行数: {$rowCount}");
                    }
                }
            } catch (Exception $e) {
                $errorCount++;
                if ($errorCount <= 5) {
                    $this->warn("行 {$i} でエラー: " . $e->getMessage());
                }
            }
        }
        
        $this->info("総データ行数: " . count($cleanedData));
        $this->info("エラー行数: {$errorCount}");
        
        if (empty($cleanedData)) {
            $this->error('インポートするデータがありません');
            return 1;
        }
        
        // データの値域をチェック
        $this->analyzeDataRanges($cleanedData);
        
        // 最初の数行をサンプル表示
        $this->info('最初の3行のサンプル:');
        for ($i = 0; $i < min(3, count($cleanedData)); $i++) {
            $sample = $cleanedData[$i];
            $this->line("  {$sample['food_group']} - {$sample['food_name']} - {$sample['energy_kcal']}kcal");
        }
        
        // データベースにインサート
        $this->insertToDatabase($cleanedData);
        
        $this->info('データクリーニングとインポートが完了しました');
        return 0;
    }
    
    private function getStandardHeaders()
    {
        // 日本食品標準成分表の標準カラム構造
        return [
            0 => 'food_group',              // 食品群
            1 => 'food_number',             // 食品番号
            2 => 'index_number',            // 索引番号
            3 => 'food_name',               // 食品名
            4 => 'refuse_rate',             // 廃棄率
            5 => 'energy_kj',               // エネルギー(kJ)
            6 => 'energy_kcal',             // エネルギー(kcal)
            7 => 'water',                   // 水分
            8 => 'protein_amino_acid',      // アミノ酸組成によるたんぱく質
            9 => 'protein',                 // たんぱく質
            10 => 'triglyceride',           // 脂肪酸のトリアシルグリセロール当量
            11 => 'cholesterol',            // コレステロール
            12 => 'fat',                    // 脂質
            13 => 'available_carb_monosaccharide', // 利用可能炭水化物(単糖当量)
            14 => 'available_carb_mass',    // 利用可能炭水化物(質量計)
            15 => 'available_carb_subtraction', // 差引き法による利用可能炭水化物
            16 => 'dietary_fiber',          // 食物繊維総量
            17 => 'sugar_alcohol',          // 糖アルコール
            18 => 'carbohydrate',           // 炭水化物
            19 => 'organic_acid',           // 有機酸
            20 => 'ash',                    // 灰分
            21 => 'sodium',                 // ナトリウム
            22 => 'potassium',              // カリウム
            23 => 'calcium',                // カルシウム
            24 => 'magnesium',              // マグネシウム
            25 => 'phosphorus',             // リン
            26 => 'iron',                   // 鉄
            27 => 'zinc',                   // 亜鉛
            28 => 'copper',                 // 銅
            29 => 'manganese',              // マンガン
            30 => 'iodine_placeholder',     // プレースホルダー
            31 => 'iodine',                 // ヨウ素
            32 => 'selenium',               // セレン
            33 => 'chromium',               // クロム
            34 => 'molybdenum',             // モリブデン
            35 => 'retinol',                // レチノール
            36 => 'alpha_carotene',         // αカロテン
            37 => 'beta_carotene',          // βカロテン
            38 => 'beta_cryptoxanthin',     // βクリプトキサンチン
            39 => 'beta_carotene_equivalent', // βカロテン当量
            40 => 'retinol_activity_equivalent', // レチノール活性当量
            41 => 'vitamin_d',              // ビタミンD
            42 => 'alpha_tocopherol',       // αトコフェロール
            43 => 'beta_tocopherol',        // βトコフェロール
            44 => 'gamma_tocopherol',       // γトコフェロール
            45 => 'delta_tocopherol',       // δトコフェロール
            46 => 'vitamin_k',              // ビタミンK
            47 => 'vitamin_b1',             // ビタミンB1
            48 => 'vitamin_b2',             // ビタミンB2
            49 => 'niacin',                 // ナイアシン
            50 => 'niacin_equivalent',      // ナイアシン当量
            51 => 'vitamin_b6',             // ビタミンB6
            52 => 'vitamin_b12',            // ビタミンB12
            53 => 'folate',                 // 葉酸
            54 => 'pantothenic_acid',       // パントテン酸
            55 => 'biotin',                 // ビオチン
            56 => 'vitamin_c',              // ビタミンC
            57 => 'alcohol',                // アルコール
            58 => 'salt_equivalent',        // 食塩相当量
            59 => 'remarks',                // 備考
        ];
    }
    
    private function resolveFilePath($filePath)
    {
        // 絶対パスの場合
        if (file_exists($filePath)) {
            return $filePath;
        }
        
        // 相対パスを試す
        $possiblePaths = [
            storage_path('app/' . $filePath),
            storage_path($filePath),
            base_path($filePath),
            public_path($filePath),
            $filePath
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path) && is_readable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    private function parseDataRow($dataLine, $headers)
    {
        $columns = str_getcsv($dataLine);
        $data = [];
        
        // デバッグ用：カラム数をチェック
        if (count($columns) < 10) {
            throw new Exception("カラム数が少なすぎます: " . count($columns));
        }
        
        foreach ($headers as $index => $columnName) {
            if ($columnName === 'iodine_placeholder') continue; // スキップ
            
            $value = isset($columns[$index]) ? $this->cleanValue($columns[$index], $columnName) : null;
            $data[$columnName] = $value;
        }
        
        // 必須フィールドのチェック
        if (empty($data['food_name']) || trim($data['food_name']) === '') {
            return null;
        }
        
        return $data;
    }
    
    private function cleanText($text)
    {
        if (empty($text)) return '';
        
        // 全角スペースを半角スペースに変換
        $text = str_replace('　', ' ', $text);
        
        // 連続するスペースを単一スペースに
        $text = preg_replace('/\s+/', ' ', $text);
        
        // 前後の空白を削除
        $text = trim($text);
        
        // 特殊文字の正規化
        $text = str_replace(['（', '）'], ['(', ')'], $text);
        $text = str_replace(['｜', '∣'], ['|', '|'], $text);
        
        // 全角の不等号記号などを半角に変換
        $text = str_replace(['＜', '＞'], ['<', '>'], $text);
        
        return $text;
    }
    
    private function cleanValue($value, $columnName = null)
    {
        if (empty($value)) return null;
        
        $value = $this->cleanText($value);
        
        // 特殊な値の処理
        if (in_array($value, ['*', '-', 'Tr', '(0)', '(Tr)', '', '?'])) {
            return match($value) {
                '*' => null,
                '-' => null,
                'Tr' => 0.001, // 微量
                '(0)' => 0,
                '(Tr)' => 0.001,
                '' => null,
                '?' => null,  // 追加: ?マークもnullとして処理
                default => null
            };
        }
        
        // 括弧内の値を抽出（推定値など）
        if (preg_match('/^\(([^)]+)\)$/', $value, $matches)) {
            $value = $matches[1];
            // 括弧内が特殊文字の場合は再処理
            if (in_array($value, ['*', '-', 'Tr', '0', '?'])) {
                return $this->cleanValue($value, $columnName);
            }
        }
        
        // †記号を削除（推定値マーク）
        $value = str_replace('†', '', $value);
        
        // 数値チェックと範囲制限
        if (is_numeric($value)) {
            $numValue = (float) $value;
            
            // カラム別の値域制限
            return $this->validateNumericValue($numValue, $columnName);
        }
        
        return $value;
    }
    
    private function validateNumericValue($value, $columnName)
    {
        // 各カラムの最大値制限（データ型に基づく）- より安全な値に修正
        $limits = [
            // DECIMAL(8,2) カラム - より安全な範囲に設定
            'available_carb_monosaccharide' => ['min' => 0, 'max' => 99999.9, 'precision' => 1],
            'refuse_rate' => ['min' => 0, 'max' => 999.99, 'precision' => 2],
            'water' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'protein' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'protein_amino_acid' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'fat' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'triglyceride' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'carbohydrate' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'available_carb_mass' => ['min' => 0, 'max' => 99999.9, 'precision' => 1],
            'available_carb_subtraction' => ['min' => 0, 'max' => 99999.9, 'precision' => 1],
            'dietary_fiber' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'sugar_alcohol' => ['min' => 0, 'max' => 99999.9, 'precision' => 1],
            'organic_acid' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'ash' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'iron' => ['min' => 0, 'max' => 9999.9, 'precision' => 1],
            'zinc' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'copper' => ['min' => 0, 'max' => 99.99, 'precision' => 2],
            'manganese' => ['min' => 0, 'max' => 99.99, 'precision' => 2],
            'vitamin_d' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'alpha_tocopherol' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'beta_tocopherol' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'gamma_tocopherol' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'delta_tocopherol' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'vitamin_b1' => ['min' => 0, 'max' => 99.99, 'precision' => 2],
            'vitamin_b2' => ['min' => 0, 'max' => 99.99, 'precision' => 2],
            'niacin' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'niacin_equivalent' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'vitamin_b6' => ['min' => 0, 'max' => 99.99, 'precision' => 2],
            'vitamin_b12' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'pantothenic_acid' => ['min' => 0, 'max' => 99.99, 'precision' => 2],
            'biotin' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'alcohol' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            'salt_equivalent' => ['min' => 0, 'max' => 999.9, 'precision' => 1],
            
            // INT カラム
            'energy_kj' => ['min' => 0, 'max' => 99999],
            'energy_kcal' => ['min' => 0, 'max' => 9999],
            'cholesterol' => ['min' => 0, 'max' => 9999],
            'sodium' => ['min' => 0, 'max' => 99999],
            'potassium' => ['min' => 0, 'max' => 99999],
            'calcium' => ['min' => 0, 'max' => 99999],
            'magnesium' => ['min' => 0, 'max' => 99999],
            'phosphorus' => ['min' => 0, 'max' => 99999],
            'iodine' => ['min' => 0, 'max' => 9999],
            'selenium' => ['min' => 0, 'max' => 9999],
            'chromium' => ['min' => 0, 'max' => 9999],
            'molybdenum' => ['min' => 0, 'max' => 9999],
            'retinol_activity_equivalent' => ['min' => 0, 'max' => 99999],
            'retinol' => ['min' => 0, 'max' => 99999],
            'alpha_carotene' => ['min' => 0, 'max' => 99999],
            'beta_carotene' => ['min' => 0, 'max' => 99999],
            'beta_cryptoxanthin' => ['min' => 0, 'max' => 99999],
            'beta_carotene_equivalent' => ['min' => 0, 'max' => 99999],
            'vitamin_k' => ['min' => 0, 'max' => 9999],
            'folate' => ['min' => 0, 'max' => 9999],
            'vitamin_c' => ['min' => 0, 'max' => 9999],
        ];
        
        if (isset($limits[$columnName])) {
            $limit = $limits[$columnName];
            if ($value < $limit['min']) {
                $value = $limit['min'];
            }
            if ($value > $limit['max']) {
                $value = $limit['max'];
            }
            if (isset($limit['precision'])) {
                $value = round($value, $limit['precision']);
            }
        }
        return $value;
    }
    
    private function analyzeDataRanges($data)
    {
        $this->info('データ値域分析中...');
        
        $ranges = [];
        foreach ($data as $row) {
            foreach ($row as $column => $value) {
                if (is_numeric($value)) {
                    $numValue = (float) $value;
                    if (!isset($ranges[$column])) {
                        $ranges[$column] = ['min' => $numValue, 'max' => $numValue];
                    } else {
                        $ranges[$column]['min'] = min($ranges[$column]['min'], $numValue);
                        $ranges[$column]['max'] = max($ranges[$column]['max'], $numValue);
                    }
                }
            }
        }
        
        // 注意が必要な値域を報告
        $warnings = [];
        foreach ($ranges as $column => $range) {
            if ($range['max'] > 99999) {
                $warnings[] = "{$column}: 最大値 {$range['max']} (INTの範囲を超過する可能性)";
            } elseif ($range['max'] > 999.9 && strpos($column, 'vitamin') !== false) {
                $warnings[] = "{$column}: 最大値 {$range['max']} (DECIMALの範囲を超過する可能性)";
            }
        }
        
        if (!empty($warnings)) {
            $this->warn('値域警告:');
            foreach ($warnings as $warning) {
                $this->line("  {$warning}");
            }
        }
    }
    
    private function insertToDatabase($data)
    {
        $this->info('データベーステーブルを作成中...');
        
        // テーブル削除・作成
        DB::statement('DROP TABLE IF EXISTS food_compositions');
        
        // より大きな値域に対応したテーブル定義
        $createTableSql = "
            CREATE TABLE food_compositions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                food_group VARCHAR(10),
                food_number VARCHAR(10),
                index_number VARCHAR(10),
                food_name VARCHAR(500) NOT NULL,
                refuse_rate DECIMAL(8,2),
                energy_kj INT,
                energy_kcal INT,
                water DECIMAL(8,1),
                protein DECIMAL(8,1),
                protein_amino_acid DECIMAL(8,1),
                fat DECIMAL(8,1),
                triglyceride DECIMAL(8,1),
                cholesterol INT,
                carbohydrate DECIMAL(8,1),
                available_carb_monosaccharide DECIMAL(10,1),
                available_carb_mass DECIMAL(10,1),
                available_carb_subtraction DECIMAL(10,1),
                dietary_fiber DECIMAL(8,1),
                sugar_alcohol DECIMAL(10,1),
                organic_acid DECIMAL(8,1),
                ash DECIMAL(8,1),
                sodium INT,
                potassium INT,
                calcium INT,
                magnesium INT,
                phosphorus INT,
                iron DECIMAL(8,1),
                zinc DECIMAL(8,1),
                copper DECIMAL(8,2),
                manganese DECIMAL(8,2),
                iodine INT,
                selenium INT,
                chromium INT,
                molybdenum INT,
                retinol_activity_equivalent INT,
                retinol INT,
                alpha_carotene INT,
                beta_carotene INT,
                beta_cryptoxanthin INT,
                beta_carotene_equivalent INT,
                vitamin_d DECIMAL(8,1),
                alpha_tocopherol DECIMAL(8,1),
                beta_tocopherol DECIMAL(8,1),
                gamma_tocopherol DECIMAL(8,1),
                delta_tocopherol DECIMAL(8,1),
                vitamin_k INT,
                vitamin_b1 DECIMAL(8,2),
                vitamin_b2 DECIMAL(8,2),
                niacin DECIMAL(8,1),
                niacin_equivalent DECIMAL(8,1),
                vitamin_b6 DECIMAL(8,2),
                vitamin_b12 DECIMAL(8,1),
                folate INT,
                pantothenic_acid DECIMAL(8,2),
                biotin DECIMAL(8,1),
                vitamin_c INT,
                alcohol DECIMAL(8,1),
                salt_equivalent DECIMAL(8,1),
                remarks TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_food_name (food_name(255)),
                INDEX idx_food_group (food_group),
                INDEX idx_food_number (food_number),
                INDEX idx_energy_kcal (energy_kcal)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        DB::statement($createTableSql);
        $this->info('テーブルを作成しました');
        
        // データのクリーンアップ（NULLフィールドを適切に処理）
        $cleanedData = [];
        foreach ($data as $row) {
            $cleanedRow = [];
            foreach ($row as $key => $value) {
                // 空文字列をNULLに変換
                if ($value === '' || $value === null) {
                    $cleanedRow[$key] = null;
                } else {
                    $cleanedRow[$key] = $value;
                }
            }
            $cleanedData[] = $cleanedRow;
        }
        
        // PDOエラーモードをサイレントに設定
        DB::connection()->getPdo()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        
        // データをバッチインサート
        $this->info('データをインサート中...');
        $batchSize = 10; // バッチサイズをさらに小さくして安全性を向上
        $batches = array_chunk($cleanedData, $batchSize);
        $insertedCount = 0;
        $skipCount = 0;
        
        foreach ($batches as $batchIndex => $batch) {
            try {
                DB::table('food_compositions')->insert($batch);
                $insertedCount += count($batch);
                $this->info("バッチ " . ($batchIndex + 1) . "/" . count($batches) . " 完了 (累計: {$insertedCount}件)");
            } catch (Exception $e) {
                $this->error("バッチ " . ($batchIndex + 1) . " でエラー: " . $e->getMessage());
                
                // 個別にインサートを試行
                foreach ($batch as $rowIndex => $row) {
                    try {
                        DB::table('food_compositions')->insert([$row]);
                        $insertedCount++;
                    } catch (Exception $rowError) {
                        $skipCount++;
                        $this->warn("行をスキップ ({$skipCount}): " . ($row['food_name'] ?? '不明'));
                        
                        // デバッグ用：最初の5個だけ詳細表示
                        if ($skipCount <= 5) {
                            $this->line("  エラー: " . $rowError->getMessage());
                            // 問題のある値を特定
                            foreach ($row as $col => $val) {
                                if (is_numeric($val) && $val > 999999) {
                                    $this->line("  大きな値: {$col} = {$val}");
                                }
                            }
                        }
                    }
                }
            }
        }
        
        $totalCount = DB::table('food_compositions')->count();
        $this->info("インサート完了: {$totalCount} 件 (スキップ: {$skipCount} 件)");
        
        // 統計情報を表示
        $this->displayStatistics();
    }
    
    private function displayStatistics()
    {
        $this->info("\n=== データベース統計 ===");
        
        $totalCount = DB::table('food_compositions')->count();
        $this->info("総件数: {$totalCount}");
        
        $groupStats = DB::table('food_compositions')
            ->select('food_group', DB::raw('COUNT(*) as count'))
            ->groupBy('food_group')
            ->orderBy('food_group')
            ->get();
            
        $this->info("食品群別件数:");
        foreach ($groupStats as $stat) {
            $this->line("  食品群 {$stat->food_group}: {$stat->count}件");
        }
        
        // サンプルデータを表示
        $samples = DB::table('food_compositions')
            ->select('food_name', 'energy_kcal', 'protein', 'fat', 'carbohydrate')
            ->limit(5)
            ->get();
            
        $this->info("サンプルデータ:");
        foreach ($samples as $sample) {
            $this->line("  {$sample->food_name} - {$sample->energy_kcal}kcal");
        }
    }
}