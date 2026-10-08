/*jshint browser:true jquery:true*/
define([
    'jquery'
], function ($) {
    'use strict';

    function parseParameters(value) {
        var result = {};

        if (!value) {
            return result;
        }

        value.replace(/^[?#]/, '').split('&').forEach(function (pair) {
            var parts;
            var key;

            if (!pair) {
                return;
            }
            parts = pair.split('=');
            try {
                key = decodeURIComponent(parts.shift().replace(/\+/g, ' '));
                result[key] = decodeURIComponent(parts.join('=').replace(/\+/g, ' '));
            } catch (error) {
                // Ignore malformed URL encoding without interrupting product selection.
            }
        });

        return result;
    }

    function getUrlParameters() {
        if (window.location.hash) {
            return parseParameters(window.location.hash);
        }

        return parseParameters(window.location.search);
    }

    function getSelectAttributeId(element) {
        var name = element.attr('name') || '';
        var match = name.match(/super_attribute\[(\d+)\]/);

        return match ? match[1] : (element.attr('id') || '').replace('attribute', '');
    }

    var ShoppingFeedConfigurable = function (productConfig, associatedProducts, tagConfig) {
        this.productConfig = productConfig;
        this.associatedProducts = associatedProducts || {};
        this.tagConfig = tagConfig || {};
        this.lastProductId = null;
        this.parameters = getUrlParameters();
        this.registerEvents();
        this.selectUrlOptions();
    };

    ShoppingFeedConfigurable.prototype = {
        registerEvents: function () {
            var self = this;

            // Magento may populate options after this module's DOM-ready callback.
            $('#product_addtocart_form').on(
                'configurable.initialized.mageosShoppingFeed swatch.initialized.mageosShoppingFeed',
                function () {
                    self.selectUrlOptions();
                }
            );
            $('.super-attribute-select').on('change.mageosShoppingFeed', function () {
                self.update();
            });
            $('.swatch-attribute').on('click.mageosShoppingFeed', '.swatch-option', function () {
                window.setTimeout(function () {
                    self.update();
                }, 0);
            });
        },

        selectUrlOptions: function () {
            var self = this;
            var delay = 0;

            $('.swatch-attribute').each(function () {
                var attribute = $(this);
                var attributeId = String(attribute.attr('attribute-id'));
                var optionId = self.parameters[attributeId];
                var option;

                if (typeof optionId !== 'string' || !/^\d+$/.test(optionId)) {
                    return;
                }
                option = attribute.find('.swatch-option').filter(function () {
                    return $(this).attr('option-id') === optionId;
                });
                if (option.length) {
                    delay += 100;
                    window.setTimeout(function () {
                        option.first().trigger('click');
                    }, delay);
                }
            });

            $('.super-attribute-select').each(function () {
                var select = $(this);
                var attributeId = String(getSelectAttributeId(select));
                var optionId = self.parameters[attributeId];

                if (typeof optionId === 'string' && /^\d+$/.test(optionId) && select.find('option').filter(function () {
                    return this.value === optionId;
                }).length) {
                    select.val(optionId).trigger('change');
                }
            });
        },

        getSelectedAttributes: function () {
            var selected = {};

            $('.super-attribute-select').each(function () {
                var select = $(this);
                var value = select.val();

                if (value) {
                    selected[String(getSelectAttributeId(select))] = String(value);
                }
            });
            $('.swatch-attribute').each(function () {
                var attribute = $(this);
                var value = attribute.attr('option-selected');

                if (value) {
                    selected[String(attribute.attr('attribute-id'))] = String(value);
                }
            });

            return selected;
        },

        findProductId: function (selected) {
            var selectedKeys = Object.keys(selected);
            var requiredCount = Object.keys(this.productConfig.attributes || {}).length;
            var productId;
            var attributeId;
            var matches;

            if (selectedKeys.length !== requiredCount) {
                return null;
            }

            for (productId in this.productConfig.index) {
                if (!Object.prototype.hasOwnProperty.call(this.productConfig.index, productId)) {
                    continue;
                }
                matches = true;
                for (attributeId in selected) {
                    if (Object.prototype.hasOwnProperty.call(selected, attributeId) &&
                        String(this.productConfig.index[productId][attributeId]) !== selected[attributeId]) {
                        matches = false;
                        break;
                    }
                }
                if (matches) {
                    return productId;
                }
            }

            return null;
        },

        update: function () {
            var productId = this.findProductId(this.getSelectedAttributes());
            var optionPrices;
            var itemId;
            var eventData;

            if (!productId || productId === this.lastProductId) {
                return;
            }
            this.lastProductId = productId;
            optionPrices = this.productConfig.optionPrices[productId] || {};
            itemId = this.associatedProducts[productId] || productId;
            eventData = {
                value: optionPrices.finalPrice ? Number(optionPrices.finalPrice.amount) : 0,
                items: [{
                    id: itemId,
                    google_business_vertical: 'retail'
                }]
            };

            window.dispatchEvent(new CustomEvent('mageos:shopping-feed:view-item', {
                detail: eventData
            }));

            if (this.tagConfig.enabled && typeof window.gtag === 'function') {
                if (this.tagConfig.destinationId) {
                    eventData.send_to = this.tagConfig.destinationId;
                }
                window.gtag('event', 'view_item', eventData);
            }
        }
    };

    return ShoppingFeedConfigurable;
});
