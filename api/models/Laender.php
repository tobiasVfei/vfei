<?php

class Laender {
    
    private PDO $conn;
    private string $table_name = 'tbl_countries';

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll(): array {
        $query = 'SELECT id_country, country FROM ' . $this->table_name . ' ORDER BY country ASC';
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne(int $id): array|false {
        $query = 'SELECT id_country, country FROM ' . $this->table_name . ' WHERE id_country = :id LIMIT 1';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create(object $data): int|false {
        $query = 'INSERT INTO ' . $this->table_name . ' (country) VALUES (:country)';

        $stmt = $this->conn->prepare($query);
        
        $countryName = htmlspecialchars(strip_tags($data->country));

        $stmt->bindParam(':country', $countryName);

        try {
            if ($stmt->execute()) {
                return (int)$this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            return false; 
        }
    }

    public function update(int $id, object $data): bool {
        $query = 'UPDATE ' . $this->table_name . ' SET country = :country WHERE id_country = :id';

        $stmt = $this->conn->prepare($query);
        
        $countryName = htmlspecialchars(strip_tags($data->country));

        $stmt->bindParam(':country', $countryName);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete(int $id): bool {
        $query = 'DELETE FROM ' . $this->table_name . ' WHERE id_country = :id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            return false; 
        }
    }
}