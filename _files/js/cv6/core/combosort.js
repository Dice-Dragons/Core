((window, document) => 
{
    "use strict";

    console.log("combosort.js");

    XF.cv6AutoSorter = XF.Element.newHandler({
        eventNameSpace: 'cv6AutoSorter',
        options: {
            target: null,
        },

        container: null,
        buttonCage: null,
        changeTimer: null,
        xhr: null,

        init: function () {

            var container = document.querySelector(".inputGroup-container:not(.cv6-choice-order)");
            if (!container) {
                console.error('Could not find choice container.');
                return;
            }
            this.buttonCage = document.querySelector('.js-cv6-sorter-buttons');
            this.container = container;

            XF.on(this.buttonCage.querySelector('.js-cv6-key-down'), 'click', this.doSort.bind(this, 'down', 'key'));
            XF.on(this.buttonCage.querySelector('.js-cv6-key-up'), 'click', this.doSort.bind(this, 'up', 'key'));
            XF.on(this.buttonCage.querySelector('.js-cv6-value-down'), 'click', this.doSort.bind(this, 'down', 'value'));
            XF.on(this.buttonCage.querySelector('.js-cv6-value-up'), 'click', this.doSort.bind(this, 'up', 'value'));

            XF.on(this.buttonCage.querySelector('.js-cv6-key-num-down'), 'click', this.doNumSort.bind(this, 'down', 'key'));
            XF.on(this.buttonCage.querySelector('.js-cv6-key-num-up'), 'click', this.doNumSort.bind(this, 'up', 'key'));
            XF.on(this.buttonCage.querySelector('.js-cv6-value-num-down'), 'click', this.doNumSort.bind(this, 'down', 'value'));
            XF.on(this.buttonCage.querySelector('.js-cv6-value-num-up'), 'click', this.doNumSort.bind(this, 'up', 'value'));
        },

        doSort: function (direction, field) {
            var keys = this.container.querySelectorAll("input[name='field_choice[]'");
            var values = this.container.querySelectorAll("input[name='field_choice_text[]'");
            var fields = Array();
            keys.forEach(function (e,i) {
                fields.push({ key: e.value, value: values[i].value, row: i });
            });
            fields.pop();
            if (direction == 'down') {
                if (field == 'key') {
                    fields.sort(function (a, b) {
                        let x = a.key.toLowerCase();
                        let y = b.key.toLowerCase();
                        if (x < y) { return -1; }
                        if (x > y) { return 1; }
                        return 0;
                    });
                }
                else {
                    fields.sort(function (a, b) {
                        let x = a.value.toLowerCase();
                        let y = b.value.toLowerCase();
                        if (x < y) { return -1; }
                        if (x > y) { return 1; }
                        return 0;
                    });
                }
            }
            else {
                if (field == 'key') {
                    fields.sort(function (a, b) {
                        let x = a.key.toLowerCase();
                        let y = b.key.toLowerCase();
                        if (x < y) { return 1; }
                        if (x > y) { return -1; }
                        return 0;
                    });
                }
                else {
                    fields.sort(function (a, b) {
                        let x = a.value.toLowerCase();
                        let y = b.value.toLowerCase();
                        if (x < y) { return 1; }
                        if (x > y) { return -1; }
                        return 0;
                    });
                }
            }
            keys.forEach(function (e, i) {
                if (i < keys.length - 1) e.value = fields[i].key;
            });
            values.forEach(function (e, i) {
                if (i < values.length - 1) e.value = fields[i].value;
            });
        },

        doNumSort: function (direction, field) {
            var keys = this.container.querySelectorAll("input[name='field_choice[]'");
            var values = this.container.querySelectorAll("input[name='field_choice_text[]'");
            var fields = Array();
            keys.forEach(function (e, i) {
                fields.push({ key: e.value, value: values[i].value, row: i });
            });
            fields.pop();
            if (direction == 'down') {
                if (field == 'key') {
                    fields.sort(function (a, b) {
                        return a.key - b.key;
                    });
                }
                else {
                    fields.sort(function (a, b) {
                        return a.value - b.value;
                    });
                }
            }
            else {
                if (field == 'key') {
                    fields.sort(function (a, b) {
                        return b.key - a.key;
                    });
                }
                else {
                    fields.sort(function (a, b) {
                        return b.value - a.value;
                    });
                }
            }
            keys.forEach(function (e, i) {
                if (i < keys.length - 1) e.value = fields[i].key;
            });
            values.forEach(function (e, i) {
                if (i < values.length - 1) e.value = fields[i].value;
            });
        }

    });

    XF.Element.register('cv6AutoSorter', 'XF.cv6AutoSorter');

})(window, document);
