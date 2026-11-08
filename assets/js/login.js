// Страница входа публичного сайта: логин через REST API -> JWT -> переход в админку / каталог.
(() => {
  'use strict';

  const API_URL = '/perfume-catalog/api';
  const ADMIN_URL = '/perfume-catalog/admin/';
  const SITE_URL = '/perfume-catalog/';
  const TOKEN_KEY = 'accessToken'; // тот же ключ читает Vue-админка (общий origin -> общий localStorage)
  const STAFF_ROLES = ['admin', 'editor'];

  const form = document.getElementById('loginForm');
  if (!form) return;

  const errorBox = document.getElementById('loginError');
  const submitBtn = form.querySelector('button[type="submit"]');

  function showError(message) {
    errorBox.textContent = message;
    errorBox.hidden = false;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    errorBox.hidden = true;

    const login = form.elements.login.value.trim();
    const password = form.elements.password.value;

    submitBtn.disabled = true;
    try {
      const response = await fetch(`${API_URL}/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ login, password })
      });
      const data = await response.json().catch(() => ({}));

      if (!response.ok || !data.token) {
        showError(data.error || 'Не удалось войти. Попробуйте позже.');
        return;
      }

      localStorage.setItem(TOKEN_KEY, data.token);

      // admin/editor -> админ-панель, обычный пользователь -> каталог
      const isStaff = STAFF_ROLES.includes(data.user?.role);
      window.location.href = isStaff ? ADMIN_URL : SITE_URL;
    } catch {
      showError('Нет связи с сервером');
    } finally {
      submitBtn.disabled = false;
    }
  });
})();
