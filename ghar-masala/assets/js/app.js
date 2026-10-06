/**
 * Ghar Masala front page: view switching, basket, delivery slots and checkout.
 * Data comes from window.GM_CONFIG, printed by functions.php.
 */
(function () {
	'use strict';

	var C = window.GM_CONFIG || {};
	var STORE_KEY = 'gm_basket_v1';
	var VIEWS = ['home', 'menu', 'how', 'story', 'reviews', 'news', 'contact', 'faq', 'allergens', 'login', 'account', 'pay', 'done'];
	var ALIASES = { testimonials: 'reviews' };
	var LEVELS = C.spiceLevels || {};
	var CAN_HOVER = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
	var BARE_VIEWS = ['login', 'account', 'pay', 'done']; // own slim header, no footer

	var items = {};
	(C.menu || []).forEach(function (g) {
		g.items.forEach(function (it) { items[it.id] = it; });
	});

	var resetLink = null; // { key, login } from a password-reset email

	var state = {
		view: 'home',
		cart: {},
		spice: {}, // id -> chosen spice level ('' = the dish's standard)
		days: null,
		day: null,
		slot: null, // { date, time, label }
		order: null,
		code: null, // discount code the customer applied: { code, label, offer, type, amount, min }
		delivery: { status: 'idle' }, // idle | loading | ok | out | unknown | error
		useLoyalty: true, // spend a ready loyalty reward on this order (customer can untick to save it)
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

	/** REST URL that works with pretty (/wp-json/…) and plain (?rest_route=…) permalinks. */
	function rest(path, query) {
		var url = C.rest + path;
		if (query) url += (url.indexOf('?') === -1 ? '?' : '&') + query;
		return url;
	}

	function save() {
		try { localStorage.setItem(STORE_KEY, JSON.stringify({ cart: state.cart, spice: state.spice })); } catch (e) { /* private mode */ }
	}

	function load() {
		try {
			var s = JSON.parse(localStorage.getItem(STORE_KEY) || 'null');
			if (s && s.cart) {
				Object.keys(s.cart).forEach(function (id) {
					var q = parseInt(s.cart[id], 10);
					if (items[id] && q > 0) state.cart[id] = Math.min(q, 50);
				});
				Object.keys(s.spice || {}).forEach(function (id) {
					if (items[id] && items[id].adjustable && LEVELS[s.spice[id]]) state.spice[id] = s.spice[id];
				});
			}
		} catch (e) { /* ignore */ }
	}

	/** Madras/Vindaloo cost extra unless the dish is already that hot as standard (matches the server). */
	function surcharge(id) {
		var chosen = LEVELS[state.spice[id]];
		var standard = LEVELS[items[id].spice];
		return chosen && chosen.extra_pence && !(standard && standard.extra_pence) ? chosen.extra_pence : 0;
	}

	function unitPrice(id) { return items[id].price + surcharge(id); }

	function subtotal() {
		return Object.keys(state.cart).reduce(function (sum, id) {
			return sum + unitPrice(id) * state.cart[id];
		}, 0);
	}

	/* ------------------------------------------------------------ discounts (mirrors inc/discounts.php) */

	var DISC = C.discounts || { auto: [], stack: false };

	function ruleValue(r, sub) {
		var v = r.type === 'fixed' ? r.amount : Math.round(sub * r.amount / 100);
		return Math.max(0, Math.min(v, sub));
	}

	function bestAuto(sub) {
		var best = null, bestV = 0;
		DISC.auto.forEach(function (r) {
			var v = sub >= r.min ? ruleValue(r, sub) : 0;
			if (v > bestV) { best = r; bestV = v; }
		});
		return best ? { rule: best, value: bestV } : null;
	}

	/** The next automatic offer the customer can unlock by adding more. */
	function nextAuto(sub) {
		var now = bestAuto(sub);
		var next = null;
		DISC.auto.forEach(function (r) {
			if (r.min <= sub) return;
			if (now && ruleValue(r, r.min) <= ruleValue(now.rule, r.min)) return; // no better than what they have
			if (!next || r.min < next.min) next = r;
		});
		return next;
	}

	/** { lines: [{label, pence}], pence, note, codeProblem } */
	function discounts(sub) {
		var out = { lines: [], pence: 0, note: '', codeProblem: '' };
		var auto = bestAuto(sub);
		var code = null;
		if (state.code) {
			if (sub < state.code.min) out.codeProblem = 'Code ' + state.code.code + ' needs a food total of at least ' + money(state.code.min) + '.';
			else code = { rule: state.code, value: ruleValue(state.code, sub) };
		}
		var add = function (d, isCode) {
			out.lines.push({ label: (isCode ? 'Code ' + d.rule.code + ': ' : '') + d.rule.label, pence: d.value });
			out.pence += d.value;
		};
		if (auto && code && !DISC.stack && !code.rule.always) {
			if (code.value > auto.value) add(code, true);
			else { add(auto, false); out.note = 'Discount codes can’t be combined with our automatic offer, so we’ve applied the better saving.'; }
		} else {
			if (auto) add(auto, false);
			if (code) add(code, true);
		}
		out.pence = Math.min(out.pence, sub);
		// Loyalty reward: a % off what's left of the food total, on top of everything else.
		var L = C.loyalty;
		if (L && L.ready && state.useLoyalty) {
			var food = sub - out.pence;
			var v = Math.round(food * L.percent / 100);
			if (L.cap > 0) v = Math.min(v, L.cap);
			v = Math.max(0, Math.min(v, food));
			if (v > 0) {
				out.lines.push({ label: L.label, pence: v });
				out.pence += v;
			}
		}
		return out;
	}

	function discountRows(sub) {
		return discounts(sub).lines.map(function (l) {
			return '<div class="gm-sum gm-sum--tight gm-sum--discount"><span>' + esc(l.label) + '</span><span class="gm-tnum">−' + money(l.pence) + '</span></div>';
		}).join('');
	}

	/** "Add £3.20 more to get 10% off" (or what's already applied). */
	function offerNudge(sub) {
		var next = nextAuto(sub);
		var now = bestAuto(sub);
		if (next) {
			var pct = Math.max(4, Math.min(100, Math.round(sub / next.min * 100)));
			return '<div class="gm-nudge"><p>Add <strong>' + money(next.min - sub) + '</strong> more to get <strong>' + esc(next.offer) + '</strong> your order</p>' +
				'<span class="gm-nudge__bar"><i style="width:' + pct + '%"></i></span></div>';
		}
		if (now) return '<div class="gm-nudge gm-nudge--done"><p>✓ <strong>' + esc(now.rule.offer) + '</strong> applied — ' + esc(now.rule.label) + '</p></div>';
		return '';
	}

	function count() {
		return Object.keys(state.cart).reduce(function (n, id) { return n + state.cart[id]; }, 0);
	}

	function bump(id, delta) {
		var next = (state.cart[id] || 0) + delta;
		if (next <= 0) {
			delete state.cart[id];
			delete state.spice[id];
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

		var writeReview = hash === 'write-review';
		if (writeReview) view = 'reviews';
		if (hash === 'order' || hash === 'delivery') {
			scrollTo = hash;
			view = hash === 'order' ? 'menu' : 'how';
		}
		if (!view || view === 'top') view = 'home';
		if (ALIASES[view]) view = ALIASES[view];
		closeMenus();
		var AUTH_MODES = ['register', 'forgot', 'reset'];
		var authMode = AUTH_MODES.indexOf(view) !== -1 ? view : 'login';
		if (authMode === 'reset' && !resetLink) authMode = 'forgot';
		if (AUTH_MODES.indexOf(view) !== -1) view = 'login';
		if (view === 'account' && !C.loggedIn) view = 'login';
		if (view === 'login' && C.loggedIn) view = 'account';
		if (VIEWS.indexOf(view) === -1) return; // an ordinary in-page anchor
		if (view === 'pay' && (!state.slot || subtotal() < C.minOrder)) view = 'menu';
		if (view === 'done' && !state.order) view = 'home';

		show(view);
		if (view === 'login') {
			$$('[data-gm-auth]').forEach(function (el) { el.hidden = el.getAttribute('data-gm-auth') !== authMode; });
			$$('[data-gm-auth-error],[data-gm-auth-ok]').forEach(function (el) { el.hidden = true; });
		}
		if (writeReview) {
			openReviewForm();
			return;
		}
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
		// Highlight "Home" / "Menu" when one of their sub-pages is open.
		$$('.gm-nav__item--sub').forEach(function (li) {
			li.classList.toggle('is-current', !!li.querySelector('[aria-current="page"]'));
		});

		var titles = { home: '', menu: 'Menu', how: 'How it works', story: 'My story', reviews: 'Reviews', news: 'News', contact: 'Contact us', faq: 'FAQs', allergens: 'Allergens', login: 'Sign in', account: 'My account', pay: 'Checkout', done: 'Order confirmed' };
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
		$$('[data-gm-badge]').forEach(function (el) {
			el.textContent = n;
			el.hidden = !n;
		});
		$$('[data-gm-baskettoggle]').forEach(function (el) {
			el.setAttribute('aria-label', n ? 'Your order, ' + n + ' item' + (n === 1 ? '' : 's') : 'Your order, empty');
		});
		$$('[data-gm-qty]').forEach(function (el) {
			var id = el.getAttribute('data-gm-qty');
			var q = state.cart[id] || 0;
			el.textContent = q;
			var stepper = el.closest('.gm-stepper');
			if (stepper) {
				stepper.classList.toggle('is-active', q > 0);
				$('[data-gm-dec]', stepper).disabled = q === 0;
			}
		});
		renderBasket();
		renderMini();
		renderSlots();
	}

	/** Spice-level dropdown for dishes marked "spice to order". */
	function spiceSelect(id) {
		var it = items[id];
		if (!it.adjustable) return '';
		var standard = LEVELS[it.spice];
		var opts = '<option value="">Standard' + (standard ? ' (' + esc(standard.short) + ')' : '') + '</option>';
		Object.keys(LEVELS).forEach(function (key) {
			var l = LEVELS[key];
			var extra = l.extra_pence && !(standard && standard.extra_pence) ? ' (+' + money(l.extra_pence) + ')' : '';
			opts += '<option value="' + esc(key) + '"' + (state.spice[id] === key ? ' selected' : '') + '>' + esc(l.short) + extra + '</option>';
		});
		return '<label class="gm-line__spice"><span>Spice</span><select class="input" data-gm-spice="' + esc(id) + '" aria-label="Spice level for ' + esc(it.name) + '">' + opts + '</select></label>';
	}

	function stepper(id) {
		var it = items[id];
		return '<span class="gm-stepper is-active">' +
			'<button type="button" class="gm-stepper__btn" data-gm-dec="' + esc(id) + '" aria-label="Remove one ' + esc(it.name) + '">−</button>' +
			'<span class="gm-stepper__qty gm-tnum">' + state.cart[id] + '</span>' +
			'<button type="button" class="gm-stepper__btn" data-gm-inc="' + esc(id) + '" aria-label="Add one ' + esc(it.name) + '">+</button>' +
			'</span>';
	}

	var TRASH = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>';

	function lineHtml(id) {
		var it = items[id];
		return '<div class="gm-line" data-gm-line="' + esc(id) + '">' +
			'<div class="gm-line__swipe" aria-hidden="true">' + TRASH + ' Remove</div>' +
			'<div class="gm-line__content">' +
			'<div class="gm-line__row">' +
			'<span class="gm-line__name">' + esc(it.name) + '</span>' +
			'<span class="gm-line__total gm-tnum">' + money(unitPrice(id) * state.cart[id]) + '</span>' +
			'<button type="button" class="gm-trash" data-gm-remove="' + esc(id) + '" aria-label="Remove ' + esc(it.name) + ' from your order" title="Remove">' + TRASH + '</button>' +
			'</div>' +
			'<div class="gm-line__row gm-line__row--controls">' + spiceSelect(id) + stepper(id) + '</div>' +
			'</div></div>';
	}

	function removeItem(id) {
		delete state.cart[id];
		delete state.spice[id];
		save();
		renderCart();
	}

	function renderBasket() {
		var box = $('[data-gm-basket]');
		if (!box) return;
		var ids = Object.keys(state.cart);
		if (!ids.length) {
			box.innerHTML = reorderCard() + '<p class="gm-muted">Nothing in the basket yet. Add dishes from the menu above and they appear here.</p>';
			return;
		}
		var sub = subtotal();
		var d = discounts(sub);
		box.innerHTML = offerNudge(sub) + '<div class="gm-lines">' + ids.map(lineHtml).join('') + '</div>' +
			'<p class="gm-small gm-muted gm-swipe-hint">Tip: swipe an item left to remove it.</p>' +
			'<div class="gm-sum"><span class="gm-soft">Subtotal</span><span class="gm-tnum">' + money(sub) + '</span></div>' +
			discountRows(sub) +
			(d.pence ? '<div class="gm-sum gm-sum--tight"><span class="gm-soft">Food total</span><span class="gm-tnum">' + money(sub - d.pence) + '</span></div>' : '') +
			'<div class="gm-sum gm-sum--tight"><span class="gm-soft">Delivery</span><span class="gm-soft">From your postcode at checkout</span></div>' +
			'<p class="gm-small gm-muted" style="margin:4px 0 0">' + esc(C.deliveryRules) + '</p>';
	}

	/** The mini basket that drops down from "Your order" in the header. */
	function renderMini() {
		var ids = Object.keys(state.cart);
		var sub = subtotal();
		var html = ids.length
			? '<p class="gm-mini__title">Your order</p>' + offerNudge(sub) +
				'<div class="gm-mini__lines">' + ids.map(lineHtml).join('') + '</div>' +
				'<div class="gm-sum"><span>Subtotal</span><span class="gm-tnum">' + money(sub) + '</span></div>' +
				discountRows(sub) +
				(sub < C.minOrder ? '<p class="gm-small gm-warn" style="margin:6px 0 0">Add ' + money(C.minOrder - sub) + ' more to reach the ' + money(C.minOrder) + ' minimum.</p>' : '') +
				'<a class="gm-btn gm-btn--block gm-mini__go" href="#order" data-gm-close-mini>' + (sub < C.minOrder ? 'View your order' : 'Choose a delivery slot') + '</a>'
			: '<p class="gm-mini__title">Your basket is empty</p>' + (reorderCard() || '<p class="gm-small gm-muted">Add dishes from the menu and they will appear here.</p>') +
				'<a class="gm-btn gm-btn--block gm-mini__go" href="#menu" data-gm-close-mini>See the menu</a>';
		$$('[data-gm-mini]').forEach(function (el) { el.innerHTML = html; });
	}

	/* ------------------------------------------------------------ next delivery countdown */

	var countdownTimer = null;

	function nextDelivery() {
		var now = Date.now() / 1000;
		return (state.days || []).filter(function (d) { return d.open && d.deadline > now; })[0] || null;
	}

	function pad(n) { return (n < 10 ? '0' : '') + n; }

	function renderCountdown() {
		var boxes = $$('[data-gm-countdown]');
		if (!C.countdown || !boxes.length) return;
		var next = nextDelivery();
		clearInterval(countdownTimer);
		if (!next) {
			boxes.forEach(function (b) { b.hidden = true; });
			return;
		}
		var text = esc(C.countdown).replace('{next}', '<strong>' + esc(next.long) + '</strong>');
		boxes.forEach(function (b) {
			b.innerHTML = '<span class="gm-countdown__text">' + text + '</span> <span class="gm-countdown__timer gm-tnum" data-gm-timer aria-live="off"></span>' +
				'<a class="gm-countdown__cta" href="' + (b.hasAttribute('data-gm-countdown-order') ? '#order' : '#menu') + '">Order now</a>';
			b.hidden = false;
		});
		var tick = function () {
			var left = Math.max(0, Math.floor(next.deadline - Date.now() / 1000));
			if (!left) { clearInterval(countdownTimer); loadSlots(true); return; }
			var d = Math.floor(left / 86400), h = Math.floor(left % 86400 / 3600), m = Math.floor(left % 3600 / 60), sec = left % 60;
			var html = (d ? '<b>' + d + '</b><i>' + (d === 1 ? 'day' : 'days') + '</i>' : '') +
				'<b>' + pad(h) + '</b><i>hrs</i><b>' + pad(m) + '</b><i>min</i><b>' + pad(sec) + '</b><i>sec</i>';
			$$('[data-gm-timer]').forEach(function (t) { t.innerHTML = html; });
		};
		tick();
		countdownTimer = setInterval(tick, 1000);
	}

	/* ------------------------------------------------------------ "Do we deliver to you?" checker */

	var PC_KEY = 'gm_postcode';

	function checkerResult(form, cls, html) {
		var box = $('[data-gm-checker-result]', form);
		box.className = 'gm-checker__result gm-checker__result--' + cls;
		box.innerHTML = html;
		box.hidden = false;
	}

	document.addEventListener('submit', function (e) {
		var form = e.target.closest && e.target.closest('[data-gm-checker]');
		if (!form) return;
		e.preventDefault();
		var input = form.elements.postcode;
		var key = postcodeKey(input.value);
		if (!/^[A-Z]{1,2}[0-9][A-Z0-9]?[0-9][A-Z]{2}$/.test(key)) {
			checkerResult(form, 'bad', 'Please enter a full postcode, e.g. B69 1NY.');
			input.focus();
			return;
		}
		checkerResult(form, 'wait', 'Checking…');
		fetch(rest('delivery', 'postcode=' + encodeURIComponent(key)), { credentials: 'same-origin', cache: 'no-store' })
			.then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
			.then(function (res) {
				var b = res.body;
				if (res.ok && b.ok) {
					try { localStorage.setItem(PC_KEY, b.postcode); } catch (err) {}
					var inMenu = state.view === 'menu';
					checkerResult(form, 'ok', '<strong>✓ Good news — we deliver to ' + esc(b.postcode) + '!</strong> ' +
						esc(b.miles.toFixed(1)) + ' miles away · ' + (b.fee ? esc(money(b.fee)) + ' delivery' : 'free delivery') + '.' +
						(inMenu ? '' : ' <a class="gm-checker__go" href="#menu">Start your order →</a>'));
				} else if (res.ok) {
					var out = $('[data-gm-checker-out]', form);
					var extra = out ? out.innerHTML : '';
					if (C.phone && C.phone.label) extra = extra.split(esc(C.phone.label)).join('<a href="' + esc(C.phone.href) + '">' + esc(C.phone.label) + '</a>');
					checkerResult(form, 'out', '<strong>' + esc(b.message) + '</strong><br>' + extra);
				} else {
					checkerResult(form, 'bad', esc(b.message || 'We could not find that postcode — please check it.'));
				}
			})
			.catch(function () {
				checkerResult(form, 'bad', 'We could not check just now — please try again, or ring <a href="' + esc(C.phone.href) + '">' + esc(C.phone.label) + '</a>.');
			});
	});

	/* ------------------------------------------------------------ welcome back */

	var LAST_KEY = 'gm_last_order';

	/** Remember the latest order on this device, to welcome the customer back next time. */
	function rememberOrder(o, name) {
		if (!o || !o.lines) return;
		var cart = {};
		o.lines.forEach(function (l) { if (items[l.id]) cart[l.id] = l.qty; });
		try {
			localStorage.setItem(LAST_KEY, JSON.stringify({
				name: name || (C.user && C.user.name) || '',
				lines: o.lines.map(function (l) { return { id: l.id, qty: l.qty, name: l.name }; }),
				slot_date: o.slot_date || '', date: o.date || ''
			}));
		} catch (e) {}
	}

	/** The customer's latest order: from their account, or remembered on this device. */
	function lastOrder() {
		var last = null;
		if (C.user) last = (C.user.orders || [])[0] || null;
		else {
			try { last = JSON.parse(localStorage.getItem(LAST_KEY) || 'null'); } catch (e) {}
		}
		if (!last || !last.lines || !last.lines.length) return null;
		var cart = {};
		last.lines.forEach(function (l) { if (items[l.id]) cart[l.id] = (cart[l.id] || 0) + l.qty; });
		last.cart = Object.keys(cart).length ? cart : null;
		return last;
	}

	function reorderButton(last, label) {
		return last && last.cart ? '<button type="button" class="gm-welcome__btn" data-gm-reorder="' + esc(JSON.stringify(last.cart)) + '">' + esc(label) + '</button>' : '';
	}

	/** "Order again?" card for an empty basket. */
	function reorderCard() {
		var last = lastOrder();
		if (!last || !last.cart) return '';
		var what = last.lines.filter(function (l) { return items[l.id]; }).map(function (l) { return l.qty + ' × ' + l.name; }).join(', ');
		return '<div class="gm-reorder"><p><strong>Order again?</strong> Your last order: ' + esc(what) + '</p>' + reorderButton(last, 'Add it to my basket') + '</div>';
	}

	function dishText(lines) {
		var names = lines.slice().sort(function (a, b) { return b.qty - a.qty; }).map(function (l) { return l.name; });
		if (names.length <= 1) return names[0] || 'meal';
		if (names.length === 2) return names[0] + ' and ' + names[1];
		return names[0] + ', ' + names[1] + ' and the rest';
	}

	function todayIso() {
		var d = new Date();
		return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
	}

	function renderWelcome() {
		var boxes = $$('[data-gm-welcome]');
		if (!C.welcome || !boxes.length) return;
		var last = lastOrder();
		var name = C.user ? C.user.name : (last && last.name);
		if (!C.user && !last) return; // only greet people we know
		var first = String(name || '').trim().split(/\s+/)[0];
		if (!first) return;
		var tpl, reorder = '';
		if (!last) {
			tpl = C.welcome.first;
		} else {
			tpl = last.slot_date && last.slot_date >= todayIso() ? C.welcome.upcoming : C.welcome.back;
			reorder = reorderButton(last, 'Order it again');
		}
		if (!tpl) return;
		var msg = esc(tpl)
			.replace(/\{name\}/g, '<strong>' + esc(first) + '</strong>')
			.replace(/\{dish\}/g, last && last.lines ? esc(dishText(last.lines)) : 'meal')
			.replace(/\{date\}/g, last && last.date ? esc(last.date) : 'your delivery day');
		boxes.forEach(function (b) {
			b.innerHTML = '<span class="gm-welcome__icon" aria-hidden="true">👋</span><p>' + msg + '</p>' + reorder;
			b.hidden = false;
		});
	}

	/* ------------------------------------------------------------ slots */

	var slotsLoadedAt = 0;

	function loadSlots(force) {
		if (!force && state.days && Date.now() - slotsLoadedAt < 60000) return;
		slotsLoadedAt = Date.now();
		fetch(rest('slots', '_=' + Date.now()), { credentials: 'same-origin', cache: 'no-store' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				state.days = data.days || [];
				renderSlots();
				renderCountdown();
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
				return '<div class="gm-slot gm-slot--taken"><span class="gm-tnum">' + esc(s.label) + '</span><small>' + esc(s.note || 'Taken') + '</small></div>';
			}
			return '<button type="button" class="gm-slot" data-gm-slot="' + esc(s.time) + '"><span class="gm-tnum">' + esc(s.label) + '</span><small class="gm-tnum">' + esc(s.window) + '</small></button>';
		}).join('');

		box.innerHTML = '<div class="gm-days">' + tabs + '</div>' +
			'<p class="gm-small gm-muted gm-days__label">' + esc(active.long) + '</p>' +
			'<div class="gm-slots">' + slots + '</div>' +
			'<p class="gm-small gm-muted" style="margin-top:20px">Choosing a slot takes you to checkout. Orders must be placed by ' + esc(C.cutoffText) + '.</p>';
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
			var level = LEVELS[state.spice[id]];
			var note = level ? 'Spice: ' + level.short + (surcharge(id) ? ' (+' + money(surcharge(id)) + ' each)' : '') : '';
			return '<div class="gm-sumline"><span><span>' + state.cart[id] + ' × ' + esc(it.name) + '</span>' +
				(note ? '<small>' + esc(note) + '</small>' : '') +
				'</span><span class="gm-tnum">' + money(unitPrice(id) * state.cart[id]) + '</span></div>';
		}).join('') + '<div data-gm-totals></div>';

		// Pre-fill from the customer's last order when signed in.
		var saved = C.user && C.user.saved;
		if (saved) {
			[['address', 'address'], ['postcode', 'postcode'], ['phone', 'phone']].forEach(function (pair) {
				var input = $('[data-gm-pay] [name="' + pair[0] + '"]');
				if (input && !input.value && saved[pair[1]]) input.value = saved[pair[1]];
			});
		}
		$('[data-gm-error]').hidden = true;
		var postcode = $('[data-gm-pay] [name="postcode"]');
		if (!postcode.value.trim()) {
			try { postcode.value = localStorage.getItem(PC_KEY) || ''; } catch (e) {} // from the delivery checker
		}
		if (postcode.value.trim()) checkDelivery(postcode.value);
		else renderTotals();
	}

	/* ------------------------------------------------------------ delivery charge */

	var deliveryTimer = null;
	var deliveryFor = '';

	function postcodeKey(v) { return String(v || '').toUpperCase().replace(/[^A-Z0-9]/g, ''); }

	function checkDelivery(value) {
		var key = postcodeKey(value);
		clearTimeout(deliveryTimer);
		if (!/^[A-Z]{1,2}[0-9][A-Z0-9]?[0-9][A-Z]{2}$/.test(key)) {
			deliveryFor = '';
			state.delivery = { status: 'idle' };
			renderTotals();
			return;
		}
		if (key === deliveryFor && state.delivery.status !== 'error') {
			renderTotals();
			return;
		}
		deliveryFor = key;
		state.delivery = { status: 'loading' };
		renderTotals();
		deliveryTimer = setTimeout(function () {
			fetch(rest('delivery', 'postcode=' + encodeURIComponent(key)), { credentials: 'same-origin', cache: 'no-store' })
				.then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
				.then(function (res) {
					if (key !== deliveryFor) return; // the customer has typed something else since
					var b = res.body;
					if (res.ok) state.delivery = { status: b.ok ? 'ok' : 'out', fee: b.fee, miles: b.miles, message: b.message };
					else state.delivery = { status: b.code === 'gm_postcode_unknown' ? 'unknown' : 'error', message: b.message };
					renderTotals();
				})
				.catch(function () {
					if (key !== deliveryFor) return;
					state.delivery = { status: 'error' };
					renderTotals();
				});
		}, 350);
	}

	function orderTotal() {
		var sub = subtotal();
		return sub - discounts(sub).pence + deliveryFee();
	}

	/* ------------------------------------------------------------ discount code at checkout */

	function renderCodeMsg(text, bad) {
		var el = $('[data-gm-code-msg]');
		if (!el) return;
		if (text === undefined) {
			var problem = state.code ? discounts(subtotal()).codeProblem : '';
			text = problem || (state.code ? '✓ Code ' + state.code.code + ' applied — ' + state.code.offer : '');
			bad = !!problem;
		}
		el.textContent = text;
		el.hidden = !text;
		el.className = 'gm-small ' + (bad ? 'gm-warn' : 'gm-ok');
	}

	function applyCode() {
		var input = $('#gm-discount');
		var code = input.value.trim().toUpperCase();
		if (!code) {
			state.code = null;
			renderCodeMsg('');
			renderTotals();
			return;
		}
		renderCodeMsg('Checking…', false);
		fetch(rest('discount', 'code=' + encodeURIComponent(code)), { credentials: 'same-origin', cache: 'no-store' })
			.then(function (r) { return r.json().then(function (b) { return { ok: r.ok, body: b }; }); })
			.then(function (res) {
				if (!res.ok) {
					state.code = null;
					renderTotals();
					renderCodeMsg(res.body.message || 'That code isn’t valid.', true);
					return;
				}
				state.code = res.body;
				renderTotals();
			})
			.catch(function () { renderCodeMsg('Could not check the code — please try again.', true); });
	}

	function deliveryFee() {
		return state.delivery.status === 'ok' ? state.delivery.fee : 0;
	}

	function renderTotals() {
		var box = $('[data-gm-totals]');
		if (!box) return;
		var d = state.delivery;
		var sub = subtotal();
		var cell = {
			idle: '<span class="gm-soft">Enter your postcode</span>',
			loading: '<span class="gm-soft">Checking…</span>',
			ok: d.fee ? money(d.fee) : 'Free',
			out: '<span class="gm-warn">Outside our area</span>',
			unknown: '<span class="gm-warn">Postcode not found</span>',
			error: '<span class="gm-soft">Confirmed by the kitchen</span>'
		}[d.status];
		var disc = discounts(sub);
		box.innerHTML =
			'<div class="gm-sum"><span class="gm-soft">Subtotal</span><span class="gm-tnum">' + money(sub) + '</span></div>' +
			discountRows(sub) +
			'<div class="gm-sum gm-sum--tight"><span class="gm-soft">Delivery' + (d.status === 'ok' ? ' · ' + d.miles.toFixed(1) + ' miles' : '') + '</span><span class="gm-tnum">' + cell + '</span></div>' +
			'<div class="gm-sum gm-sum--total"><span>Total</span><span class="gm-tnum">' + money(orderTotal()) + '</span></div>' +
			(disc.note ? '<p class="gm-small gm-muted" style="margin:8px 0 0">' + esc(disc.note) + '</p>' : '');
		renderCodeMsg();

		var msg = $('[data-gm-postcode-msg]');
		var text = {
			idle: C.deliveryRules,
			loading: 'Working out your delivery charge…',
			ok: d.message,
			out: d.message,
			unknown: d.message || 'We could not find that postcode — please check it.',
			error: 'We could not check the distance just now. You can still order — the kitchen will confirm any delivery charge.'
		}[d.status];
		msg.textContent = text || '';
		msg.className = 'gm-small ' + (d.status === 'out' || d.status === 'unknown' ? 'gm-warn' : 'gm-muted');
		updatePayButton();
	}

	function updatePayButton() {
		var total = orderTotal();
		var overCash = C.cashLimit > 0 && total > C.cashLimit;
		var cod = $('[data-gm-pay] input[type="radio"][value="cod"]');
		var cardRadio = $('[data-gm-pay] input[type="radio"][value="card"]');
		if (cod) {
			// Orders over the cash limit must be paid online.
			cod.disabled = overCash;
			cod.closest('label').classList.toggle('is-disabled', overCash);
			$('[data-gm-cod-note]').textContent = overCash
				? 'Not available for orders over ' + money(C.cashLimit)
				: (C.cashLimit ? 'Orders up to ' + money(C.cashLimit) : '');
			if (overCash && cod.checked && cardRadio) cardRadio.checked = true;
		}
		var card = payMethod() === 'card';
		var button = $('[data-gm-submit]');
		var blockedArea = state.delivery.status === 'out' || state.delivery.status === 'unknown';
		var blockedCash = !card && overCash; // only cash is offered, and the order is over the limit
		button.disabled = blockedArea || blockedCash || state.busy;
		button.textContent = blockedArea ? 'Check your postcode'
			: blockedCash ? 'Card payment needed'
			: (card ? 'Pay ' : 'Place order · ') + money(total);
		$('[data-gm-pay-note]').innerHTML = blockedCash
			? 'Orders over ' + money(C.cashLimit) + ' need to be paid by card. Please ring us on <a href="' + esc(C.phone.href) + '">' + esc(C.phone.label) + '</a> to place this order.'
			: card
				? 'You will be taken to Stripe’s secure page to pay by card, Apple Pay or Google Pay — card details never touch this site. Your slot is held while you pay. Discount codes are checked when we confirm your order.'
				: 'Pay cash when your food arrives. Discount codes are checked when we confirm your order.';
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

		var payload = { items: state.cart, spice: state.spice, date: state.slot.date, time: state.slot.time, payment: payMethod() };
		payload.use_loyalty = !!(C.loyalty && C.loyalty.ready && state.useLoyalty);
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

		fetch(rest('orders'), { method: 'POST', credentials: 'same-origin', headers: headers, body: JSON.stringify(payload) })
			.then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
			.then(function (res) {
				if (!res.ok) throw res.body;
				if (res.body.redirect) {
					window.location.href = res.body.redirect;
					return;
				}
				state.order = res.body.order;
				rememberOrder(state.order, payload.name);
				if (payload.use_loyalty) {
					C.loyalty.ready = false; // spent
					$$('[data-gm-loyalty-use]').forEach(function (el) { el.hidden = true; });
				}
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
		state.spice = {};
		state.code = null;
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
		var invite = $('[data-gm-done-review]');
		if (invite) invite.hidden = processing;
	}

	/* ------------------------------------------------------------ events */

	document.addEventListener('click', function (e) {
		var t = e.target.closest('[data-gm-inc],[data-gm-dec],[data-gm-remove],[data-gm-apply-code],[data-gm-day],[data-gm-slot],[data-gm-reorder],[data-gm-restart],[data-gm-release]');
		if (!t) return;
		if (t.hasAttribute('data-gm-remove')) {
			removeItem(t.getAttribute('data-gm-remove'));
		} else if (t.hasAttribute('data-gm-apply-code')) {
			applyCode();
		} else if (t.hasAttribute('data-gm-inc')) {
			bump(t.getAttribute('data-gm-inc'), 1);
			notice('');
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
			state.spice = {};
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
		if (e.target.name === 'postcode' && e.target.closest('[data-gm-pay]')) checkDelivery(e.target.value);
		if (e.target.id === 'gm-discount' && state.code && e.target.value.trim().toUpperCase() !== state.code.code) {
			state.code = null; // edited after applying: needs applying again
			renderTotals();
		}
	});

	document.addEventListener('change', function (e) {
		if (e.target.name === 'payment') updatePayButton();
		if (e.target.hasAttribute && e.target.hasAttribute('data-gm-use-loyalty')) {
			state.useLoyalty = e.target.checked;
			renderTotals();
			renderCart();
		}
		var spiceFor = e.target.getAttribute && e.target.getAttribute('data-gm-spice');
		if (spiceFor) {
			if (e.target.value) state.spice[spiceFor] = e.target.value;
			else delete state.spice[spiceFor];
			save();
			renderCart();
		}
	});

	/* ------------------------------------------------------------ header: dropdowns, phone menu, mini basket */

	var miniTimer = null;

	function setMini(wrap, open) {
		clearTimeout(miniTimer);
		$$('[data-gm-basketwrap]').forEach(function (w) {
			var on = open && w === wrap;
			$('[data-gm-mini]', w).hidden = !on;
			w.classList.toggle('is-open', on);
			$('[data-gm-baskettoggle]', w).setAttribute('aria-expanded', on ? 'true' : 'false');
		});
	}

	function closeMenus() {
		setMini(null, false);
		$$('[data-gm-nav]').forEach(function (nav) {
			nav.classList.remove('is-open');
			$('[data-gm-navtoggle]', nav).setAttribute('aria-expanded', 'false');
		});
		$$('.gm-nav__item--sub').forEach(function (li) {
			li.classList.remove('is-open');
			$('[data-gm-subtoggle]', li).setAttribute('aria-expanded', 'false');
		});
	}

	document.addEventListener('click', function (e) {
		var navToggle = e.target.closest('[data-gm-navtoggle]');
		if (navToggle) {
			var nav = navToggle.closest('[data-gm-nav]');
			var open = !nav.classList.contains('is-open');
			closeMenus();
			nav.classList.toggle('is-open', open);
			navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			return;
		}
		var sub = e.target.closest('[data-gm-subtoggle]');
		if (sub) {
			var li = sub.closest('.gm-nav__item--sub');
			var isOpen = !li.classList.contains('is-open');
			li.classList.toggle('is-open', isOpen);
			sub.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
			return;
		}
		var basket = e.target.closest('[data-gm-baskettoggle]');
		if (basket && !CAN_HOVER) {
			// Touch screens: tap opens the mini basket rather than leaving the page.
			e.preventDefault();
			var wrap = basket.closest('[data-gm-basketwrap]');
			setMini(wrap, !wrap.classList.contains('is-open'));
			return;
		}
		if (e.target.closest('[data-gm-close-mini]')) {
			closeMenus();
			return;
		}
		// Click outside the header menus closes them. (A tap on + / − inside the
		// mini basket re-draws it, detaching the target — that is not "outside".)
		if (e.target.isConnected && !e.target.closest('[data-gm-nav]')) closeMenus();
	});

	$$('[data-gm-basketwrap]').forEach(function (wrap) {
		if (!CAN_HOVER) return;
		wrap.addEventListener('mouseenter', function () { setMini(wrap, true); });
		wrap.addEventListener('mouseleave', function () {
			clearTimeout(miniTimer);
			miniTimer = setTimeout(function () { setMini(null, false); }, 300);
		});
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') closeMenus();
	});

	/* ------------------------------------------------------------ reviews & contact */

	function openReviewForm() {
		var box = $('[data-gm-review-box]');
		if (!box) return;
		box.hidden = false;
		$('[data-gm-review-cta]').hidden = true;
		box.scrollIntoView({ block: 'start' });
		var name = $('#gm-rv-name');
		if (C.user && !name.value) name.value = C.user.name || '';
		var email = $('#gm-rv-email');
		if (C.user && !email.value) email.value = C.user.email || '';
		name.focus({ preventScroll: true });
	}

	var openReview = $('[data-gm-open-review]');
	if (openReview) openReview.addEventListener('click', openReviewForm);

	/* Reviews slideshow: rotates every 7s, pauses on hover/touch, swipe on phones. */
	$$('[data-gm-slides]').forEach(function (box) {
		var slides = $$('[data-gm-slide]', box);
		var dots = $$('[data-gm-slide-dot]', box);
		if (slides.length < 2) return;
		var n = 0, timer = null, paused = false;
		function showSlide(i) {
			n = (i + slides.length) % slides.length;
			slides.forEach(function (s, k) { s.hidden = k !== n; });
			dots.forEach(function (d, k) { if (k === n) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current'); });
		}
		function restart() {
			clearInterval(timer);
			timer = setInterval(function () { if (!paused && !box.closest('[hidden]')) showSlide(n + 1); }, 7000);
		}
		box.addEventListener('click', function (e) {
			if (e.target.closest('[data-gm-slide-prev]')) showSlide(n - 1);
			else if (e.target.closest('[data-gm-slide-next]')) showSlide(n + 1);
			else if (e.target.closest('[data-gm-slide-dot]')) showSlide(+e.target.closest('[data-gm-slide-dot]').getAttribute('data-gm-slide-dot'));
			else return;
			restart();
		});
		box.addEventListener('mouseenter', function () { paused = true; });
		box.addEventListener('mouseleave', function () { paused = false; });
		var x0 = null;
		box.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; paused = true; }, { passive: true });
		box.addEventListener('touchend', function (e) {
			paused = false;
			if (x0 === null) return;
			var dx = e.changedTouches[0].clientX - x0;
			x0 = null;
			if (Math.abs(dx) > 40) { showSlide(dx < 0 ? n + 1 : n - 1); restart(); }
		}, { passive: true });
		restart();
	});

	/* ------------------------------------------------------------ sign in / create account */

	/** POST to admin-ajax.php; resolves with `data`, rejects with { message }. */
	function ajax(action, fields) {
		var body = new URLSearchParams();
		body.append('action', action);
		Object.keys(fields || {}).forEach(function (k) { body.append(k, fields[k]); });
		return fetch(C.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json().catch(function () { return {}; }); })
			.then(function (res) {
				if (!res.success) throw (res.data && res.data.message ? res.data : { message: 'Something went wrong — please try again.' });
				return res.data || {};
			});
	}

	/** Reload so the page is rebuilt for the signed-in customer. */
	function openAccount() {
		history.replaceState(null, '', location.pathname + '#account');
		location.reload();
	}

	function authForm(form, action, busyLabel, done) {
		if (!form) return;
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var errorBox = $('[data-gm-auth-error]', form);
			var okBox = $('[data-gm-auth-ok]', form);
			var button = $('button[type="submit"]', form);
			var missing = $$('input[required],textarea[required]', form).filter(function (i) { return !i.value.trim() || !i.checkValidity(); })[0];
			if (okBox) okBox.hidden = true;
			if (missing) {
				errorBox.textContent = missing.type === 'password' && missing.value
					? 'Please choose a password of at least 8 characters.'
					: missing.type === 'email' && missing.value
						? 'Please enter a valid email address.'
						: 'Please enter your ' + missing.labels[0].textContent.toLowerCase() + '.';
				errorBox.hidden = false;
				missing.focus();
				return;
			}
			var fields = {};
			$$('input[name],textarea[name],select[name]', form).forEach(function (input) {
				if (input.type === 'checkbox' || input.type === 'radio') { if (input.checked) fields[input.name] = input.value; }
				else fields[input.name] = input.value;
			});
			if (action === 'gm_reset_password') {
				fields.key = resetLink.key;
				fields.login = resetLink.login;
			}
			var label = button.textContent;
			button.disabled = true;
			button.textContent = busyLabel;
			errorBox.hidden = true;
			ajax(action, fields)
				.then(function (data) {
					button.disabled = false;
					button.textContent = label;
					done(data, form);
				})
				.catch(function (err) {
					errorBox.textContent = err.message;
					errorBox.hidden = false;
					button.disabled = false;
					button.textContent = label;
				});
		});
	}

	authForm($('[data-gm-login]'), 'gm_login', 'Signing in…', openAccount);
	authForm($('[data-gm-register]'), 'gm_register', 'Creating your account…', openAccount);
	authForm($('[data-gm-reset]'), 'gm_reset_password', 'Saving…', openAccount);
	function showThanks(data, form) {
		var okBox = $('[data-gm-auth-ok]', form);
		okBox.textContent = data.message;
		okBox.hidden = false;
	}
	authForm($('[data-gm-forgot]'), 'gm_lost_password', 'Sending…', showThanks);
	authForm($('[data-gm-contact]'), 'gm_contact', 'Sending…', function (data, form) {
		form.reset();
		showThanks(data, form);
	});
	authForm($('[data-gm-review]'), 'gm_review', 'Sending…', function (data, form) {
		form.reset();
		showThanks(data, form);
		$('button[type="submit"]', form).hidden = true;
	});

	document.addEventListener('click', function (e) {
		var link = e.target.closest('[data-gm-logout]');
		if (!link) return;
		e.preventDefault();
		ajax('gm_logout').then(function () {
			location.replace(location.pathname);
		}).catch(function () { location.href = link.href; });
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' && e.target.id === 'gm-discount') {
			e.preventDefault();
			applyCode();
		}
	});

	/* ------------------------------------------------------------ swipe left to remove (touch screens) */

	(function () {
		var line = null, startX = 0, startY = 0, dx = 0, dragging = false;
		document.addEventListener('touchstart', function (e) {
			var l = e.target.closest('[data-gm-line]');
			if (!l || e.target.closest('select,input')) return; // a plain tap on a button still works as a tap
			line = l; startX = e.touches[0].clientX; startY = e.touches[0].clientY; dx = 0; dragging = false;
		}, { passive: true });
		document.addEventListener('touchmove', function (e) {
			if (!line) return;
			var x = e.touches[0].clientX - startX, y = e.touches[0].clientY - startY;
			if (!dragging) {
				if (Math.abs(y) > 10 && Math.abs(y) > Math.abs(x)) { line = null; return; } // scrolling, not swiping
				if (Math.abs(x) < 10) return;
				dragging = true;
				line.classList.add('is-swiping');
			}
			dx = Math.min(0, x);
			$('.gm-line__content', line).style.transform = 'translateX(' + dx + 'px)';
			line.classList.toggle('is-armed', dx < -90);
		}, { passive: true });
		document.addEventListener('touchend', function () {
			if (!line) return;
			var l = line; line = null;
			var content = $('.gm-line__content', l);
			l.classList.remove('is-swiping');
			if (dragging && dx < -90) {
				content.style.transform = 'translateX(-110%)';
				l.classList.add('is-removing');
				setTimeout(function () { removeItem(l.getAttribute('data-gm-line')); }, 220);
			} else {
				content.style.transform = '';
				l.classList.remove('is-armed');
			}
		});
	})();

	var payForm = $('[data-gm-pay]');
	if (payForm) payForm.addEventListener('submit', submitOrder);

	window.addEventListener('hashchange', route);

	/* ------------------------------------------------------------ start */

	load();

	// Arrived from a password-reset email: keep the key in memory, tidy the URL.
	var params = new URLSearchParams(location.search);
	if (params.get('gm_reset') && params.get('login')) {
		resetLink = { key: params.get('gm_reset'), login: params.get('login') };
		history.replaceState(null, '', location.pathname + '#reset');
	}

	var startNotice = '';
	if (C.returned) {
		// Back from Stripe: tidy the URL, then show the outcome.
		history.replaceState(null, '', location.pathname);
		if (C.returned.order) {
			state.order = C.returned.order;
			rememberOrder(state.order, '');
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
	renderWelcome();
	if (C.countdown) loadSlots();
})();
