# AGENTS.md — studioraz/magento2-baseprice

> **For LLM agents working with this repository.**
> Read this file before writing, refactoring, or reviewing any code in this package.

---

## Package overview
**Composer package:** `studioraz/magento2-baseprice`  
**Magento module name:** `Magenerds_BasePrice`  
**PHP:** 8.2+  
**Magento:** 2.4.x (Open Source / Adobe Commerce)  
**Namespace:** `Magenerds\BasePrice`  
**Purpose:** Calculates and displays unit base prices (e.g. price per 100g, 1kg, 1L, etc.) for products sold by volume, weight, length, or count. Supports simple, configurable, grouped, and bundle products across both Luma and Hyvä storefronts.

---

## Architecture & Directory Structure

```
studioraz/magento2-baseprice/
├── composer.json
├── registration.php
├── README.md
├── AGENTS.md
├── etc/
│   ├── module.xml                     ← Sequence: Magento_Catalog, Magento_Eav, Magento_Config
│   ├── config.xml                     ← Defaults: enabled=1, default template string
│   ├── acl.xml                        ← ACL resource: Magenerds_BasePrice::config
│   ├── di.xml                         ← Plugins & preferences registration
│   └── adminhtml/
│       └── system.xml                 ← Stores > Config > Magenerds > Base Price
├── Setup/
│   ├── InstallData.php                ← Creates EAV attributes & system config conversion table
│   └── Patch/Data/
│       └── CreateEnableBasePriceAttr.php ← Adds baseprice_is_enable attribute
├── Model/
│   ├── BasePriceBlock.php             ← Safely instantiates/retrieves AfterPrice block per product
│   └── Plugin/
│       ├── AfterPrice.php             ← Around plugin on Magento\Framework\Pricing\Render
│       ├── ConfigurablePrice.php      ← Injects base price into configurable JSON swatch config
│       └── Grouped.php                ← Preference on Grouped product type to load EAV attributes
├── ViewModel/
│   └── BasePrice.php                  ← Hyvä-friendly ViewModel implementing ArgumentInterface
├── Block/
│   ├── AfterPrice.php                 ← Frontend block rendering base price output
│   └── Adminhtml/System/Config/Form/Field/UnitConversion.php ← Dynamic unit conversion grid in admin
├── Helper/
│   └── Data.php                       ← Core calculation, unit conversion, and template formatting
├── Plugin/
│   └── ProductAttributes.php          ← Appends unit to product amount in PDP attributes table
└── view/
    └── frontend/
        └── templates/
            ├── afterprice.phtml              ← Standard base price container
            └── configurable/afterprice.phtml ← Base price container + JS listener for swatch updates
```

---

## EAV Product Attributes

All attributes belong to the `Base Price` attribute group on products:

| Attribute Code | Type | Backend Input | Role |
|---|---|---|---|
| `baseprice_is_enable` | int (bool) | boolean | Master toggle to enable/disable base price on an individual product |
| `baseprice_product_amount` | decimal | text | Sold quantity/amount of the product (e.g. `100`, `750`, `1.5`) |
| `baseprice_product_unit` | int | select | Unit of the product (e.g. `g`, `ml`, `kg`, `l`, `m`, `cm`, `mm`) |
| `baseprice_reference_amount` | decimal | text | Reference quantity to calculate the base price for (e.g. `1`, `100`, `1000`) |
| `baseprice_reference_unit` | int | select | Reference unit the price is calculated against (e.g. `kg`, `l`, `m`) |

---

## System Configuration Paths

Under **Stores → Configuration → Magenerds → Base Price** (`section: baseprice`):

| Config Field | XML Path | Default | Description |
|---|---|---|---|
| Enable Extension | `baseprice/general/enabled` | `1` | Global switch to turn base price rendering on or off |
| Base Price Template | `baseprice/general/template` | `{BASE_AMOUNT} {BASE_UNIT} \| {BASE_PRICE} / {REF_AMOUNT} {REF_UNIT}` | Formatting template supporting dynamic tag replacements |
| Unit Conversions | `baseprice/general/conversion` | Serialized array | Defines mathematical conversion factors between units (e.g. 1000g = 1kg) |

### Template Placeholders

