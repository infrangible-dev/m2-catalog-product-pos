<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductPOS\Observer;

use Infrangible\CatalogProductPOS\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Simple products (also the variants of configurable products) whose point of sale does not allow buying online are
 * not saleable.
 */
class CatalogProductIsSalableAfter implements ObserverInterface
{
    /**
     * @param Data $helper
     */
    public function __construct(protected Data $helper)
    {
    }

    /**
     * Marks simple products as not saleable if their point of sale does not allow buying online.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $saleable = $observer->getEvent()->getData('salable');

        if (! $saleable instanceof DataObject) {
            return;
        }

        $product = $saleable->getData('product');

        if ($product instanceof Product && $product->getTypeId() === Type::TYPE_SIMPLE &&
            $saleable->getData('is_salable') && $this->helper->isNotSaleable($product)) {

            $saleable->setData('is_salable', false);
        }
    }
}
