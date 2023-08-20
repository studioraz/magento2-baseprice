<?php
/**
 * Copyright © 2023 Studio Raz. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Magenerds\BasePrice\Model;

use Magenerds\BasePrice\Block\AfterPrice;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

class BasePriceBlock
{
    protected LayoutInterface $layout;

    protected SaleableInterface $product;

    public function __construct(
        LayoutInterface $layout
    ) {
        $this->layout = $layout;
    }

    public function getProduct(): SaleableInterface
    {
        return $this->product;
    }

    public function setProduct(SaleableInterface $product): self
    {
        // if a grouped product is given we need the current child
        if ($product->getTypeId() === Grouped::TYPE_CODE) {
            $childProduct = $this->product->getPriceInfo()
                ->getPrice(FinalPrice::PRICE_CODE)
                ->getMinProduct();

            $this->product = $childProduct;

            return $this;
        }

        $this->product = $product;

        return $this;
    }

    public function getBasePriceBlock(): Template
    {
        /** @var AfterPrice $afterPriceBlock */
        $afterPriceBlock = $this->layout->createBlock(
            AfterPrice::class,
            'baseprice_afterprice_' . $this->product->getId(),
            ['product' => $this->product]
        );

        $templateFile = ($this->product->getTypeId() === Configurable::TYPE_CODE)
            ? 'Magenerds_BasePrice::configurable/afterprice.phtml'
            : 'Magenerds_BasePrice::afterprice.phtml';

        return $afterPriceBlock->setTemplate($templateFile);
    }
}
