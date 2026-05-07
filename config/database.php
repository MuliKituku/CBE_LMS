<?php
class Database {

    private string $host;
    private string $db_name;
    private string $username;
    private string $password;

    public function __construct()
    {
        // Credentials are read from the .env file loaded in public/index.php
        $this->host     = Env::get('DB_HOST',     'localhost');
        $this->db_name  = Env::get('DB_NAME',     'cbelms');
        $this->username = Env::get('DB_USERNAME',  'root');
        $this->password = Env::get('DB_PASSWORD',  '');
    }

    public function connect(): PDO
    {
        try {
            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            $msg = defined('APP_DEBUG') && APP_DEBUG
                ? $e->getMessage()
                : 'A database error occurred. Please contact the administrator.';
            die("Database connection failed: " . $msg);
        }
    }
}
