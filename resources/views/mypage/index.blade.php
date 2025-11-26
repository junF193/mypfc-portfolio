@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-4">マイページ</h1>

  {{-- セクションボタン群（朝/昼/夕/間食） --}}
  <div class="grid grid-cols-1 gap-4">
    @foreach(['breakfast'=>'朝食','lunch'=>'昼食','dinner'=>'夕食','snack'=>'間食'] as $mealKey => $mealLabel)
      <div class="mb-4 p-4 border rounded shadow">
        <h2 class="text-xl font-semibold mb-3">{{ $mealLabel }}</h2>
        <div class="flex items-center space-x-4">
          <a href="{{ route('food-log.create', ['meal_type' => $mealKey]) }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">手入力で登録</a>
          <a href="{{ route('food.search', ['meal_type' => $mealKey]) }}" class="ml-4 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">検索して登録</a>
          <button type="button" onclick="openSuggestionModal('{{ $mealKey }}')" class="ml-4 bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">履歴から選択</button>
        </div>
      </div>
    @endforeach
  </div>

  {{-- 履歴選択用モーダル（一覧は最小表示、詳細編集は下の editor で） --}}
  <div id="suggestion-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded shadow-lg w-11/12 md:w-2/3 lg:w-1/2">
      <div class="flex items-start justify-between mb-4">
        <h3 class="text-xl font-semibold" id="suggestion-title">履歴から選択</h3>
        <button type="button" onclick="closeSuggestionModal()" aria-label="閉じる" class="text-gray-600 hover:text-gray-800">✕</button>
      </div>

      {{-- === ここから追加: モーダル内タブ（履歴登録 / お気に入り一覧） === --}}
      <div class="mb-4">
        <div class="inline-flex rounded-md shadow-sm" role="tablist" aria-label="モーダル内タブ">
          <button id="modal-tab-history" class="mypage-tab px-4 py-2 bg-white border border-gray-200 rounded-l-md" data-tab="modal-history" role="tab" aria-selected="true">履歴登録</button>
          <button id="modal-tab-favorites" class="mypage-tab px-4 py-2 bg-white border-t border-b border-r border-gray-200 rounded-r-md" data-tab="modal-favorites" role="tab" aria-selected="false">お気に入り一覧</button>
        </div>
      </div>
      {{-- === 追加ここまで === --}}

      {{-- モーダル内のコンテンツ領域 --}}
      <div class="grid grid-cols-1 gap-4">
        {{-- 履歴の一覧（最小表示：食品名 / % / ハート） -- the original list remains but wrapped as a pane --}}
        <div id="modal-pane-history" data-pane="modal-history" class="mypage-pane">
          <ul id="modal-suggestion-list" class="history-list space-y-2 max-h-64 overflow-y-auto">

            @forelse($suggestions as $item)
              @php
                $isFav = isset($item->is_favorited) ? (bool)$item->is_favorited : (in_array($item->id, $favoriteIds ?? []) ? true : false);
                $multiplier = $item->multiplier ?? 1;
                $dName = e($item->food_name);
              @endphp

              <li class="flex items-center justify-between p-2 border rounded" data-food-log-id="{{ $item->id }}">
                <div class="flex items-center space-x-3">
                  <div class="font-medium text-sm">{{ $item->food_name }}</div>
                  <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700" aria-label="既定のパーセント">
                    {{ number_format($multiplier * 100, 0) }}%
                  </span>
                </div>

                <div class="flex items-center space-x-2">
                  {{-- 選択（開いて % 編集する） --}}
                  <button type="button"
                          class="select-history-btn text-sm bg-indigo-500 text-white px-3 py-1 rounded hover:bg-indigo-600"
                          data-food-log-id="{{ $item->id }}"
                          data-multiplier="{{ $multiplier }}"
                          data-energy="{{ $item->energy_kcal_100g ?? 0 }}"
                          data-proteins="{{ $item->proteins_100g ?? 0 }}"
                          data-fat="{{ $item->fat_100g ?? 0 }}"
                          data-carbs="{{ $item->carbohydrates_100g ?? 0 }}"
                          data-food-name="{{ $dName }}"
                          data-meal-type="{{ $item->meal_type ?? '' }}">
                    選択して編集
                  </button>

                  {{-- ハート（お気に入り） --}}
                  <button type="button" class="favorite-btn p-2 rounded"
                          data-food-log-id="{{ $item->id }}"
                          data-food-name="{{ e($item->food_name) }}" 
                          data-favorited="{{ $isFav ? 'true' : 'false' }}"
                          aria-pressed="{{ $isFav ? 'true' : 'false' }}"
                          title="{{ $isFav ? 'お気に入りを解除' : 'お気に入りに追加' }}">
                    <span class="favorite-icon text-lg">{{ $isFav ? '❤️' : '🤍' }}</span>
                  </button>
                </div>
              </li>
            @empty
              <li class="p-2 text-gray-500">履歴はありません。</li>
            @endforelse
          </ul>
        </div>

        {{-- お気に入りペイン（初期は非表示。既存の favorites 変数を使う） --}}
       <div id="modal-pane-favorites" data-pane="modal-favorites" class="mypage-pane hidden">
  <div id="favorite-vue"
       data-initial-favorites='@json($favoriteLogs ?? [])'
       data-fetch-url='{{ route("favorites.index") }}'
       data-toggle-url-base='{{ route("favorites.update", ["favorite" => "__ID__"]) }}'>
    <!-- Vue がここに描画します -->
  </div>
