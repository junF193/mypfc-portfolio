@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
  <div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">マイページ</h1>
    <button onclick="openProfileModal()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 text-sm">
      ⚙️ 目標設定
    </button>
  </div>

  {{-- セクションボタン群（朝/昼/夕/間食） --}}
  <!-- Date Navigation -->
  <div class="flex justify-between items-center mb-6 bg-white p-4 rounded-lg shadow">
    <a href="{{ route('mypage.index', ['date' => $currentDate->copy()->subDay()->format('Y-m-d')]) }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
      &lt; 前日
    </a>
    <div class="text-center">
      <label for="header-date-input" class="sr-only">日付選択</label>
      <input type="date" id="header-date-input" value="{{ $currentDate->format('Y-m-d') }}" class="text-xl font-bold text-gray-800 border-none bg-transparent text-center focus:ring-indigo-500 rounded cursor-pointer">
      <div class="text-sm text-gray-500">({{ $currentDate->isoFormat('ddd') }})</div>
    </div>
    <a href="{{ route('mypage.index', ['date' => $currentDate->copy()->addDay()->format('Y-m-d')]) }}" class="text-indigo-600 hover:text-indigo-800 font-bold">
      翌日 &gt;
    </a>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          @php
            $mealTypes = [
              'breakfast' => '朝食',
              'lunch' => '昼食',
              'dinner' => '夕食',
              'snack' => '間食'
            ];
          @endphp

          @foreach ($mealTypes as $mealKey => $mealLabel)
          <div class="bg-white p-4 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-bold text-gray-700">{{ $mealLabel }}</h3>
              <div class="text-gray-600">
                <span class="font-bold text-xl">{{ number_format($mealTotals->get($mealKey, 0)) }}</span> kcal
              </div>
            </div>

            <!-- Food List -->
            @if(isset($groupedLogs[$mealKey]) && $groupedLogs[$mealKey]->isNotEmpty())
            <ul class="mb-4 space-y-2">
              @foreach($groupedLogs[$mealKey] as $log)
              <li class="flex justify-between items-center border-b pb-2 text-sm">
                <div class="flex-1">
                  <div class="font-medium text-gray-800">{{ $log->food_name }}</div>
                  <div class="text-xs text-gray-500">
                    {{ number_format($log->energy_kcal_100g * ($log->multiplier ?? 1)) }} kcal
                  </div>
                </div>
                <button type="button" 
                        class="text-red-400 hover:text-red-600 ml-2 p-1"
                        onclick="deleteFoodLog({{ $log->id }})">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </li>
              @endforeach
            </ul>
            @endif

            <button type="button" 
                    class="js-open-food-modal w-full bg-indigo-50 text-indigo-600 px-4 py-2 rounded hover:bg-indigo-100 flex items-center justify-center transition-colors"
                    data-meal-type="{{ $mealKey }}">
              <span class="mr-1 text-lg font-bold">＋</span> 追加
            </button>
          </div>
          @endforeach
  </div>

  {{-- 新しいVueモーダル用コンテナ --}}
  <div id="food-entry-modal"></div>

  {{-- 既存の PFC 表示など続き（そのまま） --}}
  <div id="daily-nutrition" class="mb-6 p-4 bg-white rounded shadow">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold">1日のPFC / カロリー</h2>

      <div class="flex items-center space-x-2">
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
    dailyNutritionUrl: @json(route('mypage.daily-nutrition')),
    fetchFavoritesUrl: @json(route('favorites.index')),
};
</script>


<!-- Chart.js を先に読み込む（UMD ビルド。非モジュール向け） -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>


<script src="{{ asset('js/mypage.js') }}"></script>

@endpush


  {{-- プロフィール設定モーダル --}}
  <div id="profile-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded shadow-lg w-11/12 md:w-1/3">
      <div class="flex items-start justify-between mb-4">
        <h3 class="text-xl font-semibold">目標設定 (TDEE計算)</h3>
        <button type="button" onclick="closeProfileModal()" class="text-gray-600 hover:text-gray-800">✕</button>
      </div>
      
      <form id="profile-form" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">性別</label>
          <div class="mt-1 flex space-x-4">
            <label class="inline-flex items-center">
              <input type="radio" name="gender" value="male" class="form-radio" {{ ($user->gender->value ?? '') === 'male' ? 'checked' : '' }}>
              <span class="ml-2">男性</span>
            </label>
            <label class="inline-flex items-center">
              <input type="radio" name="gender" value="female" class="form-radio" {{ ($user->gender->value ?? '') === 'female' ? 'checked' : '' }}>
              <span class="ml-2">女性</span>
            </label>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">身長 (cm)</label>
            <input type="number" name="height" step="0.1" value="{{ $user->height }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border p-2">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">体重 (kg)</label>
            <input type="number" name="weight" step="0.1" value="{{ $user->weight }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border p-2">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">年齢</label>
          <input type="number" name="age" value="{{ $user->age }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border p-2">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">活動レベル</label>
          <select name="activity_level" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border p-2">
            <option value="low" {{ ($user->activity_level->value ?? '') === 'low' ? 'selected' : '' }}>低い (デスクワーク中心)</option>
            <option value="medium" {{ ($user->activity_level->value ?? 'medium') === 'medium' ? 'selected' : '' }}>普通 (立ち仕事・軽い運動)</option>
            <option value="high" {{ ($user->activity_level->value ?? '') === 'high' ? 'selected' : '' }}>高い (肉体労働・激しい運動)</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">目的</label>
          <select name="diet_goal" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border p-2">
            <option value="lose" {{ ($user->diet_goal->value ?? 'maintain') === 'lose' ? 'selected' : '' }}>減量 (-500kcal)</option>
            <option value="maintain" {{ ($user->diet_goal->value ?? 'maintain') === 'maintain' ? 'selected' : '' }}>現状維持 (±0kcal)</option>
            <option value="gain" {{ ($user->diet_goal->value ?? 'maintain') === 'gain' ? 'selected' : '' }}>増量 (+300kcal)</option>
          </select>
        </div>

        <div class="flex justify-end pt-4">
          <button type="button" onclick="saveProfile()" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">保存して計算</button>
        </div>
        <p class="text-xs text-gray-500 mt-4">
          ※計算結果は目安であり、医学的な助言ではありません。
        </p>
      </form>
    </div>
  </div>

@endsection

