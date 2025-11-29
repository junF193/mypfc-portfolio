<template>
  <div class="favorite-vue-root">
    <div v-if="errorMessage" class="p-2 mb-2 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
      {{ errorMessage }}
    </div>
    <ul id="modal-favorite-list" class="space-y-2 max-h-64 overflow-y-auto">
      <li v-if="loading" class="p-4 text-center text-gray-500">
        <div class="loading-spinner"></div>
        <p>読み込み中...</p>
      </li>
      <li v-else-if="favorites.length === 0" class="p-2 text-gray-500">お気に入りはありません。</li>

      <li v-for="fav in favorites" :key="fav.id"
          class="flex items-center justify-between p-2 border rounded"
          :data-favorite-id="fav.id">
        
        <template v-if="editingFavoriteId === fav.id">
          <!-- 編集モード -->
          <div class="flex-grow space-y-2">
            <input type="text" v-model="editedFavorite.food_name" class="border p-1 w-full rounded text-sm" placeholder="食品名">
            <div class="grid grid-cols-2 gap-2 text-xs">
              <input type="number" v-model.number="editedFavorite.energy_kcal_100g" class="border p-1 rounded" placeholder="カロリー(kcal)">
              <input type="number" v-model.number="editedFavorite.proteins_100g" class="border p-1 rounded" placeholder="タンパク質(g)">
              <input type="number" v-model.number="editedFavorite.fat_100g" class="border p-1 rounded" placeholder="脂質(g)">
              <input type="number" v-model.number="editedFavorite.carbohydrates_100g" class="border p-1 rounded" placeholder="炭水化物(g)">
            </div>
            <textarea v-model="editedFavorite.memo" class="border p-1 w-full rounded text-xs" rows="2" placeholder="メモ"></textarea>
          </div>
          <div class="flex items-center space-x-2 ml-2">
            <button type="button" @click="updateFavorite(fav.id)" :disabled="isSaving" class="text-sm bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">保存</button>
            <button type="button" @click="cancelEditing()" :disabled="isSaving" class="text-sm bg-gray-300 text-gray-800 px-2 py-1 rounded hover:bg-gray-400">キャンセル</button>
          </div>
        </template>

        <template v-else>
          <!-- 表示モード -->
          <div class="flex-grow flex items-center space-x-3">
            <div class="font-medium text-sm">{{ fav.food_name }}</div>
            <div class="text-xs text-gray-500" v-if="fav.energy_kcal_100g">{{ Number(fav.energy_kcal_100g).toFixed(1) }} kcal</div>
            <div class="text-xs text-gray-500" v-if="fav.memo" :title="fav.memo">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block align-text-bottom" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-3l-4 4z" />
              </svg>
            </div>
          </div>

          <div class="flex items-center space-x-2 ml-2">
            <button type="button" @click="startEditing(fav)" class="text-sm bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600" title="編集">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
            </button>
            <button type="button"
                    class="select-history-btn text-sm bg-indigo-500 text-white px-3 py-1 rounded hover:bg-indigo-600"
                    :data-food-log-id="fav.source_food_log_id"
                    :data-favorite-id="fav.id"
                    :data-food-name="fav.food_name || ''"
                    data-meal-type="">
              食事に登録
            </button>

            <button type="button"
                    @click.stop.prevent="removeFavorite(fav.id)"
                    class="favorite-btn p-2 rounded"
                    :data-favorite-id="fav.id"
                    title="お気に入りを解除">
              <span class="favorite-icon text-lg">❤️</span>
            </button>
          </div>
        </template>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  name: 'FavoriteList',
  props: {
    initialFavorites: { type: Array, default: () => [] },
    fetchUrl: { type: String, default: '/api/favorites' },
    toggleUrlBase: { type: String, default: '/api/favorites' }
  },
  data() {
    return {
      favorites: [],
      loading: false,
      errorMessage: '',
      editingFavoriteId: null, // 編集中のfavorite.id
      editedFavorite: {},      // 編集フォームの入力値
      isSaving: false,         // 保存処理中かどうかのフラグ
    };
  },
  methods: {
    async fetchFavorites(perPage = null) {
      this.loading = true;
      try {
        const url = new URL(this.fetchUrl, window.location.origin);
        if (perPage !== null) url.searchParams.set('per_page', perPage);

        const res = await fetch(url.toString(), {
          method: 'GET',
          headers: {
            'Accept': 'application/json'
          },
          credentials: 'include'
        });

        if (!res.ok) {
          this.showError('お気に入りの読み込みに失敗しました');
          this.favorites = [];
          return;
        }

        const result = await res.json();
        // 新しいAPIは`data`プロパティに配列を持つ
        this.favorites = result.data || []; 
      } catch (e) {
        this.showError('通信エラーが発生しました');
        this.favorites = [];
      } finally {
        this.loading = false;
      }
    },

    async removeFavorite(favoriteId) {
      if (!favoriteId) {
        this.showError('IDが不明なため削除できません');
        return;
      }

      const idx = this.favorites.findIndex(f => String(f.id) === String(favoriteId));
      if (idx === -1) return;

      const removed = this.favorites.splice(idx, 1)[0];

      try {
        let deleteUrl = this.toggleUrlBase;
        if (deleteUrl.includes('__ID__')) {
            deleteUrl = deleteUrl.replace('__ID__', favoriteId);
        } else {
            // 末尾にスラッシュがない場合は追加
            if (!deleteUrl.endsWith('/')) {
                deleteUrl += '/';
            }
            deleteUrl += encodeURIComponent(favoriteId);
        }
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const res = await fetch(deleteUrl, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          credentials: 'include'
        });

        if (!res.ok) {
          this.favorites.splice(idx, 0, removed);
          const body = await res.json().catch(() => null);
          this.showError(body?.message || `お気に入りの削除に失敗しました (${res.status})`);
        } else {
          // 外部のJS（mypage.js）に、履歴リストのアイコンを更新するよう通知
          document.dispatchEvent(new CustomEvent('favorite-removed', {
            detail: { source_food_log_id: removed.source_food_log_id }
          }));
        }
      } catch (e) {
        this.favorites.splice(idx, 0, removed);
        this.showError('通信エラーが発生しました');
        console.error('removeFavorite error', e);
      }
    },

    showError(message) {
      this.errorMessage = message;
      setTimeout(() => {
        this.errorMessage = '';
      }, 3000);
    },

    addFavoriteFromOutside(rawFav) {
      if (!rawFav || !rawFav.id) return;
      const exists = this.favorites.some(f => String(f.id) === String(rawFav.id));
      if (!exists) {
        this.favorites.unshift(rawFav);
      }
    },

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    },

    showToast(message = '', kind = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, kind);
        } else {
            console.warn('showToast function not found on window', message, kind);
            this.errorMessage = message;
            setTimeout(() => this.errorMessage = '', 3000);
        }
    },

    startEditing(favorite) {
      this.editingFavoriteId = favorite.id;
      this.editedFavorite = { ...favorite }; 
    },

    cancelEditing() {
      this.editingFavoriteId = null;
      this.editedFavorite = {};
    },

    async updateFavorite(favoriteId) {
        this.isSaving = true;
        this.errorMessage = ''; 

        try {
            let url = this.toggleUrlBase;
            if (url.includes('__ID__')) {
                url = url.replace('__ID__', favoriteId);
            } else {
                 if (!url.endsWith('/')) url += '/';
                 url += favoriteId;
            }
            const csrfToken = this.getCsrfToken();

            const res = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include',
                body: JSON.stringify(this.editedFavorite)
            });

            const result = await res.json();

            if (!res.ok) {
                let msg = result.message || 'お気に入りの更新に失敗しました';
                if (result.errors) {
                    msg += ': ' + Object.values(result.errors).map(err => err.join(' ')).join(' ');
                }
                this.showError(msg, 'error');
                return;
            }

            const index = this.favorites.findIndex(fav => fav.id === favoriteId);
            if (index !== -1) {
                this.$set(this.favorites, index, result.data); 
            }
            this.showToast(result.message || 'お気に入りを更新しました', 'success');
            this.cancelEditing(); 

        } catch (e) {
            this.showError('通信エラーが発生しました', 'error');
            console.error('updateFavorite error', e);
        } finally {
            this.isSaving = false;
        }
    }
  },

  async mounted() {
       try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
      } catch (e) {
        console.warn('Failed to get csrf cookie', e);
      }

        this.fetchFavorites(null);

        document.addEventListener('external-favorite-added', (e) => {
          console.log('Event received: external-favorite-added', e.detail);
          if (e?.detail) this.addFavoriteFromOutside(e.detail);
        });

        document.addEventListener('external-favorite-removed', (e) => {
          const detail = e.detail;
          if (detail && detail.source_food_log_id) {
            this.favorites = this.favorites.filter(f => Number(f.source_food_log_id) !== Number(detail.source_food_log_id));
          } else if (detail && (detail.id || detail.favorite_id)) {
             const removedId = detail.id || detail.favorite_id;
             this.favorites = this.favorites.filter(f => String(f.id) !== String(removedId));
          }
        });
  }
}
</script>

<style scoped>
.loading-spinner {
  border: 4px solid #f3f3f3; /* Light grey */
  border-top: 4px solid #3498db; /* Blue */
  border-radius: 50%;
  width: 24px;
  height: 24px;
  animation: spin 1s linear infinite;
  margin: 0 auto 8px auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>