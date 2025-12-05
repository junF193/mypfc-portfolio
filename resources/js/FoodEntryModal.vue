<template>
  <div v-if="isOpen" v-show="isOpen" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50">
    
    <!-- モーダルのメインコンテンツコンテナ -->
    <!-- w-11/12 md:w-2/3: 画面幅に応じたレスポンシブな幅設定 -->
    <div style="position: relative; width: 90%; max-width: 600px; max-height: 90vh; display: flex; flex-direction: column; background-color: white; border-radius: 0.5rem; margin: auto; overflow: hidden; padding: 20px;">
      
      <!-- ヘッダー部分：タイトルと閉じるボタン -->
      <div class="flex items-start justify-between mb-4">
        <!-- タイトル表示 (computedプロパティ title を使用して「朝食の登録」などを表示) -->
        <h3 class="text-xl font-semibold">{{ title }}</h3>
        
        <!-- 閉じるボタン (@clickでcloseメソッドを呼び出し) -->
        <button type="button" @click="close" aria-label="閉じる" class="text-gray-600 hover:text-gray-800 text-2xl leading-none">&times;</button>
      </div>

      <!-- 日付選択 -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">日付</label>
        <input type="date" v-model="date" class="border rounded p-2 w-full md:w-auto">
      </div>

      <!-- タブナビゲーションエリア -->
      <div class="mb-4 border-b border-gray-200">
        <nav class="-mb-px flex space-x-4 overflow-x-auto" aria-label="Tabs">
          <!-- tabs配列をループしてタブボタンを生成 -->
          <button v-for="tab in tabs" :key="tab.id"
                  @click="currentTab = tab.id" 
                  :class="[
                    currentTab === tab.id
                      ? 'border-indigo-500 text-indigo-600' // 選択中のタブのスタイル（青色）
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', // 非選択タブのスタイル
                    'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                  ]">
            {{ tab.name }} <!-- タブ名（履歴、お気に入り、など） -->
          </button>
        </nav>
      </div>

      <!-- コンテンツエリア：選択されたタブの中身を表示 -->
      <div class="mt-4">
        <!-- keep-alive: タブを切り替えても入力内容やスクロール位置などの状態を維持する -->
        <keep-alive>
          <component :is="currentTabComponent" 
                     :meal-type="mealType" 
                     :date="date"
                     @registered="handleRegistered" />
        </keep-alive>
      </div>
    </div>
  </div>
</template>

<script>
// 各タブの中身となる子コンポーネントのインポート
import HistoryList from './HistoryList.vue';
import FavoriteList from './FavoriteList.vue'; // 既存のコンポーネント
import ManualEntry from './ManualEntry.vue';
import SearchEntry from './SearchEntry.vue';

export default {
  name: 'FoodEntryModal', // コンポーネント名
  components: {
    HistoryList,
    FavoriteList,
    ManualEntry,
    SearchEntry
  },
  data() {
    return {
      isOpen: false, // モーダルの表示状態 (trueで表示、falseで非表示)
      mealType: '', // 食事タイプ (breakfast, lunch, dinner, snack)
      date: (() => {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      })(), // 日付 (デフォルトは今日 YYYY-MM-DD - ローカル時間)
      currentTab: 'history', // 現在選択されているタブID (初期値は履歴)
      tabs: [ // タブの定義リスト
        { id: 'history', name: '履歴' },
        { id: 'favorites', name: 'お気に入り' },
        { id: 'manual', name: '手入力' },
        { id: 'search', name: '検索' },
      ]
    };
  },
  computed: {
    // モーダルのタイトルを動的に生成するプロパティ
    title() {
      const mealLabels = {
        breakfast: '朝食',
        lunch: '昼食',
        dinner: '夕食',
        snack: '間食'
      };
      // mealTypeに対応する日本語ラベルを取得、なければ'食事'とする
      const label = mealLabels[this.mealType] || '食事';
      return `${label}の登録`; // 例: "朝食の登録"
    },
    // 現在のタブIDに対応するコンポーネント名を返すプロパティ
    currentTabComponent() {
      switch (this.currentTab) {
        case 'history': return 'HistoryList';
        case 'favorites': return 'FavoriteList';
        case 'manual': return 'ManualEntry';
        case 'search': return 'SearchEntry';
        default: return 'HistoryList';
      }
    }
  },
  created() {
    console.log('FoodEntryModal created. Initial isOpen:', this.isOpen);
    // コンポーネント作成時にグローバルイベントリスナーを登録
    // 外部（mypage.jsなど）から 'open-food-entry-modal' イベントが発火されたら open メソッドを実行
    window.addEventListener('open-food-entry-modal', this.open);
  },
  beforeUnmount() {
    // コンポーネント破棄時にイベントリスナーを削除 (メモリリーク防止のため必須)
    window.removeEventListener('open-food-entry-modal', this.open);
  },
  methods: {
    // モーダルを開く処理
    open(event) {
      this.mealType = event.detail.mealType; // イベント詳細から食事タイプを取得
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      const today = `${year}-${month}-${day}`;
      
      this.date = event.detail.date || today; // 日付を取得 (ローカル時間)
      console.log('FoodEntryModal open date:', this.date); // Debug log
      // 指定があればそのタブを開く、なければ履歴タブをデフォルトにする
      this.currentTab = event.detail.tab || 'history';
      this.isOpen = true; // モーダルを表示状態にする
      // 今日の栄養摂取状況を更新 (mypage.jsなどで定義されたグローバル関数呼び出し)
      if (typeof window.refreshDailyNutrition === 'function') {
          window.refreshDailyNutrition();
      }
    },
    // モーダルを閉じる処理
    close() {
      this.isOpen = false;
    },
    // 食事登録完了時の処理（子コンポーネントから呼ばれる）
    handleRegistered(data) {
      // 登録完了時の処理
      // 登録した日付のページにリダイレクトして表示を更新する
      const targetDate = this.date;
      window.location.href = `/mypage?date=${targetDate}`;
    }
  }
}
</script>
