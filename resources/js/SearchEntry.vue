<template>
  <div class="search-entry">
    <div v-if="errorMessage" class="p-2 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
      {{ errorMessage }}
    </div>

    <!-- 検索フォーム -->
    <div class="mb-6 space-y-4">
      <div>
        <label for="search-keyword" class="block mb-1 font-semibold text-sm">食品名で検索</label>
        <div class="flex gap-2">
          <input type="text" id="search-keyword" v-model="searchKeyword" @keyup.enter="searchFood" placeholder="食品名を入力" class="border p-2 w-full rounded text-sm">
          <button type="button" @click="searchFood" :disabled="isSearching" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm whitespace-nowrap">
            {{ isSearching ? '検索中' : '検索' }}
          </button>
        </div>
      </div>

      <div>
        <label for="search-barcode" class="block mb-1 font-semibold text-sm">バーコードで検索</label>
        <div class="flex gap-2">
          <input type="text" id="search-barcode" v-model="barcode" @keyup.enter="searchBarcode" placeholder="バーコードを入力" class="border p-2 w-full rounded text-sm">
          <button type="button" @click="searchBarcode" :disabled="isSearching" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 text-sm whitespace-nowrap">
            検索
          </button>
        </div>
        <!-- バーコードスキャナー起動ボタン (今回は省略または後で実装) -->
      </div>
    </div>

    <!-- 検索結果 -->
    <div v-if="searchResults.length > 0" class="space-y-4 max-h-96 overflow-y-auto">
      <div v-for="product in searchResults" :key="product.code || product.food_name" class="border p-3 rounded shadow-sm hover:bg-gray-50">
        <div class="flex justify-between items-start">
          <div class="flex gap-3">
             <div class="w-16 h-16 flex-shrink-0 bg-gray-100 rounded overflow-hidden flex items-center justify-center">
                 <img v-if="product.image_url" :src="product.image_url" alt="" class="w-full h-full object-cover">
                 <span v-else class="text-xs text-gray-400">No Image</span>
             </div>
             <div>
                <h3 class="font-bold text-sm mb-1">{{ product.food_name }}</h3>
                <div class="text-xs text-gray-600 grid grid-cols-2 gap-x-4 gap-y-1">
                  <span>Cal: {{ formatNutrient(product.energy_kcal_100g) }} kcal</span>
                  <span>P: {{ formatNutrient(product.proteins_100g) }} g</span>
                  <span>F: {{ formatNutrient(product.fat_100g) }} g</span>
                  <span>C: {{ formatNutrient(product.carbohydrates_100g) }} g</span>
                </div>
             </div>
          </div>
          <button type="button" @click="selectProduct(product)" class="bg-yellow-500 text-white px-3 py-1 rounded text-xs hover:bg-yellow-600 whitespace-nowrap">
            選択
          </button>
        </div>
      </div>
    </div>
    <div v-else-if="searched && searchResults.length === 0" class="text-center text-gray-500 py-4">
      検索結果が見つかりませんでした。
    </div>

    <!-- 選択後の確認・登録モーダル (簡易的) -->
    <!-- 選択後の確認・登録モーダル (簡易的) -->
    <Teleport to="body">
      <div v-if="selectedProduct" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white p-4 rounded-lg w-full max-w-sm">
          <h3 class="font-bold text-lg mb-2">{{ selectedProduct.food_name }}</h3>
          <p class="text-sm text-gray-600 mb-4">この食品を登録しますか？</p>
          
          <div class="mb-4">
               <label class="block mb-1 font-semibold text-sm">量（%）</label>
               <div class="flex items-center gap-2">
                  <input type="number" v-model.number="percentInput" min="1" max="9999" step="1" class="border p-2 rounded w-24 text-sm">
                  <span class="text-sm text-gray-600">%</span>
               </div>
          </div>

          <div class="flex justify-end gap-2">
            <button @click="selectedProduct = null" class="bg-gray-300 text-gray-800 px-4 py-2 rounded text-sm">キャンセル</button>
            <button @click="registerProduct" :disabled="isRegistering" class="bg-green-500 text-white px-4 py-2 rounded text-sm">
              {{ isRegistering ? '登録中...' : '登録' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* Scoped styles to prevent leakage */
</style>

<script>
import { debounce } from 'lodash';

export default {
  name: 'SearchEntry',
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
      searchKeyword: '',
      barcode: '',
      searchResults: [],
      isSearching: false,
      searched: false,
      errorMessage: '',
      selectedProduct: null,
      percentInput: 100,
      isRegistering: false,
    };
  },
  created() {
      this.debouncedSearch = debounce(this.performSearch, 500);
  },
  watch: {
      searchKeyword(newVal) {
          if (newVal && newVal.length >= 2) {
              this.debouncedSearch();
          } else {
              this.searchResults = [];
              this.searched = false;
          }
      }
  },
  methods: {
    formatNutrient(val) {
      return val !== undefined && val !== null ? Number(val).toFixed(1) : '-';
    },
    async performSearch() {
      if (!this.searchKeyword.trim()) return;
      this.isSearching = true;
      this.errorMessage = '';
      this.searched = false;
      this.searchResults = [];

      try {
        await axios.get('/sanctum/csrf-cookie');
        const res = await axios.get('/api/food/search', {
          params: { search: this.searchKeyword }
        });
        
        if (res.data.meta && res.data.meta.error) {
            this.errorMessage = '外部サービスに接続できませんでした';
            this.searchResults = [];
        } else {
            this.searchResults = res.data.data || [];
        }
      } catch (error) {
        console.error('Search failed', error);
        if (error.response && error.response.status === 422) {
             // Validation error (too short etc) - ignore or show specific msg
        } else {
             this.errorMessage = '検索に失敗しました';
        }
      } finally {
        this.isSearching = false;
        this.searched = true;
      }
    },
    // Triggered by button click (optional if using debounce on input)
    searchFood() {
        if (this.searchKeyword && this.searchKeyword.length >= 2) {
            this.performSearch();
        }
    },
    async searchBarcode() {
      if (!this.barcode.trim()) return;
      this.isSearching = true;
      this.errorMessage = '';
      this.searched = false;
      this.searchResults = [];

      try {
        await axios.get('/sanctum/csrf-cookie');
        const res = await axios.get('/api/food/barcode', {
          params: { barcode: this.barcode }
        });
        // Barcode search returns a single object or error
        if (res.data && !res.data.error) {
             this.searchResults = [res.data];
        } else {
             this.searchResults = [];
        }
      } catch (error) {
        console.error('Barcode search failed', error);
        this.errorMessage = '検索に失敗しました';
      } finally {
        this.isSearching = false;
        this.searched = true;
      }
    },
    selectProduct(product) {
      this.selectedProduct = product;
      this.percentInput = 100;
    },
    async registerProduct() {
      if (!this.selectedProduct) return;
      this.isRegistering = true;

      const product = this.selectedProduct;
      const payload = {
        food_name: product.food_name,
        energy_kcal_100g: product.energy_kcal_100g,
        proteins_100g: product.proteins_100g,
        fat_100g: product.fat_100g,
        carbohydrates_100g: product.carbohydrates_100g,
        meal_type: this.mealType,
        source_type: product.source || 'search',
        source_food_number: product.code || 'search',
        multiplier: this.percentInput / 100,
        consumed_at: this.date,
      };

      try {
        await axios.get('/sanctum/csrf-cookie');
        const res = await axios.post('/api/food-logs', payload);
        
        this.$emit('registered', res.data.data);
        this.selectedProduct = null;
        this.searchResults = [];
        this.searchKeyword = '';
        this.barcode = '';
        this.searched = false;

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
