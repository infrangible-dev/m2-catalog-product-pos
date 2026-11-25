<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Block;

use Infrangible\CatalogProductPOS\Helper\Data;
use Infrangible\Core\Helper\Registry;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;

/**
 * The value of the point-of-sale attribute of the product on the product page.
 */
class Attribute extends Template
{
    /**
     * @param Template\Context $context
     * @param Registry $registryHelper
     * @param Data $helper
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        protected Registry $registryHelper,
        protected Data $helper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Returns the product of the page.
     *
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        $product = $this->registryHelper->registry('product');

        return $product instanceof Product ? $product : null;
    }

    /**
     * Returns the code of the point-of-sale attribute.
     *
     * @return string|null
     */
    public function getAttributeCode(): ?string
    {
        return $this->helper->getAttributeCode();
    }

    /**
     * Returns the code of the attribute as CSS class.
     *
     * @return string|null
     */
    public function getAttributeCodeClass(): ?string
    {
        return $this->helper->getAttributeCodeClass();
    }

    /**
     * Returns the labels of the options of the product.
     *
     * @return string|null
     */
    public function getAttributeValue(): ?string
    {
        $product = $this->getProduct();

        return $product === null ? null : $this->helper->getAttributeValue($product);
    }

    /**
     * Returns the value of the product as CSS class.
     *
     * @return string|null
     */
    public function getAttributeValueClass(): ?string
    {
        $product = $this->getProduct();

        return $product === null ? null : $this->helper->getAttributeValueClass($product);
    }
}
