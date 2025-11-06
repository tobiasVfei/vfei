<?php

class Lehrbetriebe {
    
    private PDO $conn;
    private string $table_name = 'tbl_lehrbetriebe';

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll(): array {
        $query = 'SELECT 
                       id_lehrbetrieb, 
                       firma, 
                       strasse, 
                       plz, 
                       ort 
                  FROM ' . $this->table_name . '
                  ORDER BY firma ASC';
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne(int $id): array|false {
        $query = 'SELECT 
                       id_lehrbetrieb, 
                       firma, 
                       strasse, 
                       plz, 
                       ort 
                  FROM ' . $this->table_name . ' 
                  WHERE id_lehrbetrieb = :id 
                  LIMIT 1';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create(object $data): int|false {
        $query = 'INSERT INTO ' . $this->table_name . ' 
                    (firma, strasse, plz, ort) 
                  VALUES 
                    (:firma, :strasse, :plz, :ort)';

        $stmt = $this->conn->prepare($query);

        $firma   = htmlspecialchars(strip_tags($data->firma));
        $strasse = !empty($data->strasse) ? htmlspecialchars(strip_tags($data->strasse)) : null;
        $plz     = !empty($data->plz)     ? htmlspecialchars(strip_tags($data->plz))     : null;
        $ort     = !empty($data->ort)     ? htmlspecialchars(strip_tags($data->ort))     : null;

        $stmt->bindParam(':firma', $firma);
        $stmt->bindParam(':strasse', $strasse);
        $stmt->bindParam(':plz', $plz);
        $stmt->bindParam(':ort', $ort);

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
        $query = 'UPDATE ' . $this->table_name . ' 
                  SET 
                      firma = :firma, 
                      strasse = :strasse, 
                      plz = :plz, 
                      ort = :ort 
                  WHERE id_lehrbetrieb = :id';

        $stmt = $this->conn->prepare($query);

        $firma   = htmlspecialchars(strip_tags($data->firma));
        $strasse = !empty($data->strasse) ? htmlspecialchars(strip_tags($data->strasse)) : null;
        $plz     = !empty($data->plz)     ? htmlspecialchars(strip_tags($data->plz))     : null;
        $ort     = !empty($data->ort)     ? htmlspecialchars(strip_tags($data->ort))     : null;

        $stmt->bindParam(':firma', $firma);
        $stmt->bindParam(':strasse', $strasse);
        $stmt->bindParam(':plz', $plz);
        $stmt->bindParam(':ort', $ort);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete(int $id): bool {
        $query = 'DELETE FROM ' . $this->table_name . ' WHERE id_lehrbetrieb = :id';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            return false; 
        }
    }
}