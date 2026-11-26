/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'mage/template'
], function ($, template) {
    'use strict';

    /**
     * The data of a variant of a configurable product, from Block\Button::getChildProductData().
     *
     * @typedef {Object} CatalogProductPosChildData
     * @property {boolean} isSaleable whether the variant can be bought online
     * @property {boolean} isNotSaleable whether the point of sale of the variant forbids buying it online
     * @property {boolean} showButton whether the variant shows the button
     * @property {?string} buttonText
     * @property {?string} buttonUrl
     * @property {?string} attributeCodeClass
     * @property {?string} attributeValueClass
     */

    return $.widget('mage.catalogProductPos', {
        options: {
            /** @type {Object<string, CatalogProductPosChildData>} the variants by product ID */
            childProductData: {}
        },

        _init: function () {
            const childProductData = this.options.childProductData;

            $('.column.main').on('swatch.changed', function (event, selectedProductId) {
                /** @type {CatalogProductPosChildData|undefined} */
                const selectedProductData = selectedProductId ? childProductData[selectedProductId] : undefined,
                    addToCartNode = $('[class="box-tocart"]');
                let posButtonBoxNode = $('.box-tocart.catalog-product-pos-button-box');

                if (!selectedProductData || selectedProductData.isSaleable) {
                    posButtonBoxNode.remove();
                    addToCartNode.show();

                    return;
                }

                addToCartNode.hide();

                if (!selectedProductData.isNotSaleable || !selectedProductData.showButton) {
                    posButtonBoxNode.remove();

                    return;
                }

                if (posButtonBoxNode.length === 0) {
                    posButtonBoxNode = $('<div>', {class: 'box-tocart catalog-product-pos-button-box'});
                    $('.product-options-bottom').append(posButtonBoxNode);
                }

                posButtonBoxNode.html(template($('#catalog-product-pos-button-template').html(), {
                    'buttonText': selectedProductData.buttonText || '',
                    'attributeCodeClass': selectedProductData.attributeCodeClass || '',
                    'attributeValueClass': selectedProductData.attributeValueClass || ''
                }));

                // the link is set here instead of in the template, where PhpStorm resolves it as a file
                posButtonBoxNode.find('#product-button-pos').attr('href', selectedProductData.buttonUrl || '');
            });
        }
    });
});
