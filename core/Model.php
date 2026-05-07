<?php
require_once BASE_PATH . '/config/database.php';

class Model {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Fetches admin announcements targeted at the user or their role.
     */
    public function getAdminAnnouncements(int $userId, string $role, int $limit = 5): array
    {
        $limit = (int)$limit;
        $stmt = $this->db->prepare("
            SELECT a.*, u.fullname as author 
            FROM announcements a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.target_role = 'all' 
               OR a.target_role = ? 
               OR a.id IN (SELECT announcement_id FROM announcement_recipients WHERE user_id = ?)
            ORDER BY a.created_at DESC
            LIMIT $limit
        ");
        $stmt->execute([$role, $userId]);
        return $stmt->fetchAll();
    }
}
