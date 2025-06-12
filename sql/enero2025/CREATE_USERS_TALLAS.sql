CREATE TABLE users_tallas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    remeras VARCHAR(10),
    pantalones VARCHAR(10),
    shorts VARCHAR(10),
    vestidos VARCHAR(10),
    abrigos VARCHAR(10),
    calzados INT,
    accesorios VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id) -- Suponiendo que tienes una tabla 'users' para los usuarios
);
