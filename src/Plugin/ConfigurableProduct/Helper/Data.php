<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPOS\Plugin\ConfigurableProduct\Helper;

use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Data
{
    /** @var Stores */
    protected $storeHelper;

    /** @var \Infrangible\CatalogProductPOS\Helper\Data */
    protected $helper;

    public function __construct(Stores $storeHelper, \Infrangible\CatalogProductPOS\Helper\Data $helper)
    {
        $this->storeHelper = $storeHelper;
        $this->helper = $helper;
    }

    public function afterGetOptions(
        \Magento\ConfigurableProduct\Helper\Data $subject,
        array $options,
        Product $currentProduct,
        array $allowedProducts
    ): array {
        if (! $this->storeHelper->getStoreConfigFlag('cataloginventory/options/show_out_of_stock')) {
            $allowAttributes = $subject->getAllowAttributes($currentProduct);

            /** @var Product $product */
            foreach ($allowedProducts as $product) {
                $productId = $product->getId();

                foreach ($allowAttributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $productAttributeId = $productAttribute->getId();
                    $attributeValue = $product->getData($productAttribute->getAttributeCode());

                    if (! $product->isSalable() && $this->helper->isNotSaleable($product)) {
                        $options[ $productAttributeId ][ $attributeValue ][] = $productId;
                    }
                }
            }
        }

        return $options;
    }
}
