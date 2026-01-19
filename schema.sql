CREATE TABLE clients (
    clntId VARCHAR(80) NOT NULL PRIMARY KEY,
    clntSecret VARCHAR(255),
    clntName VARCHAR(255) NOT NULL,
    clntRedirectUri VARCHAR(2000) NOT NULL,
    clntIsConfidential TINYINT(1) DEFAULT 0
);

CREATE TABLE users (
    userId INTEGER PRIMARY KEY AUTO_INCREMENT,
    userUsername VARCHAR(255) NOT NULL UNIQUE,
    userPassword VARCHAR(255) NOT NULL
);

CREATE TABLE scopes (
    scopId VARCHAR(80) NOT NULL PRIMARY KEY,
    scopDescription VARCHAR(255)
);

CREATE TABLE access_tokens (
    actkId VARCHAR(100) NOT NULL PRIMARY KEY,
    actkUserId INTEGER,
    actkClientId VARCHAR(80) NOT NULL,
    actkScopes TEXT,
    actkRevoked TINYINT(1) DEFAULT 0,
    actkExpiresAt DATETIME,
    FOREIGN KEY (actkClientId) REFERENCES clients(clntId)
);

CREATE TABLE refresh_tokens (
    rftkId VARCHAR(100) NOT NULL PRIMARY KEY,
    rftkAccessTokenId VARCHAR(100) NOT NULL,
    rftkRevoked TINYINT(1) DEFAULT 0,
    rftkExpiresAt DATETIME,
    FOREIGN KEY (rftkAccessTokenId) REFERENCES access_tokens(actkId)
);

CREATE TABLE auth_codes (
    aucdId VARCHAR(100) NOT NULL PRIMARY KEY,
    aucdUserId INTEGER NOT NULL,
    aucdClientId VARCHAR(80) NOT NULL,
    aucdScopes TEXT,
    aucdRevoked TINYINT(1) DEFAULT 0,
    aucdExpiresAt DATETIME,
    FOREIGN KEY (aucdClientId) REFERENCES clients(clntId)
);

-- Insert a test client
INSERT INTO clients (clntId, clntSecret, clntName, clntRedirectUri, clntIsConfidential) VALUES 
('testclient', 'testsecret', 'Test Client', 'http://localhost:8080/callback', 1);

-- Insert a test user (password: password)
INSERT INTO users (userUsername, userPassword) VALUES 
('testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert scopes
INSERT INTO scopes (scopId, scopDescription) VALUES ('basic', 'Basic Access');
