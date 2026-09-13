<?php
// includes/db.php
require_once __DIR__ . '/config.php';

class DB {
    private static $pdo = null;

    /**
     * Singleton PDO connection instance for MySQL
     */
    public static function getConnection() {
        if (self::$pdo === null) {
            $host = 'localhost';
            $db   = 'csite_grad_school';
            $user = 'root'; // Default XAMPP MySQL user
            $pass = '';     // Default XAMPP MySQL password is empty
            $port = '3306'; // Default MySQL port

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            
            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                die("Database Connection Error: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    /**
     * Insert a single record and return the inserted data array with its ID
     */
    public static function insert($table, $data) {
        $pdo = self::getConnection();
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":$f", $fields);

        $sql = sprintf(
            "INSERT INTO `%s` (`%s`) VALUES (%s)",
            $table,
            implode('`, `', $fields),
            implode(', ', $placeholders)
        );

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        
        $insertId = $pdo->lastInsertId();

        // Return array including auto-generated primary key if applicable
        if ($insertId && !isset($data['user_id'])) {
            $data['user_id'] = (int) $insertId;
        }

        return $data;
    }

    /**
     * Fetch a single record matching key-value conditions
     */
    public static function find($table, $conditions) {
        $pdo = self::getConnection();
        $where = array_map(fn($k) => "`$k` = :$k", array_keys($conditions));
        $sql = sprintf("SELECT * FROM `%s` WHERE %s LIMIT 1", $table, implode(' AND ', $where));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($conditions);
        return $stmt->fetch() ?: null;
    }

    /**
     * Fetch all records matching optional conditions
     */
    public static function all($table, $conditions = [], $orderBy = '') {
        $pdo = self::getConnection();
        
        if (empty($conditions)) {
            $sql = "SELECT * FROM `$table`";
            if (!empty($orderBy)) $sql .= " ORDER BY $orderBy";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll();
        }

        $where = array_map(fn($k) => "`$k` = :$k", array_keys($conditions));
        $sql = sprintf("SELECT * FROM `%s` WHERE %s", $table, implode(' AND ', $where));
        if (!empty($orderBy)) $sql .= " ORDER BY $orderBy";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($conditions);
        return $stmt->fetchAll();
    }

    /**
     * Update records matching key-value conditions
     */
    public static function update($table, $data, $conditions) {
        $pdo = self::getConnection();
        $set = array_map(fn($k) => "`$k` = :set_$k", array_keys($data));
        $where = array_map(fn($k) => "`$k` = :where_$k", array_keys($conditions));

        $sql = sprintf("UPDATE `%s` SET %s WHERE %s", $table, implode(', ', $set), implode(' AND ', $where));

        $params = [];
        foreach ($data as $k => $v) $params["set_$k"] = $v;
        foreach ($conditions as $k => $v) $params["where_$k"] = $v;

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete records matching conditions
     */
    public static function delete($table, $conditions) {
        $pdo = self::getConnection();
        $where = array_map(fn($k) => "`$k` = :$k", array_keys($conditions));
        $sql = sprintf("DELETE FROM `%s` WHERE %s", $table, implode(' AND ', $where));

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($conditions);
    }

    /**
     * Execute custom SQL query
     */
    public static function query($sql, $params = []) {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
