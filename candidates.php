<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

$success_message = '';
$filter_job = isset($_GET['job_id']) ? $_GET['job_id'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_candidate':
                $application_id = $_POST['application_id'];
                
                $app_stmt = $conn->prepare("SELECT candidate_id FROM job_applications WHERE application_id = ?");
                $app_stmt->execute([$application_id]);
                $app_data = $app_stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Reference Check' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                $success_message = "✅ Candidate approved and moved to reference check!";
                break;
                
            case 'reject_candidate':
                $application_id = $_POST['application_id'];
                
                $app_stmt = $conn->prepare("SELECT candidate_id FROM job_applications WHERE application_id = ?");
                $app_stmt->execute([$application_id]);
                $app_data = $app_stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Rejected' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Rejected' WHERE candidate_id = ?");
                $stmt->execute([$app_data['candidate_id']]);
                
                $success_message = "❌ Candidate rejected!";
                break;
        }
    }
}

// Get dashboard statistics
$stats_query = "SELECT 
    COUNT(*) as total_candidates,
    COUNT(CASE WHEN ja.status IN ('Screening', 'Interview', 'Assessment') THEN 1 END) as active_candidates,
    COUNT(CASE WHEN ja.status = 'Hired' THEN 1 END) as hired_candidates,
    COUNT(CASE WHEN ja.status = 'Rejected' THEN 1 END) as rejected_candidates,
    COUNT(CASE WHEN ja.status = 'Hired' AND MONTH(ja.application_date) = MONTH(CURDATE()) THEN 1 END) as hired_this_month
    FROM candidates c 
    JOIN job_applications ja ON c.candidate_id = ja.candidate_id";
