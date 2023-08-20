<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 */

/**
 * @category   Magenerds
 * @package    Magenerds_BasePrice
 * @subpackage Model
 * @copyright  Copyright (c) 2019 TechDivision GmbH (https://www.techdivision.com)
 * @link       https://www.techdivision.com/
 * @author     Florian Sydekum <f.sydekum@techdivision.com>
 */

namespace Magenerds\BasePrice\Model\Plugin;

use Closure;
use Magenerds\BasePrice\Model\BasePriceBlock;
use Magento\Framework\Pricing\Render;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\View\LayoutInterface;

/**
 * Class AfterPrice
 * @package Magenerds\BasePrice\Model\Plugin
 */
class AfterPrice
{
    /**
     * Hold final price code
     *
     * @var string
     */
    const FINAL_PRICE = 'final_price';

    /**
     * Hold tier price code
     *
     * @var string
     */
    const TIER_PRICE = 'tier_price';

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @var []
     */
    protected $afterPriceHtml = [];

    protected BasePriceBlock $basePriceBlock;

    /**
     * @param LayoutInterface $layout
     */
    public function __construct(
        LayoutInterface $layout,
        BasePriceBlock $basePriceBlock
    ) {
        $this->layout = $layout;
        $this->basePriceBlock = $basePriceBlock;
    }

    /**
     * Plugin for price rendering in order to display after price information
     *
     * @param Render $subject
     * @param Closure $closure
     * @param string $priceCode
     * @param SaleableInterface $saleableItem
     * @param array $arguments
     *
     * @return mixed|string
     */
    public function aroundRender(Render $subject, Closure $closure, $priceCode, SaleableInterface $saleableItem, array $arguments = [])
    {
        $renderHtml = $closure($priceCode, $saleableItem, $arguments);

        try {
            $hasNoTierPrice = empty($saleableItem->getTierPrice());

            // If it is final price block and no tier prices exist set additional render
            // If it is tier price block and tier prices exist set additional render
            if ((self::FINAL_PRICE === $priceCode && $hasNoTierPrice) || (self::TIER_PRICE === $priceCode && !$hasNoTierPrice)) {
                $renderHtml .= $this->getBasePriceHtml($saleableItem);
            }
        } catch (\Exception $e) {
            return $renderHtml;
        }

        return $renderHtml;
    }

    /**
     * Renders and caches the after price html
     *
     * @return null|string
     */
    protected function getBasePriceHtml(SaleableInterface $saleableItem)
    {
        if (!array_key_exists($saleableItem->getId(), $this->afterPriceHtml)) {
            $this->afterPriceHtml[$saleableItem->getId()] = $this->basePriceBlock->setProduct($saleableItem)->getBasePriceBlock()->toHtml();
        }

        return $this->afterPriceHtml[$saleableItem->getId()];
    }
}
