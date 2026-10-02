/**
 * Ghar Masala front page: view switching, basket, delivery slots and checkout.
 * Data comes from window.GM_CONFIG, printed by functions.php.
 */
(function () {
	'use strict';

	var C = window.GM_CONFIG || {};
	var STORE_KEY = 'gm_basket_v1';
	var VIEWS = ['home', 'menu', 'how', 'story', 'testimonials', 'news', 'faq', 'allergens', 'login', 'account', 'pay', 'done'];
	var BARE_VIEWS = ['login', 'account', 'pay', 'done']; // own slim header, no footer

	var items = {};
	(C.menu || []).forEach(function (g) {
		g.items.forEach(function (it) { items[it.id] = it; });
	});

	var state = {
		view: 'home',
		cart: {},
		notes: {},
		days: null,
		day: null,
		slot: null, // { date, time, label }
		order: null,
		busy: false
	};

	/* ------------------------------------------------------------ helpers */

	function $(sel, root) { return (root || document).querySelector(sel); }
	function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	function money(p) { return '£' + (p / 100).toFixed(2); }

	function save() {
		try { localStorage.setItem(STORE_KEY, JSON.stringify({ cart: state.cart, notes: state.notes })); } catch (e) { /* private mode */ }
	}

	function load() {
		try {
			var s = JSON.parse(localStorage.getItem(STORE_KEY) || 'null');
			if (s && s.cart) {
				Object.keys(s.cart).forEach(function (id) {
					var q = parseInt(s.cart[id], 10);
					if (items[id] && q > 0) state.cart[id] = Math.min(q, 50);
				});
				state.notes = s.notes || {};
			}
		} catch (e) { /* ignore */ }
	}

	function subtotal() {
		return Object.keys(state.cart).reduce(function (sum, id) {
			return sum + items[id].price * state.cart[id];
		}, 0);
	}

	function count() {
		return Object.keys(state.cart).reduce(function (n, id) { return n + state.cart[id]; }, 0);
	}

	function bump(id, delta) {
		var next = (state.cart[id] || 0) + delta;
		if (next <= 0) {
			delete state.cart[id];
			delete state.notes[id];
		} else {
			state.cart[id] = Math.min(next, 50);
		}
		save();
		renderCart();
	}

	function notice(msg) {
		var el = $('[data-gm-notice]');
		if (!el) return;
		el.textContent = msg || '';
		el.hidden = !msg;
	}

	/* ------------------------------------------------------------ routing */

	function route() {
		var hash = decodeURIComponent(location.hash.replace(/^#/, ''));
		var scrollTo = null;
		var view = hash;

		if (hash === 'order' || hash === 'delivery') {
			scrollTo = hash;
			view = hash === 'order' ? 'menu' : 'how';
		}
		if (!view || view === 'top') view = 'home';
		if (view === 'account' && !C.loggedIn) view = 'login';
		if (view === 'login' && C.loggedIn) view = 'account';
		if (VIEWS.indexOf(view) === -1) return; // an ordinary in-page anchor
		if (view === 'pay' && (!state.slot || subtotal() < C.minOrder)) view = 'menu';
		if (view === 'done' && !state.order) view = 'home';

		show(view);
		if (scrollTo) {
			var target = document.getElementById(scrollTo);
			if (target) target.scrollIntoView();
		} else {
			window.scrollTo(0, 0);
		}
	}

	function go(view) {
		if (location.hash === '#' + view) route();
		else location.hash = view;
	}

	function show(view) {
		state.view = view;
		$$('.gm-view').forEach(function (el) { el.hidden = el.getAttribute('data-view') !== view; });

		var bare = BARE_VIEWS.indexOf(view) !== -1;
		var topbar = $('[data-gm-topbar]');
		var footer = $('[data-gm-footer]');
		if (topbar) topbar.hidden = bare || view === 'home';
		if (footer) footer.hidden = bare;
		document.body.setAttribute('data-gm-view', view);

		$$('[data-nav]').forEach(function (a) {
			var active = a.getAttribute('data-nav') === view || (a.getAttribute('data-nav') === 'account' && view === 'login');
			if (active) a.setAttribute('aria-current', 'page');
			else a.removeAttribute('aria-current');
		});

		var titles = { home: '', menu: 'Menu', how: 'How it works', story: 'My story', testimonials: 'Testimonials', news: 'News', faq: 'FAQs', allergens: 'Allergens', login: 'Sign in', account: 'My account', pay: 'Checkout', done: 'Order confirmed' };
		if (!show.baseTitle) show.baseTitle = document.title;
		document.title = titles[view] ? titles[view] + ' — ' + show.baseTitle : show.baseTitle;

		if (view === 'menu') {
			renderCart();
			loadSlots();
		}
		if (view === 'pay') renderPay();
		if (view === 'done') renderDone();
	}

	/* ------------------------------------------------------------ menu + basket */

	function renderCart() {
		var n = count();
		$$('[data-gm-badge]').forEach(function (el) { el.textContent = n ? ' · ' + n : ''; });
		$$('[data-gm-qty]').forEach(function (el) {
			var q = state.cart[el.getAttribute('data-gm-qty')];
			el.textContent = q ? '×' + q : '';
		});
		renderBasket();
		renderSlots();
	}

	function renderBasket() {
		var box = $('[data-gm-basket]');
		if (!box) return;
		var ids = Object.keys(state.cart);
		if (!ids.length) {
			box.innerHTML = '<p class="gm-muted">Nothing in the basket yet. Add dishes from the menu above and they appear here.</p>';
			return;
		}
		var sub = subtotal();
		box.innerHTML = ids.map(function (id) {
			var it = items[id];
			var q = state.cart[id];
			return '<div class="gm-line">' +
				'<div class="gm-line__row">' +
				'<span class="gm-line__name">' + esc(it.name) + '</span>' +
				'<button type="button" class="gm-qtybtn" data-gm-dec="' + esc(id) + '" aria-label="One fewer ' + esc(it.name) + '">–</button>' +
				'<span class="gm-line__qty gm-tnum">' + q + '</span>' +
				'<button type="button" class="gm-qtybtn" data-gm-inc="' + esc(id) + '" aria-label="One more ' + esc(it.name) + '">+</button>' +
				'<span class="gm-line__total gm-tnum">' + money(it.price * q) + '</span>' +
				'</div>' +
				'<input class="input gm-line__note" type="text" maxlength="200" data-gm-note="' + esc(id) + '" placeholder="Add a note — e.g. medium spice, no onions" aria-label="Note for ' + esc(it.name) + '" value="' + esc(state.notes[id] || '') + '">' +
				'</div>';
		}).join('') +
			'<div class="gm-sum"><span class="gm-soft">Subtotal</span><span class="gm-tnum">' + money(sub) + '</span></div>' +
			'<div class="gm-sum gm-sum--tight"><span class="gm-soft">Delivery within 2 miles of Tividale Viewpoint</span><span>Free</span></div>' +
			'<div class="gm-sum gm-sum--total"><span>Total</span><span class="gm-tnum">' + money(sub) + '</span></div>';
	}

	/* ------------------------------------------------------------ slots */

	var slotsLoadedAt = 0;

	function loadSlots(force) {
		if (!force && state.days && Date.now() - slotsLoadedAt < 60000) return;
		slotsLoadedAt = Date.now();
		fetch(C.rest + 'slots?_=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				state.days = data.days || [];
				renderSlots();
			})
			.catch(function () {
				state.days = state.days || [];
				slotsLoadedAt = 0;
				renderSlots('Delivery slots could not be loaded. Please refresh the page, or ring the kitchen.');
			});
	}

	function renderSlots(error) {
		var box = $('[data-gm-slots]');
		if (!box) return;
		var sub = subtotal();

		if (sub < C.minOrder) {
			box.innerHTML = '<p class="gm-lock">' + (count() === 0
				? 'Add dishes to your basket and the delivery slots open up here.'
				: 'Add ' + money(C.minOrder - sub) + ' more to reach the ' + money(C.minOrder) + ' minimum, then pick your slot.') + '</p>';
			return;
		}
		if (error) {
			box.innerHTML = '<p class="gm-lock">' + esc(error) + '</p>';
			return;
		}
		if (!state.days) {
			box.innerHTML = '<p class="gm-muted">Loading delivery slots…</p>';
			return;
		}

		var open = state.days.filter(function (d) { return d.open; });
		if (!open.length) {
			box.innerHTML = '<p class="gm-lock">No delivery days are open for booking right now. Ring the kitchen on <a href="' + esc(C.phone.href) + '">' + esc(C.phone.label) + '</a>.</p>';
			return;
		}
		var active = open.filter(function (d) { return d.date === state.day; })[0] || open[0];
		state.day = active.date;

		var tabs = state.days.map(function (d) {
			if (!d.open) {
				return '<div class="gm-day gm-day--closed"><span>' + esc(d.weekday) + '</span><small>' + esc(d.status) + '</small></div>';
			}
			if (d.date === active.date) {
				return '<div class="gm-day gm-day--active" aria-current="date"><span>' + esc(d.weekday) + '</span><small class="gm-tnum">' + esc(d.dateLabel) + '</small></div>';
			}
			return '<button type="button" class="gm-day" data-gm-day="' + esc(d.date) + '"><span>' + esc(d.weekday) + '</span><small class="gm-tnum">' + esc(d.dateLabel) + '</small></button>';
		}).join('');

		var slots = active.slots.map(function (s) {
			if (s.taken) {
				return '<div class="gm-slot gm-slot--taken"><span class="gm-tnum">' + esc(s.label) + '</span><small>Taken</small></div>';
			}
			return '<button type="button" class="gm-slot" data-gm-slot="' + esc(s.time) + '"><span class="gm-tnum">' + esc(s.label) + '</span><small class="gm-tnum">' + esc(s.window) + '</small></button>';
		}).join('');

		box.innerHTML = '<div class="gm-days">' + tabs + '</div>' +
			'<p class="gm-small gm-muted gm-days__label">' + esc(active.long) + '</p>' +
			'<div class="gm-slots">' + slots + '</div>' +
			'<p class="gm-small gm-muted" style="margin-top:20px">Choosing a slot takes you to checkout. Orders must be placed by 7pm the day before delivery.</p>';
	}

	function pickSlot(time) {
		var day = (state.days || []).filter(function (d) { return d.date === state.day; })[0];
		var slot = day && day.slots.filter(function (s) { return s.time === time; })[0];
		if (!slot) return;
		state.slot = { date: day.date, time: slot.time, label: day.long + ', ' + slot.window };
		go('pay');
	}

	/* ------------------------------------------------------------ checkout */

	function payMethod() {
		var picked = $('[data-gm-pay] [name="payment"]:checked') || $('[data-gm-pay] [name="payment"]');
		return picked ? picked.value : 'cod';
	}

	function renderPay() {
		$$('[data-gm-slot-label]').forEach(function (el) { el.textContent = state.slot ? state.slot.label : ''; });
		var sub = subtotal();
		var box = $('[data-gm-summary]');
		box.innerHTML = Object.keys(state.cart).map(function (id) {
			var it = items[id];
			var note = (state.notes[id] || '').trim();
			return '<div class="gm-sumline"><span><span>' + state.cart[id] + ' × ' + esc(it.name) + '</span>' +
				(note ? '<small>' + esc(note) + '</small>' : '') +
				'</span><span class="gm-tnum">' + money(it.price * state.cart[id]) + '</span></div>';
		}).join('') +
			'<div class="gm-sum"><span class="gm-soft">Subtotal</span><span class="gm-tnum">' + money(sub) + '</span></div>' +
			'<div class="gm-sum gm-sum--tight"><span class="gm-soft">Delivery</span><span>Free</span></div>' +
			'<div class="gm-sum gm-sum--total"><span>Total</span><span class="gm-tnum">' + money(sub) + '</span></div>';

		// Pre-fill from the customer's last order when signed in.
		var saved = C.user && C.user.saved;
		if (saved) {
			[['address', 'address'], ['postcode', 'postcode'], ['phone', 'phone']].forEach(function (pair) {
				var input = $('[data-gm-pay] [name="' + pair[0] + '"]');
				if (input && !input.value && saved[pair[1]]) input.value = saved[pair[1]];
			});
		}
		$('[data-gm-error]').hidden = true;
		updatePayButton();
	}

	function updatePayButton() {
		var card = payMethod() === 'card';
		$('[data-gm-submit]').textContent = (card ? 'Pay ' : 'Place order · ') + money(subtotal());
		$('[data-gm-pay-note]').textContent = card
			? 'You will be taken to Stripe’s secure page to pay — card details never touch this site. Your slot is held while you pay. Discount codes are checked when we confirm your order.'
			: 'Pay when your food arrives, by cash or card. Discount codes are checked when we confirm your order.';
	}

	function submitOrder(e) {
		e.preventDefault();
		if (state.busy) return;
		var form = e.target;
		var errorBox = $('[data-gm-error]');
		var firstInvalid = $$('input[required]', form).filter(function (i) { return !i.value.trim() || !i.checkValidity(); })[0];
		if (firstInvalid) {
			errorBox.textContent = 'Please fill in ' + firstInvalid.labels[0].textContent.toLowerCase() + '.';
			errorBox.hidden = false;
			firstInvalid.focus();
			return;
		}

		var payload = { items: state.cart, notes: state.notes, date: state.slot.date, time: state.slot.time, payment: payMethod() };
		['name', 'email', 'address', 'postcode', 'phone', 'instructions', 'discount', 'website'].forEach(function (k) {
			payload[k] = form.elements[k] ? form.elements[k].value.trim() : '';
		});

		var headers = { 'Content-Type': 'application/json' };
		if (C.nonce) headers['X-WP-Nonce'] = C.nonce; // signed-in customers only; see functions.php

		var button = $('[data-gm-submit]');
		state.busy = true;
		button.disabled = true;
		button.textContent = 'Placing your order…';
		errorBox.hidden = true;

		fetch(C.rest + 'orders', { method: 'POST', credentials: 'same-origin', headers: headers, body: JSON.stringify(payload) })
			.then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
			.then(function (res) {
				if (!res.ok) throw res.body;
				if (res.body.redirect) {
					window.location.href = res.body.redirect;
					return;
				}
				state.order = res.body.order;
				clearBasket();
				go('done');
			})
			.catch(function (err) {
				var code = err && err.code;
				var message = (err && err.message) || 'Something went wrong — please try again, or ring the kitchen.';
				if (code === 'gm_slot_taken' || code === 'gm_slot_closed' || code === 'gm_slot') {
					state.slot = null;
					state.days = null;
					go('menu');
					notice(message);
					var order = document.getElementById('order');
					if (order) order.scrollIntoView();
					return;
				}
				errorBox.textContent = message;
				errorBox.hidden = false;
			})
			.then(function () {
				state.busy = false;
				button.disabled = false;
				if (state.view === 'pay') updatePayButton();
			});
	}

	function clearBasket() {
		state.cart = {};
		state.notes = {};
		state.slot = null;
		state.days = null;
		save();
		renderCart();
	}

	function renderDone() {
		var o = state.order;
		var paid = o.payment === 'card' && o.status === 'confirmed';
		var processing = o.payment === 'card' && o.status !== 'confirmed';
		$('[data-gm-done-kicker]').textContent = (paid ? 'Paid' : processing ? 'Payment processing' : 'Booked') + ' — order ' + o.ref;
		$('[data-gm-done-title]').textContent = processing ? 'Nearly there — we are confirming your payment.' : 'Booked in. Your table at home is set.';
		$('[data-gm-done-text]').innerHTML = 'Your delivery window is <strong>' + esc(o.slot) + '</strong>. ' +
			(processing ? 'You will get a confirmation email as soon as the payment clears. ' : 'A confirmation is on its way to your inbox. We will text you when the food leaves the kitchen. ') +
			'Any changes, ring <a href="' + esc(C.phone.href) + '">' + esc(C.phone.label) + '</a>.';
		$('[data-gm-done-total-label]').textContent = paid ? 'Total paid' : processing ? 'Total' : 'To pay on delivery';
		$('[data-gm-done-total]').textContent = o.total;
		$('[data-gm-done-slot]').textContent = o.slot;
	}

	/* ------------------------------------------------------------ events */

	document.addEventListener('click', function (e) {
		var t = e.target.closest('[data-gm-add],[data-gm-inc],[data-gm-dec],[data-gm-day],[data-gm-slot],[data-gm-reorder],[data-gm-restart],[data-gm-release]');
		if (!t) return;
		if (t.hasAttribute('data-gm-add')) {
			bump(t.getAttribute('data-gm-add'), 1);
			notice('');
		} else if (t.hasAttribute('data-gm-inc')) {
			bump(t.getAttribute('data-gm-inc'), 1);
		} else if (t.hasAttribute('data-gm-dec')) {
			bump(t.getAttribute('data-gm-dec'), -1);
		} else if (t.hasAttribute('data-gm-day')) {
			state.day = t.getAttribute('data-gm-day');
			renderSlots();
		} else if (t.hasAttribute('data-gm-slot')) {
			pickSlot(t.getAttribute('data-gm-slot'));
		} else if (t.hasAttribute('data-gm-reorder')) {
			var cart = JSON.parse(t.getAttribute('data-gm-reorder'));
			state.cart = {};
			state.notes = {};
			Object.keys(cart).forEach(function (id) { if (items[id]) state.cart[id] = cart[id]; });
			save();
			renderCart();
			go('menu');
		} else if (t.hasAttribute('data-gm-restart')) {
			state.order = null;
		} else if (t.hasAttribute('data-gm-release')) {
			state.slot = null;
		}
	});

	document.addEventListener('input', function (e) {
		var id = e.target.getAttribute && e.target.getAttribute('data-gm-note');
		if (id) {
			state.notes[id] = e.target.value;
			save();
		}
	});

	document.addEventListener('change', function (e) {
		if (e.target.name === 'payment') updatePayButton();
	});

	var payForm = $('[data-gm-pay]');
	if (payForm) payForm.addEventListener('submit', submitOrder);

	window.addEventListener('hashchange', route);

	/* ------------------------------------------------------------ start */

	load();

	var startNotice = '';
	if (C.returned) {
		// Back from Stripe: tidy the URL, then show the outcome.
		history.replaceState(null, '', location.pathname);
		if (C.returned.order) {
			state.order = C.returned.order;
			clearBasket();
			history.replaceState(null, '', location.pathname + '#done');
		} else if (C.returned.status === 'cancelled') {
			history.replaceState(null, '', location.pathname + '#order');
			startNotice = 'Payment cancelled — your basket is saved. Pick a slot when you are ready.';
		}
	}

	renderCart();
	route();
	if (startNotice) notice(startNotice);
})();
