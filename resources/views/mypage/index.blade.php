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
  <div class="grid grid-cols-1 gap-4">
    @foreach(['breakfast'=>'朝食','lunch'=>'昼食','dinner'=>'夕食','snack'=>'間食'] as $mealKey => $mealLabel)
      <div class="mb-4 p-4 border rounded shadow">
        <h2 class="text-xl font-semibold mb-3">{{ $mealLabel }}</h2>
        <div class="flex items-center space-x-4">
          <button type="button" 
                  onclick="window.dispatchEvent(new CustomEvent('open-food-entry-modal', { detail: { mealType: '{{ $mealKey }}', tab: 'manual' } }))"
                  class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            手入力で登録
          </button>
          <button type="button" 
                  onclick="window.dispatchEvent(new CustomEvent('open-food-entry-modal', { detail: { mealType: '{{ $mealKey }}', tab: 'search' } }))"
                  class="ml-4 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
            検索して登録
          </button>
          <button type="button" 
                  onclick="window.dispatchEvent(new CustomEvent('open-food-entry-modal', { detail: { mealType: '{{ $mealKey }}', tab: 'history' } }))"
                  class="ml-4 bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
            履歴から選択
          </button>
        </div>
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

        <div class="flex justify-end pt-4">
          <button type="button" onclick="saveProfile()" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">保存して計算</button>
        </div>
      </form>
    </div>
  </div>

@endsection

