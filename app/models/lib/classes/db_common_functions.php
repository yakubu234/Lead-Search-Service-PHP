<?php

declare(strict_types=1);

/**
 * Live implementation of the DB_Common_Functions class.
 * This class provides the methods expected by the LeadSearchService,
 * connecting to a real database and returning live data.
 */
class DB_Common_Functions
{
    // These constants should be defined in your config.php file.
    // define('DB_HOST', 'localhost');
    // define('DB_NAME', 'your_database_name');
    // define('DB_USER', 'your_username');
    // define('DB_PASS', 'your_password');
    private static ?PDO $pdo = null;

    public function __construct()
    {
        if (self::$pdo === null) {
            $this->connect();
        }
    }

    private function connect(): void
    {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            // In a production environment, I would log this error securely.
            // For a test environment, die() is acceptable.
            die('Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Returns a human-readable text for a search criterion.
     */
    public function getSearchCriteriaText(string $searchBy): string
    {
        return ucwords(str_replace('_', ' ', $searchBy));
    }

    /**
     * Stores data in the database using a prepared statement to prevent SQL injection.
     */
    public function store_data(array $data, array $fields, string $tableName): void
    {
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $fieldNames = implode(', ', $fields);
        $sql = "INSERT INTO {$tableName} ({$fieldNames}) VALUES ({$placeholders})";
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($data);
    }

    // --- Search methods with offset (for pagination) ---

    public function searchLeadsByFirstNameWithOffset(string $searchText, int $ownerId, int $offset): array
    {
        return $this->doSearch('fname', $searchText, $ownerId, false, $offset);
    }

    public function searchLeadsByLastNameWithOffset(string $searchText, int $ownerId, int $offset): array
    {
        return $this->doSearch('lname', $searchText, $ownerId, false, $offset);
    }

    public function searchLeadsByPhoneWithOffset(string $phoneSearchText, int $ownerId, int $offset): array
    {
        return $this->doSearch('main_phone', $phoneSearchText, $ownerId, false, $offset);
    }

    public function searchLeadsByEmailWithOffset(string $emailSearchText, int $ownerId, int $offset): array
    {
        return $this->doSearch('email', $emailSearchText, $ownerId, false, $offset);
    }
    
    public function searchLeadsByCRMIdWithOffset(string $searchText, int $ownerId, int $offset): array
    {
        return $this->doSearch('crm_id', $searchText, $ownerId, false, $offset, false);
    }
    
    public function searchLeadsByMktIdWithOffset(string $searchText, int $ownerId, int $offset): array
    {
        return $this->doSearch('mkt_id', $searchText, $ownerId, false, $offset, false);
    }

    public function searchLeadsByCompanyNameWithOffset(string $searchText, int $ownerId, int $offset): array
    {
        return $this->doSearch('company_name', $searchText, $ownerId, false, $offset);
    }

    // --- Dedicated methods for counting leads ---

    public function countLeadsByFirstName(string $searchText, int $ownerId): int
    {
        return $this->doSearch('fname', $searchText, $ownerId, true);
    }

    public function countLeadsByLastName(string $searchText, int $ownerId): int
    {
        return $this->doSearch('lname', $searchText, $ownerId, true);
    }

    public function countLeadsByPhone(string $phoneSearchText, int $ownerId): int
    {
        return $this->doSearch('main_phone', $phoneSearchText, $ownerId, true);
    }

    public function countLeadsByEmail(string $emailSearchText, int $ownerId): int
    {
        return $this->doSearch('email', $emailSearchText, $ownerId, true);
    }

    public function countLeadsByCRMId(string $searchText, int $ownerId): int
    {
        return $this->doSearch('crm_id', $searchText, $ownerId, true, 0, false);
    }
    
    public function countLeadsByMktId(string $searchText, int $ownerId): int
    {
        return $this->doSearch('mkt_id', $searchText, $ownerId, true, 0, false);
    }

    public function countLeadsByCompanyName(string $searchText, int $ownerId): int
    {
        return $this->doSearch('company_name', $searchText, $ownerId, true);
    }

    /**
     * A private helper method to execute a search query.
     * It uses prepared statements and handles both "LIKE" and exact match searches.
     * The new $isCount parameter allows it to perform a SELECT COUNT(*) query.
     */
    private function doSearch(
        string $column,
        string $searchText,
        int $ownerId,
        bool $isCount = false,
        int $offset = 0,
        bool $useLike = true
    ): array|int {
        // Build the base SQL query
        $selectClause = $isCount ? "COUNT(*)" : "SELECT *";
        $sql = "{$selectClause} FROM leads WHERE owner_id = ?";
        $params = [$ownerId];

        // Add the search criteria to the query
        if ($searchText !== '') {
            if ($useLike) {
                $sql .= " AND {$column} LIKE ?";
                $params[] = "%{$searchText}%";
            } else {
                $sql .= " AND {$column} = ?";
                $params[] = $searchText;
            }
        }

        // Add LIMIT and OFFSET for pagination only when not counting
        if (!$isCount && $offset >= 0) {
            $sql .= " LIMIT " . PAGE_SIZE . " OFFSET ?";
            $params[] = $offset;
        }

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);

        if ($isCount) {
            return (int) $stmt->fetchColumn();
        }

        return $stmt->fetchAll();
    }
}
