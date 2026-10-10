((window, document) => 
{
    "use strict";

    class cv6Icon {

        constructor(name, variant='default') {
            this.setName( name );
            this.variant = variant;
        }

        setClassType(classType, value) {
            if (value == '_default' || value == null) {
                value = '';
            }
            switch (classType) {
                case 'animation':
                    this.setAnimation(value);
                    break;
                case 'rotation':
                    this.setRotation(value);
                    break;
                case 'variant':
                    this.setVariant(value);
                    break;
                case 'size':
                    this.setSize(value);
                    break;
                default:
                    break;
            }
        }

        setAnimation(animation) {
            this.animation = animation;
        }

        setRotation(rotation) {
            this.rotation = rotation;
        }

        setVariant(variant = "default") {
            // this.newIcon.variant = XF.Icon.normalizeIconVariant(variant);
            this.variant = variant;
        }

        setSize(size) {
            this.size = size;
        }

        setName(name) {
            this.name = XF.Icon.normalizeIconName(name);
        }

        setTarget(target) {
            this.target = target;
        }

        getIcon() {
            XF.Icon.getIcon(this.variant, this.name, 'fa-pulse cv6-loading ' + this.target)
        }
    }

    XF.cv6ChangeIcon = XF.Element.newHandler({
        eventNameSpace: 'cv6ChangeIcon',
        options: {
            target: null,
            type: null,
            styleField: null,
            showError: false,
            provider: null,
            prefix: null,
            phraseUnknownIcon: null,
            phraseUnknownClass: null
        },

        oldIconClass: null,
        loadingIcon: null,
        errorIcon: null,
        noIcon: null,
        iconText: null,
        layoutMenu: null,
        targetClass: null,
        errorText: null,
        styleField: null,
        inputTimer: null,
        currentValue: '',
        activeProvider: 'fa',
        providerButtons: [],
        providerPicker: null,
        providerTrigger: null,

        newIcon: {
            variant: 'default',
            name: '',
            classes: '',
            animation: '',
            rotation: '',
            size: '',
            additional: '',
        },

        iconList: [],

        container: null,

        init: function () 
        {
            XF.Icon.getIcon('solid', 'fa-comments')

			if (!this.options.target) {
			    console.error('Element must have a data-target value');
			    return;
			}
            if (this.options.styleField) {
                this.styleField = document.getElementById(this.options.styleField);
                if (!this.styleField) {
                    console.error('Element data-style-field does not point to a valid input with the id ' + this.options.styleField);
                    return;
                }
            } else {
                this.styleField = false;
            }
            
            if (this.options.showError == 1 || this.options.showError == true) {
                const scope = this.target.closest('.formRow') || this.target.closest('.inputGroup') || document;
                this.errorText = scope.querySelector('.cv6-iconerror') || document.querySelector('.cv6-iconerror');
            }
            else {
                this.errorText = XF.createElementFromString('<div class="cv6-iconerror"></div>');
            }

            this.iconText = this.target;
            this.targetClass = this.options.target.replace('.', '');
          
            this.loadingIcon = XF.createElementFromString(
                XF.Icon.getIcon('default', 'fa-spinner', 'fa-pulse cv6-loading ' + this.targetClass + ' cv6-iconpreview')
            );
            this.errorIcon = XF.createElementFromString(
                XF.Icon.getIcon('default', 'fa-bug', 'cv6-error-icon ' + this.targetClass + ' cv6-iconpreview')
            );
            this.noIcon = XF.createElementFromString(
                XF.Icon.getIcon('default', 'fa-times-octagon', 'cv6-no-icon ' + this.targetClass + ' cv6-iconpreview')
            );

            this.oldIconClass = this.target.value;
            this.currentValue = this.target.value;
            
            XF.on(this.target, 'blur', this.blur6.bind(this));
            XF.on(this.target, 'focus', this.focus6.bind(this));
            XF.on(this.target, 'input', this.handleInput.bind(this));
            XF.on(this.target, 'change', this.handleChange.bind(this));

            this.inputGroup = this.target.closest('.inputGroup') || this.target.parentElement;
            this.layoutMenu = this.inputGroup ? this.inputGroup.querySelectorAll('.cv6-tabPanes .menu-linkRow') : [];

            this.layoutMenu.forEach(linkRow => {
                XF.on(linkRow, 'click', this.click6.bind(this));
            });            

            this.initProvider();
            this.analyzeIconText(true, false);
        },

        initProvider: function () {
            const explicitProvider = this.options.provider || this.target.getAttribute('data-provider');
            if (explicitProvider) {
                this.setActiveProvider(explicitProvider, false);
            }

            if (!this.inputGroup) return;

            this.providerPicker = this.inputGroup.querySelector('.js-cv6-provider-picker');
            if (!this.providerPicker) {
                if (!explicitProvider) {
                    const val = this.target.value.trim();
                    if (val.indexOf('gm-') !== -1 || ['gmo', 'gmr', 'gms', 'gmf'].some(s => val.indexOf(s) !== -1)) {
                        this.setActiveProvider('google', false);
                    } else if (val.indexOf('gi-') !== -1 || ['gio'].some(s => val.indexOf(s) !== -1)) {
                        this.setActiveProvider('game', false);
                    }
                }
                return;
            }

            this.providerTrigger = this.providerPicker.querySelector('.js-cv6-provider-trigger');
            this.providerButtons = this.providerPicker.querySelectorAll('.js-cv6-provider-btn');

            this.providerButtons.forEach(btn => {
                XF.on(btn, 'click', this.clickProvider.bind(this));
                const icon = btn.querySelector('.cv6-provider-menu-icon');
                const color = btn.getAttribute('data-brand-color');
                if (icon && color) {
                    icon.style.color = color;
                }
            });

            if (!explicitProvider) {
                const val = this.target.value.trim();
                let detected = 'fa';

                for (const btn of this.providerButtons) {
                    const prefix = btn.getAttribute('data-prefix');
                    const id = btn.getAttribute('data-provider');
                    if (prefix && prefix !== 'fa-' && val.indexOf(prefix) !== -1) {
                        detected = id;
                        break;
                    }
                }

                this.setActiveProvider(detected, false);
            }
        },

        clickProvider: function (e) {
            e.preventDefault();
            const btn = e.target.closest('.js-cv6-provider-btn');
            if (!btn) return;

            const newProvider = btn.getAttribute('data-provider');
            if (newProvider === this.activeProvider) return;

            this.setActiveProvider(newProvider, true);

            if (this.providerTrigger) {
                const menu = XF.ElementData.get(this.providerTrigger, 'menu');
                if (menu && typeof menu.close === 'function') {
                    menu.close();
                }
            }
        },

        setActiveProvider: function (providerId, userInitiated = false) {
            this.activeProvider = providerId;
            this.target.setAttribute('data-active-provider', providerId);

            let activeBtn = null;
            if (this.providerButtons && this.providerButtons.length) {
                this.providerButtons.forEach(btn => {
                    const id = btn.getAttribute('data-provider');
                    const color = btn.getAttribute('data-brand-color');
                    if (id === providerId) {
                        btn.classList.add('is-active');
                        if (color) btn.style.color = color;
                        activeBtn = btn;
                    } else {
                        btn.classList.remove('is-active');
                        btn.style.color = '';
                    }
                });
            }

            const prefix = this.getPrefix();
            this.target.setAttribute('placeholder', prefix + '...');

            // Update single-badge current indicator icon & brand color
            const currentBadge = this.inputGroup ? this.inputGroup.querySelector('.js-cv6-provider-current') : null;
            if (currentBadge && activeBtn) {
                const iconSpan = activeBtn.querySelector('.cv6-provider-menu-icon');
                if (iconSpan) {
                    currentBadge.innerHTML = iconSpan.innerHTML;
                }
                const brandColor = activeBtn.getAttribute('data-brand-color');
                currentBadge.style.color = brandColor || '';
            }

            if (this.inputGroup) {
                const styleRows = this.inputGroup.querySelectorAll('.js-cv6-style-row[data-provider]');
                styleRows.forEach(row => {
                    const rowProvider = row.getAttribute('data-provider');
                    row.style.display = (rowProvider === providerId) ? '' : 'none';
                });
            }

            if (userInitiated) {
                const currentVal = this.target.value.trim();
                let keptClasses = [];
                if (currentVal !== '') {
                    const ICON_CLASS_ANIMATION = /^fa-(spin|pulse)$/i;
                    const ICON_CLASS_ROTATION = /^fa-(rotate-(90|180|270)|flip-(horizontal|vertical))$/i;
                    const parts = currentVal.split(/\s+/);
                    keptClasses = parts.filter(c => c.match(ICON_CLASS_ANIMATION) || c.match(ICON_CLASS_ROTATION));
                }
                this.target.value = keptClasses.length ? keptClasses.join(' ') + ' ' : '';
                this.updatePreview(false);
                this.target.focus();
            }
        },

        getPrefix: function () {
            const explicitPrefix = this.options.prefix || this.target.getAttribute('data-prefix');
            if (explicitPrefix) return explicitPrefix;

            if (this.providerButtons && this.providerButtons.length) {
                for (const btn of this.providerButtons) {
                    if (btn.getAttribute('data-provider') === this.activeProvider) {
                        return btn.getAttribute('data-prefix') || 'fa-';
                    }
                }
            }
            const known = { 'fa': 'fa-', 'google': 'gm-', 'game': 'gi-' };
            if (known[this.activeProvider]) return known[this.activeProvider];
            return this.activeProvider === 'fa' ? 'fa-' : this.activeProvider + '-';
        },

        getPreview: function () {
            let el = null;
            if (this.inputGroup) {
                el = this.inputGroup.querySelector(this.options.target) || this.inputGroup.querySelector('.cv6-iconpreview');
            }
            if (!el) {
                const row = this.target.closest('.formRow');
                if (row) {
                    el = row.querySelector(this.options.target) || row.querySelector('.cv6-iconpreview');
                }
            }
            if (!el) {
                el = document.querySelector(this.options.target) || document.querySelector('.cv6-iconpreview');
            }
            return el;
        },

        hideError: function () {
            if (this.errorText) {
                if (window.getComputedStyle(this.errorText).display !== 'none') {
                    XF.Animate.fadeUp(this.errorText, { speed: XF.config.speed.fast });
                }
                this.errorText.classList.add('is-hidden');
            }
        },

        handleInput: function () {
            this.hideError();
            clearTimeout(this.inputTimer);
            this.inputTimer = setTimeout(() => {
                if (this.target.value === this.currentValue) {
                    return;
                }
                this.currentValue = this.target.value;
                this.updatePreview(false);
            }, 250);
        },

        handleChange: function () {
            clearTimeout(this.inputTimer);
            if (this.target.value !== this.currentValue) {
                this.currentValue = this.target.value;
                this.updatePreview(false);
            }
        },

        getPhrase: function (type) {
            if (type === 'unknownIcon') {
                return this.options.phraseUnknownIcon 
                    || XF.phrase('cv6UnknownIcon', null, 'Unknown Font Awesome icon');
            }
            if (type === 'unknownClass') {
                return this.options.phraseUnknownClass 
                    || XF.phrase('cv6UnknownClass', null, 'Invalid Font Awesome Classes:');
            }
            return '';
        },

        updatePreview: function (isBlur = false) {
            this.currentValue = this.target.value;
            var s = this.analyzeIconText(true, isBlur);
            if (s) {
                this.createIcon('cv6-iconpreview', isBlur);
            }
            else {
                if (this.iconText.value == '') {
                    this.setErrorText('');
                    this.hideError();
                    this.showNoIcon();
                    this.clearIcon();
                }
                else if (isBlur)
                {
                    this.showError();
                }
            }
        },

        blur6: function (e) {
            clearTimeout(this.inputTimer);
            this.updatePreview(true);
        },

        focus6: function (e) {
            this.hideError();
        },

        click6: function (e) {
            var elm = e.target.closest('.menu-linkRow');
            if (!elm) return;
            var parent = elm.closest('[data-type]');
            if (!parent) return;
            var newValue = this.iconText.value;

            var type = parent.getAttribute('data-type');

            parent.querySelectorAll('.menu-linkRow').forEach(linkRow => {
                if (linkRow.classList.contains('is-active')) {
                    linkRow.classList.remove('is-active');
                }
                var f = linkRow.getAttribute('data-class');
                if (f) {
                    newValue = newValue.replace(f, '').replace(/\s+/g, " ").trim();
                }
            });

            var faClass = elm.getAttribute('data-class');

            switch (faClass) {
                case '_default':
                case 'default':
                    this.setClassType(type, 'default');
                    this.iconText.value = newValue;
                    break;
                default:
                    this.setClassType(type, faClass);
                    this.iconText.value = newValue + " " + faClass;
                    break;
            }            

            elm.classList.add('is-active');
            this.analyzeIconText(false, false);
            this.createIcon('cv6-iconpreview');
        },

        analyzeIconText: function (setMenu = true, showErrorText = true) {

            const ICON_CLASS_ANIMATION_REGEX = /^fa-(spin|pulse)$/i,
                ICON_CLASS_ROTATION_REGEX = /^fa-(rotate-(90|180|270)|flip-(horizontal|vertical))$/i,
                ICON_CLASS_SIZE_REGEX = /^fa-(xs|sm|lg|\d+x|fw)$/i,
                ICON_CLASS_VARIANT_REGEX = /^(fas|far|fal|fad|fab)$/i;

            this.clearIcon();
            const icon = this.iconText.value.trim();
            var iconParts = icon.split(' ');
            var usedParts = [];

            let variant = ''
            let name = ''

            if (icon == '') {
                this.setErrorText('');
                this.showNoIcon();
                return false;
            }

            const isFa = (this.activeProvider === 'fa' || !this.activeProvider);
            const prefix = this.getPrefix();

            for (const className of iconParts) {
 
                if (className.match(ICON_CLASS_ANIMATION_REGEX)) {
                    this.setClassType('animation', className);
                    usedParts.push(className);
                    continue;
                }

                else if (className.match(ICON_CLASS_ROTATION_REGEX)) {
                    this.setClassType('rotation', className);
                    usedParts.push(className);
                    continue;
                }

                else if (className.match(ICON_CLASS_SIZE_REGEX)) {
                    this.setClassType('size', className);
                    usedParts.push(className);
                    continue;
                }

                if (isFa) {
                    if (className.match(XF.Icon.ICON_CLASS_BLOCKLIST_REGEX)) {
                        continue;
                    }

                    else if (['fal', 'far', 'fas', 'fad', 'fab'].includes(className)) {
                        variant = className;
                        usedParts.push(className);
                        continue;
                    }

                    else if (className.match(XF.Icon.ICON_CLASS_REGEX)) {
                        name = XF.Icon.normalizeIconName(className);
                        usedParts.push(className);
                        continue;
                    }
                } else {
                    if (['gmo', 'gmr', 'gms', 'gmf', 'gio'].includes(className)) {
                        variant = className;
                        usedParts.push(className);
                        continue;
                    }

                    if (prefix && className.indexOf(prefix) === 0) {
                        name = className.substring(prefix.length);
                        usedParts.push(className);
                        continue;
                    }

                    if (/^[a-z0-9_-]+$/i.test(className)) {
                        name = className;
                        usedParts.push(className);
                        continue;
                    }
                }

            }

            for (const usedPart of usedParts) {
                iconParts = iconParts.filter(part => part !== usedPart)
            }
            if (isFa && (!variant || variant === 'default' || variant === '_default')) { variant = 'fal' }
            else if (!isFa && (!variant || variant === 'default' || variant === '_default')) { variant = '' }

            this.setName(name);
            this.setVariant(variant);

            // align menu
            if (setMenu && this.layoutMenu) {
                this.layoutMenu.forEach(linkRow => {
                    var className = linkRow.getAttribute('data-class');
                    if (!className) return;
                    var parentNode = linkRow.closest('[data-type]');
                    const type = parentNode ? parentNode.getAttribute('data-type') : (linkRow.parentElement ? linkRow.parentElement.getAttribute('data-type') : null);
                    if (!type) return;
                    if (this.newIcon[type] == className || 
                        (className == '_default' && (this.newIcon[type] == '' || this.newIcon[type] == 'default' || !this.newIcon[type]))
                    )  {
                        linkRow.classList.add('is-active');
                    }
                    else {
                        linkRow.classList.remove('is-active');
                    }
                });
            }

            if (iconParts.length > 0) {
                if (showErrorText) {
                    this.setErrorText(this.getPhrase('unknownClass') + ' ' + iconParts.join(', '));
                }
                return false;
            }
            return true;
           
        },

        setClasses: function (additionalClass='') {
            this.newIcon.classes = (this.newIcon.additional + ' ' + this.newIcon.size + ' ' + this.newIcon.rotation + ' ' + this.newIcon.animation + ' ' + this.targetClass + ' ' + additionalClass).trim();
        },

        clearIcon: function () {
            this.setClassType('animation');
            this.setClassType('rotation');
            this.setClassType('variant');
            this.setClassType('size');
            if (this.styleField) {
                this.styleField.value = "";
            }            
        },

        setClassType: function (classType, value) {
            if (value == '_default' || value == null) {
                value = '';
            }
            switch (classType) {
                case 'animation':
                    this.setAnimation(value);
                    break;
                case 'rotation':
                    this.setRotation(value);
                    break;
                case 'variant':
                    this.setVariant(value);
                    break;
                case 'size':
                    this.setSize(value);
                    break;
                default:
                    break;
            }
        },

        setAnimation: function (animation) {
            this.newIcon.animation = animation;
        },

        setRotation: function (rotation) {
            this.newIcon.rotation = rotation;
        },

        setVariant: function (variant = "default") {
            // this.newIcon.variant = XF.Icon.normalizeIconVariant(variant);
            this.newIcon.variant = variant;
        },

        setSize: function (size) {
            this.newIcon.size = size;
        },

        setName: function (name) {
            if (this.activeProvider === 'fa' || !this.activeProvider) {
                this.newIcon.name = XF.Icon.normalizeIconName(name || '');
            } else {
                const prefix = this.getPrefix();
                this.newIcon.name = (name || '').replace(new RegExp('^' + prefix), '');
            }
        },

        async createIcon(additionalClass = '', isBlur = false) {
            this.setClasses(additionalClass);
            if (!this.newIcon.name) {
                this.showNoIcon();
                return;
            }

            const isFa = (this.activeProvider === 'fa' || !this.activeProvider);

            if (isFa) {
                try {
                    let icon = await XF.Icon.getInlineIcon(
                        this.newIcon.variant,
                        this.newIcon.name,
                        this.newIcon.classes
                    );
                    const object = XF.createElementFromString(icon);
                    const current = this.getPreview();
                    if (current) {
                        current.replaceWith(object);
                    }
                    this.setErrorText('');
                    this.hideError();
                    this.target.dispatchEvent(new Event("change", { bubbles: true }));
                }
                catch (e) {
                    console.warn(e);
                    if (isBlur) {
                        this.showError(this.getPhrase('unknownIcon'));
                    }
                }
            } else {
                const prefix = this.getPrefix();
                const fullName = (this.newIcon.name.indexOf(prefix) === 0) ? this.newIcon.name : prefix + this.newIcon.name;
                const fullClasses = [
                    this.newIcon.variant,
                    fullName,
                    this.newIcon.size,
                    this.newIcon.rotation,
                    this.newIcon.animation
                ].filter(Boolean).join(' ').trim();

                const script = (typeof XF.getApp === 'function' && XF.getApp() === 'admin') ? 'admin.php' : 'index.php';
                const url = XF.canonicalizeUrl(script + '?cv6-core/preview-icon');

                XF.ajax('GET', url, { classes: fullClasses, provider: this.activeProvider }, (data) => {
                    const html = (data && typeof data.html === 'string' && data.html !== '') ? data.html : ((data && data.iconHtml) || (data && data.html && data.html.content) || '');
                    if (html) {
                        const object = XF.createElementFromString(html);
                        object.classList.add(this.targetClass, 'cv6-iconpreview');
                        const current = this.getPreview();
                        if (current) {
                            current.replaceWith(object);
                        }
                        this.setErrorText('');
                        this.hideError();
                    } else if (isBlur) {
                        this.showError(this.getPhrase('unknownIcon'));
                    }
                }, { skipDefault: true });
            }

            if (this.styleField) {
                if (this.options.type === 'stylevar') {
                    const prefix = isFa ? 'fa-' : this.getPrefix();
                    const cleanPrefix = prefix.replace(/-+$/, '');
                    const varPrefix = '@' + cleanPrefix + '-var-';
                    const iconName = this.newIcon.name ? (this.newIcon.name.indexOf(prefix) === 0 ? this.newIcon.name.substring(prefix.length) : this.newIcon.name) : '';
                    this.styleField.value = varPrefix + iconName;
                } else {
                    const prefix = isFa ? 'fa-' : this.getPrefix();
                    const iconName = this.newIcon.name ? ((this.newIcon.name.indexOf(prefix) === 0) ? this.newIcon.name : prefix + this.newIcon.name) : '';
                    const fullClass = [
                        this.newIcon.variant,
                        iconName,
                        this.newIcon.size,
                        this.newIcon.rotation,
                        this.newIcon.animation,
                        this.newIcon.additional
                    ].filter(Boolean).join(' ').trim();
                    this.styleField.value = fullClass;
                }
            }            
        },

        showLoading: function () {
            const current = this.getPreview();
            if (current) {
                current.replaceWith(this.loadingIcon);
            }
            XF.Animate.fadeUp(this.errorText, { speed: XF.config.speed.fast });
        },

        showNoIcon: function () {
            const current = this.getPreview();
            if (current) {
                current.replaceWith(this.noIcon);
            }
            XF.Animate.fadeUp(this.errorText, { speed: XF.config.speed.fast });
        },


        setErrorText: function (errorText) {
            if (typeof errorText == 'string') {
                this.errorText.innerText = errorText;
            }
            else {
                this.errorText.innerText = '';
            }
        },

        showError: function (newText = null) {
            if (newText) {
                this.errorText.innerText = newText;
            }
            if (!this.errorText || this.errorText.innerText == '') {
                this.hideError();
                return;
            }
            this.errorText.classList.remove('is-hidden');
            if (window.getComputedStyle(this.errorText).display === 'none') {
                XF.Animate.fadeDown(this.errorText, { speed: XF.config.speed.fast });
            }
            const current = this.getPreview();
            if (current) {
                current.replaceWith(this.errorIcon);
            }
        },

    });

    XF.Element.register('cv6ChangeIcon', 'XF.cv6ChangeIcon');

    XF.cv6IconAutoComplete = XF.Element.newHandler({
        eventNameSpace: 'cv6IconAutoComplete',
        options: {
            acUrl: null,
            minChars: 2
        },

        menu: null,
        menuContent: null,
        timer: null,
        selectedIndex: -1,
        results: [],

        init: function() {
            this.input = this.target;
            this.input.setAttribute('autocomplete', 'off');

            XF.on(this.input, 'input', this.onInput.bind(this));
            XF.on(this.input, 'focus', this.onInput.bind(this));
            XF.on(this.input, 'keydown', this.onKeyDown.bind(this));
            XF.on(this.input, 'blur', this.onBlur.bind(this));

            this.setupMenu();
        },

        setupMenu: function() {
            this.menu = XF.createElementFromString(
                '<div class="menu cv6-icon-autocomplete-menu" style="display:none; position:absolute; top:100%; left:0; width:100%; margin-top:2px; z-index:10000; background:var(--xf-contentBg, #ffffff); border:1px solid var(--xf-borderColor, #dfdfdf); border-radius:4px; box-shadow:0 4px 12px rgba(0,0,0,0.15); max-height:240px; overflow-y:auto;">' +
                '<div class="menu-content"><div class="cv6-icon-autocomplete-results"></div></div>' +
                '</div>'
            );
            this.menuContent = this.menu.querySelector('.cv6-icon-autocomplete-results');

            var container = this.input.closest('.inputGroup') || this.input.parentElement;
            if (container) {
                if (getComputedStyle(container).position === 'static') {
                    container.style.position = 'relative';
                }
                container.appendChild(this.menu);
            }
        },

        getProvider: function() {
            if (this.input) {
                const p = this.input.getAttribute('data-active-provider') || this.input.getAttribute('data-provider');
                if (p) return p;
            }
            var activeBtn = this.input.closest('.inputGroup') ? this.input.closest('.inputGroup').querySelector('.js-cv6-provider-btn.is-active') : null;
            return activeBtn ? (activeBtn.getAttribute('data-provider') || 'fa') : 'fa';
        },

        getActiveQuery: function() {
            var val = this.input.value;
            var cursor = this.input.selectionStart || val.length;
            var textBeforeCursor = val.substring(0, cursor);
            var words = textBeforeCursor.split(/\s+/);
            var currentWord = words[words.length - 1] || '';

            const MODIFIER_REGEX = /^(fa-(spin|pulse|rotate-90|rotate-180|rotate-270|flip-horizontal|flip-vertical|xs|sm|lg|\d+x|fw)|fas|far|fal|fad|fab|gmo|gmr|gms|gmf)$/i;
            if (MODIFIER_REGEX.test(currentWord)) {
                return null;
            }

            if (/^[a-z0-9_-]{2,}$/i.test(currentWord)) {
                var allWords = val.trim().split(/\s+/);
                var targetIdx = words.length - 1;
                return {
                    word: currentWord,
                    index: targetIdx,
                    allWords: allWords
                };
            }

            return null;
        },

        onInput: function() {
            clearTimeout(this.timer);
            this.timer = setTimeout(this.search.bind(this), 200);
        },

        search: function() {
            var active = this.getActiveQuery();
            if (!active) {
                this.hideMenu();
                return;
            }

            var query = active.word;
            var provider = this.getProvider();
            var script = (typeof XF.getApp === 'function' && XF.getApp() === 'admin') ? 'admin.php' : 'index.php';
            var url = this.options.acUrl || XF.canonicalizeUrl(script + '?cv6-core/auto-complete');

            XF.ajax('GET', url, { q: query, provider: provider }, this.handleResults.bind(this), { skipDefault: true });
        },

        handleResults: function(data, status, xhr) {
            var results = null;

            if (data && Array.isArray(data.results)) {
                results = data.results;
            } else if (data && data.json && Array.isArray(data.json.results)) {
                results = data.json.results;
            } else if (status && Array.isArray(status.results)) {
                results = status.results;
            }

            if (!results || !results.length) {
                this.hideMenu();
                return;
            }

            this.results = results;
            this.selectedIndex = -1;
            this.renderResults();
            this.showMenu();
        },

        getModifiers: function() {
            var val = this.input.value;
            var words = val.trim().split(/\s+/);
            var variant = 'light';
            var extraClasses = [];
            var inlineStyles = [];

            const VARIANT_MAP = {
                'fal': 'light',
                'far': 'regular',
                'fas': 'solid',
                'fad': 'duotone',
                'fab': 'brands'
            };

            const ROTATION_MAP = {
                'fa-rotate-90': 'rotate(90deg)',
                'fa-rotate-180': 'rotate(180deg)',
                'fa-rotate-270': 'rotate(270deg)',
                'fa-flip-horizontal': 'scaleX(-1)',
                'fa-flip-vertical': 'scaleY(-1)'
            };

            words.forEach(w => {
                if (VARIANT_MAP[w]) {
                    variant = VARIANT_MAP[w];
                } else if (ROTATION_MAP[w]) {
                    extraClasses.push(w);
                    inlineStyles.push('transform: ' + ROTATION_MAP[w] + ';');
                } else if (/^fa-(xs|sm|lg|\d+x|fw)$/i.test(w)) {
                    extraClasses.push(w);
                }
            });

            return {
                variant: variant,
                extraClasses: extraClasses.join(' '),
                inlineStyles: inlineStyles.join(' ')
            };
        },

        renderResults: function() {
            this.menuContent.innerHTML = '';
            var modifiers = this.getModifiers();
            var typedVariant = modifiers.variant;
            var extraClasses = modifiers.extraClasses;
            var inlineStyles = modifiers.inlineStyles;

            this.results.forEach((item, index) => {
                var activeVariant = 'light';
                if (item.variant === 'brands') {
                    activeVariant = 'brands';
                } else if (item.variants && Array.isArray(item.variants) && item.variants.indexOf(typedVariant) !== -1) {
                    activeVariant = typedVariant;
                } else if (item.variant) {
                    activeVariant = item.variant;
                } else {
                    activeVariant = typedVariant || 'light';
                }

                var iconHtml = '';
                if (item.html) {
                    iconHtml = item.html;
                } else if (typeof XF.Icon === 'object' && typeof XF.Icon.getStandaloneIconUrl === 'function') {
                    var svgUrl = XF.Icon.getStandaloneIconUrl(activeVariant, item.name);
                    iconHtml = '<span class="' + extraClasses + '" style="display:inline-block; width:16px; height:16px; background-color:currentColor; -webkit-mask:url(\'' + svgUrl + '\') center/contain no-repeat; mask:url(\'' + svgUrl + '\') center/contain no-repeat; ' + inlineStyles + '"></span>';
                } else if (typeof XF.Icon === 'object' && typeof XF.Icon.getIcon === 'function') {
                    iconHtml = XF.Icon.getIcon(activeVariant, item.icon, 'fa-fw ' + extraClasses);
                } else {
                    iconHtml = '<i class="fa fa-fw ' + item.icon + ' ' + extraClasses + '" style="' + inlineStyles + '"></i>';
                }

                var row = XF.createElementFromString(
                    '<a class="menu-linkRow cv6-icon-autocomplete-item" data-index="' + index + '" style="display:flex; align-items:center; gap:8px; padding:6px 12px; cursor:pointer; text-decoration:none; color:var(--xf-textColor, #141414);">' +
                    '<span style="font-size:16px; width:20px; text-align:center; flex-shrink:0; display:inline-flex; align-items:center; justify-content:center;">' + iconHtml + '</span>' +
                    '<span style="font-size:13px;">' + (item.text || item.name) + '</span>' +
                    '</a>'
                );
                XF.on(row, 'mousedown', (e) => {
                    e.preventDefault();
                    this.selectItem(index);
                });
                this.menuContent.appendChild(row);
            });
        },

        onKeyDown: function(e) {
            if (!this.menu || this.menu.style.display === 'none') {
                return;
            }

            var items = this.menuContent.querySelectorAll('.cv6-icon-autocomplete-item');
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.selectedIndex = (this.selectedIndex + 1) % items.length;
                this.highlightItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectedIndex = (this.selectedIndex - 1 + items.length) % items.length;
                this.highlightItem(items);
            } else if (e.key === 'Enter') {
                if (this.selectedIndex >= 0 && this.selectedIndex < items.length) {
                    e.preventDefault();
                    this.selectItem(this.selectedIndex);
                }
            } else if (e.key === 'Escape') {
                this.hideMenu();
            }
        },

        highlightItem: function(items) {
            items.forEach((item, idx) => {
                if (idx === this.selectedIndex) {
                    item.classList.add('is-selected');
                    item.style.backgroundColor = 'var(--xf-contentHighlightBg, #e0e8f0)';
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('is-selected');
                    item.style.backgroundColor = '';
                }
            });
        },

        selectItem: function(index) {
            if (this.results[index]) {
                var selectedItem = this.results[index];
                var selectedIcon = selectedItem.id || selectedItem.icon;
                var provider = this.getProvider();
                var isBrand = (selectedItem.variant === 'brands');

                var val = this.input.value;
                var active = this.getActiveQuery();
                var words = active ? active.allWords : val.trim().split(/\s+/);
                var targetIdx = active ? active.index : words.length - 1;

                if (provider === 'fa') {
                    const VARIANTS = ['fal', 'far', 'fas', 'fad', 'fab'];
                    var currentVariant = '';

                    for (var i = 0; i < words.length; i++) {
                        var wLower = words[i].toLowerCase();
                        if (VARIANTS.indexOf(wLower) !== -1) {
                            currentVariant = wLower;
                            break;
                        }
                    }

                    if (isBrand) {
                        if (currentVariant && currentVariant !== 'fab') {
                            this.lastNonBrandStyle = currentVariant;
                        } else if (!this.lastNonBrandStyle) {
                            this.lastNonBrandStyle = 'fal';
                        }

                        if (currentVariant) {
                            words = words.map(w => VARIANTS.indexOf(w.toLowerCase()) !== -1 ? 'fab' : w);
                        } else {
                            words.unshift('fab');
                            targetIdx++;
                        }
                    } else {
                        if (currentVariant === 'fab') {
                            var restoredStyle = this.lastNonBrandStyle || 'fal';
                            words = words.map(w => w.toLowerCase() === 'fab' ? restoredStyle : w);
                        }
                    }
                }

                words[targetIdx] = selectedIcon;

                this.input.value = words.join(' ').trim();
                this.input.dispatchEvent(new Event('input', { bubbles: true }));
                this.input.dispatchEvent(new Event('change', { bubbles: true }));
                this.input.dispatchEvent(new Event('blur', { bubbles: true }));
            }
            this.hideMenu();
        },

        showMenu: function() {
            if (!this.menu) return;
            this.menu.classList.add('is-active');
            this.menu.style.display = 'block';
        },

        hideMenu: function() {
            if (this.menu) {
                this.menu.classList.remove('is-active');
                this.menu.style.display = 'none';
            }
        },

        onBlur: function() {
            setTimeout(this.hideMenu.bind(this), 200);
        }
    });

    XF.Element.register('cv6-icon-auto-complete', 'XF.cv6IconAutoComplete');
    XF.Element.register('cv6IconAutoComplete', 'XF.cv6IconAutoComplete');

    function handleMigrateChangeApply(container) {
        var key = container.dataset.key;
        var input = container.querySelector('.js-cv6-change-icon-input') || container.querySelector('[name="target_icon"]') || container.querySelector('.inputGroup input[type="text"]');
        if (!key || !input) {
            return;
        }

        var newIconVal = input.value.trim();
        var provider = container.dataset.provider || '';

        // Find the corresponding row in the datalist table
        var row = document.querySelector('[data-item-key="' + CSS.escape(key) + '"]');
        if (row) {
            var hiddenInput = row.querySelector('.js-custom-new-input');
            var isStyleVar = hiddenInput && /^@[a-z0-9]+-var-/i.test(hiddenInput.value);
            var isPhrase = key.indexOf('xf_phrase|') === 0;

            var saveVal = newIconVal;
            if (isStyleVar) {
                var prefixMatch = container.querySelector('[data-prefix]');
                var prefix = prefixMatch ? prefixMatch.getAttribute('data-prefix') : (provider ? provider + '-' : '');
                if (prefix) {
                    var cleanPrefix = prefix.replace(/-+$/, '');
                    var cleanName = newIconVal;
                    if (cleanName.indexOf(prefix) === 0) {
                        cleanName = cleanName.substring(prefix.length);
                    }
                    saveVal = '@' + cleanPrefix + '-var-' + cleanName;
                }
            } else if (isPhrase) {
                var currentVal = hiddenInput ? hiddenInput.value : '';
                if (currentVal && /<xf:fa\b[^>]*?\bicon="([^"]+)"[^>]*>/i.test(currentVal)) {
                    saveVal = currentVal.replace(/(<xf:fa\b[^>]*?\bicon=")[^"]+("[^>]*>)/i, '$1' + newIconVal + '$2');
                }
            }

            if (hiddenInput) {
                hiddenInput.value = saveVal;
            }

            var textCode = row.querySelector('.js-new-icon-text');
            if (textCode) {
                textCode.textContent = saveVal || '-';
            }

            var editBtn = row.querySelector('.js-change-target-icon');
            if (editBtn) {
                var href = editBtn.getAttribute('href') || '';
                if (href) {
                    var editParamVal = isPhrase ? newIconVal : saveVal;
                    editBtn.setAttribute('href', href.replace(/([?&]icon=)[^&]*/, '$1' + encodeURIComponent(editParamVal)));
                }
            }

            // Update icon preview
            var previewEl = row.querySelector('.js-new-icon-preview');
            if (previewEl) {
                var modalPreview = container.querySelector('.cv6-iconpreview--migrate_change') || container.querySelector('.js-cv6-change-preview') || container.querySelector('.cv6-iconpreview');
                var isError = modalPreview && (
                    modalPreview.classList.contains('cv6-error-icon') ||
                    modalPreview.classList.contains('cv6-no-icon') ||
                    modalPreview.querySelector('.fa-bug')
                );

                if (modalPreview && newIconVal && !isError) {
                    previewEl.innerHTML = modalPreview.outerHTML;
                } else if (newIconVal) {
                    XF.ajax('POST', XF.canonicalizeUrl('admin.php?cv6-core/preview-icon'), {
                        classes: newIconVal,
                        provider: provider
                    }, function(data) {
                        var html = (data && typeof data.html === 'string' && data.html !== '') ? data.html : ((data && data.iconHtml) || '');
                        if (html) {
                            previewEl.innerHTML = html;
                        }
                    });
                } else {
                    previewEl.innerHTML = '<span class="cv6-iconpreview cv6-no-icon"><i class="fa--xf far fa-question-circle" aria-hidden="true"></i></span>';
                }
            }

            // Check row checkbox
            var check = row.querySelector('.js-item-check');
            if (check) {
                check.checked = true;
            }
        }

        // Close overlay
        XF.hideParentOverlay(container);
    }

    XF.on(document, 'submit', function(e) {
        var form = e.target.closest('.js-migrateChangeForm');
        if (!form) {
            return;
        }

        e.preventDefault();
        handleMigrateChangeApply(form);
    });

    XF.on(document, 'click', function(e) {
        var applyBtn = e.target.closest('.js-cv6-change-apply');
        if (!applyBtn) {
            return;
        }

        e.preventDefault();

        var container = applyBtn.closest('.js-migrateChangeContainer') || applyBtn.closest('.js-migrateChangeForm');
        if (container) {
            handleMigrateChangeApply(container);
        }
    });

})(window, document)