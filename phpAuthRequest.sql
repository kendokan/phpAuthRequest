BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS `users` (
	`username`	TEXT NOT NULL UNIQUE,
	`password`	TEXT NOT NULL,
	`access_level`	INTEGER NOT NULL
);
COMMIT;
