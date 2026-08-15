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
            showError: false
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
                this.errorText = document.querySelector('.cv6-iconerror');
            }
            else {
                this.errorText = XF.createElementFromString('<div class="cv6-iconerror"></div>');
            }

            this.iconText = this.target;
            this.targetClass = this.options.target.replace('.', '');
          
            this.loadingIcon = XF.createElementFromString(
                XF.Icon.getIcon('default', 'fa-spinner', 'fa-pulse cv6-loading ' + this.targetClass)
            );
            this.errorIcon = XF.createElementFromString(
                XF.Icon.getIcon('default', 'fa-bug', 'cv6-error-icon ' + this.targetClass)
            );
            this.noIcon = XF.createElementFromString(
                XF.Icon.getIcon('default', 'fa-times-octagon', 'cv6-no-icon ' + this.targetClass)
            );

            this.oldIconClass = this.target.value;
            
            XF.on(this.target, 'blur', this.blur6.bind(this));
            XF.on(this.target, 'focus', this.focus6.bind(this));

            this.inputGroup = this.target.closest('.inputGroup') || this.target.parentElement;
            this.layoutMenu = this.inputGroup ? this.inputGroup.querySelectorAll('.menu-linkRow') : [];

            this.layoutMenu.forEach(linkRow => {
                XF.on(linkRow, 'click', this.click6.bind(this));
            });            

            this.analyzeIconText(true);
        },

        blur6: function (e) {
            var s = this.analyzeIconText();
            if (s) {
                this.createIcon('cv6-iconpreview');
            }
            else {
                if (this.iconText.value == '') {
                    this.showNoIcon();
                    this.clearIcon();
                }
                else
                {
                    this.showError();
                }
            }
        },

        focus6: function (e) {
            this.showLoading();
        },

        click6: function (e) {

            var elm = e.target;
            var parent = elm.parentElement;
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

            var faClass = e.target.getAttribute('data-class');

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
            this.analyzeIconText(false);
            this.createIcon('cv6-iconpreview');
        },

        analyzeIconText: function (setMenu = true) {

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

            for (const className of iconParts) {
 
                if (className.match(ICON_CLASS_ANIMATION_REGEX)) {
                    this.setClassType('animation', className);
                    usedParts.push(className);
                    continue
                }

                else if (className.match(ICON_CLASS_ROTATION_REGEX)) {
                    this.setClassType('rotation', className);
                    usedParts.push(className);
                    continue
                }

                else if (className.match(ICON_CLASS_SIZE_REGEX)) {
                    this.setClassType('size', className);
                    usedParts.push(className);
                    continue
                }

                else if (className.match(XF.Icon.ICON_CLASS_BLOCKLIST_REGEX)) {
                    continue
                }

                else if (['fal', 'far', 'fas', 'fad', 'fab'].includes(className)) {
                    // variant = XF.Icon.normalizeIconVariant(className);
                    variant = className;
                    usedParts.push(className);
                    continue
                }

                else if (className.match(XF.Icon.ICON_CLASS_REGEX)) {
                    name = XF.Icon.normalizeIconName(className);
                    usedParts.push(className);
                    continue
                }

            }

            for (const usedPart of usedParts) {
                iconParts = iconParts.filter(part => part !== usedPart)
            }
            if (!variant || variant === 'default' || variant === '_default') { variant = 'fal' }

            this.setName(name);
            this.setVariant(variant);

            // align menu
            if (setMenu && this.layoutMenu) {
                this.layoutMenu.forEach(linkRow => {
                    var className = linkRow.getAttribute('data-class');
                    var parentNode = linkRow.closest('[data-type]');
                    const type = parentNode ? parentNode.getAttribute('data-type') : linkRow.parentElement.getAttribute('data-type');
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
                this.setErrorText(XF.phrase('cv6UnknownClass') + ' ' + iconParts.join(', '));
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
            this.newIcon.name = XF.Icon.normalizeIconName(name);
        },

        async createIcon(additionalClass = '') {
            this.setClasses(additionalClass);
            if (!this.newIcon.name) {
                this.showNoIcon();
                return;
            }
            try {
                let icon = await XF.Icon.getInlineIcon(
                    this.newIcon.variant,
                    this.newIcon.name,
                    this.newIcon.classes
                );
                const object = XF.createElementFromString(icon);
                document.querySelector(this.options.target).replaceWith(object); 
                this.target.dispatchEvent(new Event("change", {  } ));
            }
            catch (e) {
                console.warn(e);
                this.showError(XF.phrase('cv6UnknownIcon'));
            }
            if (this.styleField) {
                if (this.options.type === 'stylevar') {
                    this.styleField.value = "@fa-var-" + (this.newIcon.name || '');
                } else {
                    const fullClass = [
                        this.newIcon.variant,
                        this.newIcon.name ? 'fa-' + this.newIcon.name : '',
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
            document.querySelector(this.options.target).replaceWith(this.loadingIcon);
            XF.Animate.fadeUp(this.errorText, { speed: XF.config.speed.fast })
        },

        showNoIcon: function () {
            document.querySelector(this.options.target).replaceWith(this.noIcon);
            XF.Animate.fadeUp(this.errorText, { speed: XF.config.speed.fast })
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
            if (this.errorText.innerText == '') {
                XF.Animate.fadeUp(this.errorText, { speed: XF.config.speed.fast })
            } 
            else if (this.errorText.classList.contains('is-hidden')) {
                XF.Animate.fadeDown(this.errorText, { speed: XF.config.speed.fast })
            }
           document.querySelector(this.options.target).replaceWith(this.errorIcon);
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

        getActiveQuery: function() {
            var val = this.input.value;
            var cursor = this.input.selectionStart || val.length;
            var textBeforeCursor = val.substring(0, cursor);
            var words = textBeforeCursor.split(/\s+/);
            var currentWord = words[words.length - 1] || '';

            const MODIFIER_REGEX = /^fa-(spin|pulse|rotate-90|rotate-180|rotate-270|flip-horizontal|flip-vertical|xs|sm|lg|\d+x|fw)$/i;
            if (MODIFIER_REGEX.test(currentWord)) {
                return null;
            }

            if (/^fa-[a-z0-9-]{2,}$/i.test(currentWord)) {
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
            var script = (typeof XF.getApp === 'function' && XF.getApp() === 'admin') ? 'admin.php' : 'index.php';
            var url = this.options.acUrl || XF.canonicalizeUrl(script + '?cv6-core/auto-complete');

            XF.ajax('GET', url, { q: query }, this.handleResults.bind(this), { skipDefault: true });
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
                if (typeof XF.Icon === 'object' && typeof XF.Icon.getStandaloneIconUrl === 'function') {
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
                    '<span style="font-size:13px;">' + item.name + '</span>' +
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
                var selectedIcon = selectedItem.id;
                var isBrand = (selectedItem.variant === 'brands');

                var val = this.input.value;
                var active = this.getActiveQuery();
                var words = active ? active.allWords : val.trim().split(/\s+/);
                var targetIdx = active ? active.index : words.length - 1;

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

                words[targetIdx] = selectedIcon;

                this.input.value = words.join(' ').trim();
                this.input.dispatchEvent(new Event('change', { bubbles: true }));
                this.input.dispatchEvent(new Event('blur', { bubbles: true }));
            }
            this.hideMenu();
        },

        showMenu: function() {
            if (!this.menu) return;
            if (this.menu.classList.contains('is-active') && getComputedStyle(this.menu).display !== 'none') {
                return;
            }
            this.menu.classList.add('is-active');
            XF.Animate.fadeDown(this.menu, { speed: XF.config.speed.fast });
        },

        hideMenu: function() {
            if (this.menu && this.menu.classList.contains('is-active')) {
                this.menu.classList.remove('is-active');
                XF.Animate.fadeUp(this.menu, { speed: XF.config.speed.fast });
            }
        },

        onBlur: function() {
            setTimeout(this.hideMenu.bind(this), 200);
        }
    });

    XF.Element.register('cv6-icon-auto-complete', 'XF.cv6IconAutoComplete');
    XF.Element.register('cv6IconAutoComplete', 'XF.cv6IconAutoComplete');

})(window, document)