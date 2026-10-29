<?php /* Opens PHP for the database helper class. */ ?>
<?php

// Loads config.php once for constants and helper functions.
require_once __DIR__ . '/config.php';

// Defines the DB class that wraps all database operations.
class DB {
    // Stores one shared PDO connection for the whole request.
    private static $pdo = null;

    // Starts the docblock for the connection method.
    /**
     * Singleton PDO connection instance for MySQL
     */
    // Declares the method that returns the shared PDO connection.
    public static function getConnection() {
        // Checks whether a connection has not been created yet.
        if (self::$pdo === null) {
            // Sets the database host name.
            $host = 'localhost';
            // Sets the database name used by the system.
            $db   = 'csite_grad_school';
            // Sets the MySQL user name.
            $user = 'root';
            // Sets the MySQL password; local XAMPP uses an empty password.
            $pass = '';
            // Sets the MySQL port.
            $port = '3306';

            // Builds the DSN string with host, port, database, and UTF-8 charset.
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            
            // Starts a try block so connection errors can be caught.
            try {
                // Creates the PDO connection with the DSN and login details.
                self::$pdo = new PDO($dsn, $user, $pass, [
                    // Makes PDO throw an exception when a query fails.
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    // Returns each row as an associative array keyed by column name.
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Uses real prepared statements instead of emulated ones.
                    PDO::ATTR_EMULATE_PREPARES   => false,
                // Closes the PDO options array.
                ]);
            // Catches a PDOException if the connection fails.
            } catch (PDOException $e) {
                // Stops the page and prints the connection error.
                die("Database Connection Error: " . $e->getMessage());
            // Closes the catch block.
            }
        // Closes the block that created the connection.
        }
        // Returns the shared PDO connection to the caller.
        return self::$pdo;
    // Closes the getConnection method.
    }

    // Starts the docblock for the insert method.
    /**
     * Insert a single record and return the inserted data array with its ID
     */
    // Declares insert(), which adds one row and returns it.
    public static function insert($table, $data) {
        // Gets the shared database connection.
        $pdo = self::getConnection();
        // Gets the column names from the data array keys.
        $fields = array_keys($data);
        // Builds named placeholders such as :email for each column.
        $placeholders = array_map(fn($f) => ":$f", $fields);

        // Starts building the INSERT statement.
        $sql = sprintf(
            // Puts the table name in backticks inside the query.
            "INSERT INTO `%s` (`%s`) VALUES (%s)",
            // Joins the column names in backticks.
            $table,
            // Joins the named placeholders with commas.
            implode('`, `', $fields),
            // Closes the sprintf call that builds the SQL.
            implode(', ', $placeholders)
        // Finishes the INSERT SQL string.
        );

        // Prepares the INSERT statement.
        $stmt = $pdo->prepare($sql);
        // Runs the statement with the supplied data values.
        $stmt->execute($data);

        // Reads the auto-increment id of the new row.
        $insertId = $pdo->lastInsertId();

        // Checks whether the database returned a new id.
        if ($insertId) {
            // Starts a map from table names to primary-key column names.
            $pkMap = [
                // Maps users to user_id.
                'users' => 'user_id',
                // Maps students to student_id.
                'students' => 'student_id',
                // Maps applications to application_id.
                'applications' => 'application_id',
                // Maps advisor_pool to adviser_id.
                'advisor_pool' => 'adviser_id',
                // Maps application_documents to document_id.
                'application_documents' => 'document_id',
                // Maps panel_members to panel_member_id.
                'panel_members' => 'panel_member_id',
                // Maps templates to template_id.
                'templates' => 'template_id',
                // Maps application_comments to comment_id.
                'application_comments' => 'comment_id',
                // Maps payments to payment_id.
                'payments' => 'payment_id',
                // Maps schedules to schedule_id.
                'schedules' => 'schedule_id',
                // Maps schedule_panel_assignments to assignment_id.
                'schedule_panel_assignments' => 'assignment_id',
            // Closes the primary-key map.
            ];
            // Picks the matching primary key, or id as a fallback.
            $pk = $pkMap[$table] ?? 'id';
            // Checks whether the data array already has the primary key.
            if (!isset($data[$pk])) {
                // Adds the new id to the returned data array.
                $data[$pk] = (int) $insertId;
            // Closes the primary-key check.
            }
            // Checks whether a user_id should also be added for other tables.
            if ($pk !== 'user_id' && !isset($data['user_id'])) {
                // Adds the new id as user_id when it is missing.
                $data['user_id'] = (int) $insertId;
            // Closes the user_id check.
            }
        // Closes the block that handled the insert id.
        }

        // Returns the data array with the new id.
        return $data;
    // Closes the insert method.
    }

