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
			storage: false,
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
			const activeClass = this.options.activeClass || 'is-active';
			const willBeActive = !this.target.classList.contains(activeClass);

			this.applyState(willBeActive);
			this.saveStorage(willBeActive);

			if (this.target && typeof this.target.blur === 'function')
			{
				this.target.blur();
			}

			XF.trigger(this.target, 'cv6-toggle:complete', { active: willBeActive });
		},

		hide (instant = false)
		{
			this.applyState(false);
			this.saveStorage(false);

			if (this.target && typeof this.target.blur === 'function')
			{
				this.target.blur();
			}

			XF.trigger(this.target, 'cv6-toggle:complete', { active: false });
		},

		show (instant = false)
		{
			this.applyState(true);
			this.saveStorage(true);

			if (this.target && typeof this.target.blur === 'function')
			{
				this.target.blur();
			}

			XF.trigger(this.target, 'cv6-toggle:complete', { active: true });
		},

		isVisible ()
		{
			const activeClass = this.options.activeClass || 'is-active';
			return this.target.classList.contains(activeClass);
		},

		isTransitioning ()
		{
			return false;
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

		applyState (isActive)
		{
			const activeClass = this.options.activeClass || 'is-active';

			this.target.classList.toggle(activeClass, isActive);

			if (this.toggleParent)
			{
				this.toggleParent.classList.toggle(activeClass, isActive);
			}

			const targets = this.getToggleTargets();
			targets.forEach(target =>
			{
				target.classList.toggle(activeClass, isActive);
			});

			this.updateAria(isActive);

			if (typeof XF.layoutChange === 'function')
			{
				XF.layoutChange();
			}
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

			if (this.options.storage)
			{
				const target = this.options.target || (this.target.id ? '#' + this.target.id : null);
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
				this.applyState(storedValue);
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

		getStoredState (key)
		{
			if (!key)
			{
				return null;
			}

			const container = this.options.storageContainer || 'toggle';
			const storageType = this.options.storageType || 'local';

			if (typeof XF.ToggleStorageData !== 'undefined')
			{
				const storage = XF.ToggleStorageData.getInstance(storageType);
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

			if (typeof XF.LocalStorage !== 'undefined')
			{
				const data = XF.LocalStorage.getJson(container);
				if (data && typeof data === 'object' && Object.prototype.hasOwnProperty.call(data, key))
				{
					const item = data[key];
					if (Array.isArray(item))
					{
						return Boolean(item[1]);
					}
					return Boolean(item);
				}
			}

			try
			{
				const val = window.localStorage.getItem('xf_' + container + '_' + key);
				if (val !== null)
				{
					return val === '1' || val === 'true';
				}
			}
			catch (e)
			{
				// ignore
			}

			return null;
		},

		setStoredState (key, isActive)
		{
			if (!key)
			{
				return;
			}

			const container = this.options.storageContainer || 'toggle';
			const storageType = this.options.storageType || 'local';
			const expiry = parseInt(this.options.storageExpiry, 10) || 86400 * 30;

			if (typeof XF.ToggleStorageData !== 'undefined')
			{
				const storage = XF.ToggleStorageData.getInstance(storageType);
				if (storage)
				{
					storage.set(container, key, isActive, expiry);
					return;
				}
			}

			if (typeof XF.LocalStorage !== 'undefined')
			{
				const data = XF.LocalStorage.getJson(container) || {};
				const timestamp = Math.floor(Date.now() / 1000);
				data[key] = [timestamp, isActive, expiry];
				XF.LocalStorage.setJson(container, data);
				return;
			}

			try
			{
				window.localStorage.setItem('xf_' + container + '_' + key, isActive ? '1' : '0');
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
		const selector = '[data-xf-click~="cv6-multi-toggle"][data-storage-key], ' +
			'[data-xf-click~="cv6-bundle-toggle"][data-storage-key], ' +
			'[data-xf-click~="cv6-multi-toggle"][data-storage], ' +
			'[data-xf-click~="cv6-bundle-toggle"][data-storage]';

		const elements = root.querySelectorAll(selector);
		elements.forEach(el =>
		{
			XF.Event.initElement(el, 'click');
		});
	};

	if (document.readyState === 'loading')
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

})(window, document);
