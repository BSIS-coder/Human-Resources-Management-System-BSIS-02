<?php
// get_cycle_evaluations.php
header('Content-Type: application/json');
require_once 'config.php'; // PDO connection

$cycle_id = isset($_GET['cycle_id']) ? (int) $_GET['cycle_id'] : 0;

if ($cycle_id <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "
        SELECT 
            ep.personal_info_id,
            ep.employee_id,
            CONCAT(pi.first_name, ' ', pi.last_name) AS employee_name,
            ec.competency_id,
            ec.cycle_id,
            ec.rating,
            ec.assessment_date,
            ec.comments,
            c.name,
            c.description,
            jr.title AS role
        FROM employee_competencies ec
        JOIN competencies c ON ec.competency_id = c.competency_id
        JOIN employee_profiles ep ON ec.employee_id = ep.employee_id
        JOIN personal_information pi ON ep.personal_info_id = pi.personal_info_id
        LEFT JOIN job_roles jr ON ep.job_role_id = jr.job_role_id
        WHERE ec.cycle_id = :cycle_id
        ORDER BY ec.assessment_date DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':cycle_id' => $cycle_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
