<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Test\Unit\Plugin\ConfigurableProduct\Helper;

use Infrangible\CatalogProductPOS\Helper\Data as Helper;
use Infrangible\CatalogProductPOS\Plugin\ConfigurableProduct\Helper\Data;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\ConfigurableProduct\Helper\Data as Subject;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute as ConfigurableAttribute;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Tests that variants which cannot be bought online stay selectable.
 */
#[AllowMockObjectsWithoutExpectations]
class DataTest extends TestCase
{
    /**
     * Tests that only variants which are not saleable because of their point of sale are added to the options.
     *
     * @return void
     */
    public function testAfterGetOptions(): void
    {
        $inStore = $this->createProduct(2, '50', false);
        $outOfStock = $this->createProduct(3, '51', false);
        $online = $this->createProduct(4, '52', true);

        $helper = $this->createStub(Helper::class);
        $helper->method('isNotSaleable')->willReturnCallback(
            static fn(Product $product): bool => $product === $inStore
        );

        $options = ['93' => ['52' => [4]], 'index' => [2 => ['93' => '50'], 3 => ['93' => '51'], 4 => ['93' => '52']]];

        $result = (new Data($helper, $this->createStub(Stores::class)))->afterGetOptions(
            $this->createSubject(),
            $options,
            $this->createPartialMock(Product::class, []),
            [$inStore, $outOfStock, $online]
        );

        self::assertSame(['52' => [4], '50' => [2]], $result[ '93' ]);
        self::assertSame($options[ 'index' ], $result[ 'index' ]);
    }

    /**
     * Tests that the options stay as they are when the store shows products that are out of stock.
     *
     * @return void
     */
    public function testShowOutOfStock(): void
    {
        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStoreConfigFlag')->willReturn(true);

        $options = ['93' => ['52' => [4]]];

        self::assertSame(
            $options,
            (new Data($this->createStub(Helper::class), $storeHelper))->afterGetOptions(
                $this->createSubject(),
                $options,
                $this->createPartialMock(Product::class, []),
                [$this->createProduct(2, '50', false)]
            )
        );
    }

    /**
     * Returns the helper of Magento with the attribute "color" (ID 93) of the configurable product.
     *
     * @return Subject
     */
    private function createSubject(): Subject
    {
        $productAttribute = $this->createStub(Attribute::class);
        $productAttribute->method('getId')->willReturn('93');
        $productAttribute->method('getAttributeCode')->willReturn('color');

        $attribute = $this->createPartialMock(ConfigurableAttribute::class, []);
        $attribute->setData('product_attribute', $productAttribute);

        $subject = $this->createStub(Subject::class);
        $subject->method('getAllowAttributes')->willReturn([$attribute]);

        return $subject;
    }

    /**
     * Returns a variant with the ID, its color and whether it is saleable.
     *
     * @param int $productId
     * @param string $color
     * @param bool $isSalable
     *
     * @return Product
     */
    private function createProduct(int $productId, string $color, bool $isSalable): Product
    {
        $product = $this->createPartialMock(Product::class, ['isSalable']);
        $product->method('isSalable')->willReturn($isSalable);
        $product->setId($productId);
        $product->setData('color', $color);

        return $product;
    }
}
