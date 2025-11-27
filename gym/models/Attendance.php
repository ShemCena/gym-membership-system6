<?php
/**
 * Attendance Model
 * Gym Membership Management System
 */

require_once __DIR__ . '/../config/database.php';

class Attendance {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function checkIn($memberId) {
        try {
            // Check if member exists and is active
            $stmt = $this->db->pdo->prepare("
                SELECT id, full_name, status, expiry_date 
                FROM members 
                WHERE id = ? AND status = 'Active' AND expiry_date >= CURDATE()
            ");
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();
            
            if (!$member) {
                return ['success' => false, 'message' => 'Member not found, inactive, or membership expired'];
            }
            
            // Check if already checked in today
            $stmt = $this->db->pdo->prepare("
                SELECT COUNT(*) as count FROM attendance 
                WHERE member_id = ? AND checkin_date = CURDATE()
            ");
            $stmt->execute([$memberId]);
            $alreadyCheckedIn = $stmt->fetch()['count'] > 0;
            
            if ($alreadyCheckedIn) {
                return ['success' => false, 'message' => 'Member already checked in today'];
            }
            
            // Record check-in
            $stmt = $this->db->pdo->prepare("
                INSERT INTO attendance (member_id, checkin_date, checkin_time) 
                VALUES (?, CURDATE(), CURTIME())
            ");
            $result = $stmt->execute([$memberId]);
            
            if ($result) {
                return [
                    'success' => true, 
                    'message' => "Check-in successful for {$member['full_name']}",
                    'member' => $member
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to record check-in'];
            }
        } catch (PDOException $e) {
            error_log("Check-in error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    public function getAll($search = '', $date = '', $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];
            
            if ($search) {
                $where[] = "(m.full_name LIKE ? OR m.email LIKE ? OR m.phone LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($date) {
                $where[] = "a.checkin_date = ?";
                $params[] = $date;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Get total count
            $countSql = "
                SELECT COUNT(*) as total 
                FROM attendance a
                JOIN members m ON a.member_id = m.id
                $whereClause
            ";
            $stmt = $this->db->pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Get attendance records
            $sql = "
                SELECT a.*, m.full_name, m.email, m.member_type, m.photo
                FROM attendance a
                JOIN members m ON a.member_id = m.id
                $whereClause
                ORDER BY a.checkin_date DESC, a.checkin_time DESC
                LIMIT ? OFFSET ?
            ";
            $stmt = $this->db->pdo->prepare($sql);
            $stmt->execute(array_merge($params, [$limit, $offset]));
            $attendance = $stmt->fetchAll();
            
            return [
                'attendance' => $attendance,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
        } catch (PDOException $e) {
            error_log("Get attendance error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->pdo->prepare("
                SELECT a.*, m.full_name, m.email, m.member_type, m.photo
                FROM attendance a
                JOIN members m ON a.member_id = m.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get attendance by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByMemberId($memberId, $limit = 30) {
        try {
            $stmt = $this->db->pdo->prepare("
                SELECT a.*, m.full_name, m.member_type
                FROM attendance a
                JOIN members m ON a.member_id = m.id
                WHERE a.member_id = ?
                ORDER BY a.checkin_date DESC, a.checkin_time DESC
                LIMIT ?
            ");
            $stmt->execute([$memberId, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get attendance by member ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTodayAttendance() {
        try {
            $stmt = $this->db->pdo->prepare("
                SELECT a.*, m.full_name, m.email, m.member_type, m.photo
                FROM attendance a
                JOIN members m ON a.member_id = m.id
                WHERE a.checkin_date = CURDATE()
                ORDER BY a.checkin_time DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get today's attendance error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total check-ins today
            $stmt = $this->db->pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE checkin_date = CURDATE()");
            $stmt->execute();
            $stats['checkins_today'] = $stmt->fetch()['count'];
            
            // Total check-ins this week
            $stmt = $this->db->pdo->prepare("
                SELECT COUNT(*) as count FROM attendance 
                WHERE checkin_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ");
            $stmt->execute();
            $stats['checkins_this_week'] = $stmt->fetch()['count'];
            
            // Total check-ins this month
            $stmt = $this->db->pdo->prepare("
                SELECT COUNT(*) as count FROM attendance 
                WHERE MONTH(checkin_date) = MONTH(CURDATE()) AND YEAR(checkin_date) = YEAR(CURDATE())
            ");
            $stmt->execute();
            $stats['checkins_this_month'] = $stmt->fetch()['count'];
            
            // Total check-ins all time
            $stmt = $this->db->pdo->prepare("SELECT COUNT(*) as count FROM attendance");
            $stmt->execute();
            $stats['total_checkins'] = $stmt->fetch()['count'];
            
            // Average daily check-ins (last 30 days)
            $stmt = $this->db->pdo->prepare("
                SELECT AVG(daily_count) as avg_daily 
                FROM (
                    SELECT COUNT(*) as daily_count 
                    FROM attendance 
                    WHERE checkin_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY checkin_date
                ) as daily_stats
            ");
            $stmt->execute();
            $stats['avg_daily_checkins'] = round($stmt->fetch()['avg_daily'] ?? 0, 1);
            
            // Most active members (last 30 days)
            $stmt = $this->db->pdo->prepare("
                SELECT 
                    m.id, m.full_name, m.member_type,
                    COUNT(a.id) as checkin_count
                FROM members m
                JOIN attendance a ON m.id = a.member_id
                WHERE a.checkin_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY m.id, m.full_name, m.member_type
                ORDER BY checkin_count DESC
                LIMIT 5
            ");
            $stmt->execute();
            $stats['most_active_members'] = $stmt->fetchAll();
            
            // Check-ins by day of week (last 4 weeks)
            $stmt = $this->db->pdo->prepare("
                SELECT 
                    DAYNAME(checkin_date) as day_name,
                    COUNT(*) as checkin_count
                FROM attendance 
                WHERE checkin_date >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)
                GROUP BY DAYNAME(checkin_date)
                ORDER BY FIELD(DAYNAME(checkin_date), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
            ");
            $stmt->execute();
            $stats['checkins_by_day'] = $stmt->fetchAll();
            
            // Peak hours
            $stmt = $this->db->pdo->prepare("
                SELECT 
                    HOUR(checkin_time) as hour,
                    COUNT(*) as checkin_count
                FROM attendance 
                WHERE checkin_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY HOUR(checkin_time)
                ORDER BY checkin_count DESC
                LIMIT 5
            ");
            $stmt->execute();
            $stats['peak_hours'] = $stmt->fetchAll();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Get attendance statistics error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDailyCheckins($days = 30) {
        try {
            $stmt = $this->db->pdo->prepare("
                SELECT 
                    checkin_date as date,
                    COUNT(*) as checkin_count
                FROM attendance 
                WHERE checkin_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY checkin_date
                ORDER BY checkin_date ASC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get daily checkins error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getMemberAttendanceStats($memberId) {
        try {
            $stats = [];
            
            // Total check-ins
            $stmt = $this->db->pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE member_id = ?");
            $stmt->execute([$memberId]);
            $stats['total_checkins'] = $stmt->fetch()['count'];
            
            // Check-ins this month
            $stmt = $this->db->pdo->prepare("
                SELECT COUNT(*) as count FROM attendance 
                WHERE member_id = ? AND MONTH(checkin_date) = MONTH(CURDATE()) AND YEAR(checkin_date) = YEAR(CURDATE())
            ");
            $stmt->execute([$memberId]);
            $stats['checkins_this_month'] = $stmt->fetch()['count'];
            
            // Last check-in date
            $stmt = $this->db->pdo->prepare("
                SELECT checkin_date, checkin_time FROM attendance 
                WHERE member_id = ? 
                ORDER BY checkin_date DESC, checkin_time DESC 
                LIMIT 1
            ");
            $stmt->execute([$memberId]);
            $stats['last_checkin'] = $stmt->fetch();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Get member attendance stats error: " . $e->getMessage());
            return false;
        }
    }
}
?>
