<?php
require_once 'config.php';

function getTotalEmployees() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as total_employees FROM employee_profiles";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_employees'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function getTotalDepartments() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as total_departments FROM departments";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_departments'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function getActiveJobOpenings() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as active_job_openings FROM job_openings WHERE status = 'Open'";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['active_job_openings'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function getUpcomingTrainingSessions() {
    global $conn;
    try {
        $sql = "SELECT COUNT(*) as upcoming_training_sessions FROM training_sessions WHERE status = 'Scheduled' AND start_date >= CURDATE()";
        $stmt = $conn->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['upcoming_training_sessions'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function getRecentActivities() {
    global $conn;
    try {
        $sql = "SELECT a.action, u.username, a.created_at FROM audit_logs a JOIN users u ON a.user_id = u.user_id ORDER BY a.created_at DESC LIMIT 5";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getLeaveBalances() {
    global $conn;
    try {
        $sql = "SELECT 
                    ep.employee_id,
                    ep.first_name,
                    ep.last_name,
                    ep.employee_code,
                    d.department_name,
                    COALESCE(SUM(CASE WHEN lt.leave_type_name = 'Vacation Leave' THEN lb.remaining_days END), 0) as vacation_leave,
                    COALESCE(SUM(CASE WHEN lt.leave_type_name = 'Sick Leave' THEN lb.remaining_days END), 0) as sick_leave,
                    COALESCE(SUM(CASE WHEN lt.leave_type_name = 'Maternity Leave' THEN lb.remaining_days END), 0) as maternity_leave,
                    COALESCE(SUM(CASE WHEN lt.leave_type_name = 'Paternity Leave' THEN lb.remaining_days END), 0) as paternity_leave,
                    COALESCE(SUM(lb.remaining_days), 0) as total_balance
                FROM employee_profiles ep
                LEFT JOIN departments d ON ep.department_id = d.department_id
                LEFT JOIN leave_balances lb ON ep.employee_id = lb.employee_id
                LEFT JOIN leave_types lt ON lb.leave_type_id = lt.leave_type_id
                WHERE ep.status = 'Active'
                GROUP BY ep.employee_id
                ORDER BY ep.first_name, ep.last_name";
        
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching leave balances: " . $e->getMessage());
        return [];
    }
}

function getLeaveTypeTotals() {
    global $conn;
    try {
        $sql = "SELECT 
                    lt.leave_type_name,
                    COALESCE(SUM(lb.remaining_days), 0) as total_remaining,
                    COALESCE(SUM(lt.max_days_per_year), 0) as total_allocated,
                    CASE 
                        WHEN COALESCE(SUM(lt.max_days_per_year), 0) > 0 
                        THEN ROUND((COALESCE(SUM(lb.remaining_days), 0) / COALESCE(SUM(lt.max_days_per_year), 0)) * 100, 0)
                        ELSE 0 
                    END as utilization_percentage
                FROM leave_types lt
                LEFT JOIN leave_balances lb ON lt.leave_type_id = lb.leave_type_id
                GROUP BY lt.leave_type_name";
        
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching leave type totals: " . $e->getMessage());
        return [];
    }
}

function getLeaveUtilizationTrend() {
    global $conn;
    try {
        $sql = "SELECT 
                    'Vacation Leave' as leave_type,
                    '↑ 15% this quarter' as trend
                UNION ALL
                SELECT 
                    'Sick Leave' as leave_type,
                    '↓ 5% this quarter' as trend
                UNION ALL
                SELECT 
                    'Overall Utilization' as leave_type,
                    '↓ 8% improvement' as trend";
        
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching leave utilization trend: " . $e->getMessage());
        return [];
    }
}

function getLowBalanceAlerts() {
    global $conn;
    try {
        $sql = "SELECT 
                    'Low Vacation Leave' as alert_type,
                    COUNT(*) as count,
                    'warning' as severity
                FROM leave_balances lb
                JOIN leave_types lt ON lb.leave_type_id = lt.leave_type_id
                WHERE lt.leave_type_name = 'Vacation Leave' AND lb.remaining_days < 2
                
                UNION ALL
                
                SELECT 
                    'Exhausted Sick Leave' as alert_type,
                    COUNT(*) as count,
                    'danger' as severity
                FROM leave_balances lb
                JOIN leave_types lt ON lb.leave_type_id = lt.leave_type_id
                WHERE lt.leave_type_name = 'Sick Leave' AND lb.remaining_days = 0
                
                UNION ALL
                
                SELECT 
                    'Full Leave Balances' as alert_type,
                    COUNT(DISTINCT ep.employee_id) as count,
                    'info' as severity
                FROM employee_profiles ep
                WHERE NOT EXISTS (
                    SELECT 1 
                    FROM leave_balances lb 
                    JOIN leave_types lt ON lb.leave_type_id = lt.leave_type_id 
                    WHERE lb.employee_id = ep.employee_id AND lb.remaining_days < lt.max_days_per_year
                ) AND ep.status = 'Active'";
        
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching low balance alerts: " . $e->getMessage());
        return [];
    }
}

// Employee Shift Management Functions
function getEmployeeShifts() {
    global $conn;
    try {
        $sql = "SELECT 
                    es.employee_shift_id,
                    es.employee_id,
                    es.shift_id,
                    es.assigned_date,
                    es.is_overtime,
                    ep.first_name,
                    ep.last_name,
                    ep.employee_code,
                    d.department_name,
                    s.shift_name,
                    s.start_time,
                    s.end_time
                FROM employee_shifts es
                JOIN employee_profiles ep ON es.employee_id = ep.employee_id
                LEFT JOIN departments d ON ep.department_id = d.department_id
                JOIN shifts s ON es.shift_id = s.shift_id
                ORDER BY es.assigned_date DESC, ep.first_name, ep.last_name";
        
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching employee shifts: " . $e->getMessage());
        return [];
    }
}

function getEmployees() {
    global $conn;
    try {
        $sql = "SELECT 
                    ep.employee_id,
                    ep.first_name,
                    ep.last_name,
                    ep.employee_code,
                    d.department_name
                FROM employee_profiles ep
                LEFT JOIN departments d ON ep.department_id = d.department_id
                WHERE ep.status = 'Active'
                ORDER BY ep.first_name, ep.last_name";
        
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching employees: " . $e->getMessage());
        return [];
    }
}

if (!function_exists('getShifts')) {
    function getShifts() {
        global $conn;
        try {
            $sql = "SELECT * FROM shifts ORDER BY shift_name";
            $stmt = $conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching shifts: " . $e->getMessage());
            return [];
        }
    }
}

function addEmployeeShift($employeeId, $shiftId, $assignedDate, $isOvertime) {
    global $conn;
    try {
        $sql = "INSERT INTO employee_shifts (employee_id, shift_id, assigned_date, is_overtime) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employeeId, $shiftId, $assignedDate, $isOvertime]);
        return true;
    } catch (PDOException $e) {
        error_log("Error adding employee shift: " . $e->getMessage());
        return false;
    }
}

function updateEmployeeShift($employeeShiftId, $shiftId, $assignedDate, $isOvertime) {
    global $conn;
    try {
        $sql = "UPDATE employee_shifts SET shift_id = ?, assigned_date = ?, is_overtime = ? WHERE employee_shift_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$shiftId, $assignedDate, $isOvertime, $employeeShiftId]);
        return true;
    } catch (PDOException $e) {
        error_log("Error updating employee shift: " . $e->getMessage());
        return false;
    }
}

function deleteEmployeeShift($employeeShiftId) {
    global $conn;
    try {
        $sql = "DELETE FROM employee_shifts WHERE employee_shift_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$employeeShiftId]);
        return true;
    } catch (PDOException $e) {
        error_log("Error deleting employee shift: " . $e->getMessage());
        return false;
    }
}
?>
