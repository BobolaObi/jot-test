--
-- Dump collected by the following command:
-- /usr/local/mysql/bin/mysqldump -u root jotform_new -d --databases > /Library/WebServer/Documents/jotform3/opt/jotform_new.sql
-- 



use jotform_new;

-- So that anonymous forms can also be created. tayfunsen
INSERT INTO users(username) VALUES('');


ALTER TABLE announcement ADD FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE answers ADD FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE answers ADD FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE form_properties ADD FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE forms ADD FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE listings ADD FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE monthly_usage ADD FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE payment_log ADD FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE SET NULL;

ALTER TABLE payments ADD FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE SET NULL;

ALTER TABLE pending_submissions ADD FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE products ADD FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE question_properties ADD FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE submissions ADD FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Enum Changes start here.
ALTER TABLE announcement MODIFY `account_type` ENUM('FREE', 'PREMIUM', 'ENTERPRISE', 'ADMIN');

ALTER TABLE forms MODIFY `status` ENUM('SUSPENDED', 'DELETED', 'AUTOSUSPENDED');

ALTER TABLE listings MODIFY `status` ENUM('ENABLED', 'SUSPENDED', 'DELETED', 'AUTOSUSPENDED');

ALTER TABLE submissions MODIFY `status` ENUM('ACTIVE', 'OVERQUOTA');

ALTER TABLE users MODIFY `account_type` ENUM('FREE', 'PREMIUM', 'ENTERPRISE', 'ADMIN');

ALTER TABLE users MODIFY `status` ENUM('SUSPENDED', 'DELETED', 'AUTOSUSPENDED');

 ALTER TABLE `users` CHANGE `account_type` `account_type` ENUM( 'FREE', 'PREMIUM', 'ENTERPRISE', 'ADMIN', 'GUEST' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL  


delimiter |
CREATE TRIGGER bootstrap_monthly_usage AFTER INSERT ON users
    FOR EACH ROW BEGIN
        INSERT INTO monthly_usage SET username = NEW.username;
    END;
|
delimiter ;


ALTER TABLE `submissions` ADD COLUMN `updated_at` timestamp NULL DEFAULT NULL;

CREATE TRIGGER update_submission BEFORE UPDATE ON submissions FOR EACH ROW SET NEW.updated_at = NOW();
