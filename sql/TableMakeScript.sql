-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema store_db
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema store_db
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `store_db` DEFAULT CHARACTER SET utf8 ;
USE `store_db` ;

-- -----------------------------------------------------
-- Table `store_db`.`user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `store_db`.`user` ;

CREATE TABLE IF NOT EXISTS `store_db`.`user` (
  `accountId` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(45) NOT NULL,
  `firstName` VARCHAR(45) NOT NULL,
  `lastName` VARCHAR(45) NULL,
  `email` VARCHAR(45) NOT NULL,
  `passwordHash` VARCHAR(255) NOT NULL,
  `admin` VARCHAR(45) NOT NULL DEFAULT 'F',
  `privacy` VARCHAR(5) NOT NULL DEFAULT 'F',
  `registeredAt` DATETIME NOT NULL DEFAULT NOW(),
  PRIMARY KEY (`accountId`),
  UNIQUE INDEX `accountId_UNIQUE` (`accountId` ASC),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `store_db`.`cart`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `store_db`.`cart` ;

CREATE TABLE IF NOT EXISTS `store_db`.`cart` (
  `cartId` INT NOT NULL AUTO_INCREMENT,
  `accountId` INT NOT NULL,
  `createdAt` DATETIME NOT NULL DEFAULT NOW(),
  `updatedAt` DATETIME NOT NULL DEFAULT NOW(),
  PRIMARY KEY (`cartId`, `accountId`),
  INDEX `cartFK_accountId_idx` (`accountId` ASC),
  CONSTRAINT `cartFK_accountId`
    FOREIGN KEY (`accountId`)
    REFERENCES `store_db`.`user` (`accountId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `store_db`.`productMeta`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `store_db`.`productMeta` ;

