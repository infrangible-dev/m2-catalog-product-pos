<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Test\Unit\Observer;

use Infrangible\CatalogProductPOS\Helper\Data;
use Infrangible\CatalogProductPOS\Observer\CatalogProductIsSalableAfter;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests that simple products which cannot be bought online are not saleable.
 */
#[AllowMockObjectsWithoutExpectations]
class CatalogProductIsSalableAfterTest extends TestCase
{
    /**
     * Returns the type of the product, whether it is saleable, whether its point of sale forbids buying online, and
     * the expected result.
     *
     * @return array<string, array{string, bool, bool, bool}>
     */
    public static function productProvider(): array
    {
        return [
            'simple online'         => ['simple', true, false, true],
            'simple in store only'  => ['simple', true, true, false],
            'simple out of stock'   => ['simple', false, false, false],
            'configurable in store' => ['configurable', true, true, true]
        ];
    }

    /**
     * Tests the saleability after the observer.
     *
     * @dataProvider productProvider
     *
     * @param string $typeId
     * @param bool $isSalable
     * @param bool $isNotSaleable
     * @param bool $expected
     *
     * @return void
     */
    #[DataProvider('productProvider')]
    public function testExecute(string $typeId, bool $isSalable, bool $isNotSaleable, bool $expected): void
    {
        $product = $this->createPartialMock(Product::class, []);
        $product->setTypeId($typeId);

        $helper = $this->createStub(Data::class);
        $helper->method('isNotSaleable')->willReturn($isNotSaleable);

        $saleable = new DataObject(['product' => $product, 'is_salable' => $isSalable]);

        (new CatalogProductIsSalableAfter($helper))->execute(
            new Observer(['event' => new Event(['salable' => $saleable])])
        );

        self::assertSame($expected, $saleable->getData('is_salable'));
    }
}
