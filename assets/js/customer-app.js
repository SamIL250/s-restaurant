(function () {
    'use strict';

    window.customerAccount = { logged_in: false, customer: null };
    window.favoriteMenuIds = new Set();

    function siteBase() {
        return window.SITE_BASE || '';
    }

    function showToast(message) {
        if (typeof showNotification === 'function') {
            showNotification(message);
            return;
        }
        alert(message);
    }

    async function loadCustomerState() {
        try {
            const response = await fetch(siteBase() + '/services/auth/me.php', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            window.customerAccount = {
                logged_in: !!data.logged_in,
                customer: data.customer || null
            };
            applyCheckoutAccountState();
            bindLogoutLink();
            if (window.customerAccount.logged_in) {
                await loadFavoriteStates();
            }
        } catch (error) {
            console.error('Unable to load customer state', error);
        }
    }

    window.applyCheckoutAccountState = function applyCheckoutAccountState() {
        const isLoggedIn = window.customerAccount && window.customerAccount.logged_in;
        const guestFields = document.getElementById('guestCheckoutFields');
        const guestEmailRow = document.getElementById('guestEmailRow');
        const loggedInOrderTypeRow = document.getElementById('loggedInOrderTypeRow');

        if (guestFields) {
            guestFields.style.display = isLoggedIn ? 'none' : '';
        }
        if (guestEmailRow) {
            guestEmailRow.style.display = isLoggedIn ? 'none' : '';
        }
        if (loggedInOrderTypeRow) {
            loggedInOrderTypeRow.style.display = isLoggedIn ? '' : 'none';
        }

        if (isLoggedIn && window.customerAccount.customer) {
            const customer = window.customerAccount.customer;
            const nameField = document.getElementById('customer_name');
            const phoneField = document.getElementById('customer_phone');
            const emailField = document.getElementById('customer_email');
            if (nameField) nameField.value = customer.name || '';
            if (phoneField) phoneField.value = customer.phone || '';
            if (emailField) emailField.value = customer.email || '';
        }
    };

    async function loadFavoriteStates() {
        try {
            const response = await fetch(siteBase() + '/services/favorites/list.php', {
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (!data.success) {
                return;
            }

            window.favoriteMenuIds = new Set(data.favorites.map(function (item) {
                return typeof item === 'object' ? item.menu_item_id : item;
            }));

            document.querySelectorAll('.btn-favorite[data-menu-id]').forEach(function (button) {
                const menuId = parseInt(button.getAttribute('data-menu-id'), 10);
                button.classList.toggle('active', window.favoriteMenuIds.has(menuId));
            });
        } catch (error) {
            console.error('Unable to load favorites', error);
        }
    }

    window.toggleFavorite = async function toggleFavorite(menuItemId, button) {
        if (!window.customerAccount.logged_in) {
            window.location.href = siteBase() + '/login';
            return;
        }

        try {
            const formData = new FormData();
            formData.append('menu_item_id', menuItemId);

            const response = await fetch(siteBase() + '/services/favorites/toggle.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const data = await response.json();

            if (!data.success) {
                showToast(data.message || 'Unable to update favorites.');
                return;
            }

            if (data.favorited) {
                window.favoriteMenuIds.add(menuItemId);
            } else {
                window.favoriteMenuIds.delete(menuItemId);
            }

            if (button) {
                button.classList.toggle('active', !!data.favorited);
            }

            showToast(data.message);
        } catch (error) {
            showToast('Unable to update favorites.');
        }
    };

    function bindLogoutLink() {
        const logoutLink = document.getElementById('customerLogoutLink');
        if (!logoutLink || logoutLink.dataset.bound === 'true') {
            return;
        }

        logoutLink.dataset.bound = 'true';
        logoutLink.addEventListener('click', async function (event) {
            event.preventDefault();

            try {
                const response = await fetch(siteBase() + '/services/auth/logout.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                });
                const data = await response.json();
                window.location.href = data.redirect || siteBase() + '/';
            } catch (error) {
                window.location.href = siteBase() + '/';
            }
        });
    }

    function bindOrderTypeHandlers() {
        const addressField = document.getElementById('address_field');
        const deliveryInput = document.getElementById('delivery_address');

        function syncAddressVisibility(select) {
            if (!addressField || !select) {
                return;
            }

            if (select.value === 'delivery') {
                addressField.style.display = 'flex';
                if (deliveryInput) {
                    deliveryInput.required = true;
                }
            } else {
                addressField.style.display = 'none';
                if (deliveryInput) {
                    deliveryInput.required = false;
                    deliveryInput.value = '';
                }
            }
        }

        const orderTypeSelect = document.getElementById('order_type');
        const loggedInOrderTypeSelect = document.getElementById('order_type_logged_in');

        if (orderTypeSelect) {
            orderTypeSelect.addEventListener('change', function () {
                syncAddressVisibility(orderTypeSelect);
            });
        }

        if (loggedInOrderTypeSelect) {
            loggedInOrderTypeSelect.addEventListener('change', function () {
                syncAddressVisibility(loggedInOrderTypeSelect);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindOrderTypeHandlers();
        loadCustomerState();
    });
})();
