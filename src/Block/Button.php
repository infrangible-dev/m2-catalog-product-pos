<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Block;

use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPOS\Helper\Data;
use Infrangible\Core\Helper\Registry;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\View\Element\Template;

/**
 * The button that products which cannot be bought online show instead of the add-to-cart button, and the data of the
 * variants of configurable products for the button.
 */
class Button extends Attribute
{
    /**
     * @param Template\Context $context
     * @param Registry $registryHelper
     * @param Data $helper
     * @param Variables $variables
     * @param Json $json
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registryHelper,
        Data $helper,
        protected Variables $variables,
        protected Json $json,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registryHelper,
            $helper,
            $data
        );
    }

    /**
     * Checks whether the product of the page can be bought online.
     *
     * @return bool
     */
    public function isSaleable(): bool
    {
        return $this->getProduct()?->isSaleable() === true;
    }

    /**
     * Checks whether the product of the page cannot be bought online because of its point of sale.
     *
     * @return bool
     */
    public function isNotSaleable(): bool
    {
        $product = $this->getProduct();

        return $product !== null && $this->helper->isNotSaleable($product);
    }

    /**
     * Checks whether the product of the page shows the button.
     *
     * @return bool
     */
    public function showButton(): bool
    {
        $product = $this->getProduct();

        return $product !== null && $this->helper->showButton($product);
    }

    /**
     * Returns the text of the button.
     *
     * @return string|null
     */
    public function getButtonText(): ?string
    {
        $product = $this->getProduct();

        return $product === null ? null : $this->helper->getButtonText($product);
    }

    /**
     * Returns the link of the button.
     *
     * @return string|null
     */
    public function getButtonUrl(): ?string
    {
        $product = $this->getProduct();

        return $product === null ? null : $this->helper->getButtonUrl($product);
    }

    /**
     * Returns the code of the attribute as CSS class of the button.
     *
     * @return string|null
     */
    public function getAttributeCodeClass(): ?string
    {
        return sprintf(
            'button-%s',
            parent::getAttributeCodeClass()
        );
    }

    /**
     * Returns the value of the product as CSS class of the button.
     *
     * @return string|null
     */
    public function getAttributeValueClass(): ?string
    {
        return sprintf(
            'button-%s',
            parent::getAttributeValueClass()
        );
    }

    /**
     * Returns the data of the enabled variants of a configurable product for the button as JSON, by product ID.
     *
     * @return string
     */
    public function getChildProductData(): string
    {
        $childProductData = [];

        $product = $this->getProduct();
        $typeInstance = $product?->getTypeInstance();

        if ($product !== null && $typeInstance instanceof Configurable) {
            $childProducts = [];

            foreach ($typeInstance->getUsedProducts($product) as $childProduct) {
                if ($childProduct instanceof Product &&
                    $this->variables->intValue($childProduct->getStatus()) === Status::STATUS_ENABLED) {

                    $childProducts[] = $childProduct;
                }
            }

            $this->helper->loadAttributeOptionIds($childProducts);

            foreach ($childProducts as $childProduct) {
                $childProductData[ $this->variables->intValue($childProduct->getId()) ] = [
                    'isSaleable'          => $childProduct->isSaleable(),
                    'isNotSaleable'       => $this->helper->isNotSaleable($childProduct),
                    'showButton'          => $this->helper->showButton($childProduct),
                    'buttonText'          => $this->helper->getButtonText($childProduct),
                    'buttonUrl'           => $this->helper->getButtonUrl($childProduct),
                    'attributeCodeClass'  => $this->helper->getAttributeCodeClass(),
                    'attributeValueClass' => $this->helper->getAttributeValueClass($childProduct)
                ];
            }
        }

        return $this->json->encode($childProductData) ?? '{}';
    }
}
