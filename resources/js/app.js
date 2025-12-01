import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import { createApp } from 'vue';
import FavoriteList from './FavoriteList.vue';
import FoodEntryModal from './FoodEntryModal.vue';

document.addEventListener('DOMContentLoaded', () => {
  // Existing FavoriteList mount (if still needed for standalone, but we are moving it inside modal)
  // However, the plan says "Update FavoriteList.vue to include tabs..." -> No, plan says "Update FavoriteList.vue to include tabs and new components" was the OLD plan.
  // The NEW plan says "FoodEntryModal" mounts "FavoriteList".
  // So we might not need to mount FavoriteList separately anymore if it's only used in the modal.
  // But let's keep the existing logic if it's used elsewhere, or just focus on the new modal.

  // Mount FoodEntryModal
  const modalEl = document.getElementById('food-entry-modal');
  if (modalEl) {
    const modalApp = createApp(FoodEntryModal);
    modalApp.mount(modalEl);
  }

  // We might still need to mount FavoriteList if it's used in other places?
  // The user request implies replacing the existing flow.
  // Let's check if #favorite-vue is still in the DOM in the new index.blade.php.
  // I will remove it from index.blade.php, so I don't need to mount it here separately.
});
