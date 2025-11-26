@extends('layouts.app')

@section('content')
     <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">食事記録</h1>

        {{-- エラー表示用のコンテナ --}}
        <div id="form-errors" class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg hidden"></div>

        {{-- バリデーションエラーの表示 --}}
        @if ($errors->any())
            <div class="text-red-500">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
       
       
       <form id="food-log-form" class="mb-8">
        
        @csrf

        
        <input type="hidden" id="meal_type" name="meal_type" value="{{ request('meal_type') }}">

        
        <input type="hidden" id="source_type" name="source_type" value="manual">

        <div class="mb-4">
            <label for="food_name" class="block mb-1 font-semibold">メニュー名:</label>
            <input type="text" id="food_name" name="food_name" placeholder="メニューを入力" class="border p-2 w-full rounded">
        </div>

        <div class="mb-4">
            <label for="energy_kcal_100g" class="block mb-1 font-semibold">カロリー</label>
            <input type="text" id="energy_kcal_100g" name="energy_kcal_100g" placeholder="カロリーを入力"  class="border p-2 w-full rounded">
        </div>

        <div class="mb-4">
            <label for="proteins_100g" class="block mb-1 font-semibold">タンパク質</label>
            <input type="text" id="proteins_100g" name="proteins_100g" placeholder="タンパク質を入力" class="border p-2 w-full rounded">
        </div>

        <div class="mb-4">
            <label for="fat_100g" class="block mb-1 font-semibold">脂質</label>
            <input type="text" id="fat_100g" name="fat_100g" placeholder="脂質入力" class="border p-2 w-full rounded">
        </div>
        
        <div class="mb-4">
            <label for="carbohydrates_100g" class="block mb-1 font-semibold">炭水化物</label>
            <input type="text" id="carbohydrates_100g" name="carbohydrates_100g" placeholder="炭水化物を入力" class="border p-2 w-full rounded">
        </div>  
        
        <div class="mb-4">
              <label class="block mb-1 font-semibold">量（%）</label>
              <div class="flex flex-wrap gap-2 mb-2">
                <button type="button" class="preset-percent px-3 py-1 rounded bg-gray-100 text-sm" data-percent="25">25%</button>
                <button type="button" class="preset-percent px-3 py-1 rounded bg-gray-100 text-sm" data-percent="50">50%</button>
                <button type="button" class="preset-percent px-3 py-1 rounded bg-gray-100 text-sm" data-percent="75">75%</button>
                <button type="button" class="preset-percent px-3 py-1 rounded bg-gray-100 text-sm" data-percent="100">100%</button>
                <button type="button" class="preset-percent px-3 py-1 rounded bg-gray-100 text-sm" data-percent="200">200%</button>
        </div>
        <div class="flex items-center gap-2">
    <input id="percent-input" name="percent" type="number" min="25" max="9999" step="1"
           value="100"
           class="border p-2 rounded w-28" aria-label="食べた割合（パーセント）" />
       <span class="text-sm text-gray-600">%</span>
       <div class="text-sm text-gray-500 ml-3">（デフォルト: 100%）</div>
        </div>
     </div>

        
        
        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">登録</button>
        </div>
       </form>


       

       
       <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('food-log-form');
            const errorContainer = document.getElementById('form-errors'); // エラー表示用のコンテナ

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = '登録中...';
                if(errorContainer) errorContainer.innerHTML = ''; // エラー表示をクリア

                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());

                fetch('{{ route("food-logs.store") }}', {
                    method: 'POST',
                    credentials: 'include', // クロスオリジンでもCookieを送信
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                })
                .then(response => response.json()) // どんな応答でもまずJSONとしてパース
                .then(result => {
                    if (result.status === 'success') {
                        // 成功したらマイページにリダイレクト
                        window.location.href = '{{ route("mypage.index") }}';
                    } else {
                        // バリデーションエラーなど、サーバーからのエラーメッセージを表示
                        let errorHtml = '<p>' + (result.message || '入力内容に誤りがあります。') + '</p>';
                        if (result.errors) {
                            errorHtml += '<ul class="list-disc list-inside">';
                            for (const key in result.errors) {
                                errorHtml += '<li>' + result.errors[key].join(', ') + '</li>';
                            }
                            errorHtml += '</ul>';
                        }
                        if(errorContainer) {
                            errorContainer.innerHTML = errorHtml;
                            errorContainer.classList.remove('hidden'); // hiddenクラスを削除して表示
                        }
                        // 後続のcatchで処理させないように、ここでエラーを投げる
                        throw new Error('Validation failed: ' + (result.message || ''));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // 予期せぬエラー（通信エラーなど）の場合のみ、汎用メッセージを表示
                    if(errorContainer && !errorContainer.innerHTML) {
                       errorContainer.innerHTML = '<p>登録中に予期せぬエラーが発生しました。時間をおいて再試行してください。</p>';
                       errorContainer.classList.remove('hidden');
                    }
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.textContent = '登録';
                });
            });
        });
       </script>
@endsection
