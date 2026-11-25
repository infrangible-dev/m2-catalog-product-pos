<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Test\Unit\Helper;

use FeWeDev\Base\Variables;
use Infrangible\CatalogProductPOS\Helper\Data;
use Infrangible\Core\Helper\Attribute as AttributeHelper;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the point-of-sale attribute of products and the settings of its options.
 */
#[AllowMockObjectsWithoutExpectations]
class DataTest extends TestCase
{
    /** @var array<string, string> */
    private array $config = [];

    /** @var array<int, string> values that the database returns by product ID */
    private array $databaseValues = [];

    /** @var int number of reads from the database */
    private int $databaseReads = 0;

    /**
     * Sets up the configuration: options 1 (online), 2 (in store only, with button) and 3 (not saleable, commanding).
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = [
            'infrangible_catalogproductpos/attribute/attribute'          => '157',
            'infrangible_catalogproductpos/attribute/values_separator'   => ' / ',
            'infrangible_catalogproductpos/option_id_2/not_saleable'     => '1',
            'infrangible_catalogproductpos/option_id_2/show_button'      => '1',
            'infrangible_catalogproductpos/option_id_2/button_text'      => 'Find a store',
            'infrangible_catalogproductpos/option_id_2/button_url'       => '/stores',
            'infrangible_catalogproductpos/option_id_3/not_saleable'     => '1',
            'infrangible_catalogproductpos/option_id_3/commanding'       => '1',
            'infrangible_catalogproductpos/option_id_3/button_url'       => 'JavaScript:alert(1)'
        ];
        $this->databaseValues = [];
        $this->databaseReads = 0;
    }

    /**
     * Returns the options of products and whether they cannot be bought online.
     *
     * @return array<string, array{string, bool}>
     */
    public static function saleableProvider(): array
    {
        return [
            'no option'                      => ['', false],
            'online'                         => ['1', false],
            'in store only'                  => ['2', true],
            'online and in store'            => ['1,2', false],
            'online and commanding'          => ['1,3', true],
            'in store and commanding'        => ['2,3', true]
        ];
    }

    /**
     * Tests which products cannot be bought online.
     *
     * @dataProvider saleableProvider
     *
     * @param string $optionIds
     * @param bool $expected
     *
     * @return void
     */
    #[DataProvider('saleableProvider')]
    public function testIsNotSaleable(string $optionIds, bool $expected): void
    {
        self::assertSame($expected, $this->createHelper()->isNotSaleable($this->createProduct(1, $optionIds)));
    }

    /**
     * Tests that the values of products loaded without the attribute are read from the database at once.
     *
     * @return void
     */
    public function testValuesOfProductsWithoutAttribute(): void
    {
        $this->databaseValues = [11 => '2', 12 => '1'];

        $helper = $this->createHelper();
        $first = $this->createProduct(11, null);
        $second = $this->createProduct(12, null);

        $helper->loadAttributeOptionIds([$first, $second]);

        self::assertTrue($helper->isNotSaleable($first));
        self::assertFalse($helper->isNotSaleable($second));
        self::assertSame(1, $this->databaseReads);
    }

    /**
     * Tests the labels of the options and their CSS classes.
     *
     * @return void
     */
    public function testAttributeValue(): void
    {
        $helper = $this->createHelper();
        $product = $this->createProduct(1, '1,2');

        self::assertSame('Online / In store only', $helper->getAttributeValue($product));
        self::assertSame('online___in_store_only', $helper->getAttributeValueClass($product));
        self::assertSame('point_of_sale', $helper->getAttributeCodeClass());
    }

    /**
     * Tests the button and that links with a script scheme are not returned.
     *
     * @return void
     */
    public function testButton(): void
    {
        $helper = $this->createHelper();
        $inStore = $this->createProduct(1, '2');

        self::assertTrue($helper->showButton($inStore));
        self::assertSame('Find a store', $helper->getButtonText($inStore));
        self::assertSame('/stores', $helper->getButtonUrl($inStore));
        self::assertNull($helper->getButtonUrl($this->createProduct(2, '3')));
        self::assertFalse($helper->showButton($this->createProduct(3, '1')));
    }

    /**
     * Returns a product with the option IDs, or without the attribute for null.
     *
     * @param int $productId
     * @param string|null $optionIds
     *
     * @return Product
     */
    private function createProduct(int $productId, ?string $optionIds): Product
    {
        $product = $this->createPartialMock(Product::class, []);
        $product->setId($productId);
        $product->setData('store_id', 1);

        if ($optionIds !== null) {
            $product->setData('point_of_sale', $optionIds);
        }

        return $product;
    }

    /**
     * Returns the helper with the configuration and the database values of this test.
     *
     * @return Data
     */
    private function createHelper(): Data
    {
        $storeHelper = $this->createStub(Stores::class);
        $storeHelper->method('getStoreConfig')->willReturnCallback(
            fn(string $path): ?string => $this->config[ $path ] ?? null
        );
        $storeHelper->method('getStoreConfigFlag')->willReturnCallback(
            fn(string $path): bool => ($this->config[ $path ] ?? '') === '1'
        );

        $source = $this->createStub(AbstractSource::class);
        $source->method('getAllOptions')->willReturn(
            [
                ['value' => '', 'label' => ' '],
                ['value' => '1', 'label' => 'Online'],
                ['value' => '2', 'label' => 'In store only'],
                ['value' => '3', 'label' => 'Partner']
            ]
        );

        $attribute = $this->createStub(Attribute::class);
        $attribute->method('getAttributeCode')->willReturn('point_of_sale');
        $attribute->method('getSource')->willReturn($source);

        $attributeHelper = $this->createStub(AttributeHelper::class);
        $attributeHelper->method('getAttribute')->willReturn($attribute);
        $attributeHelper->method('getAttributeValues')->willReturnCallback(
            function (): array {
                $this->databaseReads++;

                return $this->databaseValues;
            }
        );

        return new Data(
            $storeHelper,
            $attributeHelper,
            new Variables(),
            $this->createStub(Database::class),
            $this->createStub(LoggerInterface::class)
        );
    }
}
