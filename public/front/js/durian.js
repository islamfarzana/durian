/*
 * Durian storefront behaviour. No dependencies: every feature hangs off
 * data attributes, and events are delegated so server-rendered fragments
 * (cart panel, load-more pages, the options dialog) work without re-binding.
 */
(function () {
    'use strict';

    document.documentElement.classList.add('js');

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var routes = window.DurianRoutes || {};
    var desktop = window.matchMedia('(min-width: 1280px)');
    var body = document.body;

    function qs(selector, root) { return (root || document).querySelector(selector); }
    function qsa(selector, root) { return Array.prototype.slice.call((root || document).querySelectorAll(selector)); }

    /* The sticky header's height changes with the announcement bar and the
       mobile search row; everything that sticks below it reads this value. */
    var header = qs('[data-site-header]');
    function measureHeader() {
        if (header) { document.documentElement.style.setProperty('--dz-sticky-top', header.offsetHeight + 'px'); }
    }
    measureHeader();
    if (header && 'ResizeObserver' in window) { new ResizeObserver(measureHeader).observe(header); } else { window.addEventListener('resize', measureHeader); }

    /* ---------- HTTP ---------- */
    function request(url, options) {
        options = options || {};
        var headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf };
        var payload = options.body;

        if (payload && !(payload instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            payload = JSON.stringify(payload);
        }

        return fetch(url, { method: options.method || 'GET', headers: headers, body: payload, credentials: 'same-origin' })
            .then(function (response) {
                return response.text().then(function (text) {
                    var data = null;
                    try { data = text ? JSON.parse(text) : null; } catch (error) { data = null; }

                    if (response.status === 401 || response.status === 419) {
                        toast(response.status === 401 ? 'Please log in first.' : 'Your session expired. Please refresh the page.', 'error');
                        if (response.status === 401 && routes.login) {
                            setTimeout(function () { window.location.href = routes.login; }, 700);
                        }
                        throw Object.assign(new Error('auth'), { silent: true });
                    }

                    if (!response.ok) {
                        var message = (data && data.message) || 'Something went wrong. Please try again.';
                        if (data && data.errors) {
                            var first = Object.keys(data.errors)[0];
                            if (first && data.errors[first][0]) { message = data.errors[first][0]; }
                        }
                        throw Object.assign(new Error(message), { data: data, status: response.status });
                    }

                    return data;
                });
            });
    }

    /* ---------- Toasts ---------- */
    function toast(message, type) {
        var region = qs('#toasts');
        if (!region || !message) { return; }
        var item = document.createElement('div');
        item.className = 'toast' + (type ? ' toast--' + type : '');
        item.setAttribute('role', type === 'error' ? 'alert' : 'status');
        item.textContent = message;
        region.appendChild(item);
        setTimeout(function () { item.remove(); }, 3400);
    }

    /* ---------- Panels: sidebar, cart, filters ---------- */
    function setCartOpen(open, remember) {
        body.classList.toggle('cart-open', open);
        if (remember && desktop.matches) {
            try { localStorage.setItem('dz-cart-open', open ? '1' : '0'); } catch (error) { /* storage unavailable */ }
        }
    }

    function closeOverlays() {
        body.classList.remove('sidebar-open', 'filters-open');
        if (!desktop.matches) { body.classList.remove('cart-open'); }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;

        if (target.closest('[data-sidebar-toggle]')) {
            event.preventDefault();
            body.classList.toggle('sidebar-open');
            return;
        }

        if (target.closest('[data-cart-toggle]')) {
            event.preventDefault();
            setCartOpen(!body.classList.contains('cart-open'), true);
            return;
        }

        if (target.closest('[data-cart-close]')) {
            event.preventDefault();
            setCartOpen(false, true);
            return;
        }

        if (target.closest('[data-filters-open]')) {
            event.preventDefault();
            body.classList.add('filters-open');
            return;
        }

        if (target.closest('[data-filters-close]')) {
            event.preventDefault();
            body.classList.remove('filters-open');
            return;
        }

        if (target.closest('.sidebar-backdrop')) {
            closeOverlays();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { closeOverlays(); }
    });

    /* Keep the docked bag open between pages while it has items, unless the
       shopper closed it. Panels appear without sliding in on page load. */
    (function restoreCartPanel() {
        var stored = null;
        try { stored = localStorage.getItem('dz-cart-open'); } catch (error) { stored = null; }
        var hasItems = Number(body.dataset.bagState || 0) > 0;
        if (desktop.matches && !body.hasAttribute('data-no-cart-panel') && hasItems && stored !== '0') {
            setCartOpen(true, false);
        }
        requestAnimationFrame(function () { requestAnimationFrame(function () { body.classList.remove('dz-preload'); }); });
    })();

    /* ---------- Bag ---------- */
    function applyBag(data) {
        if (!data) { return; }
        var count = Number(data.count || 0);

        qsa('[data-bag-count]').forEach(function (node) {
            node.textContent = count;
            node.setAttribute('data-count', count);
        });
        qsa('[data-bag-total]').forEach(function (node) { node.textContent = data.total; });
        qsa('[data-bag-noun]').forEach(function (node) { node.textContent = count === 1 ? 'item' : 'items'; });
        qsa('[data-bag-body]').forEach(function (node) {
            if (typeof data.panel === 'string') { node.innerHTML = data.panel; }
        });
        body.classList.toggle('has-cartbar', count > 0);
        body.dataset.bagState = count;

        var quantities = data.quantities || {};
        qsa('[data-qty-control]').forEach(function (control) {
            if (control.hasAttribute('data-variant')) {
                if (String(data.product_id) === control.dataset.product && data.size === control.dataset.size && data.variant_quantity !== undefined) {
                    setControlQty(control, Number(data.variant_quantity));
                }
                return;
            }
            setControlQty(control, Number(quantities[control.dataset.product] || 0));
        });

        if (qs('[data-reload-on-bag-change]')) { window.location.reload(); }
    }

    function setControlQty(control, qty) {
        control.dataset.qty = String(qty);
        qsa('[data-qty-value]', control).forEach(function (node) { node.textContent = qty; });
    }

    function saveQuantity(productId, size, quantity, control) {
        if (control) { control.classList.add('is-busy'); }
        var wasEmpty = Number(body.dataset.bagState || 0) === 0;

        return request(routes.cartItems, {
            method: 'POST',
            body: { product_id: Number(productId), size: size, quantity: Math.max(0, quantity), mode: 'set' }
        }).then(function (data) {
            applyBag(data);
            if (wasEmpty && quantity > 0 && desktop.matches && !body.hasAttribute('data-no-cart-panel')) { setCartOpen(true, true); }
        }).catch(function (error) {
            if (error.silent) { return; }
            if (error.data) { applyBag(error.data); }
            toast(error.message, 'error');
        }).finally(function () {
            if (control) { control.classList.remove('is-busy'); }
        });
    }

    document.addEventListener('click', function (event) {
        var control = event.target.closest('[data-qty-control]');

        if (control) {
            var button = event.target.closest('button');
            if (!button || button.disabled) { return; }
            event.preventDefault();

            if (control.dataset.optionsUrl) {
                openOptions(control.dataset.optionsUrl);
                return;
            }

            var current = Number(control.dataset.qty || 0);

            if (button.classList.contains('qty-badge')) {
                control.classList.add('is-expanded');
                return;
            }

            var next = button.dataset.step === 'down' ? current - 1 : current + 1;
            saveQuantity(control.dataset.product, control.dataset.size, next, control);
            return;
        }

        var setter = event.target.closest('[data-bag-set]');
        if (setter) {
            event.preventDefault();
            saveQuantity(setter.dataset.product, setter.dataset.size, Number(setter.dataset.bagSet), null);
            return;
        }

        var remover = event.target.closest('[data-bag-remove]');
        if (remover) {
            event.preventDefault();
            request(remover.dataset.bagRemove, { method: 'DELETE' }).then(applyBag).catch(function (error) {
                if (!error.silent) { toast(error.message, 'error'); }
            });
            return;
        }

        var voucherToggle = event.target.closest('[data-voucher-toggle]');
        if (voucherToggle) {
            event.preventDefault();
            var form = qs('[data-voucher-form]', voucherToggle.closest('[data-bag-body], .cart-summary') || document);
            if (form) { form.hidden = !form.hidden; if (!form.hidden) { qs('input', form).focus(); } }
            return;
        }

        var voucherRemove = event.target.closest('[data-voucher-remove]');
        if (voucherRemove) {
            event.preventDefault();
            request(routes.voucher, { method: 'DELETE' }).then(applyBag).catch(function (error) {
                if (!error.silent) { toast(error.message, 'error'); }
            });
        }
    });

    document.addEventListener('mouseleave', function (event) {
        if (event.target.closest && event.target.matches('[data-qty-control]')) {
            event.target.classList.remove('is-expanded');
        }
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (form.matches('[data-voucher-form]')) {
            event.preventDefault();
            var code = (qs('input[name="code"]', form) || {}).value || '';
            request(routes.voucher, { method: 'POST', body: { code: code } }).then(function (data) {
                applyBag(data);
                toast(data.message, 'success');
            }).catch(function (error) {
                if (error.silent) { return; }
                if (error.data) { applyBag(error.data); }
                toast(error.message, 'error');
            });
        }
    });

    /* ---------- Variant options dialog ---------- */
    function openOptions(url) {
        var dialog = qs('#options-dialog');
        if (!dialog) { window.location.href = url; return; }
        var target = qs('[data-options-body]', dialog);
        target.innerHTML = '<p class="muted">Loading options…</p>';
        if (!dialog.open) { dialog.showModal(); }

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (response) { return response.text(); })
            .then(function (html) { target.innerHTML = html; })
            .catch(function () { target.innerHTML = '<p class="muted">Could not load the options. Please try again.</p>'; });
    }

    document.addEventListener('click', function (event) {
        var closer = event.target.closest('[data-dialog-close]');
        if (closer) {
            var dialog = closer.closest('dialog');
            if (dialog) { dialog.close(); }
            return;
        }

        if (event.target.matches('dialog.dialog')) {
            event.target.close();
            return;
        }

        var opener = event.target.closest('[data-dialog-open]');
        if (opener) {
            var target = qs(opener.dataset.dialogOpen);
            if (target) { event.preventDefault(); target.showModal(); }
        }
    });

    /* ---------- Search suggestions ---------- */
    qsa('[data-search]').forEach(function (form) {
        var input = qs('input[name="q"]', form);
        var panel = qs('[data-search-suggest]', form);
        var timer = null;
        var lastTerm = '';
        if (!input || !panel || !routes.suggest) { return; }

        function close() { panel.hidden = true; input.setAttribute('aria-expanded', 'false'); }

        function link(href, className) {
            var anchor = document.createElement('a');
            anchor.href = href;
            if (className) { anchor.className = className; }
            return anchor;
        }

        function text(tag, className, value) {
            var node = document.createElement(tag);
            node.className = className;
            node.textContent = value;
            return node;
        }

        function render(data) {
            panel.innerHTML = '';
            var groups = [['Products', data.products || []], ['Categories', data.categories || []], ['Stores', data.stores || []]];
            var any = false;

            groups.forEach(function (group) {
                if (!group[1].length) { return; }
                any = true;
                panel.appendChild(text('div', 'search-suggest__group', group[0]));
                group[1].forEach(function (item) {
                    var anchor = link(item.url);
                    if (item.image) {
                        var image = document.createElement('img');
                        image.src = item.image;
                        image.alt = '';
                        image.loading = 'lazy';
                        anchor.appendChild(image);
                    }
                    var name = text('span', 'search-suggest__name', item.name + (item.pack ? ' · ' + item.pack : '') + (item.official ? ' · Official' : ''));
                    anchor.appendChild(name);
                    if (item.price) { anchor.appendChild(text('span', 'search-suggest__price', item.price)); }
                    panel.appendChild(anchor);
                });
            });

            if (!any) {
                panel.appendChild(text('div', 'search-suggest__group', 'No quick matches — press Enter to search'));
            } else if (data.all) {
                var all = link(data.all, 'search-suggest__all');
                all.textContent = 'See all results';
                panel.appendChild(all);
            }

            panel.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }

        input.addEventListener('input', function () {
            var term = input.value.trim();
            clearTimeout(timer);
            if (term.length < 2) { close(); return; }
            timer = setTimeout(function () {
                lastTerm = term;
                request(routes.suggest + '?q=' + encodeURIComponent(term)).then(function (data) {
                    if (input.value.trim() === lastTerm) { render(data); }
                }).catch(close);
            }, 180);
        });

        input.addEventListener('keydown', function (event) {
            var links = qsa('a', panel);
            if (panel.hidden || !links.length) { return; }
            var index = links.findIndex(function (anchor) { return anchor.classList.contains('is-active'); });

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (index >= 0) { links[index].classList.remove('is-active'); }
                index = event.key === 'ArrowDown' ? (index + 1) % links.length : (index - 1 + links.length) % links.length;
                links[index].classList.add('is-active');
            } else if (event.key === 'Enter' && index >= 0) {
                event.preventDefault();
                window.location.href = links[index].href;
            } else if (event.key === 'Escape') {
                close();
            }
        });

        document.addEventListener('click', function (event) {
            if (!form.contains(event.target)) { close(); }
        });
    });

    /* ---------- Countdowns ---------- */
    function tickCountdowns() {
        qsa('[data-countdown]').forEach(function (node) {
            var remaining = Math.max(0, new Date(node.dataset.countdown).getTime() - Date.now());
            var hours = Math.floor(remaining / 3600000);
            var minutes = Math.floor((remaining % 3600000) / 60000);
            var seconds = Math.floor((remaining % 60000) / 1000);
            var pad = function (value) { return String(value).padStart(2, '0'); };
            var parts = { h: pad(hours), m: pad(minutes), s: pad(seconds) };

            qsa('[data-unit]', node).forEach(function (unit) { unit.textContent = parts[unit.dataset.unit]; });
            if (remaining === 0 && !node.dataset.ended) {
                node.dataset.ended = '1';
                var label = qs('[data-countdown-label]', node);
                if (label) { label.textContent = 'Ended'; }
            }
        });
    }
    if (qs('[data-countdown]')) { tickCountdowns(); setInterval(tickCountdowns, 1000); }

    /* ---------- Sliders + rails ---------- */
    qsa('[data-slider]').forEach(function (slider) {
        var track = qs('.slider__track', slider);
        var slides = qsa('.slider__slide', slider);
        var dots = qsa('.slider__dots button', slider);
        var index = 0;
        var timer = null;
        if (slides.length < 2) { return; }

        function go(next) {
            index = (next + slides.length) % slides.length;
            track.scrollTo({ left: track.clientWidth * index, behavior: 'smooth' });
            dots.forEach(function (dot, position) { dot.classList.toggle('is-active', position === index); });
        }
        function play() { timer = setInterval(function () { go(index + 1); }, 5000); }

        dots.forEach(function (dot, position) { dot.addEventListener('click', function () { clearInterval(timer); go(position); play(); }); });
        slider.addEventListener('mouseenter', function () { clearInterval(timer); });
        slider.addEventListener('mouseleave', play);
        play();
    });

    document.addEventListener('click', function (event) {
        var nav = event.target.closest('[data-rail-scroll]');
        if (!nav) { return; }
        var rail = qs(nav.dataset.railScroll);
        if (rail) { rail.scrollBy({ left: (nav.dataset.direction === 'prev' ? -1 : 1) * rail.clientWidth * 0.8, behavior: 'smooth' }); }
    });

    /* ---------- Wishlist + follow ---------- */
    document.addEventListener('click', function (event) {
        var wish = event.target.closest('[data-wishlist]');
        if (wish) {
            event.preventDefault();
            request(wish.dataset.wishlist, { method: 'POST' }).then(function (data) {
                qsa('[data-wishlist="' + wish.dataset.wishlist + '"]').forEach(function (button) {
                    button.setAttribute('aria-pressed', data.wished ? 'true' : 'false');
                });
                qsa('[data-wishlist-count]').forEach(function (node) { node.textContent = data.count; node.setAttribute('data-count', data.count); });
                toast(data.message, 'success');
            }).catch(function (error) { if (!error.silent) { toast(error.message, 'error'); } });
            return;
        }

        var follow = event.target.closest('[data-follow]');
        if (follow) {
            event.preventDefault();
            request(follow.dataset.follow, { method: 'POST' }).then(function (data) {
                follow.setAttribute('aria-pressed', data.following ? 'true' : 'false');
                qsa('[data-follow-label]', follow).forEach(function (node) { node.textContent = data.following ? 'Following' : 'Follow'; });
                qsa('[data-followers]').forEach(function (node) { node.textContent = data.followers; });
                toast(data.message, 'success');
            }).catch(function (error) { if (!error.silent) { toast(error.message, 'error'); } });
            return;
        }

        var copy = event.target.closest('[data-copy]');
        if (copy) {
            event.preventDefault();
            var value = copy.dataset.copy;
            var done = function () { toast(copy.dataset.copyMessage || ('Code ' + value + ' copied — apply it in your bag.'), 'success'); };
            if (navigator.clipboard) { navigator.clipboard.writeText(value).then(done, done); } else { done(); }
        }
    });

    /* ---------- Load more ---------- */
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-load-more]');
        if (!button) { return; }
        event.preventDefault();
        var grid = qs(button.dataset.target);
        var url = button.dataset.loadMore;
        if (!grid || !url) { return; }

        button.disabled = true;
        button.textContent = 'Loading…';
        var next = url + (url.indexOf('?') === -1 ? '?' : '&') + 'fragment=1';

        request(next).then(function (data) {
            grid.insertAdjacentHTML('beforeend', data.html);
            if (data.next) {
                button.dataset.loadMore = data.next.replace(/([?&])fragment=1(&|$)/, '$1').replace(/[?&]$/, '');
                button.disabled = false;
                button.textContent = button.dataset.label || 'Load more';
            } else {
                button.closest('.load-more').remove();
            }
            var fallback = qs('[data-pagination]');
            if (fallback) { fallback.remove(); }
        }).catch(function (error) {
            button.disabled = false;
            button.textContent = button.dataset.label || 'Load more';
            if (!error.silent) { toast(error.message, 'error'); }
        });
    });

    /* ---------- Filters ---------- */
    document.addEventListener('change', function (event) {
        var field = event.target.closest('[data-autosubmit]');
        if (field && field.form) { field.form.submit(); }
    });

    /* ---------- Product page ---------- */
    var buyBox = qs('[data-buy-box]');
    if (buyBox) {
        var sizeInput = qs('[data-selected-size]', buyBox);
        var quantityInput = qs('[data-quantity]', buyBox);

        function selectVariant(chip) {
            qsa('[data-variant-chip]', buyBox).forEach(function (other) { other.classList.toggle('is-active', other === chip); other.setAttribute('aria-pressed', other === chip ? 'true' : 'false'); });
            sizeInput.value = chip.dataset.size;
            qsa('[data-price-final]').forEach(function (node) { node.textContent = chip.dataset.final; });
            qsa('[data-price-original]').forEach(function (node) { node.textContent = chip.dataset.original; node.hidden = chip.dataset.discount === '0'; });
            qsa('[data-price-discount]').forEach(function (node) { node.textContent = '-' + chip.dataset.discount + '%'; node.hidden = chip.dataset.discount === '0'; });
            qsa('[data-selected-label]').forEach(function (node) { node.textContent = chip.dataset.size; });
            var stock = Number(chip.dataset.stock || 0);
            var note = qs('[data-stock-note]');
            if (note) {
                note.className = 'stock-note ' + (stock < 1 ? 'stock-note--out' : stock <= 5 ? 'stock-note--low' : 'stock-note--in');
                note.textContent = stock < 1 ? 'Out of stock' : stock <= 5 ? 'Hurry, only ' + stock + ' left' : 'In stock';
            }
            quantityInput.max = Math.max(1, stock);
            if (Number(quantityInput.value) > stock) { quantityInput.value = Math.max(1, stock); }
            qsa('[data-buy-action]').forEach(function (button) { button.disabled = stock < 1; });
            var inBag = qs('[data-in-bag]');
            if (inBag) { inBag.textContent = chip.dataset.inBag > 0 ? chip.dataset.inBag + ' in your bag' : ''; }
        }

        buyBox.addEventListener('click', function (event) {
            var chip = event.target.closest('[data-variant-chip]');
            if (chip && !chip.disabled) { event.preventDefault(); selectVariant(chip); return; }

            var step = event.target.closest('[data-quantity-step]');
            if (step) {
                event.preventDefault();
                var value = Number(quantityInput.value || 1) + Number(step.dataset.quantityStep);
                quantityInput.value = Math.min(Number(quantityInput.max || 99), Math.max(1, value));
                return;
            }

            var action = event.target.closest('[data-buy-action]');
            if (action) {
                event.preventDefault();
                action.disabled = true;
                request(routes.cartItems, {
                    method: 'POST',
                    body: { product_id: Number(buyBox.dataset.product), size: sizeInput.value, quantity: Number(quantityInput.value || 1), mode: 'add' }
                }).then(function (data) {
                    applyBag(data);
                    var chipActive = qs('[data-variant-chip].is-active', buyBox);
                    if (chipActive && data.variant_quantity !== undefined) { chipActive.dataset.inBag = data.variant_quantity; }
                    var inBag = qs('[data-in-bag]');
                    if (inBag && data.variant_quantity) { inBag.textContent = data.variant_quantity + ' in your bag'; }
                    if (action.dataset.buyAction === 'buy') {
                        window.location.href = routes.checkout;
                        return;
                    }
                    toast('Added to your bag.', 'success');
                    if (desktop.matches) { setCartOpen(true, true); }
                }).catch(function (error) {
                    if (!error.silent) { toast(error.message, 'error'); }
                }).finally(function () { action.disabled = false; });
            }
        });
    }

    document.addEventListener('click', function (event) {
        var proxy = event.target.closest('[data-buy-proxy]');
        if (!proxy) { return; }
        var action = qs('[data-buy-action="' + proxy.dataset.buyProxy + '"]');
        if (action) { action.click(); }
    });

    qsa('[data-gallery]').forEach(function (gallery) {
        var main = qs('[data-gallery-main]', gallery);
        gallery.addEventListener('click', function (event) {
            var thumb = event.target.closest('[data-gallery-thumb]');
            if (!thumb) { return; }
            main.src = thumb.dataset.galleryThumb;
            qsa('[data-gallery-thumb]', gallery).forEach(function (other) { other.classList.toggle('is-active', other === thumb); });
        });
    });

    qsa('[data-scrollspy]').forEach(function (nav) {
        var links = qsa('a[href^="#"]', nav);
        if (!('IntersectionObserver' in window)) { return; }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) { return; }
                links.forEach(function (link) { link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id); });
            });
        }, { rootMargin: '-40% 0px -55% 0px' });
        links.forEach(function (link) {
            var section = qs(link.getAttribute('href'));
            if (section) { observer.observe(section); }
        });
    });

    /* ---------- Checkout ---------- */
    var checkout = qs('[data-checkout]');
    if (checkout) {
        var feeNode = qs('[data-delivery-fee]');
        var totalNode = qs('[data-checkout-total]');
        var base = Number(checkout.dataset.subtotalAfterVoucher || 0);
        var symbol = checkout.dataset.currency || '';
        var money = function (amount) {
            var rounded = Math.round(amount * 100) / 100;
            return symbol + rounded.toLocaleString('en-US', { minimumFractionDigits: rounded % 1 ? 2 : 0, maximumFractionDigits: 2 });
        };

        checkout.addEventListener('change', function (event) {
            if (event.target.name === 'delivery_slot') {
                var dateField = qs('[data-delivery-date]', checkout);
                if (dateField) { dateField.value = event.target.dataset.date; }
                return;
            }
            if (event.target.name === 'address_id') {
                var fee = Number(event.target.dataset.fee || 0);
                if (feeNode) { feeNode.textContent = fee > 0 ? money(fee) : 'Free'; }
                if (totalNode) { totalNode.textContent = money(base + fee); }
            }
        });

        checkout.addEventListener('click', function (event) {
            var tab = event.target.closest('[data-day-tab]');
            if (!tab) { return; }
            event.preventDefault();
            qsa('[data-day-tab]', checkout).forEach(function (other) { other.classList.toggle('is-active', other === tab); other.setAttribute('aria-selected', other === tab ? 'true' : 'false'); });
            qsa('[data-day-panel]', checkout).forEach(function (panel) { panel.hidden = panel.dataset.dayPanel !== tab.dataset.dayTab; });
            var firstOpen = qs('[data-day-panel="' + tab.dataset.dayTab + '"] input:not(:disabled)', checkout);
            if (firstOpen) {
                firstOpen.checked = true;
                firstOpen.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        var checked = qs('input[name="address_id"]:checked', checkout);
        if (checked) { checked.dispatchEvent(new Event('change', { bubbles: true })); }
    }

    /* ---------- JSON forms (login, register, account, addresses) ---------- */
    function showFormErrors(form, errors) {
        qsa('[data-error-for]', form).forEach(function (node) { node.textContent = ''; });
        Object.keys(errors || {}).forEach(function (field) {
            var node = qs('[data-error-for="' + field + '"]', form);
            var message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
            if (node) { node.textContent = message; } else { toast(message, 'error'); }
        });
    }

    function formMessage(form, message, type) {
        var node = qs('[data-form-message]', form);
        if (!node) { toast(message, type === 'success' ? 'success' : 'error'); return; }
        node.hidden = !message;
        node.className = 'alert ' + (type === 'success' ? 'alert--success' : 'alert--error');
        node.textContent = message || '';
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches('[data-json-form]')) { return; }
        event.preventDefault();
        var submit = qs('[type="submit"]', form);
        if (submit) { submit.disabled = true; }
        showFormErrors(form, {});
        formMessage(form, '', 'success');

        request(form.action, { method: 'POST', body: new FormData(form) }).then(function (data) {
            data = data || {};
            if (data.type === 'error') {
                showFormErrors(form, data.errors);
                return;
            }
            if (data.type === 'incorrect' || data.type === 'inactive') {
                formMessage(form, data.message, 'error');
                return;
            }
            if (form.dataset.addressList && data.view !== undefined) {
                var list = qs(form.dataset.addressList);
                if (list) { list.innerHTML = data.view; }
                form.reset();
                var hiddenId = qs('input[name="delivery_id"]', form);
                if (hiddenId) { hiddenId.value = ''; }
                if (form.dataset.reloadAfterSave) { window.location.reload(); return; }
                toast('Address saved.', 'success');
                return;
            }
            if (form.dataset.redirectOnSuccess !== undefined && data.url) {
                window.location.href = data.url;
                return;
            }
            formMessage(form, data.message || 'Saved.', 'success');
            if (form.dataset.resetOnSuccess !== undefined) { form.reset(); }
        }).catch(function (error) {
            if (error.silent) { return; }
            if (error.data && error.data.errors) { showFormErrors(form, error.data.errors); } else { formMessage(form, error.message, 'error'); }
        }).finally(function () {
            if (submit) { submit.disabled = false; }
        });
    });

    document.addEventListener('click', function (event) {
        var edit = event.target.closest('[data-address-edit]');
        if (edit) {
            event.preventDefault();
            var form = qs('[data-address-form]');
            if (!form) { return; }
            var payload = new FormData();
            payload.append('addressid', edit.dataset.addressEdit);
            request(routes.addressGet, { method: 'POST', body: payload }).then(function (data) {
                var address = data.address || {};
                ['name', 'address', 'city', 'state', 'country', 'pincode', 'mobile'].forEach(function (key) {
                    var field = qs('[name="delivery_' + key + '"]', form);
                    if (field) { field.value = address[key] || ''; }
                });
                qs('input[name="delivery_id"]', form).value = address.id;
                var details = form.closest('details');
                if (details) { details.open = true; }
                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }).catch(function (error) { if (!error.silent) { toast(error.message, 'error'); } });
            return;
        }

        var remove = event.target.closest('[data-address-remove]');
        if (remove) {
            event.preventDefault();
            if (!window.confirm('Remove this address?')) { return; }
            var removal = new FormData();
            removal.append('addressid', remove.dataset.addressRemove);
            request(routes.addressRemove, { method: 'POST', body: removal }).then(function (data) {
                var list = qs(remove.dataset.list || '[data-address-list]');
                if (list) { list.innerHTML = data.view; }
                toast('Address removed.', 'success');
            }).catch(function (error) { if (!error.silent) { toast(error.message, 'error'); } });
        }
    });

    /* ---------- Tabs (auth page) ---------- */
    document.addEventListener('click', function (event) {
        var tab = event.target.closest('[data-tab]');
        if (!tab) { return; }
        event.preventDefault();
        var group = tab.closest('[data-tabs]');
        qsa('[data-tab]', group).forEach(function (other) { other.classList.toggle('is-active', other === tab); other.setAttribute('aria-selected', other === tab ? 'true' : 'false'); });
        qsa('[data-tab-panel]', group.parentElement).forEach(function (panel) { panel.hidden = panel.dataset.tabPanel !== tab.dataset.tab; });
    });

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('form[data-confirm]');
        if (form && !window.confirm(form.dataset.confirm)) { event.preventDefault(); }
    });

    /* ---------- Flash messages from the server ---------- */
    qsa('[data-flash]').forEach(function (node) { toast(node.dataset.flash, node.dataset.type); });
})();
