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
			this.target.style.backgroundImage = 'url(' + this.target.value + ')';
			XF.on(this.target, 'blur', this.blur);
			XF.on(this.target, 'focus', this.focus);
			XF.on(this.target, 'onchange', this.change);

		},

		focus: function (e) {
			this.classList.add('cv6-noimg');
			this.style.backgroundImage = '';			
			this.oldval = this.value;
		},

		blur: function (e) {
			this.classList.remove('cv6-noimg');
			this.style.backgroundImage = 'url(' + this.value + ')';
		},

		change: function(e) {
			// hm
			this.style.backgroundImage = 'url(' + this.value + ')'; 
		}

	});

	XF.Element.register('cv6AssetImage', 'XF.cv6AssetImage');

})(window, document)