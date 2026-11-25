<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Observer;

use Infrangible\CatalogProductPOS\Helper\Data;
use Infrangible\CatalogProductSwatches\Model\SwatchAttributes;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Shows the point of sale of the selected variant of configurable products with the swatches.
 */
class CatalogProductSwatchAttributes implements ObserverInterface
{
    /**
     * @param Data $helper
     */
    public function __construct(protected Data $helper)
    {
    }

    /**
     * Adds the point-of-sale attribute to the attributes that the swatches show.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $attributeCode = $this->helper->getAttributeCode();
        $product = $observer->getEvent()->getData('product');
        $swatchAttributes = $observer->getEvent()->getData('swatch_attributes');

        if ($attributeCode !== null && $product instanceof Product && $swatchAttributes instanceof SwatchAttributes) {
            $swatchAttributes->addAttribute(
                $product,
                $attributeCode,
                '.catalog-product-pos-attribute',
                'catalog-product-pos-template',
                $this->helper->getValuesSeparator()
            );
        }
    }
}
