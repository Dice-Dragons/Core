((window, document) => 
{
	'use strict'

	XF.AssetUpload = XF.extend(XF.AssetUpload, {
		__backup: {
			"ajaxResponse": "_cv6AfterAjaxResponseCore",
			"init": "_cv6InitCore"
		},

		preview6: null,

		init: function() {
			this.preview6 = this.target.querySelector('.cv6--asset-imagepreview');
			this._cv6InitCore();
		},

		ajaxResponse: function (data) {
			this._cv6AfterAjaxResponseCore(data);
			if (data.path) {
				this.preview6.style.backgroundImage = "url("+data.path+")";
			}
			else
			{
				this.preview6.style.backgroundImage = "";
			}
		}
	});

	XF.cv6AssetImage = XF.Element.newHandler({

		oldval: null,

		init: function () {
			this.updatePreview();

			XF.on(this.target, 'focus', () => {
				this.target.classList.add('cv6-noimg');
				this.target.style.backgroundImage = '';
				this.oldval = this.target.value;
			});

			XF.on(this.target, 'blur', () => {
				this.updatePreview();
			});

			XF.on(this.target, 'input', () => {
				this.updatePreview();
			});

			XF.on(this.target, 'change', () => {
				this.updatePreview();
			});

			const resetBtn = this.getResetButton();
			if (resetBtn && !resetBtn.hasAttribute('data-xf-click')) {
				XF.on(resetBtn, 'click', (e) => {
					e.preventDefault();
					this.reset();
				});
			}
		},

		getResetButton: function () {
			const container = this.target.closest('.inputGroup--asset') || this.target.closest('.formRow');
			return container ? container.querySelector('.js-cv6AssetReset') : null;
		},

		updatePreview: function (val) {
			const value = typeof val !== 'undefined' ? val : this.target.value;
			if (value && typeof value === 'string' && value.trim() !== '') {
				this.target.classList.remove('cv6-noimg');
				this.target.style.backgroundImage = 'url("' + value.trim().replace(/"/g, '\\"') + '")';
			} else {
				this.target.classList.remove('cv6-noimg');
				this.target.style.backgroundImage = '';
			}
		},

		reset: function () {
			const resetBtn = this.getResetButton();
			const defaultVal = (resetBtn && typeof resetBtn.dataset.default !== 'undefined')
				? resetBtn.dataset.default
				: (this.target.dataset.default || '');

			this.target.value = defaultVal;
			this.updatePreview(defaultVal);

			if (typeof XF.trigger === 'function') {
				XF.trigger(this.target, 'change');
				XF.trigger(this.target, 'input');
			} else {
				this.target.dispatchEvent(new Event('change', { bubbles: true }));
				this.target.dispatchEvent(new Event('input', { bubbles: true }));
			}
		}

	});

	XF.cv6AssetReset = XF.Event.newHandler({
		eventNameSpace: 'cv6AssetReset',

		click: function (e) {
			e.preventDefault();
			const container = this.target.closest('.inputGroup--asset') || this.target.closest('.formRow');
			if (!container) return;
			const input = container.querySelector('.cv6--asset-imagepreview, .js-assetPath');
			if (!input) return;

			const handler = XF.Element.getHandler(input, 'cv6AssetImage');
			if (handler && typeof handler.reset === 'function') {
				handler.reset();
			} else {
				const defaultVal = this.target.dataset.default || input.dataset.default || '';
				input.value = defaultVal;
				if (defaultVal && defaultVal.trim() !== '') {
					input.classList.remove('cv6-noimg');
					input.style.backgroundImage = 'url("' + defaultVal.trim().replace(/"/g, '\\"') + '")';
				} else {
					input.classList.remove('cv6-noimg');
					input.style.backgroundImage = '';
				}
				if (typeof XF.trigger === 'function') {
					XF.trigger(input, 'change');
					XF.trigger(input, 'input');
				} else {
					input.dispatchEvent(new Event('change', { bubbles: true }));
					input.dispatchEvent(new Event('input', { bubbles: true }));
				}
			}
		}
	});

	XF.Event.register('click', 'cv6-asset-reset', 'XF.cv6AssetReset');

	XF.Element.register('cv6AssetImage', 'XF.cv6AssetImage');

})(window, document)