CREATE TABLE IF NOT EXISTS `store_db`.`productMeta` (
  `metaId` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(45) NOT NULL UNIQUE,
  `type` VARCHAR(45) NOT NULL,
  `size` INT NOT NULL,
  `description` VARCHAR(250) NOT NULL,
  `discount` FLOAT NULL DEFAULT '0.0',
  PRIMARY KEY (`metaId`),
  UNIQUE INDEX `metaId_UNIQUE` (`metaId` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `store_db`.`product`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `store_db`.`product` ;

CREATE TABLE IF NOT EXISTS `store_db`.`product` (
  `productId` INT NOT NULL AUTO_INCREMENT,
  `metaId` INT NOT NULL,
  `price` FLOAT NOT NULL,
  `quantity` INT NOT NULL,
  `viewCode` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`productId`, `metaId`),
  UNIQUE INDEX `productId_UNIQUE` (`productId` ASC),
  INDEX `productFK_metaId_idx` (`metaId` ASC),
  CONSTRAINT `productFK_metaId`
    FOREIGN KEY (`metaId`)
    REFERENCES `store_db`.`productMeta` (`metaId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `store_db`.`cartItem`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `store_db`.`cartItem` ;

CREATE TABLE IF NOT EXISTS `store_db`.`cartItem` (
  `cartItemId` INT NOT NULL AUTO_INCREMENT,
  `productId` INT NOT NULL,
  `cartId` INT NOT NULL,
  `quantity` INT NOT NULL,
  `addedAt` DATETIME NOT NULL,
  PRIMARY KEY (`cartItemId`, `productId`, `cartId`),
  INDEX `cartItemFK_cartId_idx` (`cartId` ASC),
  INDEX `cartItemFK_productId_idx` (`productId` ASC),
  UNIQUE INDEX `cartItemId_UNIQUE` (`cartItemId` ASC),
  CONSTRAINT `cartItemFK_cartId`
    FOREIGN KEY (`cartId`)
    REFERENCES `store_db`.`cart` (`cartId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `cartItemFK_productId`
    FOREIGN KEY (`productId`)
    REFERENCES `store_db`.`product` (`productId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `store_db`.`location`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `store_db`.`location` ;

CREATE TABLE IF NOT EXISTS `store_db`.`location` (
  `locationId` INT NOT NULL AUTO_INCREMENT,
  `country` VARCHAR(45) NOT NULL,
  `state` VARCHAR(45) NOT NULL,
  `city` VARCHAR(45) NOT NULL,
  `address` VARCHAR(45) NOT NULL,
  `postalCode` VARCHAR(8) NOT NULL,
  `accountId` INT NOT NULL,
  PRIMARY KEY (`locationId`, `accountId`),
  UNIQUE INDEX `locationId_UNIQUE` (`locationId` ASC),
  UNIQUE INDEX `accountId_UNIQUE` (`accountId` ASC),
  CONSTRAINT `locationFK_accountId`
    FOREIGN KEY (`accountId`)
    REFERENCES `store_db`.`user` (`accountId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `store_db`.`order`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `store_db`.`order` ;

CREATE TABLE IF NOT EXISTS `store_db`.`order` (
  `orderId` INT NOT NULL AUTO_INCREMENT,
  `accountId` INT NOT NULL,
  `status` VARCHAR(45) NOT NULL,
  `subTotal` FLOAT NOT NULL,
  `tax` FLOAT NOT NULL,
  `shipping` FLOAT NOT NULL,
  `grandTotal` FLOAT NOT NULL,
  `createdAt` DATETIME NOT NULL,
  `locationId` INT NOT NULL,
  PRIMARY KEY (`orderId`, `accountId`, `locationId`),
  INDEX `orderFK_accountId_idx` (`accountId` ASC),
  INDEX `orderFK_locationId_idx` (`locationId` ASC),
  CONSTRAINT `orderFK_accountId`
    FOREIGN KEY (`accountId`)
    REFERENCES `store_db`.`user` (`accountId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `orderFK_locationId`
    FOREIGN KEY (`locationId`)
    REFERENCES `store_db`.`location` (`locationId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `store_db`.`orderItem`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `store_db`.`orderItem` ;

CREATE TABLE IF NOT EXISTS `store_db`.`orderItem` (
  `orderItemId` INT NOT NULL AUTO_INCREMENT,
  `productId` INT NOT NULL,
  `orderId` INT NOT NULL,
  `quantity` INT NOT NULL,
  `createdAt` DATETIME NOT NULL,
  PRIMARY KEY (`orderItemId`, `productId`, `orderId`),
  INDEX `orderItemFK_productId_idx` (`productId` ASC),
  INDEX `orderItemFK_orderId_idx` (`orderId` ASC),
  CONSTRAINT `orderItemFK_productId`
    FOREIGN KEY (`productId`)
    REFERENCES `store_db`.`product` (`productId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `orderItemFK_orderId`
    FOREIGN KEY (`orderId`)
    REFERENCES `store_db`.`order` (`orderId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `store_db`.`transaction`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `store_db`.`transaction` ;

CREATE TABLE IF NOT EXISTS `store_db`.`transaction` (
  `transactionId` INT NOT NULL AUTO_INCREMENT,
  `accountId` INT NOT NULL,
  `orderId` INT NOT NULL,
  `code` VARCHAR(45) NOT NULL,
  `type` VARCHAR(45) NOT NULL,
  `status` VARCHAR(45) NOT NULL,
  `createdAt` DATETIME NOT NULL,
  PRIMARY KEY (`transactionId`, `accountId`, `orderId`),
  INDEX `transactionFK_accountId_idx` (`accountId` ASC, `orderId` ASC),
  CONSTRAINT `transactionFK_accountId_orderId`
    FOREIGN KEY (`accountId` , `orderId`)
    REFERENCES `store_db`.`order` (`accountId` , `orderId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- View `productFullInfo` required in product listings page
-- -----------------------------------------------------
USE store_db;

CREATE OR REPLACE VIEW `productFullInfo`
	AS SELECT p.`productId`,
		      `metaId`,
		      p.`price`,
		      p.`quantity`,
		      p.`viewCode`,
		      pm.`title`,
		      pm.`type`,
		      pm.`size`,
		      pm.`description`,
		      pm.`discount`
		 FROM `product` p
			INNER JOIN `productMeta` pm
			USING (`metaId`);

CREATE OR REPLACE VIEW `cartInfo`
	AS SELECT ci.`cartId`,
		ci.`cartItemId`,
		ci.`quantity`,
        `productId`,
        p.`price`,
        p.`quantity` AS 'maxQuantity',
        p.`viewCode`,
        pm.`title`,
        pm.`description`
	FROM `cartItem` ci
		INNER JOIN `product` p
        USING (`productId`)
        INNER JOIN `productMeta` pm
        ON (pm.`metaId` = p.`metaId`);

CREATE OR REPLACE VIEW `orderInfo`
	AS SELECT `orderId`,
			  o.`accountId`,
			  o.`createdAt`,
              o.`status`,
			  `productId`,
              p.`price`,
              pm.`discount`,
              oi.`quantity`,
              p.`viewCode`,
              pm.`title`,
              pm.`type`,
              pm.`size`,
              pm.`description`
		 FROM `order` o
			INNER JOIN `orderItem` oi
			USING (`orderId`)
            INNER JOIN `product` p
            USING (`productId`)
            INNER JOIN `productMeta` pm
            USING (`metaId`)
		 ORDER BY `orderId`;
-- -----------------------------------------------------
-- Dummy Data Inserts
-- -----------------------------------------------------

-- user Table inserts
INSERT INTO `user` (`username`, `firstName`, `lastName`, `email`, `passwordHash`, `admin`, `privacy`, `registeredAt`)
VALUES ('Admin', 'admin', 'admin', 'admin.admin@gmail.com', '$2y$10$GupMcuoSLuF82yGMdDWUjOOx0ZPF3e8wH9kC7VMOlbg08cpRl/gi2', 'T', 'F','2021-05-14' );

-- location Table inserts
INSERT INTO `location`(`country`, `state`, `city`, `address`, `postalCode`, `accountId`)
VALUES ('Canada', 'BC', 'Sidney', '123 abc.rd', 'V1K 1M2', '1');

-- productMeta Table inserts
INSERT INTO `productMeta` (`title`, `type`, `size`, `description`, `discount`)
VALUES('Black Mug', 'mug', '8', 'Simple black mug', default);
INSERT INTO `productMeta` (`title`, `type`, `size`, `description`, `discount`)
VALUES('White Mug', 'mug', '8', 'Simple white mug',  default);
INSERT INTO `productMeta` (`title`, `type`, `size`, `description`, `discount`)
VALUES('White Teacup', 'teacup', '4', 'Simple white teacup',  default);
INSERT INTO `productMeta` (`title`, `type`, `size`, `description`, `discount`)
VALUES('Flower Patterned Teacup', 'teacup', '4', 'Standard teacup with ornate garden flowers, gold trim and vibrant colors',  default);
INSERT INTO `productMeta` (`title`, `type`, `size`, `description`, `discount`)
VALUES('Oriental Teacup', 'teacup', '4', 'Oriental teacup with ornate flowers',  default);
INSERT INTO `productMeta` (`title`, `type`, `size`, `description`, `discount`)
VALUES('Owl Shaped Cup', 'custom', '16', 'Custom-made owl shaped cup',  default);
INSERT INTO `productMeta` (`title`, `type`, `size`, `description`, `discount`)
VALUES('Red Oriental Teacup', 'special', '4', 'Special Offer! Handmade red oriental teacup', '0.05');
INSERT INTO `productMeta` (`title`, `type`, `size`, `description`, `discount`)
VALUES('Red Mug', 'special', '8', 'Special Offer! Handmade red mug', '0.05');


-- product Table inserts
INSERT INTO `product`(`metaId`, `price`, `quantity`, `viewCode`)
VALUES ('1','13.99','16','img/black_mug_cup1.jpg');
INSERT INTO `product`(`metaId`, `price`, `quantity`, `viewCode`)
VALUES ('2','11.99','12','img/white_mug_cup1.jpg');
INSERT INTO `product`(`metaId`, `price`, `quantity`, `viewCode`)
VALUES ('3','6.99','5','img/white_teacup1.jpg');
INSERT INTO `product`(`metaId`, `price`, `quantity`, `viewCode`)
VALUES ('4','19.99','7','img/pattern_teacup1.jpg');
INSERT INTO `product`(`metaId`, `price`, `quantity`, `viewCode`)
VALUES ('5','11.99','4','img/pattern_teacup2.jpg');
INSERT INTO `product`(`metaId`, `price`, `quantity`, `viewCode`)
VALUES ('6','15.99','1','img/custom_cup1.jpg');
INSERT INTO `product`(`metaId`, `price`, `quantity`, `viewCode`)
VALUES ('7','7.99','3','img/special_offer1.jpg');
INSERT INTO `product`(`metaId`, `price`, `quantity`, `viewCode`)
VALUES ('8','5.99','2','img/special_offer2.jpg');

-- cart Table inserts
INSERT INTO `cart`(`accountId`, `createdAt`, `updatedAt`)
VALUES ('1','2021-05-12','2021-05-14');

-- cartItem Table inserts
INSERT INTO `cartitem`(`productId`, `cartId`, `quantity`, `addedAt`)
VALUES ('1','1','1','2021-05-14');
INSERT INTO `cartitem`(`productId`, `cartId`, `quantity`, `addedAt`)
VALUES ('2','1','1','2021-05-14');
INSERT INTO `cartitem`(`productId`, `cartId`, `quantity`, `addedAt`)
VALUES ('3','1','1','2021-05-14');
INSERT INTO `cartitem`(`productId`, `cartId`, `quantity`, `addedAt`)
VALUES ('4','1','1','2021-05-14');
INSERT INTO `cartitem`(`productId`, `cartId`, `quantity`, `addedAt`)
VALUES ('5','1','2','2021-05-14');

-- order Table inserts
INSERT INTO `order`(`accountId`, `status`, `subTotal`, `tax`, `shipping`, `grandTotal`, `locationId`, `createdAt`) 
VALUES ('1','new','15.59','0.12','5.56','14.28','1','2021-05-14');
INSERT INTO `order`(`accountId`, `status`, `subTotal`, `tax`, `shipping`, `grandTotal`, `locationId`, `createdAt`) 
VALUES ('1','shipping','15.59','0.12','5.56','14.28','1','2021-05-07');
INSERT INTO `order`(`accountId`, `status`, `subTotal`, `tax`, `shipping`, `grandTotal`, `locationId`, `createdAt`) 
VALUES ('1','delivered','15.59','0.12','5.56','14.28','1','2021-05-01');



-- orderItem Table inserts
INSERT INTO `orderitem`(`productId`, `orderId`, `quantity`, `createdAt`) 
VALUES ('1','1','2','2021-05-15');
INSERT INTO `orderitem`(`productId`, `orderId`, `quantity`, `createdAt`) 
VALUES ('2','2','2','2021-05-07');
INSERT INTO `orderitem`(`productId`, `orderId`, `quantity`, `createdAt`) 
VALUES ('3','2','2','2021-05-07');
INSERT INTO `orderitem`(`productId`, `orderId`, `quantity`, `createdAt`) 
VALUES ('4','3','2','2021-05-01');
INSERT INTO `orderitem`(`productId`, `orderId`, `quantity`, `createdAt`) 
VALUES ('5','3','2','2021-05-01');
INSERT INTO `orderitem`(`productId`, `orderId`, `quantity`, `createdAt`) 
VALUES ('6','3','2','2021-05-01');


-- transaction Table inserts
INSERT INTO `transaction`(`accountId`, `orderId`, `code`, `type`, `status`, `createdAt`)
VALUES ('1','1','200','credit','success','2021-05-15');