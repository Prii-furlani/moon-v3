<?php
// backend/config/database.php

class Database {
    private static $host = "localhost";
    private static $db_name = "pri04258_moonfinance";
    private static $username = "pri04258_admin";
    private static $password = 'Zflf?XzMl&(';
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4";
                self::$conn = new PDO($dsn, self::$username, self::$password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    "status" => "error", 
                    "message" => "Erro na conexão com o banco de dados."
                ]);
                exit();
            }
        }
        return self::$conn;
    }
}
