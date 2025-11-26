import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import { createApp } from 'vue';
import FavoriteList from './FavoriteList.vue';


document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('favorite-vue');
  if (!el) return;

  let initial = [];
  try {
    initial = el.dataset.initialFavorites ? JSON.parse(el.dataset.initialFavorites) : [];
  } catch (e) {
    initial = [];
    console.error('Failed to parse initialFavorites', e);
  }

  const fetchUrl = el.dataset.fetchUrl || '/api/favorites';
  const toggleUrlBase = el.dataset.toggleUrlBase || '/api/favorites';

  const app = createApp(FavoriteList, {
    initialFavorites: initial,
    fetchUrl,
    toggleUrlBase
  });

  app.mount(el);
});
