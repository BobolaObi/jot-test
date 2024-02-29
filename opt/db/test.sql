UPDATE jotform_new.`users` SET `account_type`='OLDPREMIUM' WHERE `username` IN (SELECT `username` FROM jotform_main.`users` WHERE `account_type` = 'PREMIUM');


UPDATE jotform_new.`users` SET `account_type`='OLDPREMIUM' WHERE account_type='PREMIUM';




; This is for migrating from v2 to v3.
insert into jotform_new.submissions (id, form_id, ip, created_at, status, new, flag) select *, 0, 0 from jotform_main.submissions where form_id = '92870135335';