</div>
        

        {{-- 編集パネル（最初は隠す。選択ボタンで表示） --}}
        <div id="history-editor" class="mt-4 hidden border-t pt-4">
          <div class="mb-2 font-medium" id="modal-food-name">食品名</div>

          <div class="flex items-center gap-3 mb-3">
            <label class="text-sm whitespace-nowrap">食べた量（%）</label>
            <input id="custom-percent" type="number" min="25" max="9999" step="1" class="border p-1 rounded w-28" />
            <div class="text-sm text-gray-500">（25 〜 9999）</div>
          </div>

          <div class="text-sm text-gray-700 mb-4">
            kcal: <span id="preview-kcal">—</span>
            たんぱく質: <span id="preview-protein">—</span>
            脂質: <span id="preview-fat">-</span>
            炭水化物: <span id="preview-carbs">-</span>
          </div>

          <div class="flex justify-end gap-2">
            <button id="editor-cancel" class="px-3 py-1 rounded bg-gray-200">キャンセル</button>
            <button id="editor-register" class="px-3 py-1 rounded bg-green-500 text-white">登録</button>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- 既存の PFC 表示など続き（そのまま） --}}
  <div id="daily-nutrition" class="mb-6 p-4 bg-white rounded shadow">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold">1日のPFC / カロリー</h2>

      <div class="flex items-center space-x-2">
        <label for="nutrition-date" class="text-sm text-gray-600">日付：</label>
        <input id="nutrition-date" type="date" value="{{ date('Y-m-d') }}" class="border px-2 py-1 rounded" />
        <button id="refresh-nutrition-btn" class="ml-2 bg-indigo-500 text-white px-3 py-1 rounded hover:bg-indigo-600">更新</button>
      </div>
    </div>

    <div class="md:flex md:items-start md:space-x-6">
      <div class="w-full md:w-1/3" style="min-height:240px">
        <canvas id="pfcChart" aria-label="PFC Doughnut chart" role="img"></canvas>
      </div>

      <div class="mt-4 md:mt-0 md:flex-1">
        <div class="mb-2">
          <div class="text-sm text-gray-500">合計カロリー</div>
          <div id="calories-total" class="text-2xl font-bold">-- kcal</div>
        </div>

        <div class="mt-3 grid grid-cols-3 gap-3">
          <div class="text-center p-3 bg-gray-50 rounded">
            <div class="text-xs text-gray-500">Protein</div>
            <div id="protein-percent" class="text-lg font-semibold">--%</div>
            <div id="protein-kcal" class="text-sm text-gray-600">-- kcal</div>
          </div>

          <div class="text-center p-3 bg-gray-50 rounded">
            <div class="text-xs text-gray-500">Fat</div>
            <div id="fat-percent" class="text-lg font-semibold">--%</div>
            <div id="fat-kcal" class="text-sm text-gray-600">-- kcal</div>
          </div>

          <div class="text-center p-3 bg-gray-50 rounded">
            <div class="text-xs text-gray-500">Carbs</div>
            <div id="carbs-percent" class="text-lg font-semibold">--%</div>
            <div id="carbs-kcal" class="text-sm text-gray-600">-- kcal</div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

@push('scripts')


<script>


window.MYPAGE_CONFIG = {
    csrf: '{{ csrf_token() }}',
    favoritesStoreUrl: @json(route('favorites.store')),
    favoritesDestroyBase: '/api/favorites/',
    foodLogsStoreUrl: @json(route('food-logs.store')),
    dailyNutritionUrl: @json(route('mypage.daily-nutrition')),
    fetchFavoritesUrl: @json(route('favorites.index')),
};
</script>


<!-- Chart.js を先に読み込む（UMD ビルド。非モジュール向け） -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>


<script src="{{ asset('js/mypage.js') }}"></script>

@endpush


@endsection