* `{BASE_PRICE}`: Calculated, currency-formatted base price
* `{BASE_AMOUNT}`: Product sold amount
* `{BASE_UNIT}`: Product sold unit text
* `{REF_AMOUNT}`: Reference amount
* `{REF_UNIT}`: Reference unit text

---

## Key Components & Flows

### 1. Block Creation (`Model\BasePriceBlock`)
Instantiates or retrieves the template block for rendering base price.
* **Block name:** `baseprice_afterprice_<product_id>`
* **Duplicate protection:** Before creating the block, checks `$this->layout->getBlock($blockName)`. If an element with this ID is already registered in the layout (e.g. in multi-product kit popups, repeated catalog renders, or AJAX calls), it reuses the existing block or generates an anonymous block name `''` to prevent `Magento\Framework\Exception\LocalizedException: An element with ID already exists`.
* Selects template:
  * Configurable: `Magenerds_BasePrice::configurable/afterprice.phtml`
  * Standard: `Magenerds_BasePrice::afterprice.phtml`

### 2. Frontend / Hyvä Integration (`ViewModel\BasePrice`)
In Hyvä themes and modern templates, base price is rendered via the ViewModel:
```xml
<block name="product.price.baseprice" template="Magenerds_BasePrice::product/price/base-price.phtml">
    <arguments>
        <argument name="view_model" xsi:type="object">Magenerds\BasePrice\ViewModel\BasePrice</argument>
    </arguments>
</block>
```
In template:
```php
/** @var \Magenerds\BasePrice\ViewModel\BasePrice $basePriceViewModel */
$basePriceViewModel = $block->getData('view_model');
<?= $basePriceViewModel->getBasePriceBlock($product)->toHtml() ?>
```

### 3. Luma Pricing Plugin (`Model\Plugin\AfterPrice`)
An `aroundRender` plugin on `Magento\Framework\Pricing\Render`:
* Intercepts `final_price`, `wishlist_configured_price`, and `tier_price` rendering.
* Appends base price HTML directly to the rendered price string when applicable.

### 4. Configurable Swatches (`Model\Plugin\ConfigurablePrice`)
An `afterGetJsonConfig` plugin on `Magento\ConfigurableProduct\Block\Product\View\Type\Configurable`:
* Iterates through configurable simple children (`getAllowProducts()`).
* Injects `magenerds_baseprice_text` into `optionPrices[<childId>]` so that when a customer selects a swatch or dropdown option, the base price string updates dynamically.

### 5. PDP Additional Attributes (`Plugin\ProductAttributes`)
An `afterGetAdditionalData` plugin on `Magento\Catalog\Block\Product\View\Attributes`:
* Appends the unit label to `baseprice_product_amount` (e.g. formatting `100` as `100 ml`) in the "More Information" specifications table on PDP.

---

## Rules for Agents

### DO
- **Use `BasePriceBlock::getBasePriceBlock()`** whenever rendering a base price block programmatically to ensure layout uniqueness and proper template assignment.
- **Support both Luma and Hyvä storefronts:** Luma relies on `Model\Plugin\AfterPrice` and jQuery listeners, while Hyvä relies on `ViewModel\BasePrice` and Alpine.js events.
- **Strict typing & PSR-12:** Ensure `declare(strict_types=1);` and proper type declarations on all new and modified PHP classes.
- **Use Service Contracts & DI:** Inject dependencies via constructor; avoid direct `ObjectManager::getInstance()`.
- **Cache flushing:** Flush `layout`, `block_html`, `full_page`, and `config` caches (`ddev n98 cache:flush`) after changes.

### DON'T
- **Never create layout blocks with hardcoded duplicate IDs:** Always check if a block name exists in the layout before calling `$layout->createBlock()`.
- **Do not hardcode currency symbols or formats:** Always use `Magento\Framework\Pricing\Helper\Data` or `PriceCurrencyInterface` via `Helper\Data`.
- **Do not introduce heavy database queries in plugins:** Price render plugins run on catalog lists and search results for every product item; keep memory and queries minimal.

---

## Common CLI Commands

```bash
# Flush cache
ddev n98 cache:flush

# Compile DI
ddev n98 setup:di:compile

# Database upgrades & data patches
ddev n98 setup:upgrade
```
