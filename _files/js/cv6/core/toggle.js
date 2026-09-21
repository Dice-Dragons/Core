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
			hide: null,
			activeClass: 'is-active',
			activateParent: null,
			scrollTo: null,
			storageType: 'local',
			storageContainer: 'toggle',
			storageKey: null,
			storageExpiry: 86400 * 30, // 30 days
			storage: false,
		}),

		toggleTarget: null,
		toggleParent: null,
		toggleUrl: null,
		ajaxLoaded: false,
		loading: false,
		storage: null,

		init ()
		{
			// Resolve multi-targets (supports relative, container, and global selectors)
			this.toggleTarget = this.getToggleTargets();

			if (this.options.activateParent && this.target.parentNode)
			{
				this.toggleParent = this.target.parentNode;
			}

			this.toggleUrl = this.getToggleUrl();

			this.updateAria();

			// Restore state from persistent storage if configured
			this.initStorage();
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
				this.hide();
			}
			else
			{
				this.show();
			}

			if (this.target && typeof this.target.blur === 'function')
			{
				this.target.blur();
			}
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

		getToggleUrl ()
		{
			if (!this.toggleTarget || !this.toggleTarget.length)
			{
				return null;
			}
			const toggleTarget = this.toggleTarget[0];
			const url = toggleTarget ? toggleTarget.dataset.href : null;
			if (toggleTarget && url)
			{
				return url === 'trigger-href' ? this.target.getAttribute('href') : url;
			}
			return null;
		},

		getContainer ()
		{
			if (this.options.container)
			{
				const container = this.target.closest(this.options.container);
				if (!container)
				{
					console.error('Container parent not found: ' + this.options.container);
					return null;
				}
				return container;
			}
			return null;
		},

		isVisible ()
		{
			if (this.toggleTarget && this.toggleTarget.length)
			{
				return this.toggleTarget[0].classList.contains(this.options.activeClass);
			}
			return this.target.classList.contains(this.options.activeClass);
		},

		isTransitioning ()
		{
			if (!this.toggleTarget || !this.toggleTarget.length)
			{
				return false;
			}
			return this.toggleTarget.some(target => target.classList.contains('is-transitioning'));
		},

		isTableRow (el)
		{
			return Boolean(el && (
				el.tagName === 'TR' ||
				el.classList.contains('dataList-row') ||
				(el.parentElement && el.parentElement.tagName === 'TBODY')
			));
		},

		hide (instant = false)
		{
			if (!this.isVisible() || this.isTransitioning())
			{
				return;
			}

			const activeClass = this.options.activeClass;

			if (this.toggleParent)
			{
				XF.Transition.removeClassTransitioned(this.toggleParent, activeClass, this.inactiveTransitionComplete, instant);
			}

			if (this.toggleTarget && this.toggleTarget.length)
			{
				this.toggleTarget.forEach(target =>
				{
					const isInstant = instant || this.isTableRow(target);
					XF.Transition.removeClassTransitioned(target, activeClass, this.inactiveTransitionComplete, isInstant);
				});
			}

			XF.Transition.removeClassTransitioned(this.target, activeClass, this.inactiveTransitionComplete, instant);

			this.updateAria(false);
			this.saveStorage(false);

			if (typeof XF.layoutChange === 'function')
			{
				XF.layoutChange();
			}

			XF.trigger(this.target, 'cv6-toggle:complete', { active: false });
		},

		show (instant = false)
		{
			if (this.isVisible() || this.isTransitioning())
			{
				return;
			}

			if (Array.from(this.getOtherToggles()).filter(el => el.classList.contains('is-transitioning')).length)
			{
				return;
			}

			if (this.toggleUrl && !this.ajaxLoaded)
			{
				this.load();
				return;
			}

			this.closeOthers();

			const activeClass = this.options.activeClass;

			if (this.toggleParent)
			{
				XF.Transition.addClassTransitioned(this.toggleParent, activeClass, this.activeTransitionComplete, instant);
			}

			if (this.toggleTarget && this.toggleTarget.length)
			{
				this.toggleTarget.forEach(target =>
				{
					const isInstant = instant || this.isTableRow(target);
					XF.Transition.addClassTransitioned(target, activeClass, this.activeTransitionComplete, isInstant);
				});
			}

			XF.Transition.addClassTransitioned(this.target, activeClass, this.activeTransitionComplete, instant);

			this.hideSpecified();
			this.scrollTo();

			if (this.toggleTarget && this.toggleTarget.length)
			{
				XF.autoFocusWithin(this.toggleTarget[0], '[autofocus], [data-toggle-autofocus]');
			}

			this.updateAria(true);
			this.saveStorage(true);

			if (typeof XF.layoutChange === 'function')
			{
				XF.layoutChange();
			}

			XF.trigger(this.target, 'cv6-toggle:complete', { active: true });
		},

		activeTransitionComplete (e)
		{
			if (e && e.currentTarget)
			{
				XF.trigger(e.currentTarget, 'toggle:shown');
				if (typeof XF.layoutChange === 'function')
				{
					XF.layoutChange();
				}
			}
		},

		inactiveTransitionComplete (e)
		{
			if (e && e.currentTarget)
			{
				XF.trigger(e.currentTarget, 'toggle:hidden');
				if (typeof XF.layoutChange === 'function')
				{
					XF.layoutChange();
				}
			}
		},

		hideSpecified ()
		{
			if (this.options.hide)
			{
				const hide = document.querySelector(this.options.hide);
				if (hide && typeof XF.display === 'function')
				{
					XF.display(hide, 'none');
				}
			}
		},

		scrollTo ()
		{
			if (this.options.scrollTo && this.toggleTarget && this.toggleTarget.length && typeof XF.smoothScroll === 'function')
			{
				const toggleTarget = this.toggleTarget[0];
				const topOffset = toggleTarget.getBoundingClientRect().top + window.scrollY,
					height = toggleTarget.offsetHeight,
					windowHeight = document.documentElement.clientHeight;

				let offset;
				if (height < windowHeight)
				{
					offset = topOffset - ((windowHeight / 2) - (height / 2));
				}
				else
				{
					offset = topOffset;
				}

				XF.smoothScroll(offset);
			}
		},

		load ()
		{
			const href = this.toggleUrl;
			if (!href || this.loading)
			{
				return;
			}

			this.loading = true;

			XF.ajax('get', href, data =>
			{
				if (data.html && typeof XF.setupHtmlInsert === 'function')
				{
					XF.setupHtmlInsert(data.html, (html, container, onComplete) =>
					{
						const loadSelector = this.toggleTarget[0].dataset.loadSelector;
						if (loadSelector)
						{
							const newHtml = html.querySelector(loadSelector);
							if (newHtml)
							{
								html = newHtml;
							}
						}

						this.ajaxLoaded = true;
						this.toggleTarget.forEach(target =>
						{
							target.append(html.cloneNode(true));
						});
						XF.activate(html);

						onComplete(true);

						this.show();

						return false;
					});
				}
			}).finally(() =>
			{
				this.ajaxLoaded = true;
				this.loading = false;
			});
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

			const container = this.options.storageContainer || 'toggle';
			const storageType = this.options.storageType || 'local';

			if (typeof XF.ToggleStorageData !== 'undefined')
			{
				this.storage = XF.ToggleStorageData.getInstance(storageType);
			}

			if (!this.storage)
			{
				return;
			}

			const storedValue = this.storage.get(container, key, {
				allowExpired: false,
				touch: false,
			});

			if (storedValue !== null)
			{
				this.applyState(Boolean(storedValue), true);
			}

			this.storage.prune(container);
		},

		saveStorage (isActive)
		{
			const key = this.getStorageKey();
			if (!key || !this.storage)
			{
				return;
			}

			if (typeof isActive === 'undefined')
			{
				isActive = this.isVisible();
			}

			const container = this.options.storageContainer || 'toggle';
			const expiry = parseInt(this.options.storageExpiry, 10) || 86400 * 30;

			this.storage.set(container, key, isActive, expiry);
		},

		applyState (isActive, instant = true)
		{
			const activeClass = this.options.activeClass || 'is-active';

			this.target.classList.toggle(activeClass, isActive);

			if (this.target.hasAttribute('aria-expanded') || this.options.storageKey)
			{
				this.target.setAttribute('aria-expanded', isActive ? 'true' : 'false');
			}

			if (this.toggleParent)
			{
				this.toggleParent.classList.toggle(activeClass, isActive);
			}

			if (this.toggleTarget && this.toggleTarget.length)
			{
				this.toggleTarget.forEach(target =>
				{
					target.classList.toggle(activeClass, isActive);
				});
			}

			if (typeof XF.layoutChange === 'function')
			{
				XF.layoutChange();
			}
		},

		closeOthers ()
		{
			this.getOtherToggles().forEach(toggle =>
			{
				let handlers = XF.DataStore.get(toggle, 'xf-click-handlers');

				if (!handlers)
				{
					handlers = XF.Event.initElement(toggle, 'click');
				}

				if (handlers)
				{
					const handler = handlers['cv6-multi-toggle'] || handlers['cv6-bundle-toggle'] || handlers.toggle;
					if (handler && typeof handler.hide === 'function')
					{
						handler.hide(true);
					}
				}
			});
		},

		getOtherToggles ()
		{
			const container = this.getContainer();
			if (container)
			{
				return Array.from(container.querySelectorAll(
					'[data-xf-click~="cv6-multi-toggle"], [data-xf-click~="cv6-bundle-toggle"], [data-xf-click~="toggle"]'
				)).filter(element => element !== this.target);
			}
			return [];
		},
	});

	// Register event handlers for click
	XF.Event.register('click', 'cv6-multi-toggle', XF.cv6MultiToggle);
	XF.Event.register('click', 'cv6-bundle-toggle', XF.cv6MultiToggle);

	// Backwards compatibility alias on XF object
	XF.cv6BundleToggle = XF.cv6MultiToggle;

	// Element handler for explicit data-xf-init="cv6-multi-toggle"
	XF.cv6MultiToggleElement = XF.Element.newHandler({
		_onEvent ()
		{
			return true;
		},
		init ()
		{
			XF.Event.initElement(this.target, 'click');
		},
	});
	XF.Element.register('cv6-multi-toggle', 'XF.cv6MultiToggleElement');
	XF.Element.register('cv6-bundle-toggle', 'XF.cv6MultiToggleElement');

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

	if (document.readyState === 'complete')
	{
		initStoredToggles(document);
	}
	else
	{
		XF.on(document, 'xf:page-load-complete', () =>
		{
			initStoredToggles(document);
		});
	}

	XF.on(document, 'xf:reinit', e =>
	{
		initStoredToggles(e.element || document);
	});

})(window, document);
