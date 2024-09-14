<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductPOS\Block;

use Infrangible\CatalogProductPOS\Helper\Data;
use Infrangible\Core\Helper\Registry;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Attribute extends Template
{
    /** @var Registry */
    protected $registryHelper;

    /** @var Data */
    protected $helper;

    /** @var Product|null */
    private $product = null;

    public function __construct(
        Template\Context $context,
        Registry $registryHelper,
        Data $helper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );

        $this->registryHelper = $registryHelper;
        $this->helper = $helper;
    }

    public function getProduct(): ?Product
    {
        if ($this->product === null) {
            $this->product = $this->registryHelper->registry('product');
        }

        return $this->product;
    }

    public function getAttributeCode(): ?string
    {
        return $this->helper->getAttributeCode();
    }

    public function getAttributeCodeClass(): ?string
    {
        return $this->helper->getAttributeCodeClass();
    }

    public function getAttributeValue(): ?string
    {
        $product = $this->getProduct();

        return $product ? $this->helper->getAttributeValue($product) : null;
    }

    public function getAttributeValueClass(): ?string
    {
        $product = $this->getProduct();

        return $product ? $this->helper->getAttributeValueClass($product) : null;
    }
}
