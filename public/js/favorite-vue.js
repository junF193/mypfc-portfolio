
<script>
  (function () {
    const { createApp } = Vue;
    const mountEl = document.getElementById('favorite-vue');
    if (!mountEl) return;

    const initial = mountEl.dataset.initialFavorites ? JSON.parse(mountEl.dataset.initialFavorites) : [];

    const app = createApp({
      data() {
        return {
          favorites: initial || [],
          loading: false,
        };
      },
      methods: {
        async fetchFavorites() {
          this.loading = true;
          try {
            const res = await fetch('/favorites', { credentials: 'same-origin', headers: { 'Accept': 'application/json' }});
            if (!res.ok) { console.warn('favorites fetch failed', res.status); this.favorites = []; return; }
            const data = await res.json();
            this.favorites = Array.isArray(data) ? data : (data.favorites || []);
          } catch (err) {
            console.error(err);
            this.favorites = [];
          } finally {
            this.loading = false;
          }
        },
        // optional: optimistic UI after toggling favorite (you may already have global toggle)
        addFavorite(fav) {
          // ensure no dup
          if (!this.favorites.find(f => String(f.food_log_id) === String(fav.food_log_id))) {
            this.favorites.unshift(fav);
          }
        },
        removeFavorite(foodLogId) {
          this.favorites = this.favorites.filter(f => String(f.food_log_id) !== String(foodLogId));
        }
      },
      mounted() {
        // If you prefer freshest data when user opens modal, fetch here or call fetchFavorites() when modal opens.
        // Optionally: listen to a custom event when the modal favorites tab is opened:
        document.addEventListener('open-favorites-tab', () => this.fetchFavorites());
      }
    });

    app.mount('#favorite-vue');
  })();
</script>
