// public/js/mypage.js
// NOTE: this file is plain JS — it must not contain Blade directives.
// server-provided config is available at window.MYPAGE_CONFIG

// public/js/mypage.js
// MyPage frontend logic for suggestion modal, favorites, history registration, and daily nutrition.
// Works with the provided index.blade.php template's DOM structure and data-* attributes.

// public/js/mypage.js
// MyPage frontend logic for suggestion modal, favorites, history registration, and daily nutrition.

(function () {
  'use strict';

  // --- Config (from Blade) ---
  const cfg = window.MYPAGE_CONFIG || {};
  const CSRF = String(cfg.csrf || '');
  const FAVORITES_STORE_URL = String(cfg.favoritesStoreUrl || '/api/favorites');
  const FAVORITES_DESTROY_BASE = String(cfg.favoritesDestroyBase || '/api/favorites/');
  const FOODLOGS_STORE_URL = String(cfg.foodLogsStoreUrl || '/food-logs');
  const FOODLOGS_HISTORY_STORE_URL = String(cfg.foodLogsHistoryStoreUrl || '/food-logs/history');
  const DAILY_NUTRITION_URL = String(cfg.dailyNutritionUrl || '/mypage/daily-nutrition');

  function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  function showToast(message = '', kind = 'info') {
    try {
      const el = document.createElement('div');
      el.className = 'mypage-toast fixed bottom-6 right-6 p-3 rounded shadow-lg bg-white text-sm z-50';
      if (kind === 'error') el.style.border = '2px solid #f87171';
      el.textContent = message || '';
      document.body.appendChild(el);
      setTimeout(() => {
        el.remove();
      }, 3000);
    } catch (e) {
      console.warn('toast error', e);
    }
  }

  async function parseJsonSafe(resp) {
    if (!resp) return null;
    try {
      return await resp.json();
    } catch (e) {
      try {
        return await resp.text();
      } catch (e2) {
        return null;
      }
    }
  }

  function toNumberSafe(v, fallback = 0) {
    const n = Number(v);
    return Number.isFinite(n) ? n : fallback;
  }

  function todayDateString() {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  }

  function getSelectedDate() {
    const el = document.getElementById('header-date-input');
    if (el && el.value) return el.value;
    return todayDateString();
  }

  // --- Favorite icon handling ---
  // NOTE: Favorite toggling is now handled entirely by Vue components (HistoryList.vue, FavoriteList.vue).
  // The following legacy functions have been removed:
  // - setFavoriteIcon
  // - initFavoriteIcons
  // - updateHistoryButtonIcon
  // - toggleFavorite

  // --- Modal open/close (Obsolete - Removed) ---

  // --- selectHistory (Obsolete - Removed) ---

  // --- UI: Editor & Preview (Obsolete - Removed) ---

  // --- Editor register / cancel handlers (Obsolete - Removed) ---

  // --- Modal tab switching (Obsolete - Removed) ---

  // --- Daily nutrition / Chart rendering ---
  let pfcChart = null;
  function renderPfcChart(labels = ['Protein', 'Fat', 'Carbs'], data = [0, 0, 0]) {
    const canvas = document.getElementById('pfcChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    if (typeof Chart === 'undefined') return;

    try {
      if (pfcChart) {
        pfcChart.data.labels = labels;
        pfcChart.data.datasets[0].data = data;
        pfcChart.update();
        return;
      }

      pfcChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: labels,
          datasets: [{
            data: data,
            hoverOffset: 8
          }]
        },
        options: {
          plugins: {
            legend: { position: 'bottom' },
            tooltip: {
              callbacks: {
                label: (ctx) => `${ctx.label}: ${ctx.parsed} kcal`
              }
            }
          },
          maintainAspectRatio: false
        }
      });
    } catch (e) {
      console.warn('renderPfcChart error', e);
    }
  }

  async function refreshDailyNutrition(dateStr = null) {
    const date = dateStr || getSelectedDate();
    const url = new URL(DAILY_NUTRITION_URL, window.location.origin);
    if (date) url.searchParams.set('date', date);

    try {
      const res = await fetch(url.toString(), {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken()
        },
      });

      if (!res.ok) {
        const data = await parseJsonSafe(res);
        const message = data?.message || '栄養データの取得に失敗しました';
        console.error('daily nutrition fetch failed', res.status, data);
        showToast(message, 'error');
        return;
      }

      const data = await res.json();

      const caloriesEl = document.getElementById('calories-total');
      // Goal display logic
      let goalText = '';
      if (data.goal && data.goal.calories > 0) {
        goalText = ` / ${data.goal.calories} kcal`;
      }
      if (caloriesEl) caloriesEl.textContent = `${toNumberSafe(data.calories_total, 0)}${goalText}`;

      // Progress Bar
      if (data.goal && data.goal.calories > 0) {
        const percent = Math.min(100, (data.calories_total / data.goal.calories) * 100);
        let bar = document.getElementById('calorie-progress-bar');
        if (!bar) {
          const container = caloriesEl.parentElement;
          const barContainer = document.createElement('div');
          barContainer.className = 'w-full bg-gray-200 rounded-full h-2.5 mt-2';
          bar = document.createElement('div');
          bar.id = 'calorie-progress-bar';
          bar.className = 'bg-blue-600 h-2.5 rounded-full';
          barContainer.appendChild(bar);
          container.appendChild(barContainer);
        }
        bar.style.width = `${percent}%`;
        if (percent > 100) bar.classList.replace('bg-blue-600', 'bg-red-600');
        else bar.classList.replace('bg-red-600', 'bg-blue-600');
      }

      const proteinPercentEl = document.getElementById('protein-percent');
      const fatPercentEl = document.getElementById('fat-percent');
      const carbsPercentEl = document.getElementById('carbs-percent');
      const proteinKcalEl = document.getElementById('protein-kcal');
      const fatKcalEl = document.getElementById('fat-kcal');
      const carbsKcalEl = document.getElementById('carbs-kcal');

      if (proteinPercentEl) proteinPercentEl.textContent = `${toNumberSafe(data.pfc_percent?.protein, 0)}%`;
      if (fatPercentEl) fatPercentEl.textContent = `${toNumberSafe(data.pfc_percent?.fat, 0)}%`;
      if (carbsPercentEl) carbsPercentEl.textContent = `${toNumberSafe(data.pfc_percent?.carbs, 0)}%`;

      // Update PFC details with Goal
      if (proteinKcalEl) {
        let pGoal = (data.goal && data.goal.protein) ? ` / ${data.goal.protein}g` : '';
        proteinKcalEl.textContent = `${toNumberSafe(data.protein_kcal, 0)} kcal${pGoal ? ' (' + toNumberSafe(data.protein_kcal / 4, 0).toFixed(0) + 'g' + pGoal + ')' : ''}`;
      }
      if (fatKcalEl) {
        let fGoal = (data.goal && data.goal.fat) ? ` / ${data.goal.fat}g` : '';
        fatKcalEl.textContent = `${toNumberSafe(data.fat_kcal, 0)} kcal${fGoal ? ' (' + toNumberSafe(data.fat_kcal / 9, 0).toFixed(0) + 'g' + fGoal + ')' : ''}`;
      }
      if (carbsKcalEl) {
        let cGoal = (data.goal && data.goal.carbs) ? ` / ${data.goal.carbs}g` : '';
        carbsKcalEl.textContent = `${toNumberSafe(data.carbs_kcal, 0)} kcal${cGoal ? ' (' + toNumberSafe(data.carbs_kcal / 4, 0).toFixed(0) + 'g' + cGoal + ')' : ''}`;
      }

      if (data.chart && Array.isArray(data.chart.data)) {
        renderPfcChart(data.chart.labels || ['Protein', 'Fat', 'Carbs'], data.chart.data);
      }
    } catch (err) {
      console.error('refreshDailyNutrition error', err);
      showToast('栄養データ取得中にエラーが発生しました', 'error');
    }
  }

  // NOTE: Vueコンポーネントが全て管理するため、以下のイベントリスナーは削除されました:
  // - favorite-added
  // - favorite-removed

  // --- Global event delegation ---
  function attachGlobalHandlers() {
    document.addEventListener('click', function (e) {
      // NOTE: favorite-btn handling is now done by Vue components.
    });

    // header date change (navigation)
    const dateInput = document.getElementById('header-date-input');
    if (dateInput) {
      dateInput.addEventListener('change', function () {
        const newDate = dateInput.value;
        if (newDate) {
          window.location.href = `/mypage?date=${newDate}`;
        }
      });
    }

    // refresh button
    const refreshBtn = document.getElementById('refresh-nutrition-btn');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', function () {
        refreshDailyNutrition();
      });
    }

    // Generic Food Entry Modal Triggers (User Requested Pattern)
    const addButtons = document.querySelectorAll('.js-open-food-modal');
    addButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        const mealType = button.dataset.mealType;

        // 現在選択されている日付を取得
        const dateInput = document.getElementById('header-date-input');
        const date = dateInput ? dateInput.value : null;

        // Vueコンポーネントに向けてイベントを発信
        window.dispatchEvent(new CustomEvent('open-food-entry-modal', {
          detail: { mealType: mealType, date: date }
        }));
      });
    });
  }

  // --- Initialization ---
  async function init() {
    // NOTE: initFavoriteIcons() removed - Vue handles it now
    attachGlobalHandlers();
    refreshDailyNutrition();
  }

  // 非同期 init を呼び出し
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init());
  } else {
    init();
  }

  window.refreshDailyNutrition = refreshDailyNutrition;

  // --- Profile Modal Logic ---
  window.openProfileModal = function () {
    const modal = document.getElementById('profile-modal');
    if (modal) modal.classList.remove('hidden');
  };

  window.closeProfileModal = function () {
    const modal = document.getElementById('profile-modal');
    if (modal) modal.classList.add('hidden');
  };

  window.saveProfile = async function () {
    const form = document.getElementById('profile-form');
    if (!form) return;

    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    try {
      const res = await fetch('/api/user/profile', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify(payload)
      });

      const data = await parseJsonSafe(res);

      if (!res.ok) {
        if (res.status === 422) {
          const errors = data.errors || {};
          const msg = Object.values(errors).flat().join('\n') || '入力内容を確認してください';
          showToast(msg, 'error');
        } else {
          showToast(data.message || '保存に失敗しました', 'error');
        }
        return;
      }

      showToast('プロフィールを更新しました');
      closeProfileModal();
      refreshDailyNutrition(); // Recalculate goals
    } catch (e) {
      console.error('saveProfile error', e);
      showToast('通信エラーが発生しました', 'error');
    }
  };

  // --- Delete Food Log ---
  window.deleteFoodLog = async function (id) {
    if (!id) return;
    if (!confirm('本当に削除しますか？')) return;

    try {
      const res = await fetch(`/api/food-logs/${id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken()
        }
      });

      if (!res.ok) {
        const data = await parseJsonSafe(res);
        showToast(data.message || '削除に失敗しました', 'error');
        return;
      }

      // 削除成功時はリロードしてBladeの表示を更新
      window.location.reload();

    } catch (e) {
      console.error('deleteFoodLog error', e);
      showToast('通信エラーが発生しました', 'error');
    }
  };

})();