$stats = $conn->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Get stage performance data
$stage_performance = $conn->query("SELECT 
    c.source as stage_name,
    COUNT(*) as candidate_count,
    AVG(DATEDIFF(CURDATE(), ja.application_date)) as avg_days
    FROM candidates c 
    JOIN job_applications ja ON c.candidate_id = ja.candidate_id
    WHERE ja.status = 'Assessment' AND c.source != 'Assessment'
    GROUP BY c.source
    ORDER BY candidate_count DESC")->fetchAll(PDO::FETCH_ASSOC);

// Build candidates query with filters (exclude Reference Check - they go to onboarding)
$candidates_query = "SELECT c.*, ja.application_id, ja.application_date, ja.status, 
                     jo.title as job_title, d.department_name, d.department_id, c.source as current_stage,
                     DATEDIFF(CURDATE(), ja.application_date) as days_in_process
                     FROM candidates c 
                     JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                     JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                     JOIN departments d ON jo.department_id = d.department_id
                     WHERE ja.status NOT IN ('Reference Check', 'Hired', 'Rejected')";

$params = [];
if ($filter_job) {
    $candidates_query .= " AND jo.job_opening_id = ?";
    $params[] = $filter_job;
}
if ($filter_status) {
    $candidates_query .= " AND ja.status = ?";
    $params[] = $filter_status;
}
if ($search) {
    $candidates_query .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$candidates_query .= " ORDER BY d.department_name, ja.application_date DESC";
$stmt = $conn->prepare($candidates_query);
$stmt->execute($params);
$all_candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group candidates by department
$candidates_by_department = [];
foreach ($all_candidates as $candidate) {
    $dept_name = $candidate['department_name'];
    if (!isset($candidates_by_department[$dept_name])) {
        $candidates_by_department[$dept_name] = [];
    }
    $candidates_by_department[$dept_name][] = $candidate;
}

$total_candidates = count($all_candidates);

// Get job openings for filter
$job_openings = $conn->query("SELECT job_opening_id, title FROM job_openings WHERE status = 'Open' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Get recent activities
$recent_activities = $conn->query("SELECT c.first_name, c.last_name, ja.status, ja.application_date, jo.title as job_title
                                  FROM candidates c 
                                  JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                  JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                  WHERE ja.status IN ('Hired', 'Rejected') 
                                  ORDER BY ja.application_date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates Dashboard - HR Management System</title>
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
                <h2>👥 Candidates Dashboard</h2>
                
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
                                <div class="activity-icon bg-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['total_candidates']; ?></h3>
                                <p class="stats-label">Total Candidates</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['active_candidates']; ?></h3>
                                <p class="stats-label">Active in Process</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['hired_candidates']; ?></h3>
                                <p class="stats-label">Total Hired</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-info">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['hired_this_month']; ?></h3>
                                <p class="stats-label">Hired This Month</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Stage Performance -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar"></i> Stage Performance</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($stage_performance)): ?>
                                    <?php foreach($stage_performance as $stage): ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span><?php echo htmlspecialchars($stage['stage_name']); ?></span>
                                                <span class="badge badge-primary"><?php echo $stage['candidate_count']; ?> candidates</span>
                                            </div>
                                            <small class="text-muted">Avg: <?php echo round($stage['avg_days']); ?> days</small>
                                            <div class="progress mt-1" style="height: 5px;">
                                                <div class="progress-bar" style="width: <?php echo min(100, ($stage['candidate_count'] / max(1, $stats['active_candidates'])) * 100); ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No active candidates in interview stages.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-history"></i> Recent Activities</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recent_activities)): ?>
                                    <?php foreach($recent_activities as $activity): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($activity['job_title']); ?></small>
                                            </div>
                                            <div class="text-right">
                                                <span class="badge badge-<?php echo $activity['status'] == 'Hired' ? 'success' : 'secondary'; ?>">
                                                    <?php echo $activity['status']; ?>
                                                </span>
                                                <br><small class="text-muted"><?php echo date('M d', strtotime($activity['application_date'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No recent activities.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-search"></i> Search & Filter Candidates</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="job_id" class="form-control">
                                    <option value="">All Job Openings</option>
                                    <?php foreach($job_openings as $job): ?>
                                        <option value="<?php echo $job['job_opening_id']; ?>" <?php echo $filter_job == $job['job_opening_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="Applied" <?php echo $filter_status == 'Applied' ? 'selected' : ''; ?>>Applied</option>
                                    <option value="Screening" <?php echo $filter_status == 'Screening' ? 'selected' : ''; ?>>Screening</option>
                                    <option value="Interview" <?php echo $filter_status == 'Interview' ? 'selected' : ''; ?>>Interview</option>
                                    <option value="Assessment" <?php echo $filter_status == 'Assessment' ? 'selected' : ''; ?>>Assessment</option>
                                    <option value="Completed All Stages" <?php echo $filter_status == 'Completed All Stages' ? 'selected' : ''; ?>>Completed All Stages</option>
                                    <option value="Hired" <?php echo $filter_status == 'Hired' ? 'selected' : ''; ?>>Hired</option>
                                    <option value="Rejected" <?php echo $filter_status == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary action-btn w-100">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Candidates by Department -->
                <div class="mb-3">
                    <h5><i class="fas fa-users"></i> Candidates by Department (<?php echo $total_candidates; ?> total)</h5>
                    <div>
                        <a href="interview_stages.php" class="btn btn-info btn-sm action-btn">
                            <i class="fas fa-cogs"></i> Manage Interview Stages
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($candidates_by_department)): ?>
                    <?php foreach ($candidates_by_department as $dept_name => $dept_candidates): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($dept_name); ?>
                                    <span class="badge badge-primary ml-2"><?php echo count($dept_candidates); ?> candidates</span>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Candidate</th>
                                                <th>Job Position</th>
                                                <th>Status</th>
                                                <th>Current Stage</th>
                                                <th>Days</th>
                                                <th>Applied</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($dept_candidates as $candidate): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($candidate['email']); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($candidate['job_title']); ?></td>
                                                    <td>
                                                        <?php 
                                                        // Get current interview status for better status display
                                                        $current_interview = $conn->prepare("SELECT i.status as interview_status, ist.stage_name 
                                                                                             FROM interviews i 
                                                                                             JOIN interview_stages ist ON i.stage_id = ist.stage_id 
                                                                                             WHERE i.application_id = ? AND i.status != 'Completed' 
                                                                                             ORDER BY ist.stage_order DESC LIMIT 1");
                                                        $current_interview->execute([$candidate['application_id']]);
                                                        $interview_info = $current_interview->fetch(PDO::FETCH_ASSOC);
                                                        
                                                        if ($candidate['status'] == 'Assessment' && $interview_info) {
                                                            if ($interview_info['interview_status'] == 'Rescheduled') {
                                                                echo '<span class="badge badge-warning">📅 Awaiting Interview</span>';
                                                            } elseif ($interview_info['interview_status'] == 'Scheduled') {
                                                                echo '<span class="badge badge-primary">🎯 Interview Scheduled</span>';
                                                            } else {
                                                                echo '<span class="badge badge-secondary">📋 In Assessment</span>';
                                                            }
                                                        } elseif ($candidate['status'] == 'Screening') {
                                                            echo '<span class="badge badge-info">🔍 Under Review</span>';
                                                        } elseif ($candidate['status'] == 'Applied') {
                                                            echo '<span class="badge badge-warning">📝 New Application</span>';
                                                        } elseif ($candidate['current_stage'] == 'Completed All Stages') {
                                                            echo '<span class="badge badge-warning">🏆 Completed All Stages</span>';
                                                        } elseif ($candidate['status'] == 'Hired') {
                                                            echo '<span class="badge badge-success">✅ Hired</span>';
                                                        } elseif ($candidate['status'] == 'Rejected') {
                                                            echo '<span class="badge badge-danger">❌ Rejected</span>';
                                                        } else {
                                                            echo '<span class="badge badge-secondary">' . htmlspecialchars($candidate['status']) . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($candidate['status'] == 'Assessment' || ($candidate['status'] == 'Screening' && $candidate['current_stage'] != 'Approved')): ?>
                                                            <span class="badge badge-primary"><?php echo htmlspecialchars($candidate['current_stage']); ?></span>
                                                        <?php elseif ($candidate['status'] == 'Screening'): ?>
                                                            <?php 
                                                            $first_stage_query = $conn->prepare("SELECT stage_name FROM interview_stages WHERE job_opening_id = (SELECT job_opening_id FROM job_applications WHERE application_id = ?) ORDER BY stage_order LIMIT 1");
                                                            $first_stage_query->execute([$candidate['application_id']]);
                                                            $first_stage = $first_stage_query->fetch(PDO::FETCH_ASSOC);
                                                            ?>
                                                            <span class="badge badge-info"><?php echo $first_stage ? htmlspecialchars($first_stage['stage_name']) : 'Initial'; ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $candidate['days_in_process'] > 30 ? 'danger' : ($candidate['days_in_process'] > 14 ? 'warning' : 'success'); ?>">
                                                            <?php echo $candidate['days_in_process']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d', strtotime($candidate['application_date'])); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#candidateModal<?php echo $candidate['candidate_id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                        <?php if ($candidate['current_stage'] == 'Completed All Stages'): ?>
                                                            <form method="POST" style="display:inline;" class="ml-1">
                                                                <input type="hidden" name="action" value="approve_candidate">
                                                                <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this candidate for hiring?')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display:inline;" class="ml-1">
                                                                <input type="hidden" name="action" value="reject_candidate">
                                                                <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this candidate?')">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <h5><i class="fas fa-info-circle"></i> No Candidates Found</h5>
                        <p>No candidates match your current filters.</p>
                    </div>
                <?php endif; ?>

                <!-- Candidate Detail Modals -->
                <?php foreach($all_candidates as $candidate): ?>
                    <div class="modal fade" id="candidateModal<?php echo $candidate['candidate_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-info-circle"></i> Personal Information</h6>
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></p>
                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($candidate['phone']); ?></p>
                                            <p><strong>Current Position:</strong> <?php echo htmlspecialchars($candidate['current_position'] ?: 'Not specified'); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-briefcase"></i> Application Details</h6>
                                            <p><strong>Applied For:</strong> <?php echo htmlspecialchars($candidate['job_title']); ?></p>
                                            <p><strong>Department:</strong> <?php echo htmlspecialchars($candidate['department_name']); ?></p>
                                            <p><strong>Status:</strong> 
                                                <?php 
                                                // Get current interview status for modal display
                                                $current_interview = $conn->prepare("SELECT i.status as interview_status, ist.stage_name 
                                                                                     FROM interviews i 
                                                                                     JOIN interview_stages ist ON i.stage_id = ist.stage_id 
                                                                                     WHERE i.application_id = ? AND i.status != 'Completed' 
                                                                                     ORDER BY ist.stage_order DESC LIMIT 1");
                                                $current_interview->execute([$candidate['application_id']]);
                                                $interview_info = $current_interview->fetch(PDO::FETCH_ASSOC);
                                                
                                                if ($candidate['status'] == 'Assessment' && $interview_info) {
                                                    if ($interview_info['interview_status'] == 'Rescheduled') {
                                                        echo '<span class="badge badge-warning">📅 Awaiting Interview Scheduling</span>';
                                                    } elseif ($interview_info['interview_status'] == 'Scheduled') {
                                                        echo '<span class="badge badge-primary">🎯 Interview Scheduled</span>';
                                                    } else {
                                                        echo '<span class="badge badge-secondary">📋 In Assessment Process</span>';
                                                    }
                                                } elseif ($candidate['status'] == 'Screening') {
                                                    echo '<span class="badge badge-info">🔍 Under Initial Review</span>';
                                                } elseif ($candidate['status'] == 'Applied') {
                                                    echo '<span class="badge badge-warning">📝 New Application</span>';
                                                } elseif ($candidate['current_stage'] == 'Completed All Stages') {
                                                    echo '<span class="badge badge-warning">🏆 Ready for HR Approval</span>';
                                                } elseif ($candidate['status'] == 'Hired') {
                                                    echo '<span class="badge badge-success">✅ Successfully Hired</span>';
                                                } elseif ($candidate['status'] == 'Rejected') {
                                                    echo '<span class="badge badge-danger">❌ Application Rejected</span>';
                                                } else {
                                                    echo '<span class="badge badge-secondary">' . htmlspecialchars($candidate['status']) . '</span>';
                                                }
                                                ?>
                                            </p>
                                            <p><strong>Current Stage:</strong> 
                                                <?php if ($candidate['status'] == 'Assessment' || ($candidate['status'] == 'Screening' && $candidate['current_stage'] != 'Approved')): ?>
                                                    <span class="badge badge-primary"><?php echo htmlspecialchars($candidate['current_stage']); ?></span>
                                                <?php elseif ($candidate['status'] == 'Screening'): ?>
                                                    <?php 
                                                    // Get first interview stage for this job
                                                    $first_stage_query = $conn->prepare("SELECT stage_name FROM interview_stages WHERE job_opening_id = (SELECT job_opening_id FROM job_applications WHERE application_id = ?) ORDER BY stage_order LIMIT 1");
                                                    $first_stage_query->execute([$candidate['application_id']]);
                                                    $first_stage = $first_stage_query->fetch(PDO::FETCH_ASSOC);
                                                    ?>
                                                    <span class="badge badge-info"><?php echo $first_stage ? htmlspecialchars($first_stage['stage_name']) : 'Initial Screening'; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not in interview stages</span>
                                                <?php endif; ?>
                                            </p>
                                            <p><strong>Days in Process:</strong> 
                                                <span class="badge badge-<?php echo $candidate['days_in_process'] > 30 ? 'danger' : ($candidate['days_in_process'] > 14 ? 'warning' : 'success'); ?>">
                                                    <?php echo $candidate['days_in_process']; ?> days
                                                </span>
                                            </p>
                                            <p><strong>Application Date:</strong> <?php echo date('M d, Y', strtotime($candidate['application_date'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Interview History -->
                                    <div class="mt-4">
                                        <h6><i class="fas fa-history"></i> Interview History</h6>
                                        <?php 
                                        $interview_logs = $conn->prepare("SELECT i.interview_id, i.schedule_date, i.duration, i.location, i.interview_type, i.status, i.feedback, i.rating, i.recommendation, i.completed_date, ist.stage_name 
                                                                          FROM interviews i 
                                                                          LEFT JOIN interview_stages ist ON i.stage_id = ist.stage_id 
                                                                          WHERE i.application_id = ? 
                                                                          ORDER BY i.schedule_date DESC");
                                        $interview_logs->execute([$candidate['application_id']]);
                                        $logs = $interview_logs->fetchAll(PDO::FETCH_ASSOC);
                                        ?>
                                        <?php if (!empty($logs)): ?>
                                            <div class="timeline">
                                                <?php foreach($logs as $log): ?>
                                                    <div class="mb-3 p-3 border-left border-<?php echo $log['status'] == 'Completed' ? 'success' : ($log['status'] == 'Scheduled' ? 'primary' : 'warning'); ?> bg-light">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($log['stage_name'] ?: 'Interview Stage'); ?></strong>
                                                                <br><small class="text-muted">
                                                                    <?php if ($log['status'] == 'Scheduled' || $log['status'] == 'Completed'): ?>
                                                                        📅 <?php echo date('M d, Y H:i', strtotime($log['schedule_date'])); ?>
                                                                        <?php if ($log['duration']): ?> • ⏱️ <?php echo $log['duration']; ?> min<?php endif; ?>
                                                                        <?php if ($log['location']): ?> • 📍 <?php echo htmlspecialchars($log['location']); ?><?php endif; ?>
                                                                    <?php else: ?>
                                                                        ⏰ Needs scheduling
                                                                    <?php endif; ?>
                                                                </small>
                                                            </div>
                                                            <span class="badge badge-<?php echo $log['status'] == 'Completed' ? 'success' : ($log['status'] == 'Scheduled' ? 'primary' : 'warning'); ?>">
                                                                <?php echo $log['status']; ?>
                                                            </span>
                                                        </div>
                                                        
                                                        <?php if ($log['status'] == 'Completed'): ?>
                                                            <div class="mt-2">
                                                                <?php if ($log['rating']): ?>
                                                                    <div class="mb-1">
                                                                        <strong>Rating:</strong> 
                                                                        <span class="badge badge-info"><?php echo $log['rating']; ?>/5</span>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($log['recommendation']): ?>
                                                                    <div class="mb-1">
                                                                        <strong>Recommendation:</strong> 
                                                                        <span class="badge badge-<?php echo in_array($log['recommendation'], ['Strong Yes', 'Yes']) ? 'success' : 'danger'; ?>">
                                                                            <?php echo htmlspecialchars($log['recommendation']); ?>
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($log['feedback']): ?>
                                                                    <div class="mt-2">
                                                                        <strong>Feedback:</strong>
                                                                        <p class="mb-0 mt-1"><small><?php echo nl2br(htmlspecialchars($log['feedback'])); ?></small></p>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($log['completed_date']): ?>
                                                                    <small class="text-muted">✅ Completed: <?php echo date('M d, Y H:i', strtotime($log['completed_date'])); ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                <small><i class="fas fa-info-circle"></i> No interview records yet.</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <?php if ($candidate['current_stage'] == 'Completed All Stages'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="approve_candidate">
                                            <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                            <button type="submit" class="btn btn-success" onclick="return confirm('Approve this candidate for hiring?')">
                                                <i class="fas fa-check"></i> Approve & Hire
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" class="ml-2">
                                            <input type="hidden" name="action" value="reject_candidate">
                                            <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this candidate?')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
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