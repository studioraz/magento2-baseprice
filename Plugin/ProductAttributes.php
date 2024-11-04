<?php
namespace Magenerds\BasePrice\Plugin;

use Magento\Catalog\Block\Product\View\Attributes;

class ProductAttributes
{

    private const BASE_PRICE_PRODUCT_AMOUNT = 'baseprice_product_amount';
    private const BASE_PRICE_PRODUCT_UNIT = 'baseprice_product_unit';

    /**
     * AFTER PLUGIN
     * @see \Magento\Catalog\Block\Product\View\Attributes::getAdditionalData()
     *
     * @param Attributes $subject
     * @param array $result
     * @return array
     */
    public function afterGetAdditionalData(Attributes $subject, array $result)
    {
        foreach ($result as $attributeCode => $attributeValue) {
            if ($attributeCode == self::BASE_PRICE_PRODUCT_AMOUNT) {
                $productAmount = intval($attributeValue['value']);
                $productUnit = $this->getProductUnit($subject);
                $result[$attributeCode]['value'] = $productAmount . $productUnit;
            }
        }

        return $result;
    }

    /**
     * @param $subject
     * @return string
     */
    protected function getProductUnit($subject): string
    {
        $unit = '';
        if ($subject->getRequest()->getFullActionName() == 'catalog_product_view') {
            $product = $subject->getProduct();
            $unit = ' ' . $product->getAttribute(self::BASE_PRICE_PRODUCT_UNIT);
        }
        return $unit;
    }
}
