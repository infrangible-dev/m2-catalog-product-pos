<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Helper;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Attribute as AttributeHelper;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * The point-of-sale attribute of products and the settings of its options: whether products with an option can be
 * bought online and which button they show instead.
 */
class Data
{
    /** @var array<int, list<int>> option IDs by product ID */
    private array $attributeOptionIds = [];

    /** @var array<int, string>|null option labels by option ID */
    private ?array $attributeOptions = null;

    /** @var array<int, string|null> values by product ID */
    private array $attributeValues = [];

    /** @var Attribute|null the configured attribute, once loaded */
    private ?Attribute $attribute = null;

    /** @var bool whether the configured attribute was loaded */
    private bool $attributeLoaded = false;

    /**
     * @param Stores $storeHelper
     * @param AttributeHelper $attributeHelper
     * @param Variables $variables
     * @param Database $databaseHelper
     * @param LoggerInterface $logging
     */
    public function __construct(
        protected Stores $storeHelper,
        protected AttributeHelper $attributeHelper,
        protected Variables $variables,
        protected Database $databaseHelper,
        protected LoggerInterface $logging
    ) {
    }

    /**
     * Returns the ID of the configured point-of-sale attribute.
     *
     * @return int|null
     */
    public function getAttributeId(): ?int
    {
        $attributeId = $this->storeHelper->getStoreConfig('infrangible_catalogproductpos/attribute/attribute');

        return is_numeric($attributeId) ? (int)$attributeId : null;
    }

    /**
     * Returns the configured point-of-sale attribute, or null if none is configured.
     *
     * @return Attribute|null
     */
    public function getAttribute(): ?Attribute
    {
        if (! $this->attributeLoaded) {
            $this->attributeLoaded = true;

            $attributeId = $this->getAttributeId();

            if ($attributeId !== null) {
                try {
                    $this->attribute = $this->attributeHelper->getAttribute(
                        Product::ENTITY,
                        (string)$attributeId
                    );
                } catch (LocalizedException $exception) {
                    $this->logging->error($exception);
                }
            }
        }

        return $this->attribute;
    }

    /**
     * Returns the code of the point-of-sale attribute.
     *
     * @return string|null
     */
    public function getAttributeCode(): ?string
    {
        $attributeCode = $this->getAttribute()?->getAttributeCode();

        return is_string($attributeCode) && $attributeCode !== '' ? $attributeCode : null;
    }

    /**
     * Returns the code of the attribute as CSS class.
     *
     * @return string|null
     */
    public function getAttributeCodeClass(): ?string
    {
        $attributeCode = $this->getAttributeCode();

        return $attributeCode === null ? null : $this->toCssClass($attributeCode);
    }

    /**
     * Reads the values of the attribute for products that were loaded without it, with one query.
     *
     * Magento loads the variants of configurable products without the attributes it does not need itself.
     *
     * @param array<Product> $products
     *
     * @return void
     */
    public function loadAttributeOptionIds(array $products): void
    {
        $attributeCode = $this->getAttributeCode();

        if ($attributeCode === null) {
            return;
        }

        $productIds = [];
        $storeId = null;

        foreach ($products as $product) {
            $productId = $this->getProductId($product);

            if ($productId === null || array_key_exists($productId, $this->attributeOptionIds)) {
                continue;
            }

            if ($product->hasData($attributeCode)) {
                $this->attributeOptionIds[ $productId ] = $this->toOptionIds($product->getData($attributeCode));
            } else {
                $productIds[] = $productId;
                $storeId = $this->variables->intValue($product->getStoreId());
            }
        }

        if (count($productIds) === 0 || $storeId === null) {
            return;
        }

        try {
            $values = $this->attributeHelper->getAttributeValues(
                $this->databaseHelper->getDefaultConnection(),
                Product::ENTITY,
                $attributeCode,
                $productIds,
                $storeId,
                false
            );
        } catch (LocalizedException $exception) {
            $this->logging->error($exception);

            $values = [];
        }

        foreach ($productIds as $productId) {
            $this->attributeOptionIds[ $productId ] = $this->toOptionIds($values[ $productId ] ?? null);
        }
    }

    /**
     * Returns the IDs of the options of the attribute that the product has.
     *
     * @param Product $product
     *
     * @return list<int>
     */
    public function getAttributeOptionIds(Product $product): array
    {
        $productId = $this->getProductId($product);

        if ($productId === null) {
            return [];
        }

        if (! array_key_exists($productId, $this->attributeOptionIds)) {
            $this->loadAttributeOptionIds([$product]);
        }

        return $this->attributeOptionIds[ $productId ] ?? [];
    }

    /**
     * Returns the labels of the options of the attribute by option ID.
     *
     * @return array<int, string>
     */
    public function getAttributeOptions(): array
    {
        if ($this->attributeOptions === null) {
            $this->attributeOptions = [];

            $attribute = $this->getAttribute();

            if ($attribute !== null) {
                try {
                    foreach ($attribute->getSource()->getAllOptions() as $option) {
                        $optionId = $option[ 'value' ] ?? null;
                        $optionLabel = $option[ 'label' ] ?? '';

                        if (is_numeric($optionId)) {
                            $this->attributeOptions[ (int)$optionId ] =
                                $this->variables->stringValueOrDefault($optionLabel);
                        }
                    }
                } catch (LocalizedException $exception) {
                    $this->logging->error($exception);
                }
            }
        }

        return $this->attributeOptions;
    }

