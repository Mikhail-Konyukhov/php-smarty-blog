DROP TABLE IF EXISTS article_category;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS categories;

CREATE TABLE categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(255) NOT NULL,
    description TEXT         NOT NULL,
    PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE articles (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title        VARCHAR(255) NOT NULL,
    description  TEXT         NOT NULL,
    body         MEDIUMTEXT   NOT NULL,
    image        VARCHAR(255) NOT NULL,
    views        INT UNSIGNED NOT NULL DEFAULT 0,
    published_at DATETIME     NOT NULL,
    PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE article_category (
    category_id INT UNSIGNED NOT NULL,
    article_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (category_id, article_id),
    INDEX (article_id),
    FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
