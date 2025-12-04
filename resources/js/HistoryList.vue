<template>
  <div class="history-list-component">
    <div v-if="errorMessage" class="p-2 mb-2 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
      {{ errorMessage }}
    </div>

    <ul v-if="!loading && historyItems.length > 0" class="space-y-2 max-h-64 overflow-y-auto">
      <li v-for="item in historyItems" :key="item.id" class="flex items-center justify-between p-2 border rounded">
        <div class="flex items-center space-x-3">
          <div class="font-medium text-sm">{{ item.food_name }}</div>
          <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">
            {{ Math.round((item.multiplier || 1) * 100) }}%
          </span>
        </div>
        <div class="flex items-center space-x-2">
          <button type="button" @click="selectItem(item)" class="text-sm bg-indigo-500 text-white px-3 py-1 rounded hover:bg-indigo-600">
            食事に登録
          </button>
          <!-- Favorite button could be added here if needed, but FavoriteList handles favorites separately -->
        </div>
      </li>
    </ul>
    <div v-else-if="!loading" class="p-2 text-gray-500">
      履歴はありません。
    </div>
    <div v-else class="p-4 text-center text-gray-500">
      読み込み中...
    </div>

    <!-- 選択後の確認・登録モーダル -->
    <!-- 選択後の確認・登録モーダル -->
    <Teleport to="body">
      <div v-if="selectedItem" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white p-4 rounded-lg w-full max-w-sm">
          <h3 class="font-bold text-lg mb-2">{{ selectedItem.food_name }}</h3>
          
          <div class="mb-4">
               <label class="block mb-1 font-semibold text-sm">量（%）</label>
               <div class="flex items-center gap-2">
                  <input type="number" v-model.number="percentInput" min="1" max="9999" step="1" class="border p-2 rounded w-24 text-sm">
                  <span class="text-sm text-gray-600">%</span>
               </div>
               <div class="text-xs text-gray-500 mt-1">
                  kcal: {{ calculateNutrient(selectedItem.energy_kcal_100g) }} / 
                  P: {{ calculateNutrient(selectedItem.proteins_100g) }} / 
                  F: {{ calculateNutrient(selectedItem.fat_100g) }} / 
                  C: {{ calculateNutrient(selectedItem.carbohydrates_100g) }}
               </div>
          </div>

          <div class="flex justify-end gap-2">
            <button @click="selectedItem = null" class="bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm">キャンセル</button>
            <button @click="registerItem" :disabled="isRegistering" class="bg-green-500 text-white px-4 py-2 rounded text-sm">
              {{ isRegistering ? '登録中...' : '登録' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script>
export default {
  name: 'HistoryList',
  props: {
    mealType: { type: String, required: true },
    date: { 
      type: String, 
      default: () => {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }
    }
  },
  data() {
    return {
      historyItems: [],
      loading: false,
      errorMessage: '',
      selectedItem: null,
      percentInput: 100,
      isRegistering: false,
    };
  },
  mounted() {
    this.fetchHistory();
  },
  methods: {
    async fetchHistory() {
      this.loading = true;
      try {
        await axios.get('/sanctum/csrf-cookie');
        const res = await axios.get('/api/food-suggestions');
        this.historyItems = res.data;
      } catch (error) {
        console.error('Failed to fetch history', error);
        this.errorMessage = '履歴の読み込みに失敗しました';
      } finally {
        this.loading = false;
      }
    },
    selectItem(item) {
      this.selectedItem = item;
      this.percentInput = Math.round((item.multiplier || 1) * 100);
    },
    calculateNutrient(valPer100g) {
        if (valPer100g == null) return '-';
        const mult = this.percentInput / 100;
        return (valPer100g * mult).toFixed(1);
    },
    async registerItem() {
      if (!this.selectedItem) return;
      this.isRegistering = true;

      const payload = {
        from_history_id: this.selectedItem.id,
        meal_type: this.mealType,
        consumed_at: this.date,
        multiplier: this.percentInput / 100,
      };

      try {
        console.log('Posting date:', this.date); // Debug log
        await axios.get('/sanctum/csrf-cookie');
        const res = await axios.post('/api/food-logs/history', payload);
        
        this.$emit('registered', res.data.data);
        this.selectedItem = null;

      } catch (error) {
        console.error('Registration failed', error);
        alert('登録に失敗しました');
      } finally {
        this.isRegistering = false;
      }
    }
  }
}
</script>