    /**
     * Returns the labels of the options of the product, joined with the values separator.
     *
     * @param Product $product
     *
     * @return string|null
     */
    public function getAttributeValue(Product $product): ?string
    {
        $productId = $this->getProductId($product);

        if ($productId === null) {
            return null;
        }

        if (! array_key_exists($productId, $this->attributeValues)) {
            $labels = [];
            $options = $this->getAttributeOptions();

            foreach ($this->getAttributeOptionIds($product) as $optionId) {
                if (isset($options[ $optionId ]) && $options[ $optionId ] !== '') {
                    $labels[] = $options[ $optionId ];
                }
            }

            $this->attributeValues[ $productId ] =
                count($labels) === 0 ? null : implode($this->getValuesSeparator(), $labels);
        }

        return $this->attributeValues[ $productId ];
    }

    /**
     * Returns the value of the product as CSS class.
     *
     * @param Product $product
     *
     * @return string|null
     */
    public function getAttributeValueClass(Product $product): ?string
    {
        $attributeValue = $this->getAttributeValue($product);

        return $attributeValue === null ? null : $this->toCssClass($attributeValue);
    }

    /**
     * Checks whether the product cannot be bought online.
     *
     * This is the case if all its options are not saleable, or one of them is not saleable and commanding.
     *
     * @param Product $product
     *
     * @return bool
     */
    public function isNotSaleable(Product $product): bool
    {
        $attributeOptionIds = $this->getAttributeOptionIds($product);

        $isNotSaleable = count($attributeOptionIds) > 0;

        foreach ($attributeOptionIds as $attributeOptionId) {
            if ($this->getOptionFlag($attributeOptionId, 'not_saleable')) {
                if ($this->getOptionFlag($attributeOptionId, 'commanding')) {
                    return true;
                }
            } else {
                $isNotSaleable = false;
            }
        }

        return $isNotSaleable;
    }

    /**
     * Checks whether one of the options of the product shows a button.
     *
     * @param Product $product
     *
     * @return bool
     */
    public function showButton(Product $product): bool
    {
        foreach ($this->getAttributeOptionIds($product) as $attributeOptionId) {
            if ($this->getOptionFlag($attributeOptionId, 'show_button')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the button text of the first option of the product that has one.
     *
     * @param Product $product
     *
     * @return string|null
     */
    public function getButtonText(Product $product): ?string
    {
        return $this->getOptionValue($product, 'button_text');
    }

    /**
     * Returns the button URL of the first option of the product that has one; URLs with a script scheme
     * ("javascript:" and the like) are not returned.
     *
     * @param Product $product
     *
     * @return string|null
     */
    public function getButtonUrl(Product $product): ?string
    {
        $buttonUrl = $this->getOptionValue($product, 'button_url');

        if ($buttonUrl === null) {
            return null;
        }

        $scheme = strtolower((string)preg_replace('/[\x00-\x20]/', '', explode(':', $buttonUrl, 2)[ 0 ]));

        return str_contains($buttonUrl, ':') && in_array($scheme, ['javascript', 'data', 'vbscript'], true) ?
            null : $buttonUrl;
    }

    /**
     * Returns the separator of the labels of products with several options.
     *
     * @return string
     */
    public function getValuesSeparator(): string
    {
        return $this->variables->stringValueOrDefault(
            $this->storeHelper->getStoreConfig('infrangible_catalogproductpos/attribute/values_separator'),
            ', '
        );
    }

    /**
     * Returns a flag of the settings of an option.
     *
     * @param int $attributeOptionId
     * @param string $field
     *
     * @return bool
     */
    private function getOptionFlag(int $attributeOptionId, string $field): bool
    {
        return $this->storeHelper->getStoreConfigFlag(
            sprintf(
                'infrangible_catalogproductpos/option_id_%d/%s',
                $attributeOptionId,
                $field
            )
        );
    }

    /**
     * Returns a text of the settings of the first option of the product that has one.
     *
     * @param Product $product
     * @param string $field
     *
     * @return string|null
     */
    private function getOptionValue(Product $product, string $field): ?string
    {
        foreach ($this->getAttributeOptionIds($product) as $attributeOptionId) {
            $value = $this->variables->stringValueOrDefault(
                $this->storeHelper->getStoreConfig(
                    sprintf(
                        'infrangible_catalogproductpos/option_id_%d/%s',
                        $attributeOptionId,
                        $field
                    )
                )
            );

            if (trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Returns the ID of the product, or null for a product that is not saved.
     *
     * @param Product $product
     *
     * @return int|null
     */
    private function getProductId(Product $product): ?int
    {
        // Magento declares the ID as int, but it is a string or null at runtime
        $productId = $product->getData('entity_id') ?? $product->getData('id');

        return is_numeric($productId) ? (int)$productId : null;
    }

    /**
     * Returns the option IDs of a value: one ID or several separated by commas.
     *
     * @param mixed $value
     *
     * @return list<int>
     */
    private function toOptionIds(mixed $value): array
    {
        $optionIds = [];

        foreach (explode(',', $this->variables->stringValueOrDefault($value)) as $optionId) {
            if (is_numeric(trim($optionId))) {
                $optionIds[] = (int)trim($optionId);
            }
        }

        return $optionIds;
    }

    /**
     * Returns a text as CSS class: lowercase, other characters than letters and digits replaced by underscores.
     *
     * @param string $text
     *
     * @return string
     */
    private function toCssClass(string $text): string
    {
        return strtolower((string)preg_replace('/[^A-Za-z0-9]/', '_', $text));
    }
}
