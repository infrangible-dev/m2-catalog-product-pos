# Changelog

## 2.0.0

### Breaking changes

- Requires Magento 2.4.6 or newer and PHP 8.2 or newer. Older versions of Magento and PHP 7.1 to 8.1 are no longer
  supported; use version 1.x for these.
- Requires `infrangible/m2-catalog-product-swatches` 2.0, `infrangible/m2-core` 5.0, `infrangible/m2-foundation` 3.0
  and `fewedev/base` 2.4.3.
- All classes have native types and use constructor property promotion. `Helper\Data` takes the database helper and a
  logger as well, the observers and plugins take only the helpers they use.
- The button is a link (`<a>`) instead of a button with an `onclick` script.

### Fixed

- The rules did not apply to the variants of configurable products: Magento loads the variants without the
  point-of-sale attribute, so a variant "In store only" kept its add-to-cart button, could be added to the cart, and
  neither its value nor the button were shown. Now the values are read from the database.
- Variants that cannot be bought online could be added to the cart, because Magento only checks whether the
  configurable product is saleable. Now they are refused with "The selected option of this product can not be bought
  online."
- The button link was inserted into a script without escaping, so a link with a quote ran script code when the button
  was clicked. Links are escaped now, and links with a script scheme (`javascript:` and the like) are ignored.

### Added

- Translations in 24 locales.
- Unit tests.
