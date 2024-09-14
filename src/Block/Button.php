<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPOS\Block;

use Exception;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPOS\Helper\Data;
use Infrangible\Core\Helper\Registry;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\View\Element\Template;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Button extends Attribute
{
    /** @var Variables */
    protected $variables;

    /** @var Json */
    protected $json;

    public function __construct(
        Template\Context $context,
        Registry $registryHelper,
        Data $helper,
        Variables $variables,
        Json $json,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registryHelper,
            $helper,
            $data
        );

        $this->variables = $variables;
        $this->json = $json;
    }

    public function isNotSaleable(): bool
    {
        $product = $this->getProduct();

        return $product && $this->helper->isNotSaleable($product);
    }

    public function showButton(): bool
    {
        $product = $this->getProduct();

        return $product && $this->helper->showButton($product);
    }

    public function getButtonText(): ?string
    {
        $product = $this->getProduct();

        return $product ? $this->helper->getButtonText($product) : null;
    }

    public function getButtonUrl(): ?string
    {
        $product = $this->getProduct();

        return $product ? $this->helper->getButtonUrl($product) : null;
    }

    public function getAttributeCodeClass(): ?string
    {
        return sprintf(
            'button-%s',
            parent::getAttributeCodeClass()
        );
    }

    public function getAttributeValueClass(): ?string
    {
        return sprintf(
            'button-%s',
            parent::getAttributeValueClass()
        );
    }

    public function getChildProductData(): string
    {
        $childProductData = [];

        $product = $this->getProduct();

        if ($product && $product->getTypeId() === Configurable::TYPE_CODE) {
            $typeInstance = $product->getTypeInstance();

            if ($typeInstance instanceof Configurable) {
                $attributeCode = $this->helper->getAttributeCode();

                if ($attributeCode) {
                    $allProducts = $typeInstance->getUsedProducts(
                        $product,
                        [$attributeCode]
                    );
                } else {
                    $allProducts = $typeInstance->getUsedProducts($product);
                }

                /** @var Product $childProduct */
                foreach ($allProducts as $childProduct) {
                    if ((int)$childProduct->getStatus() === Status::STATUS_ENABLED) {
                        try {
                            $productId = $this->variables->intValue($childProduct->getId());

                            $childProductData[ $productId ] = [
                                'isSaleable'          => $childProduct->isSaleable(),
                                'isNotSaleable'       => $this->helper->isNotSaleable($childProduct),
                                'showButton'          => $this->helper->showButton($childProduct),
                                'buttonText'          => $this->helper->getButtonText($childProduct),
                                'buttonUrl'           => $this->helper->getButtonUrl($childProduct),
                                'attributeCodeClass'  => $this->helper->getAttributeCodeClass(),
                                'attributeValueClass' => $this->helper->getAttributeValueClass($childProduct)
                            ];
                        } catch (Exception $exception) {
                        }
                    }
                }
            }
        }

        return $this->json->encode($childProductData);
    }
}
