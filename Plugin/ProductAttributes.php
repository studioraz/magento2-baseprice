<?php
namespace Magenerds\BasePrice\Plugin;

use Magento\Catalog\Block\Product\View\Attributes;

class ProductAttributes
{

    private const BASE_PRICE_AMOUNT = 'baseprice_product_amount';

    /**
     * After plugin for getAdditionalData
     *
     * @param Attributes $subject
     * @param array $result
     * @return array
     */
    public function afterGetAdditionalData(Attributes $subject, array $result)
    {
        foreach ($result as $attributeCode => $attributeValue) {
            if ($attributeCode == self::BASE_PRICE_AMOUNT) {
                $result[$attributeCode]['value'] = intval($attributeValue['value']);
            }
        }

        return $result;
    }
}
