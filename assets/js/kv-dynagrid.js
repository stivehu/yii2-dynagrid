/*!
 * @package   yii2-dynagrid
 * @author    Kartik Visweswaran <kartikv2@gmail.com>
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2015 - 2017
 * @version   1.4.6
 *
 * JQuery Plugin for yii2-dynagrid.
 * 
 * Author: Kartik Visweswaran
 * Copyright: 2015, Kartik Visweswaran, Krajee.com
 * For more JQuery plugins visit http://plugins.krajee.com
 * For more Yii related demos visit http://demos.krajee.com
 */
(function ($) {
    "use strict";
    var isEmpty = function (value, trim) {
            return value === null || value === undefined || value === [] || value === '' || trim && $.trim(value) === '';
        },
        getFormObjectId = function ($element) {
            return $element.attr('name').toLowerCase().replace(/-/g, '_') + '_activeform';
        },
        cacheActiveForm = function ($element) {
            var $form = $element.closest('form'), objActiveForm = $form.data('yiiActiveForm'),
                id = getFormObjectId($element);
            if (isEmpty(id) || isEmpty(objActiveForm)) {
                return;
            }
            window[id] = objActiveForm;
        },
        Dynagrid = function (element, options) {
            var self = this;
            self.$element = $(element);
            $.each(options, function (key, value) {
                self[key] = value;
            });
            self.init();
            self.listen();
        };

    Dynagrid.prototype = {
        constructor: Dynagrid,
        init: function () {
            var self = this, $modal = $('#' + self.modalId), $dynaGridId = $('#' + self.dynaGridId),
                obj = getFormObjectId(self.$element), $form = self.$element.closest('form');
            self.$form = $form;
            if (isEmpty(window[obj])) {
                cacheActiveForm(self.$element);
            }
            $modal.insertAfter($dynaGridId);
            self.$visibleEl = $form.find(".sortable-visible");
            self.$hiddenEl = $form.find(".sortable-hidden");
            self.$visibleKeys = $form.find('input[name="visibleKeys"]');
            self.$btnSubmit = $modal.find('.dynagrid-submit');
            self.$btnDelete = $modal.find('.dynagrid-delete');
            self.$btnReset = $modal.find('.dynagrid-reset');
            self.$btnOpen = $form.find('.dynagrid-detail-open');            
            self.$btnLoadGrid = $form.find('.dynagrid-detail-loadgrid');
            self.$btnFastGrid = $form.find('.dynagrid-detail-fastgrid');
            self.$btnSaveGrid = $form.find('.dynagrid-detail-savegrid');
            self.$btnRemoveGrid = $form.find('.dynagrid-detail-removegrid');
            self.$formContainer = $form.parent();            
            self.setColumnKeys();
            self.visibleContent = self.$visibleEl.html();
            self.hiddenContent = self.$hiddenEl.html();
            self.visibleSortableOptions = window[self.$visibleEl.attr('data-krajee-sortable')];
            self.hiddenSortableOptions = window[self.$hiddenEl.attr('data-krajee-sortable')];
        },
        listen: function () {
            var self = this, $form = self.$form, $formContainer = self.$formContainer;
            self.$btnSubmit.off('click').on('click', function () {
                self.setColumnKeys();
                self.$visibleKeys.val(self.visibleKeys);
                $form.hide();
                $formContainer.prepend(self.submitMessage);
                setTimeout(function () {
                    $form.submit();
                }, 1000);
            });
            self.$btnFastGrid.off('click').on('click', function () {
                var url = window.location.href;
                    if (url.indexOf('?') > -1){
                        url += '&fastload='+$("#dynagridconfig-savedid").val();
                    }else{
                        url += '?fastload='+$("#dynagridconfig-savedid").val();
                    }
                window.location.href = url;
            });
            self.$btnLoadGrid.off('click').on('click', function () {
                $.ajax({
                    type: 'post',
                    url: self.configLoadUrl,
                    dataType: 'json',
                    data: {_csrf: $('input[name=_csrf]').val(),
                        DynaGridSettings: {
                            savedId: $("#dynagridconfig-savedid").val(),
                            name: $("#dynagridconfig-savedid :selected").text(),
                            category: "saved",
                            storage: $('[name="DynaGridSettings[storage]"]').val(),
                            userSpecific: $('[name="DynaGridSettings[userSpecific]"]').val(),
                            dynaGridId: $('[name="DynaGridSettings[dynaGridId]"]').val()
                        }
                    },
                    success: function (data) {
                        if (data.status === 'success' && $.isArray(data.content.keys)) {
                            //hide all items
                            $(self.$visibleEl).find('li[aria-grabbed=false]').each(function () {
                                var litem = $(this).clone(true);
                                litem.appendTo("#" + self.$hiddenEl.attr('id'));
                                $(this).remove();
                            });
                            //show items from saved
                            $.each(data.content.keys, function (value, key) {
                                var litem = $('#' + key).clone(true);
                                $('#' + key).remove();
                                litem.appendTo("#" + self.$visibleEl.attr('id'));
                            });
                            self.$form.find('input[name="DynaGridConfig[pageSize]"]').val(data.content.page);
                            self.$form.find('[name="DynaGridConfig[theme]"]').val(data.content.theme).change();
                            self.$form.find('[name="DynaGridConfig[filterId]"]').val(data.content.filterId).change();
                            self.$form.find('[name="DynaGridConfig[sortId]"]').val(data.content.sortId).change();
                        }
                    }
                });
            });
            self.$btnSaveGrid.off('click').on('click', function () {
               krajeeDialogCust.prompt({label:self.saveMessage, placeholder:self.saveMessage}, function (result) {
                if (result) {
                    self.setColumnKeys();
                    self.$visibleKeys.val(self.visibleKeys);
                    $form.hide();
                    $formContainer.prepend(self.submitMessage);
                    setTimeout(function () {
                        $form.find('[name="DynaGridConfig[savedName]"]').val(result);
                        $form.submit();
                    }, 1000);
                }
            });
            });
            self.$btnRemoveGrid.off('click').on('click',function(){
                if (!window.confirm(self.deleteConfirmation)) {
                    return;
                }                
                $.ajax({
                    type: 'post',
                    url: self.configRemoveUrl,
                    dataType: 'json',
                    data: {_csrf: $('input[name=_csrf]').val(),
                            savedId: $("#dynagridconfig-savedid").val(),
                            category: "saved",
                            storage: $('[name="DynaGridSettings[storage]"]').val(),                            
                            dynaGridId: $('[name="DynaGridSettings[dynaGridId]"]').val()
                    },
                    success: function (data) {
                        if (data.status === 'success' ) {                            
                            $("#dynagridconfig-savedid").find("option[value='" + $("#dynagridconfig-savedid").val() + "']").remove();
                        }
                    }
                });
                
            });
    
            self.$btnDelete.off('click').on('click', function () {
                if (!window.confirm(self.deleteConfirmation)) {
                    return;
                }
                var $el = $form.find('input[name="deleteFlag"]');
                $el.val(1);
                $form.hide();
                $formContainer.prepend(self.deleteMessage);
                setTimeout(function () {
                    $form.submit();
                }, 1000);
            });
            self.$btnReset.off('click').on('click', function () {
                self.$visibleEl.sortable('destroy').html(self.visibleContent);
                self.$hiddenEl.sortable('destroy').html(self.hiddenContent);
                self.setColumnKeys();
                $formContainer.find('.dynagrid-submit-message').remove();
                self.$visibleEl.sortable(self.visibleSortableOptions);
                self.$hiddenEl.sortable(self.hiddenSortableOptions);
                $form.trigger('reset.yiiActiveForm');
                setTimeout(function () {
                    $form.find("select").trigger("change");
                }, 100);
            });
            $form.off('afterValidate').on('afterValidate', function (e, msg) {
                $.each(msg, function (key, value) {
                    if (value.length > 0) {
                        $form.show();
                        $formContainer.find('.dynagrid-submit-message').remove();
                        return true;
                    }
                });
            });

        },
        reset: function () {
            var self = this, $form = self.$element.closest('form'),
                id = getFormObjectId(self.$element), objActiveForm = window[id];
            if (!isEmpty(objActiveForm)) {
                $form.yiiActiveForm('destroy');
                $form.yiiActiveForm(objActiveForm.attributes, objActiveForm.settings);
            }
            $form.find('select[data-krajee-select2]').each(function () {
                var $el = $(this), settings = window[$el.attr('data-krajee-select2')] || {};
                if ($el.data('select2')) {
                    $el.select2('destroy');
                }
                $.when($el.select2(settings)).done(function () {
                    initS2Loading($el.attr('id'), '.select2-container--krajee'); // jshint ignore:line
                });
            });
            $form.find('[data-krajee-sortable]').each(function () {
                var $el = $(this);
                if ($el.data('sortable')) {
                    $el.sortable('destroy');
                }
                $el.sortable(window[$el.attr('data-krajee-sortable')]);
            });
        },
        setColumnKeys: function () {
            var self = this;
            self.visibleKeys = self.$visibleEl.find('li').map(function (i, n) {
                return $(n).attr('id');
            }).get().join(',');

        }
    };

    // dynagrid plugin definition
    $.fn.dynagrid = function (option) {
        var args = Array.apply(null, arguments);
        args.shift();
        return this.each(function () {
            var $this = $(this), data = $this.data('dynagrid'), options = typeof option === 'object' && option;

            if (!data) {
                data = new Dynagrid(this, $.extend({}, $.fn.dynagrid.defaults, options, $(this).data()));
                $this.data('dynagrid', data);
            }

            if (typeof option === 'string') {
                data[option].apply(data, args);
            }
        });
    };

    $.fn.dynagrid.defaults = {
        submitMessage: '',
        deleteMessage: '',
        deleteConfirmation: 'Are you sure you want to delete all your grid personalization settings?',
        modalId: '',
        saveMessage: 'Please type grid name',
        configLoadUrl: '',
        dynaGridId: '',
        configRemoveUrl: ''
    };
}(window.jQuery));