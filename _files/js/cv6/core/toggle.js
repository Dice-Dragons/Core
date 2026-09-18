((window, document) => 
{
	"use strict";

	XF.cv6MultiToggle = XF.Event.newHandler({
		eventNameSpace: 'cv6MultiToggle',
		options: {
			target: null,
			container: null,
			activeClass: 'is-active',
			activateParent: null
		},

		init: function () {},

		click: function (e) {
			e.preventDefault();

			var targetSelector = this.options.target;
			if (!targetSelector) {
				return;
			}

			var activeClass = this.options.activeClass || 'is-active';

			this.target.classList.toggle(activeClass);
			var isActive = this.target.classList.contains(activeClass);

			if (this.target.hasAttribute('aria-expanded')) {
				this.target.setAttribute('aria-expanded', isActive ? 'true' : 'false');
			}

			if (this.options.activateParent && this.target.parentElement) {
				this.target.parentElement.classList.toggle(activeClass, isActive);
			}

			var targets;
			if (targetSelector.match(/^[<>|]/) && typeof XF.findRelativeIf === 'function') {
				targets = XF.findRelativeIf(targetSelector, this.target, false);
			} else if (this.options.container) {
				var container = document.querySelector(this.options.container);
				targets = container ? container.querySelectorAll(targetSelector) : [];
			} else {
				targets = document.querySelectorAll(targetSelector);
			}

			if (targets) {
				var targetList = Array.from(targets);
				targetList.forEach(function (el) {
					el.classList.toggle(activeClass, isActive);
				});
			}

			if (typeof XF.layoutChange === 'function') {
				XF.layoutChange();
			}

			if (typeof this.target.blur === 'function') {
				this.target.blur();
			}

			XF.trigger(this.target, 'cv6-toggle:complete', { active: isActive });
		}
	});

	// Register generic multi-toggle and alias bundle-toggle for backwards compatibility
	XF.Event.register('click', 'cv6-multi-toggle', 'XF.cv6MultiToggle');
	XF.Event.register('click', 'cv6-bundle-toggle', 'XF.cv6MultiToggle');

	// Backwards compatibility alias on XF object
	XF.cv6BundleToggle = XF.cv6MultiToggle;

})(window, document);
