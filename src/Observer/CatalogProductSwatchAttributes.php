<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPOS\Observer;

use Infrangible\CatalogProductPOS\Helper\Data;
use Infrangible\CatalogProductSwatches\Model\SwatchAttributes;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CatalogProductSwatchAttributes implements ObserverInterface
{
    /** @var Data */
    protected $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function execute(Observer $observer)
    {
        $attributeCode = $this->helper->getAttributeCode();

        if ($attributeCode) {
            /** @var Product $product */
            $product = $observer->getEvent()->getData('product');

            /** @var SwatchAttributes $swatchAttributes */
            $swatchAttributes = $observer->getEvent()->getData('swatch_attributes');

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
