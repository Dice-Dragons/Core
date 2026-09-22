((window, document) => 
{
	"use strict";

	const hasToggleClick = typeof XF.ToggleClick !== 'undefined';
	const baseClass = hasToggleClick ? XF.ToggleClick : XF.Event.AbstractHandler;

	XF.cv6MultiToggle = XF.extend(baseClass, {
		eventNameSpace: 'cv6MultiToggle',

		options: Object.assign({}, (hasToggleClick && XF.ToggleClick.prototype && XF.ToggleClick.prototype.options) ? XF.ToggleClick.prototype.options : {}, {
			target: null,
			container: null,
			activeClass: 'is-active',
			activateParent: null,
			storageType: 'local',
			storageContainer: 'toggle',
			storageKey: null,
			storageExpiry: 86400 * 30, // 30 days
			storage: true,
		}),

		toggleTarget: null,
		toggleParent: null,

		init ()
		{
			this.toggleTarget = this.getToggleTargets();

			if (this.options.activateParent && this.target.parentNode)
			{
				this.toggleParent = this.target.parentNode;
			}

			this.initStorage();
			this.updateAria();
		},

		click (e)
		{
			e.preventDefault();
			this.toggle();
		},

		toggle ()
		{
			if (this.isVisible())
			{
				this.hide(false);
			}
			else
			{
				this.show(false);
			}
		},

		hide (instant = false)
		{
			if (!this.isVisible() || (!instant && this.isTransitioning()))
			{
				return;
			}

			const activeClass = this.options.activeClass || 'is-active';
			const speed = (XF.config && XF.config.speed) ? XF.config.speed.fast : 200;

			this.target.classList.remove(activeClass);

			if (this.toggleParent)
			{
				this.toggleParent.classList.remove(activeClass);
			}

			this.updateAria(false);
			this.saveStorage(false);

			if (this.target && typeof this.target.blur === 'function')
			{
				this.target.blur();
			}

			const targets = this.getToggleTargets();

			if (instant || typeof XF.Animate === 'undefined' || typeof XF.Animate.animate !== 'function')
			{
				targets.forEach(target =>
				{
					target.classList.remove(activeClass);
					if (target.style && typeof target.style.removeProperty === 'function')
					{
						target.style.removeProperty('display');
					}
				});

				if (typeof XF.layoutChange === 'function')
				{
					XF.layoutChange();
				}

				XF.trigger(this.target, 'cv6-toggle:complete', { active: false });
			}
			else
			{
				let remaining = targets.length;
				if (!remaining)
				{
					XF.trigger(this.target, 'cv6-toggle:complete', { active: false });
					return;
				}

				targets.forEach(target =>
				{
					this.slideUpTarget(target, speed, () =>
					{
						remaining--;
						if (remaining <= 0)
						{
							if (typeof XF.layoutChange === 'function')
							{
								XF.layoutChange();
							}
							XF.trigger(this.target, 'cv6-toggle:complete', { active: false });
						}
					});
				});
			}
		},

		show (instant = false)
		{
			if (this.isVisible() || (!instant && this.isTransitioning()))
			{
				return;
			}

			const activeClass = this.options.activeClass || 'is-active';
			const speed = (XF.config && XF.config.speed) ? XF.config.speed.fast : 200;

			this.target.classList.add(activeClass);

			if (this.toggleParent)
			{
				this.toggleParent.classList.add(activeClass);
			}

			this.updateAria(true);
			this.saveStorage(true);

			if (this.target && typeof this.target.blur === 'function')
			{
				this.target.blur();
			}

			const targets = this.getToggleTargets();

			if (instant || typeof XF.Animate === 'undefined' || typeof XF.Animate.animate !== 'function')
			{
				targets.forEach(target =>
				{
					target.classList.add(activeClass);
					if (target.style && typeof target.style.removeProperty === 'function')
					{
						target.style.removeProperty('display');
					}
				});

				if (typeof XF.layoutChange === 'function')
				{
					XF.layoutChange();
				}

				XF.trigger(this.target, 'cv6-toggle:complete', { active: true });
			}
			else
			{
				let remaining = targets.length;
				if (!remaining)
				{
					XF.trigger(this.target, 'cv6-toggle:complete', { active: true });
					return;
				}

				targets.forEach(target =>
				{
					this.slideDownTarget(target, speed, () =>
					{
						remaining--;
						if (remaining <= 0)
						{
							if (typeof XF.layoutChange === 'function')
							{
								XF.layoutChange();
							}
							XF.trigger(this.target, 'cv6-toggle:complete', { active: true });
						}
					});
				});
			}
		},

		slideUpTarget (target, speed, onComplete)
		{
			const activeClass = this.options.activeClass || 'is-active';
			target.classList.add('is-transitioning');

			if (target.tagName && target.tagName.toLowerCase() === 'tr')
			{
				const cells = Array.from(target.querySelectorAll('td, th'));
				if (!cells.length)
				{
					target.classList.remove(activeClass);
					target.classList.remove('is-transitioning');
					target.style.display = 'none';
					onComplete();
					return;
				}

				const originalStyles = cells.map(td => td.style.cssText);
				const paddings = cells.map(td => ({
					top: parseFloat(window.getComputedStyle(td).paddingTop) || 0,
					bottom: parseFloat(window.getComputedStyle(td).paddingBottom) || 0
				}));

				const wrappers = cells.map(td =>
				{
					const w = document.createElement('div');
					w.className = 'cv6-tr-slide-wrapper';
					w.style.overflow = 'hidden';
					while (td.firstChild)
					{
						w.appendChild(td.firstChild);
					}
					td.appendChild(w);
					return w;
				});

				const heights = wrappers.map(w => w.offsetHeight);

				XF.Animate.animate(target, {
					speed: speed,
					step: (el, { delta }) =>
					{
						const factor = 1 - delta;
						wrappers.forEach((w, i) =>
						{
							w.style.height = (heights[i] * factor) + 'px';
							cells[i].style.paddingTop = (paddings[i].top * factor) + 'px';
							cells[i].style.paddingBottom = (paddings[i].bottom * factor) + 'px';
						});
					},
					finish: () =>
					{
						target.classList.remove(activeClass);
						target.style.display = 'none';
						wrappers.forEach((w, i) =>
						{
							const td = cells[i];
							while (w.firstChild)
							{
								td.appendChild(w.firstChild);
							}
							w.remove();
							td.style.cssText = originalStyles[i];
						});
						target.classList.remove('is-transitioning');
					},
					complete: onComplete
				});
			}
			else
			{
				XF.Animate.slideUp(target, {
					speed: speed,
					complete: () =>
					{
						target.classList.remove(activeClass);
						target.classList.remove('is-transitioning');
						if (target.style && typeof target.style.removeProperty === 'function')
						{
							target.style.removeProperty('display');
						}
						onComplete();
					}
				});
			}
		},

		slideDownTarget (target, speed, onComplete)
		{
			const activeClass = this.options.activeClass || 'is-active';
			target.classList.add('is-transitioning');

			if (target.tagName && target.tagName.toLowerCase() === 'tr')
			{
				const cells = Array.from(target.querySelectorAll('td, th'));
				if (!cells.length)
				{
					target.classList.add(activeClass);
					target.classList.remove('is-transitioning');
					target.style.removeProperty('display');
					onComplete();
					return;
				}

				const originalStyles = cells.map(td => td.style.cssText);

				target.style.display = 'table-row';
				target.classList.add(activeClass);

				const paddings = cells.map(td => ({
					top: parseFloat(window.getComputedStyle(td).paddingTop) || 0,
					bottom: parseFloat(window.getComputedStyle(td).paddingBottom) || 0
				}));

				const wrappers = cells.map(td =>
				{
					const w = document.createElement('div');
					w.className = 'cv6-tr-slide-wrapper';
					w.style.overflow = 'hidden';
					while (td.firstChild)
					{
						w.appendChild(td.firstChild);
					}
					td.appendChild(w);
					return w;
				});

				const heights = wrappers.map(w => w.offsetHeight);

				wrappers.forEach((w, i) =>
				{
					w.style.height = '0px';
					cells[i].style.paddingTop = '0px';
					cells[i].style.paddingBottom = '0px';
				});

				XF.Animate.animate(target, {
					speed: speed,
					step: (el, { delta }) =>
					{
						wrappers.forEach((w, i) =>
						{
							w.style.height = (heights[i] * delta) + 'px';
							cells[i].style.paddingTop = (paddings[i].top * delta) + 'px';
							cells[i].style.paddingBottom = (paddings[i].bottom * delta) + 'px';
						});
					},
					finish: () =>
					{
						wrappers.forEach((w, i) =>
						{
							const td = cells[i];
							while (w.firstChild)
							{
								td.appendChild(w.firstChild);
							}
							w.remove();
							td.style.cssText = originalStyles[i];
						});
						target.style.removeProperty('display');
						target.classList.remove('is-transitioning');
					},
					complete: onComplete
				});
			}
			else
			{
				target.style.display = 'none';
				target.classList.add(activeClass);

				XF.Animate.slideDown(target, {
					speed: speed,
					complete: () =>
					{
						target.classList.remove('is-transitioning');
						if (target.style && typeof target.style.removeProperty === 'function')
						{
							target.style.removeProperty('display');
						}
						onComplete();
					}
				});
			}
		},

		applyState (isActive, instant = true)
		{
			const activeClass = this.options.activeClass || 'is-active';
			const targets = this.getToggleTargets();

			if (isActive)
			{
				this.target.classList.add(activeClass);
				if (this.toggleParent)
				{
					this.toggleParent.classList.add(activeClass);
				}
				targets.forEach(target =>
				{
					target.classList.add(activeClass);
					if (target.style && typeof target.style.removeProperty === 'function')
					{
						target.style.removeProperty('display');
					}
				});
				this.updateAria(true);
			}
			else
			{
				this.target.classList.remove(activeClass);
				if (this.toggleParent)
				{
					this.toggleParent.classList.remove(activeClass);
				}
				targets.forEach(target =>
				{
					target.classList.remove(activeClass);
					target.style.display = 'none';
				});
				this.updateAria(false);
			}

			if (typeof XF.layoutChange === 'function')
			{
				XF.layoutChange();
			}
		},

		isVisible ()
		{
			const activeClass = this.options.activeClass || 'is-active';
			return this.target.classList.contains(activeClass);
		},

		isTransitioning ()
		{
			const targets = this.getToggleTargets();
			return targets.some(target => target.classList.contains('is-transitioning'));
		},

		getToggleTargets ()
		{
			const targetSelector = this.options.target;
			if (!targetSelector)
			{
				return this.target.nextElementSibling ? [this.target.nextElementSibling] : [];
			}

			let targets = [];

			if (targetSelector.match(/^[<>|]/) && typeof XF.findRelativeIf === 'function')
			{
				targets = XF.findRelativeIf(targetSelector, this.target, false);
			}
			else if (this.options.container)
			{
				const container = this.getContainer();
				if (container)
				{
					targets = container.querySelectorAll(targetSelector);
				}
			}
			else
			{
				targets = document.querySelectorAll(targetSelector);
			}

			return typeof XF.toElementArray === 'function' ? XF.toElementArray(targets) : Array.from(targets || []);
		},

		getContainer ()
		{
			if (this.options.container)
			{
				const container = this.target.closest(this.options.container);
				if (!container)
				{
					return document.querySelector(this.options.container);
				}
				return container;
			}
			return null;
		},

		updateAria (isActive)
		{
			if (typeof isActive === 'undefined')
			{
				isActive = this.isVisible();
			}
			if (this.target.hasAttribute('aria-expanded') || this.options.storageKey)
			{
				this.target.setAttribute('aria-expanded', isActive ? 'true' : 'false');
			}
		},

		getStorageKey ()
		{
			if (this.options.storageKey)
			{
				return this.options.storageKey;
			}

			if (this.target.dataset && this.target.dataset.storageKey)
			{
				return this.target.dataset.storageKey;
			}

			if (this.options.storage !== false && (!this.target.dataset || this.target.dataset.storage !== 'false'))
			{
				const target = this.options.target || (this.target.dataset ? this.target.dataset.target : null) || (this.target.id ? '#' + this.target.id : null);
				if (target)
				{
					return 'cv6-toggle:' + target.replace(/[^a-zA-Z0-9_-]/g, '_');
				}
			}

			return null;
		},

		initStorage ()
		{
			const key = this.getStorageKey();
			if (!key)
			{
				return;
			}

			const storedValue = this.getStoredState(key);
			if (storedValue !== null)
			{
				this.applyState(storedValue, true);
			}
		},

		saveStorage (isActive)
		{
			const key = this.getStorageKey();
			if (!key)
			{
				return;
			}

			if (typeof isActive === 'undefined')
			{
				isActive = this.isVisible();
			}

			this.setStoredState(key, isActive);
		},

		getStorageContainer ()
		{
			if (this.options.storageContainer)
			{
				return this.options.storageContainer;
			}
			if (this.target && this.target.dataset && this.target.dataset.storageContainer)
			{
				return this.target.dataset.storageContainer;
			}
			return 'toggle';
		},

		getStoredState (key)
		{
			if (!key)
			{
				return null;
			}

			const container = this.getStorageContainer();
			const prefix = (XF.config && XF.config.cookie && XF.config.cookie.prefix) || (document.documentElement && document.documentElement.getAttribute('data-cookie-prefix')) || '';
			const storageKey = prefix + container;

			try
			{
				const raw = window.localStorage.getItem(storageKey);
				if (raw)
				{
					const parsed = JSON.parse(raw);
					if (parsed && typeof parsed === 'object' && Object.prototype.hasOwnProperty.call(parsed, key))
					{
						const item = parsed[key];
						if (Array.isArray(item))
						{
							return Boolean(item.length >= 3 ? item[2] : item[1]);
						}
						return Boolean(item);
					}
				}
			}
			catch (e)
			{
				// fallback
			}

			if (typeof XF.ToggleStorageData !== 'undefined' && XF.config && XF.config.cookie)
			{
				try
				{
					const storage = XF.ToggleStorageData.getInstance(this.options.storageType || 'local');
					if (storage)
					{
						const val = storage.get(container, key, {
							allowExpired: false,
							touch: false,
						});
						if (val !== null)
						{
							return Boolean(val);
						}
					}
				}
				catch (e)
				{
					// ignore
				}
			}

			return null;
		},

		setStoredState (key, isActive)
		{
			if (!key)
			{
				return;
			}

			const container = this.getStorageContainer();
			const expiry = parseInt(this.options.storageExpiry, 10) || 86400 * 30;
			const timestamp = Math.floor(Date.now() / 1000);
			const prefix = (XF.config && XF.config.cookie && XF.config.cookie.prefix) || (document.documentElement && document.documentElement.getAttribute('data-cookie-prefix')) || '';
			const storageKey = prefix + container;

			try
			{
				// Read current stored data freshly and directly from localStorage
				const raw = window.localStorage.getItem(storageKey);
				let data = {};
				if (raw)
				{
					try
					{
						data = JSON.parse(raw) || {};
					}
					catch (e)
					{
						data = {};
					}
				}

				// Update or add the single key while keeping all other keys untouched
				data[key] = [timestamp, expiry, isActive];

				// Write back complete merged dataset
				window.localStorage.setItem(storageKey, JSON.stringify(data));

				// Keep XF.ToggleStorageData in-memory cache synchronized if available
				if (typeof XF.ToggleStorageData !== 'undefined')
				{
					const storage = XF.ToggleStorageData.getInstance(this.options.storageType || 'local');
					if (storage && storage.dataCache)
					{
						storage.dataCache[container] = Object.assign({}, data);
					}
				}
			}
			catch (e)
			{
				// ignore
			}
		},
	});

	// Register event handlers for click
	XF.Event.register('click', 'cv6-multi-toggle', 'XF.cv6MultiToggle');
	XF.Event.register('click', 'cv6-bundle-toggle', 'XF.cv6MultiToggle');

	// Backwards compatibility alias on XF object
	XF.cv6BundleToggle = XF.cv6MultiToggle;

	// Auto-initialize persistent toggles on page load (so stored state is restored without requiring a click)
	const initStoredToggles = (root = document) =>
	{
		const selector = '[data-xf-click~="cv6-multi-toggle"]:not([data-storage="false"]), ' +
			'[data-xf-click~="cv6-bundle-toggle"]:not([data-storage="false"])';

		const elements = root.querySelectorAll(selector);
		elements.forEach(el =>
		{
			const handlers = XF.Event.initElement(el, 'click');
			const handler = handlers ? (handlers['cv6-multi-toggle'] || handlers['cv6-bundle-toggle']) : null;
			if (handler && typeof handler.initStorage === 'function')
			{
				handler.initStorage();
			}
		});
	};

	// Run immediately if DOM body is present to apply stored state before the browser paints (prevents FOUC)
	if (document.body)
	{
		initStoredToggles(document);
	}

	// Initialize when XF is ready (guarantees XF.config and DOM are ready)
	if (typeof XF.ready === 'function')
	{
		XF.ready(() => initStoredToggles(document));
	}
	else if (document.readyState === 'loading')
	{
		document.addEventListener('DOMContentLoaded', () => initStoredToggles(document));
	}
	else
	{
		initStoredToggles(document);
	}

	XF.on(document, 'xf:page-load-complete', () =>
	{
		initStoredToggles(document);
	});

	XF.on(document, 'xf:reinit', e =>
	{
		initStoredToggles(e.element || document);
	});

	if (document.readyState === 'complete' || (typeof XF.isReady !== 'undefined' && XF.isReady))
	{
		initStoredToggles(document);
	}

})(window, document);
