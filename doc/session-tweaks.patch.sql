ALTER TABLE users ADD COLUMN scopes VARCHAR(511) DEFAULT '' AFTER token;
ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT '' AFTER scopes;