<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Plugin\ConfigurableProduct\Model\Product\Type;

use Infrangible\CatalogProductPOS\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as Subject;
use Magento\Framework\DataObject;

/**
 * Refuses variants of configurable products that cannot be bought online because of their point of sale when they
 * are added to the cart. Magento only checks whether the configurable product itself is saleable.
 */
class Configurable
{
    /**
     * @param Data $helper
     */
    public function __construct(protected Data $helper)
    {
    }

    /**
     * Returns an error instead of the products to add if the selected variant cannot be bought online.
     *
     * @param Subject $subject
     * @param array|string $result
     * @param DataObject $buyRequest
     * @param Product $product
     * @param string|null $processMode
     * @phpstan-param array<int|string, mixed>|string $result
     *
     * @return array|string
     * @phpstan-return array<int|string, mixed>|string
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterPrepareForCartAdvanced(
        Subject $subject,
        array|string $result,
        DataObject $buyRequest,
        Product $product,
        ?string $processMode = null
    ): array|string {
        if (! is_array($result) || ($processMode !== null && $processMode !== AbstractType::PROCESS_MODE_FULL)) {
            return $result;
        }

        foreach ($result as $candidate) {
            if ($candidate instanceof Product && $candidate->getId() !== $product->getId() &&
                $this->helper->isNotSaleable($candidate)) {

                return __('The selected option of this product can not be bought online.')->render();
            }
        }

        return $result;
    }
}
