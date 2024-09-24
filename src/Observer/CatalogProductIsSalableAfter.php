<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPOS\Observer;

use Infrangible\CatalogProductPOS\Helper\Data;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class CatalogProductIsSalableAfter implements ObserverInterface
{
    /** @var Data */
    protected $helper;

    /** @var Stores */
    protected $storeHelper;

    public function __construct(Data $helper, Stores $storeHelper)
    {
        $this->helper = $helper;
        $this->storeHelper = $storeHelper;
    }

    public function execute(Observer $observer)
    {
        /** @var DataObject $saleable */
        $saleable = $observer->getEvent()->getData('salable');

        /** @var Product $product */
        $product = $saleable->getData('product');

        if ($product->getTypeId() === 'simple') {
            $isSaleable = $saleable->getData('is_salable');

            if ($isSaleable) {
                $isNotSaleable = $this->helper->isNotSaleable($product);

                if ($isNotSaleable) {
                    $isSaleable = false;
                }
            }

            $saleable->setData(
                'is_salable',
                $isSaleable
            );
        }
    }
}
