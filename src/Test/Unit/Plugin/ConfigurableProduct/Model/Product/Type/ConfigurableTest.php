<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Test\Unit\Plugin\ConfigurableProduct\Model\Product\Type;

use Infrangible\CatalogProductPOS\Helper\Data;
use Infrangible\CatalogProductPOS\Plugin\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as Subject;
use Magento\Framework\DataObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests that variants which cannot be bought online are refused when they are added to the cart.
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigurableTest extends TestCase
{
    /**
     * Tests the result for a variant that cannot be bought online, one that can and an error of Magento.
     *
     * @return void
     */
    public function testAfterPrepareForCartAdvanced(): void
    {
        $parent = $this->createProduct(1);
        $inStore = $this->createProduct(2);
        $online = $this->createProduct(3);

        $helper = $this->createStub(Data::class);
        $helper->method('isNotSaleable')->willReturnCallback(
            static fn(Product $product): bool => $product === $inStore || $product === $parent
        );

        $plugin = new Configurable($helper);
        $subject = $this->createStub(Subject::class);
        $buyRequest = new DataObject();

        self::assertSame(
            'The selected option of this product can not be bought online.',
            $plugin->afterPrepareForCartAdvanced($subject, [$parent, $inStore], $buyRequest, $parent)
        );
        self::assertSame(
            [$parent, $online],
            $plugin->afterPrepareForCartAdvanced($subject, [$parent, $online], $buyRequest, $parent)
        );
        self::assertSame(
            [$parent, $inStore],
            $plugin->afterPrepareForCartAdvanced(
                $subject,
                [$parent, $inStore],
                $buyRequest,
                $parent,
                AbstractType::PROCESS_MODE_LITE
            )
        );
        self::assertSame('error', $plugin->afterPrepareForCartAdvanced($subject, 'error', $buyRequest, $parent));
    }

    /**
     * Returns a product with the ID.
     *
     * @param int $productId
     *
     * @return Product
     */
    private function createProduct(int $productId): Product
    {
        $product = $this->createPartialMock(Product::class, []);
        $product->setId($productId);

        return $product;
    }
}
