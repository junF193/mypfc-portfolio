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
    const el = document.getElementById('nutrition-date');
    if (el && el.value) return el.value;
    return todayDateString();
  }

  // --- Favorite icon handling ---
  function setFavoriteIcon(button, favorited) {
    if (!button) return;
    button.dataset.favorited = favorited ? 'true' : 'false';
    button.setAttribute('aria-pressed', favorited ? 'true' : 'false');
    const icon = button.querySelector('.favorite-icon');
    if (icon) icon.textContent = favorited ? '❤️' : '🤍';
    button.title = favorited ? 'お気に入りを解除' : 'お気に入りを追加';
  }

  function initFavoriteIcons() {
    document.querySelectorAll('.favorite-btn').forEach(btn => {
      const fav = btn.dataset.favorited === 'true';
      setFavoriteIcon(btn, fav);
    });
  }

  function updateHistoryButtonIcon(foodLogId, favorited) {
    try {
      const selector = `.history-list [data-food-log-id="${foodLogId}"] .favorite-btn`;
      let btn = document.querySelector(selector);

      if (!btn) {
        const li = document.querySelector(`.history-list [data-food-log-id="${foodLogId}"]`);
        if (li) btn = li.querySelector('.favorite-btn');
      }

      if (btn) {
        btn.setAttribute('data-favorited', favorited ? 'true' : 'false');
        btn.setAttribute('aria-pressed', favorited ? 'true' : 'false');
        const icon = btn.querySelector('.favorite-icon');
        if (icon) icon.textContent = favorited ? '❤️' : '🤍';
        btn.title = favorited ? 'お気に入りを解除' : 'お気に入りに追加';
      } else {
        console.warn('updateHistoryButtonIcon: button not found for', foodLogId);
      }
    } catch (e) {
      console.error('updateHistoryButtonIcon error', e);
    }
  }

  // --- Toggle favorite (POST or DELETE) ---
  async function toggleFavorite(button) {
    if (!button || !button.dataset) return;
    const foodLogId = button.dataset.foodLogId;
    if (!foodLogId) {
      showToast('お気に入り登録に失敗しました', 'error');
      return;
    }

    const currentlyFav = button.dataset.favorited === 'true';

    // --- 楽観的UI：先にUIを更新 ---
    updateHistoryButtonIcon(foodLogId, !currentlyFav);
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');

    try {
      const csrfToken = getCsrfToken();
      if (!csrfToken) {
        throw new Error('CSRFトークンがありません。ページをリフレッシュしてください。');
      }

      let resp;
      if (!currentlyFav) {
        // お気に入り追加
        resp = await fetch(FAVORITES_STORE_URL, {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({ food_log_id: Number(foodLogId) })
        });
      } else {
        // お気に入り解除 (履歴ID指定)
        const url = `${FAVORITES_DESTROY_BASE}by-food-log/${encodeURIComponent(foodLogId)}`;
        resp = await fetch(url, {
          method: 'DELETE',
          credentials: 'include',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
        });
      }

      const data = await parseJsonSafe(resp);

      if (!resp.ok) {
        // --- ロールバック処理 ---
        // 失敗したらUIを元の状態に戻す
        updateHistoryButtonIcon(foodLogId, currentlyFav);
        const msg = (data && data.message) ? data.message : `エラー: ${resp.status}`;
        showToast(msg, 'error');
      } else {
        // 成功時の通知とイベント発火
        const msg = (data && data.message) ? data.message : (!currentlyFav ? 'お気に入りに追加しました' : 'お気に入りを解除しました');
        showToast(msg);

        if (!currentlyFav) {
          // Vueコンポーネントに新しいお気に入りオブジェクトを通知
          console.log('Event dispatched: external-favorite-added', data.data);
          document.dispatchEvent(new CustomEvent('external-favorite-added', { detail: data.data }));
        } else {
          // Vueコンポーネントに、どの履歴との紐付けが解除されたかを通知
          document.dispatchEvent(new CustomEvent('external-favorite-removed', {
            detail: { source_food_log_id: Number(foodLogId) }
          }));
        }
      }
    } catch (err) {
      // --- ロールバック処理 ---
      // 通信エラー時もUIを元の状態に戻す
      updateHistoryButtonIcon(foodLogId, currentlyFav);
      console.error('toggleFavorite error', err);
      showToast(err.message || '通信エラーが発生しました', 'error');
    } finally {
      button.disabled = false;
      button.removeAttribute('aria-busy');
    }
  }

  // --- Modal open/close ---
  window.openSuggestionModal = function (mealType) {
    const modal = document.getElementById('suggestion-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.dataset.mealType = mealType || '';
    switchModalTab('modal-history', modal);
  };

  window.closeSuggestionModal = function () {
    const modal = document.getElementById('suggestion-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    const editor = modal.querySelector('#history-editor');
    if (editor) editor.classList.add('hidden');
  };

  // --- selectHistory: send POST to create a food log from history ---
  async function selectHistoryAPI(foodLogId, mealType, percent) {
    if (!foodLogId) throw new Error('invalid id');
    const date = getSelectedDate();
    const payload = {
      from_history_id: Number(foodLogId),
      meal_type: String(mealType || ''),
      date: date
    };
    if (typeof percent === 'number') payload.percent = Number(percent);



    const res = await fetch(FOODLOGS_HISTORY_STORE_URL, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken()
      },
      body: JSON.stringify(payload)
    });

    const data = await parseJsonSafe(res);
    if (!res.ok) {
      const msg = (data && data.message) ? data.message : ('エラー: ' + (res.status || 'unknown'));
      throw new Error(msg);
    }
    return data;
  }

  // --- UI: when clicking a select-history button, populate editor and show preview ---
  function openEditorWithButton(btn) {
    if (!btn) return;
    const modal = document.getElementById('suggestion-modal');
    if (!modal) return;

    // タイトルを「食事の登録（量を調整）」に変更
    const titleEl = document.getElementById('suggestion-title');
    if (titleEl) titleEl.textContent = '食事の登録（量を調整）';

    const id = btn.dataset.foodLogId || btn.closest('li')?.dataset.foodLogId;
    const energy = toNumberSafe(btn.dataset.energy || btn.dataset.energyKcal100g || 0, 0);
    const proteins = toNumberSafe(btn.dataset.proteins || 0, 0);
    const fat = toNumberSafe(btn.dataset.fat || 0, 0);
    const carbs = toNumberSafe(btn.dataset.carbs || 0, 0);
    const name = btn.dataset.foodName || btn.closest('li')?.querySelector('.font-medium')?.textContent || '食品';
    const mealType = btn.dataset.mealType || modal.dataset.mealType || '';

    modal.dataset.selectedFoodLogId = id;
    modal.dataset.selectedEnergy = String(energy);
    modal.dataset.selectedProteins = String(proteins);
    modal.dataset.selectedFat = String(fat);
    modal.dataset.selectedCarbs = String(carbs);
    modal.dataset.selectedMealType = mealType;

    const editor = document.getElementById('history-editor');
    if (!editor) return;
    editor.classList.remove('hidden');

    const nameEl = document.getElementById('modal-food-name');
    if (nameEl) nameEl.textContent = name;

    const percentInput = document.getElementById('custom-percent');
    if (percentInput) {
      const defaultPercent = toNumberSafe(btn.dataset.multiplier ? btn.dataset.multiplier * 100 : 100, 100);
      percentInput.value = Math.round(defaultPercent);
      updatePreviewFromPercent();
      percentInput.focus();
    }
  }

  function updatePreviewFromPercent() {
    const modal = document.getElementById('suggestion-modal');
    if (!modal) return;
    const percentInput = document.getElementById('custom-percent');
    const p = toNumberSafe(percentInput?.value, 100);
    const mult = p / 100;

    const energy = toNumberSafe(modal.dataset.selectedEnergy, 0);
    const proteins = toNumberSafe(modal.dataset.selectedProteins, 0);
    const fat = toNumberSafe(modal.dataset.selectedFat, 0);
    const carbs = toNumberSafe(modal.dataset.selectedCarbs, 0);

    const kcalVal = Number((energy * mult).toFixed(1));
    const protVal = Number((proteins * mult).toFixed(1));
    const fatVal = Number((fat * mult).toFixed(1));
    const carbsVal = Number((carbs * mult).toFixed(1));

    const kcalEl = document.getElementById('preview-kcal');
    const protEl = document.getElementById('preview-protein');
    const fatEl = document.getElementById('preview-fat');
    const carbsEl = document.getElementById('preview-carbs');

    if (kcalEl) kcalEl.textContent = (isNaN(kcalVal) ? '--' : `${kcalVal} kcal`);
    if (protEl) protEl.textContent = (isNaN(protVal) ? '--' : `${protVal} g`);
    if (fatEl) fatEl.textContent = (isNaN(fatVal) ? '--' : `${fatVal} g`);
    if (carbsEl) carbsEl.textContent = (isNaN(carbsVal) ? '--' : `${carbsVal} g`);
  }

  // --- Editor register / cancel handlers ---
  async function handleEditorRegister() {
    const modal = document.getElementById('suggestion-modal');
    if (!modal) return;
    const id = modal.dataset.selectedFoodLogId;
    const mealType = modal.dataset.selectedMealType || '';
    const percentInput = document.getElementById('custom-percent');
    const percent = toNumberSafe(percentInput?.value, 100);

    if (!id || id === 'undefined' || id === 'null') {
      showToast('選択した履歴が無効です', 'error');
      return;
    }
    if (Number.isNaN(Number(id))) {
      showToast('選択した履歴IDが無効です', 'error');
      return;
    }
    if (!Number.isInteger(percent) || percent < 1 || percent > 9999) {
      showToast('分量は 1 ~ 9999 の整数で指定してください', 'error');
      return;
    }

    const registerBtn = document.getElementById('editor-register');
    if (registerBtn) {
      registerBtn.disabled = true;
      registerBtn.setAttribute('aria-busy', 'true');
    }

    try {
      const data = await selectHistoryAPI(Number(id), mealType, Number(percent));
      showToast((data && data.message) ? data.message : '登録しました');

      const editor = document.getElementById('history-editor');
      if (editor) editor.classList.add('hidden');
      window.closeSuggestionModal();

      if (typeof window.refreshDailyNutrition === 'function') {
        try {
          await window.refreshDailyNutrition();
        } catch (e) {
          console.warn('refreshDailyNutrition error', e);
        }
      }
    } catch (err) {
      console.error('register error', err);
      showToast(err.message || '登録に失敗しました', 'error');
    } finally {
      if (registerBtn) {
        registerBtn.disabled = false;
        registerBtn.removeAttribute('aria-busy');
      }
    }
  }

  function handleEditorCancel() {
    const editor = document.getElementById('history-editor');
    if (editor) editor.classList.add('hidden');
  }

  // --- Modal tab switching ---
  function switchModalTab(tabName, modalRoot = null) {
    const modal = modalRoot || document.getElementById('suggestion-modal');
    if (!modal) return;

    // 編集パネルが開いていれば閉じる
    const editor = modal.querySelector('#history-editor');
    if (editor) editor.classList.add('hidden');

    const panes = modal.querySelectorAll('.mypage-pane');
    panes.forEach(p => {
      if (p.dataset.pane === tabName) p.classList.remove('hidden');
      else p.classList.add('hidden');
    });

    const tabs = modal.querySelectorAll('.mypage-tab');
    tabs.forEach(t => {
      t.setAttribute('aria-selected', t.dataset.tab === tabName ? 'true' : 'false');
    });

    // タブ切り替え時にタイトルをリセット
    const titleEl = document.getElementById('suggestion-title');
    if (titleEl) {
      if (tabName === 'modal-favorites') {
        titleEl.textContent = 'お気に入りから選択';
      } else {
        titleEl.textContent = '履歴から選択';
      }
    }
  }

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
    const date = dateStr || (document.getElementById('nutrition-date')?.value) || getSelectedDate();
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

        // Update PFC goal text if elements exist (optional, or just tooltip)
        // For now, let's just update the main calorie display to show "Current / Goal"
      }
      if (caloriesEl) caloriesEl.textContent = `${toNumberSafe(data.calories_total, 0)}${goalText}`;

      // Progress Bar (Optional: Add a visual progress bar below calories)
      if (data.goal && data.goal.calories > 0) {
        const percent = Math.min(100, (data.calories_total / data.goal.calories) * 100);
        let bar = document.getElementById('calorie-progress-bar');
        if (!bar) {
          // Create bar if not exists
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

  // Vue コンポーネントからのイベントリスナー
  document.addEventListener('favorite-added', (e) => {
    const fav = e.detail;
    // source_food_log_id がある場合のみ、履歴リストのアイコンを更新
    if (fav && fav.source_food_log_id) {
      updateHistoryButtonIcon(fav.source_food_log_id, true);
    }
  });

  document.addEventListener('favorite-removed', (e) => {
    const detail = e.detail;
    // source_food_log_id がある場合のみ、履歴リストのアイコンを更新
    if (detail && detail.source_food_log_id) {
      updateHistoryButtonIcon(detail.source_food_log_id, false);
    }
  });

  // --- Global event delegation ---
  function attachGlobalHandlers() {
    document.addEventListener('click', function (e) {
      // select-history-btn
      const selectBtn = e.target.closest('.select-history-btn');
      if (selectBtn) {
        e.preventDefault();
        openEditorWithButton(selectBtn);
        return;
      }

      // favorite-btn (Vue管理領域外のみ)
      const favBtn = e.target.closest('.favorite-btn');
      if (favBtn) {
        e.preventDefault();

        // Vue管理領域内のボタンは無視
        if (favBtn.closest && favBtn.closest('#favorite-vue')) {
          return;
        }

        if (favBtn.disabled) return;
        favBtn.disabled = true;
        toggleFavorite(favBtn).finally(() => {
          favBtn.disabled = false;
        });
        return;
      }

      // modal tab clicks
      const tabBtn = e.target.closest('.mypage-tab');
      if (tabBtn && tabBtn.dataset && tabBtn.dataset.tab) {
        e.preventDefault(); // Add preventDefault to be safe
        const name = tabBtn.dataset.tab;
        const modal = document.getElementById('suggestion-modal');
        switchModalTab(name, modal);
      }
    });

    // percent input change
    const percentInput = document.getElementById('custom-percent');
    if (percentInput) {
      percentInput.addEventListener('input', function () {
        // 入力中は自由に入力させる（空文字も許容）
        // プレビュー更新だけ行う
        updatePreviewFromPercent();
      });

      // フォーカスが外れた時にバリデーションと補正を行う
      percentInput.addEventListener('blur', function () {
        let v = parseInt(percentInput.value, 10);
        if (Number.isNaN(v) || v < 1) v = 1; // 最低1%
        if (v > 9999) v = 9999;
        percentInput.value = v;
        updatePreviewFromPercent();
      });
    }

    // editor buttons
    const editorCancel = document.getElementById('editor-cancel');
    if (editorCancel) {
      editorCancel.addEventListener('click', function (e) {
        e.preventDefault();
        handleEditorCancel();
      });
    }

    const editorRegister = document.getElementById('editor-register');
    if (editorRegister) {
      editorRegister.addEventListener('click', function (e) {
        e.preventDefault();
        handleEditorRegister();
      });
    }

    // nutrition date change
    const dateInput = document.getElementById('nutrition-date');
    if (dateInput) {
      dateInput.addEventListener('change', function () {
        refreshDailyNutrition(dateInput.value);
      });
    }

    // refresh button
    const refreshBtn = document.getElementById('refresh-nutrition-btn');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', function () {
        refreshDailyNutrition();
      });
    }
  }

  // --- Initialization ---
  async function init() {
    initFavoriteIcons();
    attachGlobalHandlers();
    switchModalTab('modal-history'); // デフォルトで履歴タブを表示
    refreshDailyNutrition();
  }

  // 非同期 init を呼び出し
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init());
  } else {
    init();
  }

  // Expose functions for console / other scripts if needed
  window.selectHistory = async function (id, mealType, btn, percent) {
    return await selectHistoryAPI(id, mealType, percent);
  };

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

  // Update refreshDailyNutrition to show goal
  const originalRefreshDailyNutrition = refreshDailyNutrition;
  refreshDailyNutrition = async function (dateStr = null) {
    await originalRefreshDailyNutrition(dateStr);

    // After original refresh, we might need to update the UI for Goal if the API returns it
    // Note: The original function updates #calories-total etc.
    // We need to check if the response data is available. 
    // Since original function doesn't return data easily to us without modifying it, 
    // let's modify the original function in the next step or assume the original function
    // handles the goal display if we updated it. 
    // WAIT: I didn't update the original refreshDailyNutrition in this JS file yet.
    // I should update the original function instead of wrapping it here.
  };


})();