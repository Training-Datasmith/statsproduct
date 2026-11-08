CREATE TABLE IF NOT EXISTS ps_orders (
    id_order INT NOT NULL PRIMARY KEY,
    id_shop INT NOT NULL,
    id_customer INT NOT NULL,
    valid TINYINT(1) NOT NULL DEFAULT 1,
    date_add DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_order_detail (
    id_order_detail INT NOT NULL PRIMARY KEY,
    id_order INT NOT NULL,
    product_id INT NOT NULL,
    product_attribute_id INT NOT NULL DEFAULT 0,
    product_name VARCHAR(255) NOT NULL DEFAULT '',
    product_quantity INT NOT NULL,
    unit_price_tax_excl DECIMAL(20, 6) NOT NULL DEFAULT 0,
    total_price_tax_excl DECIMAL(20, 6) NOT NULL DEFAULT 0,
    tax_name VARCHAR(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_product (
    id_product INT NOT NULL PRIMARY KEY,
    reference VARCHAR(64) NOT NULL DEFAULT '',
    state INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_product_shop (
    id_product INT NOT NULL,
    id_shop INT NOT NULL,
    PRIMARY KEY (id_product, id_shop)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_product_lang (
    id_product INT NOT NULL,
    id_lang INT NOT NULL,
    id_shop INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    PRIMARY KEY (id_product, id_lang, id_shop)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_stock_available (
    id_product INT NOT NULL,
    id_product_attribute INT NOT NULL DEFAULT 0,
    id_shop INT NOT NULL,
    id_shop_group INT NOT NULL DEFAULT 0,
    quantity INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id_product, id_product_attribute, id_shop, id_shop_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_page_type (
    id_page_type INT NOT NULL PRIMARY KEY,
    name VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_page (
    id_page INT NOT NULL PRIMARY KEY,
    id_page_type INT NOT NULL,
    id_object INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_date_range (
    id_date_range INT NOT NULL PRIMARY KEY,
    time_start DATETIME NOT NULL,
    time_end DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_page_viewed (
    id_page INT NOT NULL,
    id_date_range INT NOT NULL,
    id_shop INT NOT NULL,
    counter INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id_page, id_date_range, id_shop)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_category (
    id_category INT NOT NULL PRIMARY KEY,
    id_parent INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    level_depth INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_category_lang (
    id_category INT NOT NULL,
    id_lang INT NOT NULL,
    id_shop INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    PRIMARY KEY (id_category, id_lang, id_shop)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_category_shop (
    id_category INT NOT NULL,
    id_shop INT NOT NULL,
    position INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id_category, id_shop)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_product_attribute (
    id_product_attribute INT NOT NULL PRIMARY KEY,
    id_product INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ps_category_product (
    id_category INT NOT NULL,
    id_product INT NOT NULL,
    PRIMARY KEY (id_category, id_product)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
