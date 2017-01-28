CREATE TABLE `drug_stock_movement` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `drug` varchar(200) NOT NULL,
  `transaction_date` varchar(10) NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `transaction_type` varchar(200) NOT NULL,
  `source` varchar(10) NOT NULL,
  `destination` varchar(10) NOT NULL,
  `Source_Destination` varchar(50) DEFAULT NULL,
  `expiry_date` varchar(10) NOT NULL,
  `packs` varchar(10) NOT NULL,
  `quantity` varchar(10) NOT NULL,
  `quantity_out` varchar(10) NOT NULL DEFAULT '0',
  `balance` double NOT NULL DEFAULT '0',
  `unit_cost` varchar(10) NOT NULL,
  `amount` varchar(10) NOT NULL,
  `remarks` text NOT NULL,
  `operator` varchar(50) NOT NULL,
  `order_number` varchar(10) NOT NULL,
  `facility` varchar(10) NOT NULL,
  `machine_code` varchar(10) NOT NULL,
  `merged_from` varchar(50) NOT NULL,
  `timestamp` varchar(50) NOT NULL,
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `ccc_store_sp` (`ccc_store_sp`),
  KEY `Source_Destination` (`Source_Destination`),
  CONSTRAINT `drug_stock_movement_ibfk_1` FOREIGN KEY (`ccc_store_sp`) REFERENCES `ccc_store_service_point` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1