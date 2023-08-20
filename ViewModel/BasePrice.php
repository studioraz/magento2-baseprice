<?php
/**
 * Copyright © 2023 Studio Raz. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Magenerds\BasePrice\ViewModel;

use Magenerds\BasePrice\Model\BasePriceBlock;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template;

class BasePrice implements ArgumentInterface
{
    protected BasePriceBlock $basePriceBlock;

    public function __construct(
        BasePriceBlock $basePriceBlock
    ) {
        $this->basePriceBlock = $basePriceBlock;
    }

    public function getBasePriceBlock(SaleableInterface $product): Template
    {
        return $this->basePriceBlock->setProduct($product)->getBasePriceBlock();
    }
}
