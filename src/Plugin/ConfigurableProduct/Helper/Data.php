<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Plugin\ConfigurableProduct\Helper;

use Infrangible\CatalogProductPOS\Helper\Data as Helper;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Helper\Data as Subject;

/**
 * Keeps the variants that cannot be bought online because of their point of sale selectable, so shoppers see their
 * point of sale and button. Magento leaves out variants that are not saleable unless the store shows products that
 * are out of stock.
 */
class Data
{
    /**
     * @param Helper $helper
     * @param Stores $storeHelper
     */
    public function __construct(protected Helper $helper, protected Stores $storeHelper)
    {
    }

    /**
     * Adds the variants that are not saleable because of their point of sale to the options of the attributes.
     *
     * @param Subject $subject
     * @param array $options
     * @param Product $currentProduct
     * @param array $allowedProducts
     * @phpstan-param array<int|string, mixed> $options
     * @phpstan-param array<int|string, mixed> $allowedProducts
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function afterGetOptions(
        Subject $subject,
        array $options,
        Product $currentProduct,
        array $allowedProducts
    ): array {
        if ($this->storeHelper->getStoreConfigFlag('cataloginventory/options/show_out_of_stock')) {
            return $options;
        }

        $products = array_filter($allowedProducts, static fn(mixed $product): bool => $product instanceof Product);

        $this->helper->loadAttributeOptionIds($products);

        $allowAttributes = $subject->getAllowAttributes($currentProduct);

        foreach ($products as $product) {
            if ($product->isSalable() || ! $this->helper->isNotSaleable($product)) {
                continue;
            }

            foreach ($allowAttributes as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $attributeValue = $product->getData($productAttribute->getAttributeCode());

                $productAttributeId = $productAttribute->getId();

                if (! is_scalar($attributeValue) || ! is_scalar($productAttributeId)) {
                    continue;
                }

                $attributeOptions = $options[ $productAttributeId ] ?? [];
                $attributeOptions = is_array($attributeOptions) ? $attributeOptions : [];
                $productIds = $attributeOptions[ $attributeValue ] ?? [];
                $productIds = is_array($productIds) ? $productIds : [];

                $productIds[] = $product->getId();
                $attributeOptions[ $attributeValue ] = $productIds;
                $options[ $productAttributeId ] = $attributeOptions;
            }
        }

        return $options;
    }
}
