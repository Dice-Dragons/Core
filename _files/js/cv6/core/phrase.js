((window, document) => {
    "use strict";

    XF.cv6PhraseQuickSave = XF.Element.newHandler({
        init: function () {
            XF.on(this.target, 'ajax-submit:response', this.handleResponse.bind(this));
        },

        handleResponse: function (e) {
            const data = e.data;
            if (data && data.targetId && data.masterText !== undefined) {
                const el = document.getElementById(data.targetId) 
                    || document.querySelector('[data-phrase-target="' + data.targetId + '"]');
                if (el) {
                    el.value = data.masterText;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }
    });

    XF.Element.register('cv6-phrase-quick-save', 'XF.cv6PhraseQuickSave');
})(window, document);
