<?php
/**
 * Soft Delete Utility Service
 * Provides functions for soft delete operations across the system
 */

require_once '../../../config/config.php';

class SoftDelete {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Soft delete a record by setting deleted_at timestamp
     * @param string $table_name - Name of the table
     * @param string $id_column - Name of the ID column
     * @param int $id_value - ID value to soft delete
     * @return bool - Success status
     */
    public function softDelete($table_name, $id_column, $id_value) {
        $sql = "UPDATE $table_name SET deleted_at = NOW() WHERE $id_column = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id_value);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Soft delete multiple records
     * @param string $table_name - Name of the table
     * @param string $id_column - Name of the ID column
     * @param array $id_values - Array of ID values to soft delete
     * @return bool - Success status
     */
    public function softDeleteMultiple($table_name, $id_column, $id_values) {
        if (empty($id_values)) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($id_values) - 1) . '?';
        $sql = "UPDATE $table_name SET deleted_at = NOW() WHERE $id_column IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        
        // Create types string for bind_param
        $types = str_repeat('i', count($id_values));
        $stmt->bind_param($types, ...$id_values);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Restore a soft deleted record
     * @param string $table_name - Name of the table
     * @param string $id_column - Name of the ID column
     * @param int $id_value - ID value to restore
     * @return bool - Success status
     */
    public function restore($table_name, $id_column, $id_value) {
        $sql = "UPDATE $table_name SET deleted_at = NULL WHERE $id_column = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id_value);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Permanently delete a record (use with caution)
     * @param string $table_name - Name of the table
     * @param string $id_column - Name of the ID column
     * @param int $id_value - ID value to permanently delete
     * @return bool - Success status
     */
    public function hardDelete($table_name, $id_column, $id_value) {
        $sql = "DELETE FROM $table_name WHERE $id_column = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id_value);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Check if a record is soft deleted
     * @param string $table_name - Name of the table
     * @param string $id_column - Name of the ID column
     * @param int $id_value - ID value to check
     * @return bool - True if deleted, false if not
     */
    public function isDeleted($table_name, $id_column, $id_value) {
        $sql = "SELECT deleted_at FROM $table_name WHERE $id_column = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id_value);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row && $row['deleted_at'] !== null;
    }
    
    /**
     * Get deleted records count
     * @param string $table_name - Name of the table
     * @return int - Count of deleted records
     */
    public function getDeletedCount($table_name) {
        $sql = "SELECT COUNT(*) as count FROM $table_name WHERE deleted_at IS NOT NULL";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
    
    /**
     * Get all deleted records
     * @param string $table_name - Name of the table
     * @param string $order_by - Order by clause (optional)
     * @return array - Array of deleted records
     */
    public function getDeletedRecords($table_name, $order_by = 'deleted_at DESC') {
        $sql = "SELECT * FROM $table_name WHERE deleted_at IS NOT NULL ORDER BY $order_by";
        $result = $this->conn->query($sql);
        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        return $records;
    }
    
    /**
     * Clean up old soft deleted records (older than specified days)
     * @param string $table_name - Name of the table
     * @param int $days_old - Number of days old to consider for cleanup
     * @return int - Number of records cleaned up
     */
    public function cleanupOldDeleted($table_name, $days_old = 365) {
        $sql = "DELETE FROM $table_name WHERE deleted_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $days_old);
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $affected_rows;
    }
}

// Helper function to get soft delete instance
function getSoftDelete() {
    global $conn;
    return new SoftDelete($conn);
}
?>
