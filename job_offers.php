<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'create_employee') {
        try {
            $application_id = $_POST['application_id'];
            $salary = $_POST['salary'];
            $start_date = $_POST['start_date'];
            
            // Get candidate and job info
            $stmt = $conn->prepare("SELECT c.*, jo.title as job_title, d.department_name FROM candidates c JOIN job_applications ja ON c.candidate_id = ja.candidate_id JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id JOIN departments d ON jo.department_id = d.department_id WHERE ja.application_id = ?");
            $stmt->execute([$application_id]);
            $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($candidate) {
                // Create employee
                $stmt = $conn->prepare("INSERT INTO employees (first_name, last_name, email, current_position, status) VALUES (?, ?, ?, ?, 'Active')");
                $stmt->execute([
                    $candidate['first_name'],
                    $candidate['last_name'], 
                    $candidate['email'],
                    $candidate['job_title']
                ]);
                
                $employee_id = $conn->lastInsertId();
                
                // Update application status
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Employee Created' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                $success_message = "✅ Employee created successfully! Employee ID: " . $employee_id;
            } else {
                $success_message = "❌ Error: Candidate not found!";
            }
        } catch (Exception $e) {
            $success_message = "❌ Error creating employee: " . $e->getMessage();
        }
    }
}

// Get dashboard statistics
$stats_query = "SELECT 
    COUNT(*) as total_hired,
    COUNT(CASE WHEN ja.status = 'Offer' THEN 1 END) as pending_creation,
    COUNT(CASE WHEN ja.status = 'Employee Created' THEN 1 END) as employees_created,
    COUNT(CASE WHEN ja.status = 'Employee Created' AND MONTH(ja.application_date) = MONTH(CURDATE()) THEN 1 END) as created_this_month
    FROM candidates c 
    JOIN job_applications ja ON c.candidate_id = ja.candidate_id
    WHERE ja.status IN ('Hired', 'Employee Created')";
$stats = $conn->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Get hired candidates with job details
$hired_candidates = $conn->query("SELECT c.*, ja.application_id, ja.application_date, ja.job_opening_id, jo.title as job_title, d.department_name
                                 FROM candidates c 
                                 JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                 JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                 JOIN departments d ON jo.department_id = d.department_id
                                 WHERE ja.status = 'Offer'
                                 ORDER BY ja.application_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get recent employee creations
$recent_employees = $conn->query("SELECT c.first_name, c.last_name, jo.title as job_title, ja.application_date
                                 FROM candidates c 
                                 JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                 JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                 WHERE ja.status = 'Employee Created'
                                 ORDER BY ja.application_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Creation - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <h2>👔 Employee Creation Dashboard</h2>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- Overview Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['total_hired']; ?></h3>
                                <p class="stats-label">Total Hired</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['pending_creation']; ?></h3>
                                <p class="stats-label">Pending Creation</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['employees_created']; ?></h3>
                                <p class="stats-label">Employees Created</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-info">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['created_this_month']; ?></h3>
                                <p class="stats-label">Created This Month</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Recent Employee Creations -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-history"></i> Recent Employee Creations</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recent_employees)): ?>
                                    <?php foreach($recent_employees as $employee): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($employee['job_title']); ?></small>
                                            </div>
                                            <div class="text-right">
                                                <span class="badge badge-success">✅ Created</span>
                                                <br><small class="text-muted"><?php echo date('M d', strtotime($employee['application_date'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No employees created yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <a href="onboarding.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-tasks text-primary"></i> Manage Onboarding Tasks
                                    </a>
                                    <a href="candidates.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-users text-info"></i> View All Candidates
                                    </a>
                                    <a href="recruitment_analytics.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-chart-bar text-success"></i> Recruitment Analytics
                                    </a>
                                    <a href="employees.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-id-badge text-warning"></i> Employee Directory
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hired Candidates Ready for Employee Creation -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-user-plus"></i> Hired Candidates Ready for Employee Creation (<?php echo count($hired_candidates); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($hired_candidates) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Candidate</th>
                                            <th>Job Position</th>
                                            <th>Department</th>
                                            <th>Applied Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($hired_candidates as $candidate): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($candidate['email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($candidate['job_title']); ?></td>
                                                <td><?php echo htmlspecialchars($candidate['department_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($candidate['application_date'])); ?></td>
                                                <td>
                                                    <button class="btn btn-success btn-sm action-btn" data-toggle="modal" data-target="#employeeModal<?php echo $candidate['application_id']; ?>">
                                                        <i class="fas fa-user-plus"></i> Create Employee
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <h5><i class="fas fa-info-circle"></i> No Hired Candidates</h5>
                                <p>No candidates are currently ready for employee creation.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Employee Creation Modals -->
                <?php foreach($hired_candidates as $candidate): ?>
                    <div class="modal fade" id="employeeModal<?php echo $candidate['application_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-user-plus"></i> Create Employee - <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="create_employee">
                                        <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-info-circle"></i> Candidate Information</h6>
                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></p>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($candidate['phone']); ?></p>
                                                <p><strong>Applied For:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-briefcase"></i> Employee Details</h6>
                                                <p><strong>Position:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                                <p><strong>Department:</strong> <?php echo htmlspecialchars($candidate['department_name']); ?></p>
                                                <div class="form-group">
                                                    <label>Salary</label>
                                                    <input type="number" name="salary" class="form-control" placeholder="Annual salary" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Start Date</label>
                                                    <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success action-btn">
                                            <i class="fas fa-user-plus"></i> Create Employee
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>