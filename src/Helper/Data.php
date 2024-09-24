<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPOS\Helper;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Data
{
    /** @var Stores */
    protected $storeHelper;

    /** @var \Infrangible\Core\Helper\Attribute */
    protected $attributeHelper;

    /** @var Variables */
    protected $variables;

    /** @var Arrays */
    protected $arrays;

    /** @var int|null */
    private $attributeId = null;

    /** @var Attribute|null */
    private $attribute = null;

    /** @var string|null */
    private $attributeCode = null;

    /** @var array<int, int|null> */
    private $attributeOptionIds = [];

    /** @var string[]|null */
    private $attributeOptions = null;

    /** @var array<int, mixed> */
    private $attributeValues = [];

    /** @var string|null */
    private $valuesSeparator = null;

    public function __construct(
        Stores $storeHelper,
        \Infrangible\Core\Helper\Attribute $attributeHelper,
        Variables $variables,
        Arrays $arrays
    ) {
        $this->storeHelper = $storeHelper;
        $this->attributeHelper = $attributeHelper;
        $this->variables = $variables;
        $this->arrays = $arrays;
    }

    public function getAttributeId(): ?int
    {
        if ($this->attributeId === null) {
            $attributeId = $this->storeHelper->getStoreConfig('infrangible_catalogproductpos/attribute/attribute');

            if ($attributeId) {
                try {
                    $this->attributeId = $this->variables->intValue($attributeId);
                } catch (\Exception $exception) {
                }
            }
        }

        return $this->attributeId;
    }

    public function getAttribute(): ?Attribute
    {
        if ($this->attribute === null) {
            $attributeId = $this->getAttributeId();

            if ($attributeId) {
                try {
                    $this->attribute = $this->attributeHelper->getAttribute(
                        Product::ENTITY,
                        (string)$attributeId
                    );
                } catch (\Exception $exception) {
                }
            }
        }

        return $this->attribute;
    }

    public function getAttributeCode(): ?string
    {
        if ($this->attributeCode === null) {
            $attribute = $this->getAttribute();

            if ($attribute) {
                $this->attributeCode = $attribute->getAttributeCode();
            }
        }

        return $this->attributeCode;
    }

    public function getAttributeCodeClass(): ?string
    {
        $attributeCode = $this->getAttributeCode();

        if ($attributeCode) {
            return strtolower(
                preg_replace(
                    '/[^A-Za-z0-9]/',
                    '_',
                    $attributeCode
                )
            );
        }

        return null;
    }

    /**
     * @return int[]
     */
    public function getAttributeOptionIds(Product $product): array
    {
        try {
            $productId = $this->variables->intValue($product->getId());

            if (! array_key_exists(
                $productId,
                $this->attributeOptionIds
            )) {
                $attributeCode = $this->getAttributeCode();

                if ($attributeCode) {
                    $attributeOptionIds = $product->getData($attributeCode);

                    $attributeOptionIds = $this->variables->isEmpty($attributeOptionIds) ? [] : explode(
                        ',',
                        $attributeOptionIds
                    );

                    if (! $this->variables->isEmpty($attributeOptionIds)) {
                        $this->attributeOptionIds[ $productId ] = [];

                        try {
                            foreach ($attributeOptionIds as $attributeOptionId) {
                                $this->attributeOptionIds[ $productId ][] =
                                    $this->variables->intValue($attributeOptionId);
                            }
                        } catch (Exception $exception) {
                        }
                    } else {
                        $this->attributeOptionIds[ $productId ] = [];
                    }
                }
            }

            return $this->attributeOptionIds[ $productId ];
        } catch (Exception $exception) {
        }

        return [];
    }

    /**
     * @return string[]|null
     */
    public function getAttributeOptions(): array
    {
        if ($this->attributeOptions === null) {
            $this->attributeOptions = [];

            $attribute = $this->getAttribute();

            if ($attribute) {
                try {
                    foreach ($attribute->getSource()->getAllOptions() as $option) {
                        $optionId = $this->arrays->getValue(
                            $option,
                            'value'
                        );
                        $optionLabel = $this->arrays->getValue(
                            $option,
                            'label'
                        );

                        if ($optionId) {
                            $this->attributeOptions[ $this->variables->intValue($optionId) ] = $optionLabel;
                        }
                    }
                } catch (\Exception $exception) {
                }
            }
        }

        return $this->attributeOptions;
    }

    public function getAttributeValue(Product $product): ?string
    {
        try {
            $productId = $this->variables->intValue($product->getId());

            if (! array_key_exists(
                $productId,
                $this->attributeValues
            )) {
                $attributeOptionIds = $this->getAttributeOptionIds($product);

                if (! $this->variables->isEmpty($attributeOptionIds)) {
                    $attributeCode = $this->getAttributeCode();

                    if ($attributeCode) {
                        $storeId = $this->variables->intValue($this->storeHelper->getStore()->getId());

                        $attributeValue = $this->attributeHelper->getAttributeOptionValue(
                            Product::ENTITY,
                            $attributeCode,
                            $storeId,
                            implode(
                                ',',
                                $attributeOptionIds
                            )
                        );

                        if (is_array($attributeValue)) {
                            $attributeValue = implode(
                                $this->getValuesSeparator(),
                                $attributeValue
                            );
                        }

                        $this->attributeValues[ $productId ] = $attributeValue;

                        return $this->attributeValues[ $productId ];
                    }
                }
            } else {
                return $this->attributeValues[ $productId ];
            }
        } catch (Exception $exception) {
        }

        return null;
    }

    public function getAttributeValueClass(Product $product): ?string
    {
        $attributeValue = $this->getAttributeValue($product);

        if ($attributeValue) {
            return strtolower(
                preg_replace(
                    '/[^A-Za-z0-9]/',
                    '_',
                    $attributeValue
                )
            );
        }

        return null;
    }

    public function isNotSaleable(Product $product): bool
    {
        $attributeOptionIds = $this->getAttributeOptionIds($product);

        $isNotSaleable = ! $this->variables->isEmpty($attributeOptionIds);

        foreach ($attributeOptionIds as $attributeOptionId) {
            $optionGroupKey = sprintf(
                'option_id_%d',
                $attributeOptionId
            );

            $optionIsNotSaleable = $this->storeHelper->getStoreConfigFlag(
                sprintf(
                    'infrangible_catalogproductpos/%s/not_saleable',
                    $optionGroupKey
                )
            );

            if ($optionIsNotSaleable) {
                $optionIsCommanding = $this->storeHelper->getStoreConfigFlag(
                    sprintf(
                        'infrangible_catalogproductpos/%s/commanding',
                        $optionGroupKey
                    )
                );

                if ($optionIsCommanding) {
                    return true;
                }
            } else {
                $isNotSaleable = false;
            }
        }

        return $isNotSaleable;
    }

    public function showButton(Product $product): bool
    {
        $attributeOptionIds = $this->getAttributeOptionIds($product);

        foreach ($attributeOptionIds as $attributeOptionId) {
            $optionGroupKey = sprintf(
                'option_id_%d',
                $attributeOptionId
            );

            $showButton = $this->storeHelper->getStoreConfigFlag(
                sprintf(
                    'infrangible_catalogproductpos/%s/show_button',
                    $optionGroupKey
                )
            );

            if ($showButton) {
                return true;
            }
        }

        return false;
    }

    public function getButtonText(Product $product): ?string
    {
        $attributeOptionIds = $this->getAttributeOptionIds($product);

        foreach ($attributeOptionIds as $attributeOptionId) {
            $optionGroupKey = sprintf(
                'option_id_%d',
                $attributeOptionId
            );

            $buttonText = $this->storeHelper->getStoreConfig(
                sprintf(
                    'infrangible_catalogproductpos/%s/button_text',
                    $optionGroupKey
                )
            );

            if (! $this->variables->isEmpty($buttonText)) {
                return $buttonText;
            }
        }

        return null;
    }

    public function getButtonUrl(Product $product): ?string
    {
        $attributeOptionIds = $this->getAttributeOptionIds($product);

        foreach ($attributeOptionIds as $attributeOptionId) {
            $optionGroupKey = sprintf(
                'option_id_%d',
                $attributeOptionId
            );

            $buttonUrl = $this->storeHelper->getStoreConfig(
                sprintf(
                    'infrangible_catalogproductpos/%s/button_url',
                    $optionGroupKey
                )
            );

            if (! $this->variables->isEmpty($buttonUrl)) {
                return $buttonUrl;
            }
        }

        return null;
    }

    public function getValuesSeparator(): string
    {
        if ($this->valuesSeparator === null) {
            $this->valuesSeparator = $this->storeHelper->getStoreConfig(
                'infrangible_catalogproductpos/attribute/values_separator',
                ', '
            );
        }

        return $this->valuesSeparator;
    }
}
