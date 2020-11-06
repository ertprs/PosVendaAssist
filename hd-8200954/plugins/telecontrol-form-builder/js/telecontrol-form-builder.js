var fbApi = {};

function fbInit() {
    return new Promise(function (resolve, reject) {
        try {
            if (typeof $.fn.modal !== 'function') {
                throw new Error('library bootstrap is undefined');
            }

            if (typeof $.fn.select2 !== 'function') {
                throw new Error('plugin select2 is undefined');
            }

            if (typeof $.fn.rateYo !== 'function') {
                throw new Error('plugin rateYo is undefined');
            }

            if (typeof $.fn.checkradios !== 'function') {
                throw new Error('plugin checkradios is undefined');
            }

            if (typeof $.fn.priceFormat !== 'function') {
                throw new Error('plugin priceFormat is undefined');
            }

            if ($('.build-wrap').length == 0) {
                throw new Error('div .build-wrap is undefined');
            }

            if (typeof window.fbEditing === 'undefined') {
                throw new Error('window.fbEditing is undefined');
            }

            var fbElement = '.build-wrap';

            var fields = [
                {
                    label: 'Classificação',
                    attrs: {
                        type: 'starRating'
                    },
                    icon: '<i class=\'glyphicon glyphicon-star\' ></i>'
                }
            ];

            var templates = {
                starRating: function (fieldData) {
                    return {
                        field: '<span id="' + fieldData.name + '">',
                        onRender: function () {
                            $(document.getElementById(fieldData.name)).rateYo({ rating: 0 });
                        }
                    };
                }
            };

            var disabledAttrs = ['access'];
            var roles = {};
            var numberAttrs = {
                decimalPlaces: {
                    type: 'text',
                    label: 'Casas Decimais'
                },
                minimumValue: {
                    type: 'text',
                    label: 'Valor Mínimo'
                },
                maximumValue: {
                    type: 'text',
                    label: 'Valor Máximo'
                }
            };

            if (typeof window.fbDisabledAttrs === 'object') {
                disabledAttrs = window.fbDisabledAttrs;
            }

            if (typeof window.fbRoles === 'object') {
                $.each(window.fbRoles, function (key, value) {
                    roles[key] = value;
                });
            }

            if (typeof window.fbNumberAttrs === 'object') {
                $.each(window.fbNumberAttrs, function (key, value) {
                    numberAttrs[key] = value;
                });
            }

            var typeUserAttrs = { number: numberAttrs };

            var typeUserDisabledAttrs = {
                autocomplete: disabledAttrs,
                select: disabledAttrs.concat(['placeholder']),
                'checkbox-group': disabledAttrs.concat(['toggle']),
                checkbox: disabledAttrs,
                'radio-group': disabledAttrs,
                paragraph: disabledAttrs,
                header: disabledAttrs,
                text: disabledAttrs.concat(['value']),
                date: disabledAttrs.concat(['value', 'placeholder']),
                number: disabledAttrs.concat(['value', 'subtype', 'min', 'max', 'step']),
                textarea: disabledAttrs.concat(['value', 'rows']),
                starRating: disabledAttrs.concat(['value', 'placeholder'])
            };

            const inputSets = [
                {
                    label: '<i class="fa fa-smile"></i> NPS',
                    name: 'nps',
                    showHeader: false,
                    fields: [
                        {
                            type: 'radio-group',
                            label: 'NPS',
                            inline: true,
                            edit: false,
                            required:true,
                            values: [
                                {
                                    label: '0',
                                    value: '0',
                                    selected: false
                                },
                                {
                                    label: '1',
                                    value: '1',
                                    selected: false
                                },
                                {
                                    label: '2',
                                    value: '2',
                                    selected: false
                                },
                                {
                                    label: '3',
                                    value: '3',
                                    selected: false
                                },
                                {
                                    label: '4',
                                    value: '4',
                                    selected: false
                                },
                                {
                                    label: '5',
                                    value: '5',
                                    selected: false
                                },
                                {
                                    label: '6',
                                    value: '6',
                                    selected: false
                                },
                                {
                                    label: '7',
                                    value: '7',
                                    selected: false
                                },
                                {
                                    label: '8',
                                    value: '8',
                                    selected: false
                                },
                                {
                                    label: '9',
                                    value: '9',
                                    selected: false
                                },
                                {
                                    label: '10',
                                    value: '10',
                                    selected: false
                                },
                            ]
                        }
                    ]
                }
            ];

            var fbOptions = {
                stickyControls: {
                    enable: false
                },
                sortableControls: false,
                fields: fields,
                templates: templates,
                inputSets: inputSets,
                typeUserDisabledAttrs: typeUserDisabledAttrs,
                typeUserAttrs: typeUserAttrs,
                disableInjectedStyle: false,
                actionButtons: [],
                disableFields: [],
                replaceFields: [],
                roles: roles,
                i18n: {
                    locale: 'pt-BR'
                }
            };

            var formData = {};

            if (Object.keys(formData).length > 0) {
                fbOptions.formData = formData;
            }

            (async function () {
                var formBuilder = await $(fbElement).formBuilder(fbOptions).promise;

                fbApi.getData = function () {
                    return JSON.parse(formBuilder.actions.getData('json'));
                }

                fbApi.clearForm = function () {
                    formBuilder.actions.clearFields();
                }

                fbApi.setData = function (data) {
                    if (typeof data === 'object') {
                        data = JSON.stringify(data);
                    }
                    formBuilder.actions.setData(data);
                }

                fbApi.getFormData = function () {
                    let formData = {};

                    let data = $('#fb-form-viewer').serializeArray();

                    $('#fb-form-viewer').find('div.star-rating').each(function () {
                        data.push({ name: $(this).attr('id'), value: $(this).rateYo('rating') });
                    });

                    data.forEach(function (field, i) {
                        let name = field.name;

                        if (name.match(/\[\]$/)) {
                            name = name.replace(/\[|\]/g, '');
                        }

                        if (typeof formData[name] == 'undefined') {
                            formData[name] = field.value;
                        } else if (typeof formData[name] != 'object') {
                            let oldValue = formData[name];
                            formData[name] = [];
                            formData[name].push(oldValue);
                            formData[name].push(field.value);
                        } else {
                            formData[name].push(field.value);
                        }
                    });

                    return formData;
                }

                fbApi.validateRequiredFields = function () {
                    let data = fbApi.getFormData();
                    let errors = { messages: {}, fields: [] };

                    fbApi.getData().forEach(function (element, i) {
                        if (['header', 'paragraph'].indexOf(element.type) != -1) {
                            return;
                        }

                        if (element.required === true) {
                            if (typeof data[element.name] == 'undefined' || data[element.name] === null) {
                                errors.messages.required = 'Preencha os campos obrigatórios';
                                errors.fields.push(element.name);

                                if (element.type == 'number') {
                                    if (typeof element.tools != 'undefined' && element.tools.length > 0) {
                                        if (typeof data[element.name + '-tool'] == 'undefined' || data[element.name + '-tool'] === null || data[element.name + '-tool'].length === 0) {
                                            errors.messages.required = 'Preencha os campos obrigatórios';
                                            errors.fields.push(element.name + '-tool');
                                        }
                                    }
                                }
                            } else if (element.type == 'number') {
                                if (typeof element.minimumValue != 'undefined' && data[element.name] < parseFloat(element.minimumValue)) {
                                    errors.messages.minimumValue = 'O valor do campo: "' + element.label + '" não pode ser inferior a ' + element.minimumValue;
                                    errors.fields.push(element.name);
                                }

                                if (typeof element.maximumValue != 'undefined' && data[element.name] > parseFloat(element.maximumValue)) {
                                    errors.messages.maximumValue = 'O valor do campo: "' + element.label + '" não pode ser superior a ' + element.maximumValue;
                                    errors.fields.push(element.name);
                                }
                            } else if (element.type == 'checkbox-group') {
                                if (
                                    (typeof data[element.name] == 'object' && data[element.name].indexOf('outro') != -1)
                                    || (typeof data[element.name] == 'string' && data[element.name] == 'outro')
                                ) {
                                    if (typeof data[element.name + '-other'] == 'undefined' || data[element.name + '-other'] === null || data[element.name + '-other'].length === 0) {
                                        errors.messages.required = 'Preencha os campos obrigatórios';
                                        errors.fields.push(element.name + '-other');
                                    }
                                }
                            } else if (element.type == 'radio-group') {
                                if (data[element.name] == 'outro') {
                                    if (typeof data[element.name + '-other'] == 'undefined' || data[element.name + '-other'] === null || data[element.name + '-other'].length === 0) {
                                        errors.messages.required = 'Preencha os campos obrigatórios';
                                        errors.fields.push(element.name + '-other');
                                    }
                                }
                            } else if (element.type == 'date') {
                                let validDate = new Date(data[element.name]);

                                if (validDate == 'Invalid Date') {
                                    errors.messages.invalidDate = 'A data do campo ' + element.label + ' é inválida';
                                    errors.fields.push(element.name);
                                }
                            }
                        }
                    });

                    if (Object.keys(errors.messages).length == 0) {
                        return true;
                    } else {
                        errors.fields = errors.fields.filter(function (field, k, newArray) {
                            return k == newArray.indexOf(field);
                        });

                        return errors;
                    }
                }

                fbApi.toggleEdit = function (edit) {
                    if (edit === true) {
                        $("body").removeClass("form-rendered");
                        $("#fb-form-viewer").remove();
                        resolve(false);
                    } else {
                        $("body").addClass("form-rendered");

                        let data = fbApi.getData();

                        var formViewer = $('<form></form>', { id: 'fb-form-viewer' });
                        var divFormViewer = $('<div class="container center-block form-viewer" ></div>');

                        $(divFormViewer).append($('<div></div>', { class: 'div-logo' }).append($('<img />', { src: window.fbLogo, class: 'logo' })));

                        $(divFormViewer).append($('<div></div>', { class: 'page-header', html: '<h1 class=\'text-center\'>' + window.fbTitle + '</h1>' }));

                        var requiredAsterisk = $('<span></span>', { class: 'required-asterisk text-danger', text: '*' });
                        var tooltipElement = $('<span></span>', { class: 'tooltip-element', tooltip: null, text: '?' });
                        var formGroup = $('<div></div>', { class: 'form-group' });

                        data.forEach(function (e, i) {
                            let row = $('<div class="row"></div>');
                            let col = $('<div></div>');

                            switch (e.type) {
                                case 'header':
                                    var element = $('<' + e.subtype + '></' + e.subtype + '>', { html: e.label });
                                    $(col).addClass('col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-10 col-lg-offset-1');
                                    break;

                                case 'paragraph':
                                    var element = $('<p></p>', { html: e.label });
                                    $(col).addClass('col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-10 col-lg-offset-1');
                                    break;

                                case 'text':
                                    var element = $(formGroup).clone();
                                    var label = $('<label></label>', { html: e.label });

                                    if (e.required === true) {
                                        $(label).append($(requiredAsterisk).clone());
                                    }

                                    if (typeof e.description === 'string' && e.description.length > 0) {
                                        $(label).append($(tooltipElement).clone().attr({ tooltip: e.description }));
                                    }

                                    var input = $('<input />', { class: e.className, name: e.name, type: e.subtype });

                                    if (typeof e.placeholder === 'string' && e.placeholder.length > 0) {
                                        $(input).attr({ placeholder: e.placeholder });
                                    }

                                    if (typeof e.maxlength === 'string' && e.maxlength.length > 0) {
                                        $(input).attr({ maxlength: e.maxlength });
                                    }

                                    $(element).append(label);
                                    $(element).append(input);

                                    $(col).addClass('col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-10 col-lg-offset-1');
                                    break;

                                case 'number':
                                    var element = $(formGroup).clone();
                                    var label = $('<label></label>', { html: e.label });

                                    if (e.required === true) {
                                        $(label).append($(requiredAsterisk).clone());
                                    }

                                    if (typeof e.description === 'string' && e.description.length > 0) {
                                        $(label).append($(tooltipElement).clone().attr({ tooltip: e.description }));
                                    }

                                    var input = $('<input />', { class: e.className, name: e.name, type: 'text' });

                                    if (typeof e.placeholder === 'string' && e.placeholder.length > 0) {
                                        $(input).attr({ placeholder: e.placeholder });
                                    }

                                    let dec = 0;
                                    if (typeof e.decimalPlaces != 'undefined') {
                                        dec = parseInt(e.decimalPlaces);
                                    }

                                    let centsSeparator = '';
                                    if (dec > 0) {
                                        centsSeparator = '.';
                                    }

                                    let min = null;
                                    if (typeof e.minimumValue != 'undefined') {
                                        min = parseFloat(e.minimumValue);
                                    }

                                    let max = null;
                                    if (typeof e.maximumValue != 'undefined') {
                                        max = parseFloat(e.maximumValue);
                                    }

                                    $(input).data({ min: min, max: max, dec: dec });

                                    $(input).priceFormat({
                                        prefix: '',
                                        thousandsSeparator: '',
                                        centsSeparator: centsSeparator,
                                        centsLimit: dec
                                    });

                                    $(input).on('change', function (e) {
                                        let min = $(e.target).data('min');
                                        let max = $(e.target).data('max');
                                        let dec = $(e.target).data('dec');
                                        let value = parseFloat(e.target.value);
                                        let newValue = value;

                                        if (isNaN(value) && min != null) {
                                            newValue = min;
                                        } else if (!isNaN(value)) {
                                            if (min != null && value < min) {
                                                newValue = min;
                                            } else if (max != null && value > max) {
                                                newValue = max;
                                            }
                                        }

                                        if (value != newValue) {
                                            if (dec == null) {
                                                dec = 0;
                                            }

                                            e.target.value = newValue.toFixed(dec);
                                        }
                                    });


                                    var divInput = $('<div></div>', { class: 'col-xs-12 col-sm-8 col-md-6 col-lg-6', css: { 'padding-left': '0px' } });
                                    $(divInput).append(label);

                                    if (typeof e.unit === 'string' && e.unit.length > 0) {
                                        var inputGroup = $('<div></div>', { class: 'input-group' });
                                        $(inputGroup).append(input);
                                        $(inputGroup).append($('<div></div>', { class: 'input-group-addon', text: e.unit }));
                                        $(divInput).append(inputGroup);
                                    } else {
                                        $(divInput).append(input);
                                    }

                                    $(element).append(divInput);

                                    if (typeof e.tools != 'undefined' && e.tools.length > 0) {
                                        var divInput = $('<div></div>', { class: 'col-xs-12 col-sm-12 col-md-12 col-lg-12', css: { 'padding-left': '0px' } });
                                        var elementTools = $(formGroup).clone();
                                        elementTools.css({ 'margin-top': '10px' });
                                        var labelTools = $('<label></label>', { text: window.fbOptionsTools[e.tools] + ' utilizado(a):' });
                                        $(labelTools).append($(requiredAsterisk).clone());
                                        var selectTools = $('<select></select>', { class: e.className, name: e.name + '-tool' });

                                        let option = $('<option></option>', { value: '', text: 'Selecione' });
                                        $(selectTools).append(option);

                                        if (typeof window.fbTools != 'undefined' && typeof window.fbTools[e.tools] != "undefined") {
                                            window.fbTools[e.tools].forEach(function (v, i) {
                                                let option = $('<option></option>', { value: v.value, text: v.text });
                                                $(selectTools).append(option);
                                            });
                                        }

                                        $(elementTools).append(labelTools);
                                        $(elementTools).append(selectTools);
                                        $(divInput).append(elementTools);
                                        $(element).append(divInput);
                                    }

                                    $(col).addClass('col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-10 col-lg-offset-1');
                                    break;

                                case 'date':
                                    var element = $(formGroup).clone();
                                    var label = $('<label></label>', { html: e.label });

                                    if (e.required === true) {
                                        $(label).append($(requiredAsterisk).clone());
                                    }

                                    if (typeof e.description === 'string' && e.description.length > 0) {
                                        $(label).append($(tooltipElement).clone().attr({ tooltip: e.description }));
                                    }

                                    var input = $('<input />', { class: e.className, name: e.name, type: e.type });

                                    var divInput = $('<div></div>', { class: 'col-xs-12 col-sm-4 col-md-3 col-lg-3', css: { 'padding-left': '0px' } });
                                    $(divInput).append(label);
                                    $(divInput).append(input);
                                    $(element).append(divInput);

                                    $(col).addClass('col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-10 col-lg-offset-1');
                                    break;

                                case 'textarea':
                                    var element = $(formGroup).clone();
                                    var label = $('<label></label>', { html: e.label });

                                    if (e.required === true) {
                                        $(label).append($(requiredAsterisk).clone());
                                    }

                                    if (typeof e.description === 'string' && e.description.length > 0) {
                                        $(label).append($(tooltipElement).clone().attr({ tooltip: e.description }));
                                    }

                                    var textarea = $('<textarea></textarea>', { class: e.className, name: e.name });

                                    if (typeof e.placeholder === 'string' && e.placeholder.length > 0) {
                                        $(textarea).attr({ placeholder: e.placeholder });
                                    }

                                    if (typeof e.maxlength === 'string' && e.maxlength.length > 0) {
                                        $(textarea).attr({ maxlength: e.maxlength });
                                    }

                                    $(element).append(label);
                                    $(element).append(textarea);

                                    $(col).addClass('col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-10 col-lg-offset-1');
                                    break;

                                case 'select':
                                    var element = $(formGroup).clone();
                                    var label = $('<label></label>', { html: e.label });

                                    if (e.required === true) {
                                        $(label).append($(requiredAsterisk).clone());
                                    }

                                    if (typeof e.description === 'string' && e.description.length > 0) {
                                        $(label).append($(tooltipElement).clone().attr({ tooltip: e.description }));
                                    }

                                    var select = $('<select></select>', { class: e.className, name: e.name });

                                    if (typeof e.multiple != 'undefined' && e.multiple === true) {
                                        $(select).prop({ multiple: true });
                                    } else {
                                        let option = $('<option></option>', { value: '', text: 'Selecione' });
                                        $(select).append(option);
                                    }

                                    e.values.forEach(function (v, i) {
                                        let option = $('<option></option>', { value: v.value, text: v.label });

                                        if (typeof v.selected != 'undefined' && v.selected === true) {
                                            $(option).prop({ selected: true });
                                        }

                                        $(select).append(option);
                                    });

                                    $(element).append(label);
                                    $(element).append(select);

                                    $(col).addClass('col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-10 col-lg-offset-1');
                                    break;
                                case 'checkbox-group':
                                    var element = $(formGroup).clone();
                                    var label = $('<label></label>', { html: e.label });

                                    if (e.required === true) {
                                        $(label).append($(requiredAsterisk).clone());
                                    }

                                    if (typeof e.description === 'string' && e.description.length > 0) {
                                        $(label).append($(tooltipElement).clone().attr({ tooltip: e.description }));
                                    }

                                    var divCheckboxGroup = $('<div></div>', { class: 'checkbox-group' });

                                    e.values.forEach(function (v, i) {
                                        let divCheckbox = $('<div></div>', { class: 'checkbox' });
                                        let labelCheckbox = $('<label></label>', { html: '&nbsp;&nbsp;&nbsp;' + v.label });
                                        let checkbox = $('<input />', { type: 'checkbox', value: v.value, name: e.name + '[]' });

                                        if (typeof v.selected != 'undefined' && v.selected === true) {
                                            $(checkbox).prop({ checked: true });
                                        }

                                        if (typeof e.inline != 'undefined' && e.inline === true) {
                                            $(divCheckbox).css({ display: 'inline-block' });
                                        }

                                        $(labelCheckbox).prepend(checkbox);
                                        $(divCheckbox).append(labelCheckbox);
                                        $(divCheckboxGroup).append(divCheckbox);
                                    });

                                    if (typeof e.other != 'undefined' && e.other === true) {
                                        let divCheckbox = $('<div></div>', { class: 'checkbox' });
                                        let labelCheckbox = $('<label></label>', { html: '&nbsp;&nbsp;&nbsp;Outro:&nbsp;&nbsp;&nbsp;' });
                                        let checkbox = $('<input />', { type: 'checkbox', value: 'outro', name: e.name + '[]' });
                                        let inputOther = $('<input />', { type: 'text', value: '', name: e.name + '-other', class: 'form-control input-sm', css: { display: 'inline-block', width: 'auto' } });

                                        if (typeof e.inline != 'undefined' && e.inline === true) {
                                            $(divCheckbox).css({ display: 'inline-block', top: '-5px' });
                                        }

                                        $(inputOther).prop({ readonly: true });
                                        $(labelCheckbox).prepend(checkbox);
                                        $(labelCheckbox).append(inputOther);
                                        $(divCheckbox).append(labelCheckbox);
                                        $(divCheckboxGroup).append(divCheckbox);
                                    }

                                    $(element).append(label);
                                    $(element).append(divCheckboxGroup);

                                    $(col).addClass('col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-10 col-lg-offset-1');
                                    break;

                                case 'radio-group':
                                    var element = $(formGroup).clone();
                                    if (e.name == "nps") {
                                        var label = $('<label></label>', { html: e.label, class: "label_nps" });
                                    } else {
                                        var label = $('<label></label>', { html: e.label });
                                    }


                                    if (e.required === true) {
                                        $(label).append($(requiredAsterisk).clone());
                                    }

                                    if (typeof e.description === 'string' && e.description.length > 0) {
                                        $(label).append($(tooltipElement).clone().attr({ tooltip: e.description }));
                                    }

                                    var divRadioGroup = $('<div></div>', { class: 'radio-group' });

                                    e.values.forEach(function (v, i) {


                                        let divRadio = $('<div></div>', { class: 'radio' });
                                        let labelRadio = $('<label></label>', { html: '&nbsp;&nbsp;&nbsp;' + v.label });
                                        let radio = $('<input />', { type: 'radio', value: v.value, name: e.name });
                                        if (e.name == "nps") {
                                            let iconLabel = "";
                                            if (i <= 6) {
                                                iconLabel = "fa-frown";
                                                iconColor = "#d90000";
                                            } else if (i > 6 && i <= 8) {
                                                iconLabel = "fa-meh";
                                                iconColor = "#f0ad4e";
                                            } else {
                                                iconLabel = "fa-smile";
                                                iconColor = "#5cb85c";
                                            }

                                            labelRadio = $('<label></label>', { html: '<div class="label label_nps_itens">' + v.label + '</div> <p style="color: ' + iconColor + ';" class="icone_nps"><i class="fa ' + iconLabel + '"></i></p>' });
                                        }
                                        if (typeof v.selected != 'undefined' && v.selected === true) {
                                            $(radio).prop({ checked: true });
                                        }

                                        if (typeof e.inline != 'undefined' && e.inline === true) {
                                            $(divRadio).css({ display: 'inline-block' });
                                        }

                                        $(labelRadio).prepend(radio);
                                        $(divRadio).append(labelRadio);
                                        $(divRadioGroup).append(divRadio);
                                    });

                                    if (typeof e.other != 'undefined' && e.other === true) {
                                        let divRadio = $('<div></div>', { class: 'radio' });
                                        let labelRadio = $('<label></label>', { html: '&nbsp;&nbsp;&nbsp;Outro:&nbsp;&nbsp;&nbsp;' });
                                        let radio = $('<input />', { type: 'radio', value: 'outro', name: e.name });
                                        let inputOther = $('<input />', { type: 'text', value: '', name: e.name + '-other', class: 'form-control input-sm', css: { display: 'inline-block', width: 'auto' } });

                                        if (typeof e.inline != 'undefined' && e.inline === true) {
                                            $(divRadio).css({ display: 'inline-block', top: '-5px' });
                                        }

                                        $(inputOther).prop({ readonly: true });
                                        $(labelRadio).prepend(radio);
                                        $(labelRadio).append(inputOther);
                                        $(divRadio).append(labelRadio);
                                        $(divRadioGroup).append(divRadio);
                                    }

                                    $(element).append(label);
                                    $(element).append(divRadioGroup);

                                    $(col).addClass('col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-10 col-lg-offset-1');
                                    break;

                                case 'starRating':
                                    var element = $(formGroup).clone();
                                    var label = $('<label></label>', { html: e.label });

                                    if (typeof e.description === 'string' && e.description.length > 0) {
                                        $(label).append($(tooltipElement).clone().attr({ tooltip: e.description }));
                                    }

                                    var divStarRating = $('<div></div>', { id: e.name, class: 'star-rating' });

                                    $(element).append(label);
                                    $(element).append(divStarRating);

                                    $(col).addClass('col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-10 col-lg-offset-1');
                                    break;
                            }

                            $(col).append(element);
                            $(row).append(col);
                            $(divFormViewer).append(row);
                        });

                        if (typeof window.fbNoActions == 'undefined' || window.fbNoActions === false) {
                            let row = $('<div class="row"></div>');
                            let col = $('<div></div>');
                            let element = $(formGroup).clone();
                            $(col).addClass('col-xs-12 col-sm-10 col-sm-offset-1 col-md-10 col-md-offset-1 col-lg-10 col-lg-offset-1 text-center');
                            $(element).append($('<button></button>', { type: 'button', class: 'btn btn-primary btn-lg fb-callback', html: '<i class=\'fa fa-save\' ></i> Gravar' }));
                            $(col).append(element);
                            $(row).append(col);
                            $(divFormViewer).append(row);
                        }

                        $(formViewer).append(divFormViewer);
                        $(fbElement).after(formViewer);

                        $(divFormViewer).find('button.fb-callback').on('click', function () {
                            let btn = $(this);

                            $(btn).prop({ disabled: true }).find('i').removeClass('fa-save').addClass('fa-spinner fa-pulse');
                            $(divFormViewer).find('.page-header').next('.alert-danger').remove();
                            $(divFormViewer).find('.has-error').removeClass('has-error');

                            let data = fbApi.getFormData();

                            window.fbCallback(data).then(
                                function (resolved) {
                                    $(divFormViewer).find('.row').hide();
                                    $(divFormViewer).append($('<div></div>', { class: 'alert alert-success text-center', html: '<h2><strong>' + resolved + '</strong></h2>' }));

                                    if (typeof window.fbCallbackFinish == 'function') {
                                        window.fbCallbackFinish(true);
                                    }
                                },
                                function (rejected) {
                                    let messages = [];

                                    for (let errorType in rejected.messages) {
                                        messages.push(rejected.messages[errorType]);
                                    }

                                    if (typeof rejected.fields == 'object') {
                                        rejected.fields.forEach(function (field, k) {
                                            let fieldObject = $(divFormViewer).find('input[name="' + field + '"], select[name="' + field + '"], textarea[name="' + field + '"]');

                                            if (['radio', 'checkbox'].indexOf($(fieldObject).attr('type'))) {
                                                let fieldObject = $(divFormViewer).find('input[name="' + field + '"]');
                                            }

                                            $.each(fieldObject, function () {
                                                if ($(this).parents('div.checkbox').length > 0) {
                                                    $(this).parents('div.checkbox').addClass('has-error');
                                                } else if ($(this).parents('div.radio').length > 0) {
                                                    $(this).parents('div.radio').addClass('has-error');
                                                } else if ($(this).parents('div.input-group').length > 0) {
                                                    $(this).parents('div.input-group').addClass('has-error');
                                                } else {
                                                    $(this).parents('div.form-group').addClass('has-error');
                                                }
                                            });
                                        });
                                    }

                                    $(divFormViewer).find('.page-header').after($('<div></div>', { class: 'alert alert-danger', html: '<strong>' + messages.join('<br />') + '</strong>' }));
                                    $('html').scrollTop($(divFormViewer).find('.alert-danger').offset().top - 50);
                                    $(btn).prop({ disabled: false }).html('<i class=\'fa fa-save\'></i> Gravar');
                                    $(btn).prop({ disabled: false }).find('i').removeClass('fa-spinner fa-pulse').addClass('fa-save');

                                    if (typeof window.fbCallbackFinish == 'function') {
                                        window.fbCallbackFinish(false);
                                    }
                                }
                            );
                        });

                        $(divFormViewer).find('img.logo').on('load', function () {
                            let logoHeight = $(divFormViewer).find('img.logo').height();
                            let logoWidth = $(divFormViewer).find('img.logo').width();

                            $(divFormViewer).find('.div-logo').css({
                                width: logoWidth + 'px',
                                height: logoHeight + 'px',
                                'margin-top': '-' + (logoHeight / 2) + 'px',
                                'margin-left': '-' + (logoWidth / 2) + 'px'
                            });

                            $(divFormViewer).find('.logo').css({
                                'max-width': logoWidth + 'px',
                                'max-height': logoHeight + 'px'
                            });

                            $(formViewer).css({ 'margin-top': (logoHeight / 2) + 'px' });
                        });

                        $(divFormViewer).find('select').select2();

                        $(divFormViewer).find('div.checkbox-group input[type=checkbox]').checkradios({
                            checkbox: {
                                iconClass: 'fa fa-check'
                            }
                        });

                        $(divFormViewer).find('input[type=checkbox]').on('change', function () {
                            if ($(this).val() == 'outro') {
                                if ($(this).is(':checked')) {
                                    $(this).parents('div.checkbox').find('input[type=text]').prop({ readonly: false });
                                } else {
                                    $(this).parents('div.checkbox').find('input[type=text]').prop({ readonly: true }).val('');
                                }
                            }
                        });

                        $(divFormViewer).find('div.radio-group input[type=radio]').checkradios({
                            radio: {
                                iconClass: 'fa fa-check'
                            }
                        });

                        $(divFormViewer).find('input[type=radio]').on('change', function () {
                            if ($(this).val() == 'outro') {
                                if ($(this).is(':checked')) {
                                    $(this).parents('div.radio').find('input[type=text]').prop({ readonly: false });
                                } else {
                                    $(this).parents('div.radio').find('input[type=text]').prop({ readonly: true }).val('');
                                }
                            }
                        });

                        $(divFormViewer).find('div.star-rating').rateYo({
                            multiColor: {
                                'startColor': '#d9534f',
                                'endColor': '#f0ad4e'
                            },
                            precision: 0,
                            maxValue: 5,
                            fullStar: true
                        });
                    }
                }

                fbApi.setFormData = function (data) {
                    if (typeof data == 'string') {
                        data = JSON.parse(data);
                    }

                    $.each(data, function (field, value) {
                        var field = $('select[name="' + field + '"], input[name="' + field + '"], textarea[name="' + field + '"], input[name="' + field + '[]"], div[id="' + field + '.star-rating"]');

                        if (field[0].nodeName == 'INPUT') {
                            if (['checkbox', 'radio'].indexOf(field[0].type) == -1) {
                                $(field).val(value);
                            } else {
                                $(field).each(function () {
                                    if (typeof value == 'object') {
                                        if (value.indexOf($(this).attr('value')) != -1) {
                                            if (!$(this).is(':checked')) {
                                                $(this).prop('checked', true).parent().removeClass('unchecked').addClass('fa fa-check checked');
                                                $(this).trigger('change');
                                            }
                                        } else {
                                            if ($(this).is(':checked')) {
                                                $(this).prop('checked', false).parent().removeClass('fa fa-check checked').addClass('unchecked');
                                                $(this).trigger('change');
                                            }
                                        }
                                    } else {
                                        if ($(this).attr('value') == value) {
                                            if (!$(this).is(':checked')) {
                                                $(this).prop('checked', true).parent().removeClass('unchecked').addClass('fa fa-check checked');
                                                $(this).trigger('change');
                                            }
                                        } else {
                                            if ($(this).is(':checked')) {
                                                $(this).prop('checked', false).parent().removeClass('fa fa-check checked').addClass('unchecked');
                                                $(this).trigger('change');
                                            }
                                        }
                                    }
                                });
                            }
                        } else if (field[0].nodeName == "SELECT") {
                            if (typeof value == 'object') {
                                value.forEach(function (v, i) {
                                    $(field).find('option[value=' + v + ']').prop('selected', true);
                                });
                            } else {
                                $(field).val(value);
                            }

                            $(field).select2();
                        } else if (field[0].nodeName == "DIV") {
                            if ($(field).hasClass('star-rating')) {
                                $(field).rateYo('rating', value);
                            }
                        } else if (field[0].nodeName == 'TEXTAREA') {
                            $(field).val(value);
                        }
                    });
                }

                fbApi.setFormReadonly = function () {
                    let data = fbApi.getData();

                    data.forEach(function (field, i) {
                        let e;

                        switch (field.type) {
                            case 'date':
                            case 'text':
                                e = $('input[name="' + field.name + '"]');
                                $(e).prop('disabled', true);
                                break;

                            case 'number':
                                e = $('input[name="' + field.name + '"]');
                                $(e).prop('disabled', true);

                                if (typeof field.tools != 'undefined' && field.tools.length > 0) {
                                    e = $('select[name="' + field.name + '-tool"]');
                                    $(e).prop('disabled', true);
                                }
                                break;

                            case 'textarea':
                                e = $('textarea[name="' + field.name + '"]');
                                $(e).prop('disabled', true);
                                break;

                            case 'select':
                                e = $('select[name="' + field.name + '"]');
                                $(e).prop('disabled', true);
                                break;

                            case 'checkbox-group':
                                e = $('input[name="' + field.name + '[]"]').not(':checked');

                                $(e).each(function () {
                                    $(this).parents('.checkbox').remove();
                                });

                                e = $('input[name="' + field.name + '[]"]');

                                $(e).each(function () {
                                    if ($(this).val() == 'outro') {
                                        $(this).parent().data('checked-other', $(this).parents('label').find('input[type=text]').val());
                                        $(this).parents('label').find('input[type=text]').prop('disabled', true);
                                    }

                                    $(this).parent().on('click', function () {
                                        $(this).removeClass('unchecked').addClass('fa fa-check checked');
                                        $(this).find('input[type=checkbox]').prop('checked', true);

                                        let checkedOther = $(this).data('checked-other');

                                        if (checkedOther) {
                                            $(this).parent().find('input[type=text]').val(checkedOther).prop('disabled', true);
                                        }
                                    });
                                });
                                break;

                            case 'radio-group':
                                e = $('input[name="' + field.name + '"]').not(':checked');

                                $(e).each(function () {
                                    $(this).parents('.radio').remove();
                                });

                                e = $('input[name="' + field.name + '"]');

                                if ($(e).val() == 'outro') {
                                    $(e).parents('label').find('input[type=text]').prop('disabled', true);
                                }
                                break;

                            case 'starRating':
                                $("div[id='" + field.name + "']").rateYo('option', 'readOnly', true);
                                break;
                        }
                    });
                }

                if (typeof window.fbData === 'string') {
                    fbApi.setData(window.fbData);
                }

                if (window.fbEditing === false) {
                    fbApi.toggleEdit(false);
                }

                resolve(true);
            })();
        } catch (error) {
            reject(error);
        }
    });
}