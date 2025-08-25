<?php
session_start();
require_once 'config.php';

// Function to get all public holidays
function getPublicHolidays() {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM public_holidays ORDER BY holiday_date");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching public holidays: " . $e->getMessage());
        return [];
    }
}

// Function to get a single public holiday by ID
function getPublicHoliday($holiday_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM public_holidays WHERE holiday_id = ?");
        $stmt->execute([$holiday_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching public holiday: " . $e->getMessage());
        return null;
    }
}

// Function to get upcoming public holidays
function getUpcomingHolidays() {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM public_holidays WHERE holiday_date >= CURDATE() ORDER BY holiday_date LIMIT 5");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching upcoming holidays: " . $e->getMessage());
        return [];
    }
}

// Function to check if holiday_type column exists
function holidayTypeColumnExists() {
    global $conn;
    try {
        $stmt = $conn->query("SHOW COLUMNS FROM public_holidays LIKE 'holiday_type'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking holiday_type column: " . $e->getMessage());
        return false;
    }
}

// Function to add a new public holiday
function addPublicHoliday($holiday_name, $holiday_date, $holiday_type, $description) {
    global $conn;
    try {
        $hasHolidayType = holidayTypeColumnExists();
        
        if ($hasHolidayType) {
            $stmt = $conn->prepare("INSERT INTO public_holidays (holiday_name, holiday_date, holiday_type, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$holiday_name, $holiday_date, $holiday_type, $description]);
        } else {
            $stmt = $conn->prepare("INSERT INTO public_holidays (holiday_name, holiday_date, description) VALUES (?, ?, ?)");
            $stmt->execute([$holiday_name, $holiday_date, $description]);
        }
        
        return ['success' => true, 'message' => 'Holiday added successfully'];
    } catch (PDOException $e) {
        error_log("Error adding public holiday: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error adding holiday: ' . $e->getMessage()];
    }
}

// Function to update a public holiday
function updatePublicHoliday($holiday_id, $holiday_name, $holiday_date, $holiday_type, $description) {
    global $conn;
    try {
        $hasHolidayType = holidayTypeColumnExists();
        
        if ($hasHolidayType) {
            $stmt = $conn->prepare("UPDATE public_holidays SET holiday_name = ?, holiday_date = ?, holiday_type = ?, description = ? WHERE holiday_id = ?");
            $stmt->execute([$holiday_name, $holiday_date, $holiday_type, $description, $holiday_id]);
        } else {
            $stmt = $conn->prepare("UPDATE public_holidays SET holiday_name = ?, holiday_date = ?, description = ? WHERE holiday_id = ?");
            $stmt->execute([$holiday_name, $holiday_date, $description, $holiday_id]);
        }
        
        return ['success' => true, 'message' => 'Holiday updated successfully'];
    } catch (PDOException $e) {
        error_log("Error updating public holiday: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error updating holiday: ' . $e->getMessage()];
    }
}

// Function to delete a public holiday
function deletePublicHoliday($holiday_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("DELETE FROM public_holidays WHERE holiday_id = ?");
        $stmt->execute([$holiday_id]);
        return ['success' => true, 'message' => 'Holiday deleted successfully'];
    } catch (PDOException $e) {
        error_log("Error deleting public holiday: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error deleting holiday: ' . $e->getMessage()];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($action) {
        case 'add_holiday':
            if (isset($_POST['holiday_name'], $_POST['holiday_date'], $_POST['holiday_type'])) {
                $holiday_name = sanitizeInput($_POST['holiday_name']);
                $holiday_date = sanitizeInput($_POST['holiday_date']);
                $holiday_type = sanitizeInput($_POST['holiday_type']);
                $description = sanitizeInput($_POST['description'] ?? '');
                $response = addPublicHoliday($holiday_name, $holiday_date, $holiday_type, $description);
            }
            break;

        case 'update_holiday':
            if (isset($_POST['holiday_id'], $_POST['holiday_name'], $_POST['holiday_date'], $_POST['holiday_type'])) {
                $holiday_id = (int)$_POST['holiday_id'];
                $holiday_name = sanitizeInput($_POST['holiday_name']);
                $holiday_date = sanitizeInput($_POST['holiday_date']);
                $holiday_type = sanitizeInput($_POST['holiday_type']);
                $description = sanitizeInput($_POST['description'] ?? '');
                $response = updatePublicHoliday($holiday_id, $holiday_name, $holiday_date, $holiday_type, $description);
            }
            break;

        case 'delete_holiday':
            if (isset($_POST['holiday_id'])) {
                $holiday_id = (int)$_POST['holiday_id'];
                $response = deletePublicHoliday($holiday_id);
            }
            break;

        case 'get_holiday':
            if (isset($_POST['holiday_id'])) {
                $holiday_id = (int)$_POST['holiday_id'];
                $holiday = getPublicHoliday($holiday_id);
                if ($holiday) {
                    $response = ['success' => true, 'holiday' => $holiday];
                } else {
                    $response = ['success' => false, 'message' => 'Holiday not found'];
                }
            }
            break;

        case 'get_upcoming_holidays':
            $holidays = getUpcomingHolidays();
            $response = ['success' => true, 'holidays' => $holidays];
            break;

        case 'get_holidays':
            $holidays = getPublicHolidays();
            $response = ['success' => true, 'holidays' => $holidays];
            break;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
