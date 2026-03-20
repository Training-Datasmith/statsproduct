# Architecture: statsproduct

## Purpose

A PrestaShop statistics module that shows per-product detail stats: page views, purchases, and conversion rate, accessible from the product list or a dedicated stats page.

## Directory Structure

```
statsproduct.php   - Module class (ModuleGraph subclass); all business logic
upgrade/           - Migration scripts
tests/             - PHPUnit test stubs and PHPStan bootstrap
translations/      - Locale string overrides
```

## Key Design Decisions

- **Product-scoped queries**: Filters all data by a single `id_product` parameter so the stats are contextual to the product being viewed.
- **ModuleGraph subclass**: Renders a time-series chart of product views and sales.

## Extension Points

- Extend `getData()` to include add-to-cart events or wishlist counts.

## Dependency Flow

```
statsproduct (ModuleGraph)
  └─> hookDisplayAdminStatsModules() — renders per-product stats chart
  └─> getData()                      — product-specific query
        └─> Db::getInstance()
        └─> Tools::getValue('id_product')
```
