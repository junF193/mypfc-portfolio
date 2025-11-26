@extends('layouts.app')

@section('content')
   <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">食品検索</h1>

        {{-- ★★★ actionとmethodを、正しく、設定する ★★★ --}}
        <form action="{{ route('food.search') }}" method="GET" id="search-form" class="mb-8">
            {{-- CSRFはGETリクエストでは不要ですが、あっても害はありません --}}
            @csrf 
            {{-- ★★★ Mypageから引き継いだ、meal_typeを、検索時にも、引き継がせる ★★★ --}}
            <input type="hidden" name="meal_type" value="{{ request('meal_type') }}">

            <div class="mb-4">
                <label for="search" class="block mb-1 font-semibold">食品名で検索:</label>
                <input type="text" id="search" name="search" placeholder="食品名を入力" class="border p-2 w-full rounded" value="{{ request('search') }}">
            </div>
            <div class="mb-4">
                <label for="barcode" class="block mb-1 font-semibold">バーコードで検索:</label>
                <input type="text" id="barcode" name="barcode" placeholder="バーコードを入力" class="border p-2 w-full rounded">
                <button type="button" id="startScanner" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded">スキャン開始</button>
            </div>
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">検索</button>
        </form>

        <div id="interactive" class="viewport mb-4"></div>

        @if (isset($error))
            <p class="text-red-500">{{ $error }}</p>
        @elseif (isset($results) && !$results->isEmpty())
            <h2 class="text-xl font-bold mb-4">検索結果</h2>
            <ul class="space-y-4">
                @foreach ($results as $product)
                    <li class="border p-4 rounded shadow">
                        <h3 class="text-lg font-semibold">{{ $product['food_name'] ?? '不明' }}</h3>
                        <p>カロリー: {{ $product['nutriments']['energy_kcal_100g'] ?? 'N/A' }}kcal</p>
                        <p>たんぱく質: {{ $product['nutriments']['proteins_100g'] ?? 'N/A' }}g</p>
                        <p>脂質: {{ $product['nutriments']['fat_100g'] ?? 'N/A' }}g</p>
                        <p>炭水化物: {{ $product['nutriments']['carbohydrates_100g'] ?? 'N/A' }}g</p>
                        <button data-product='{{ json_encode($product) }}' class="register-button mt-2 bg-yellow-500 text-white px-4 py-2 rounded">登録</button>
                    </li>
                @endforeach
            </ul>
        @elseif (isset($results))
            <p>検索結果が見つかりませんでした。</p>
        @endif
    </div>

    {{-- ★★★ ただ一つの、完璧な、scriptタグ ★★★ --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script>
        // この関数は、ボタンのonclickから、直接、呼び出されるように、グローバルに、定義する
        function selectFood(productJsonString) {
            const product = JSON.parse(productJsonString);

            const urlParams = new URLSearchParams(window.location.search);
            const mealType = urlParams.get('meal_type');

            if (!mealType) {
                alert('食事のタイミングが指定されていません。Mypageから、やり直してください。');
                return;
            }

            const dataToSend = {
                food_name: product.food_name,
                energy_kcal_100g: product.nutriments.energy_kcal_100g ?? null,
                proteins_100g: product.nutriments.proteins_100g ?? null,
                fat_100g: product.nutriments.fat_100g ?? null,
                carbohydrates_100g: product.nutriments.carbohydrates_100g ?? null,
                meal_type: mealType,
                source_type: product.source,
                source_food_number: product.food_number,
            };

            const csrfToken = document.querySelector('input[name="_token"]').value;

            fetch('{{ route("food-logs.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(dataToSend),
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(result => {
                if (result.message) {
                    alert(result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                let errorMessage = '登録中にエラーが発生しました。';
                if (error.errors) {
                    errorMessage += '\n' + Object.values(error.errors).flat().join('\n');
                }
                alert(errorMessage);
            });
        }

        // ページの読み込みが完了したら、イベントリスナーを、設定する
        document.addEventListener('DOMContentLoaded', function () {
            // --- 「登録」ボタンの、設定 ---
            const registerButtons = document.querySelectorAll('.register-button');
            registerButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const productJsonString = this.getAttribute('data-product');
                    selectFood(productJsonString);
                });
            });

            // --- バーコードスキャナの、設定 ---
            const startScannerBtn = document.getElementById('startScanner');
            const searchForm = document.getElementById('search-form');

            if (startScannerBtn) {
                startScannerBtn.addEventListener('click', function() {
                    Quagga.init({ /* ... Quaggaの設定 ... */ }, function(err) { if (err) { console.error(err); alert('カメラの初期化に失敗しました。'); return; } Quagga.start(); });
                });
            }

            Quagga.onDetected(function(data) {
                document.getElementById('barcode').value = data.codeResult.code;
                Quagga.stop();
                searchForm.action = "{{ route('food.barcode') }}";
                searchForm.method = "GET";
                searchForm.submit();
            });
        });
    </script>
@endsection