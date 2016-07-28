CREATE TABLE IF NOT EXISTS `EMFUser` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `checked_in` BOOLEAN NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `EMFUserUser` (
  `user_id` int(11) NOT NULL,
  `UID` int(11) NOT NULL,
  PRIMARY KEY (`user_id`, `UID`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- No foreign key constraint on EMFUser as it's refreshed regularly

ALTER TABLE `EMFUserUser`
  ADD CONSTRAINT `emfuseruser_ibfk_1` FOREIGN KEY (`UID`) REFERENCES `User` (`UID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `User` MODIFY COLUMN `Nick` varchar(255) NOT NULL DEFAULT '';
ALTER TABLE `User` MODIFY COLUMN `Name` varchar(123) DEFAULT NULL;