    // Starts the docblock for the find method.
    /**
     * Fetch a single record matching key-value conditions
     */
    // Declares find(), which returns one matching row.
    public static function find($table, $conditions) {
        // Gets the shared database connection.
        $pdo = self::getConnection();
        // Builds WHERE clauses with one named placeholder per condition.
        $where = array_map(fn($k) => "`$k` = :$k", array_keys($conditions));
        // Builds the SELECT query and limits it to one row.
        $sql = sprintf("SELECT * FROM `%s` WHERE %s LIMIT 1", $table, implode(' AND ', $where));

        // Prepares the SELECT statement.
        $stmt = $pdo->prepare($sql);
        // Runs it with the condition values.
        $stmt->execute($conditions);
        // Returns the row, or null when nothing matches.
        return $stmt->fetch() ?: null;
    // Closes the find method.
    }

    // Starts the docblock for the all method.
    /**
     * Fetch all records matching optional conditions
     */
    // Declares all(), which returns every matching row.
    public static function all($table, $conditions = [], $orderBy = '') {
        // Gets the shared database connection.
        $pdo = self::getConnection();
        
        // Checks whether there are no filter conditions.
        if (empty($conditions)) {
            // Builds a simple SELECT all query.
            $sql = "SELECT * FROM `$table`";
            // Adds ORDER BY when an order was supplied.
            if (!empty($orderBy)) $sql .= " ORDER BY $orderBy";
            // Runs the query directly because it has no user values.
            $stmt = $pdo->query($sql);
            // Returns all matching rows.
            return $stmt->fetchAll();
        // Closes the no-filter branch.
        }

        // Builds WHERE clauses for the filter conditions.
        $where = array_map(fn($k) => "`$k` = :$k", array_keys($conditions));
        // Builds the filtered SELECT query.
        $sql = sprintf("SELECT * FROM `%s` WHERE %s", $table, implode(' AND ', $where));
        // Adds ORDER BY when an order was supplied.
        if (!empty($orderBy)) $sql .= " ORDER BY $orderBy";

        // Prepares the filtered query.
        $stmt = $pdo->prepare($sql);
        // Runs it with the filter values.
        $stmt->execute($conditions);
        // Returns all matching rows.
        return $stmt->fetchAll();
    // Closes the all method.
    }

    // Starts the docblock for the update method.
    /**
     * Update records matching key-value conditions
     */
    // Declares update(), which changes matching rows.
    public static function update($table, $data, $conditions) {
        // Gets the shared database connection.
        $pdo = self::getConnection();
        // Builds SET clauses with set_ placeholders.
        $set = array_map(fn($k) => "`$k` = :set_$k", array_keys($data));
        // Builds WHERE clauses with where_ placeholders.
        $where = array_map(fn($k) => "`$k` = :where_$k", array_keys($conditions));

        // Builds the full UPDATE query.
        $sql = sprintf("UPDATE `%s` SET %s WHERE %s", $table, implode(', ', $set), implode(' AND ', $where));

        // Starts the parameter array.
        $params = [];
        // Adds each new value under its set_ placeholder.
        foreach ($data as $k => $v) $params["set_$k"] = $v;
        // Adds each condition value under its where_ placeholder.
        foreach ($conditions as $k => $v) $params["where_$k"] = $v;

        // Prepares the UPDATE statement.
        $stmt = $pdo->prepare($sql);
        // Runs it and returns true or false.
        return $stmt->execute($params);
    // Closes the update method.
    }

    // Starts the docblock for the delete method.
    /**
     * Delete records matching conditions
     */
    // Declares delete(), which removes matching rows.
    public static function delete($table, $conditions) {
        // Gets the shared database connection.
        $pdo = self::getConnection();
        // Builds WHERE clauses with one placeholder per condition.
        $where = array_map(fn($k) => "`$k` = :$k", array_keys($conditions));
        // Builds the DELETE query.
        $sql = sprintf("DELETE FROM `%s` WHERE %s", $table, implode(' AND ', $where));

        // Prepares the DELETE statement.
        $stmt = $pdo->prepare($sql);
        // Runs it with the condition values.
        return $stmt->execute($conditions);
    // Closes the delete method.
    }

    // Starts the docblock for the custom query method.
    /**
     * Execute custom SQL query
     */
    // Declares query() for running custom SQL.
    public static function query($sql, $params = []) {
        // Gets the shared database connection.
        $pdo = self::getConnection();
        // Prepares the custom SQL string.
        $stmt = $pdo->prepare($sql);
        // Runs it with the supplied parameters.
        $stmt->execute($params);
        // Returns the statement so the caller can fetch rows.
        return $stmt;
    // Closes the query method.
    }
// Closes the DB class.
}
