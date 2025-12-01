<template>
  <div v-if="isOpen" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded shadow-lg w-11/12 md:w-2/3 lg:w-1/2 max-h-[90vh] overflow-y-auto">
      <div class="flex items-start justify-between mb-4">
        <h3 class="text-xl font-semibold">{{ title }}</h3>
        <button type="button" @click="close" aria-label="閉じる" class="text-gray-600 hover:text-gray-800 text-2xl leading-none">&times;</button>
      </div>

      <!-- Tabs -->
      <div class="mb-4 border-b border-gray-200">
        <nav class="-mb-px flex space-x-4 overflow-x-auto" aria-label="Tabs">
          <button v-for="tab in tabs" :key="tab.id"
                  @click="currentTab = tab.id"
                  :class="[
                    currentTab === tab.id
                      ? 'border-indigo-500 text-indigo-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                    'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                  ]">
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Content -->
      <div class="mt-4">
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
import HistoryList from './HistoryList.vue';
import FavoriteList from './FavoriteList.vue'; // Existing component
import ManualEntry from './ManualEntry.vue';
import SearchEntry from './SearchEntry.vue';

export default {
  name: 'FoodEntryModal',
  components: {
    HistoryList,
    FavoriteList,
    ManualEntry,
    SearchEntry
  },
  data() {
    return {
      isOpen: false,
      mealType: '',
      date: new Date().toISOString().slice(0, 10),
      currentTab: 'history',
      tabs: [
        { id: 'history', name: '履歴' },
        { id: 'favorites', name: 'お気に入り' },
        { id: 'manual', name: '手入力' },
        { id: 'search', name: '検索' },
      ]
    };
  },
  computed: {
    title() {
      const mealLabels = {
        breakfast: '朝食',
        lunch: '昼食',
        dinner: '夕食',
        snack: '間食'
      };
      const label = mealLabels[this.mealType] || '食事';
      return `${label}の登録`;
    },
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
    // Listen for global event to open modal
    window.addEventListener('open-food-entry-modal', this.open);
  },
  beforeUnmount() {
    window.removeEventListener('open-food-entry-modal', this.open);
  },
  methods: {
    open(event) {
      this.mealType = event.detail.mealType;
      this.date = event.detail.date || new Date().toISOString().slice(0, 10);
      // Default to history or specific tab if requested
      this.currentTab = event.detail.tab || 'history';
      this.isOpen = true;
    },
    close() {
      this.isOpen = false;
    },
    handleRegistered(data) {
      // Close modal
      this.close();
      
      // Notify user
      if (typeof window.showToast === 'function') {
          window.showToast('登録しました', 'success');
      } else {
          alert('登録しました');
      }

      // Refresh daily nutrition
      if (typeof window.refreshDailyNutrition === 'function') {
          window.refreshDailyNutrition();
      }
    }
  }
}
</script>
