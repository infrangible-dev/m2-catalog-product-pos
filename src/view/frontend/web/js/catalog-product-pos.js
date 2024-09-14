/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'mage/template'
], function ($, template) {
    'use strict';

    var globalOptions = {
        childProductData: {}
    };

    $.widget('mage.catalogProductPos', {
        options: globalOptions,

        _init: function initCatalogProductPos() {
            var childProductData = this.options.childProductData;

            $('.column.main').on('swatch.changed', function (event, selectedProductId) {
                if (selectedProductId) {
                    var selectedProductData = childProductData[selectedProductId];

                    if (selectedProductData.isSaleable) {
                        $('.box-tocart.catalog-product-pos-button-box').remove();
                        $('[class="box-tocart"]').show();
                    } else {
                        $('[class="box-tocart"]').hide();

                        if (selectedProductData.isNotSaleable && selectedProductData.showButton) {
                            var posButtonBoxNode = $('.box-tocart.catalog-product-pos-button-box');
                            if (posButtonBoxNode.length === 0) {
                                posButtonBoxNode = $('<div>', {class: 'box-tocart catalog-product-pos-button-box'});
                                $('.product-options-bottom').append(posButtonBoxNode);
                            }

                            posButtonBoxNode.html(template($('#catalog-product-pos-button-template').html(), {
                                'buttonText': selectedProductData.buttonText,
                                'buttonUrl': selectedProductData.buttonUrl,
                                'attributeCodeClass': selectedProductData.attributeCodeClass,
                                'attributeValueClass': selectedProductData.attributeValueClass
                            }));
                        } else {
                            $('.box-tocart.catalog-product-pos-button-box').remove();
                        }
                    }
                } else {
                    $('[class="box-tocart"]').show();
                }
            });
        },

        _create: function createCatalogProductPos() {
        }
    });

    return $.mage.catalogProductPos;
});
