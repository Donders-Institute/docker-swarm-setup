USE registry;

CREATE TABLE users (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(255),
    email varchar(255),
    PRIMARY KEY (id),
    UNIQUE KEY (name, email)
);
