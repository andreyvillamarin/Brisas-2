<?php

class Establishment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene todos los establecimientos de la base de datos.
     * @return array Un array de establecimientos.
     */
    public function getAll() {
        try {
            $stmt = $this->db->query("SELECT * FROM establishments ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener establecimientos: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM establishments WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching establishment by ID: " . $e->getMessage());
            return false;
        }
    }

    public function create($name) {
        try {
            $stmt = $this->db->prepare("INSERT INTO establishments (name) VALUES (:name)");
            $stmt->execute(['name' => $name]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating establishment: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $name) {
        try {
            $sql = "UPDATE establishments SET name = :name WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $id, 'name' => $name]);
        } catch (PDOException $e) {
            error_log("Error updating establishment: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM establishments WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting establishment: " . $e->getMessage());
            return false;
        }
    }
}
?>
