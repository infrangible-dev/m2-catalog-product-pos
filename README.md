# Infrangible Catalog Product POS

Ties the online saleability of products to a product attribute, the point of sale, for example with the values
"Online", "In store only" and "Partner". For each value you decide whether products can be bought online, and products
that cannot show a button of your own instead of the add-to-cart button, for example "Find a store" with a link to your
store finder.

## Configuration

Stores > Configuration > Infrangible Solutions > Product Point of Sale:

- **Attribute > Product Attribute:** the point-of-sale attribute (select or multiselect).
- **Attribute > Values Separator:** separates the labels of products with several values (default `, `).

After saving, the page shows one group per option of the attribute:

| Setting          | Use                                                                                       |
|------------------|-------------------------------------------------------------------------------------------|
| **Not Saleable** | products with this value cannot be bought online                                          |
| **Commanding**   | this value decides on its own, even if other values of the product allow buying online    |
| **Show Button**  | products with this value show a button instead of the add-to-cart button                  |
| **Button Text**  | text of the button                                                                        |
| **Button Url**   | link of the button; links with a script scheme (`javascript:` and the like) are ignored   |

All settings can be changed per website and store view.

## How it works

- A product with several values can be bought online as long as one of its values allows it, unless a commanding value
  forbids it.
- The value of the product is shown on the product page.
- Simple products that cannot be bought online are not saleable: the add-to-cart button is hidden and they cannot be
  added to the cart.
- Variants of configurable products that cannot be bought online stay selectable, so shoppers see their point of sale
  and the button. When such a variant is selected, the button replaces the add-to-cart button, and the variant cannot
  be added to the cart ("The selected option of this product can not be bought online.").
- The values of the variants are read from the database when Magento loaded the variants without the attribute.

[Infrangible Catalog Product Swatches](https://www.infrangible.dev/modules/catalog-product-swatches) shows the value of
the selected variant. [Infrangible Catalog Product POS Hyvä Theme](https://www.infrangible.dev/modules/catalog-product-pos-hyva)
brings the features to stores with the Hyvä theme.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.6 or newer
- PHP 8.2 or newer

## Installation

```bash
composer require infrangible/m2-catalog-product-pos
bin/magento module:enable Infrangible_CatalogProductPOS
bin/magento setup:upgrade
```

In production mode, also run `bin/magento setup:di:compile` and `bin/magento setup:static-content:deploy`.

## License

Infrangible Catalog Product POS is licensed under the MIT License - see the LICENSE file for details.
