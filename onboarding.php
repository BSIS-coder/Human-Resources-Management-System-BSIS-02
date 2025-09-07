<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'complete_task') {
        $conn->prepare("UPDATE onboarding_task_progress SET status = 'Completed', completed_date = CURDATE() WHERE progress_id = ?")->execute([$_POST['progress_id']]);
        
        $remaining = $conn->prepare("SELECT COUNT(*) as count FROM onboarding_task_progress WHERE onboarding_id = ? AND status = 'Pending'");
        $remaining->execute([$_POST['onboarding_id']]);
        
        if ($remaining->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
            $conn->prepare("UPDATE onboarding SET status = 'Completed' WHERE onboarding_id = ?")->execute([$_POST['onboarding_id']]);
            $conn->prepare("UPDATE job_applications ja JOIN onboarding o ON ja.application_id = o.application_id SET ja.status = 'Offer' WHERE o.onboarding_id = ?")->execute([$_POST['onboarding_id']]);
            $success_message = "🎉 All tasks completed! Candidate moved to Offer status.";
        } else {
            $success_message = "✅ Task completed!";
        }
    } elseif ($_POST['action'] === 'fail_task') {
        $conn->prepare("UPDATE onboarding_task_progress SET status = 'Failed', completed_date = CURDATE(), notes = ? WHERE progress_id = ?")->execute([$_POST['notes'] ?? 'Task failed', $_POST['progress_id']]);
        $success_message = "❌ Task marked as failed.";
    } elseif ($_POST['action'] === 'assign_tasks') {
        $onboarding_id = $_POST['onboarding_id'];
        $application_id = $_POST['application_id'];
        
        // Get department for this application
        $dept_query = $conn->prepare("SELECT jo.department_id FROM job_applications ja 
                                     JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id 
                                     WHERE ja.application_id = ?");
        $dept_query->execute([$application_id]);
        $dept_id = $dept_query->fetchColumn();
        
        // Get tasks for this department only
        $tasks_query = $conn->prepare("SELECT task_id FROM onboarding_tasks WHERE department_id = ? OR department_id IS NULL");
        $tasks_query->execute([$dept_id]);
        $tasks = $tasks_query->fetchAll(PDO::FETCH_COLUMN);
        
        $assigned_count = 0;
        foreach ($tasks as $task_id) {
            $check = $conn->prepare("SELECT COUNT(*) FROM onboarding_task_progress WHERE onboarding_id = ? AND task_id = ?");
            $check->execute([$onboarding_id, $task_id]);
            
            if ($check->fetchColumn() == 0) {
                $conn->prepare("INSERT INTO onboarding_task_progress (onboarding_id, task_id, status) VALUES (?, ?, 'Pending')")->execute([$onboarding_id, $task_id]);
                $assigned_count++;
            }
        }
        
        $success_message = "✅ Assigned {$assigned_count} tasks to candidate.";
    }
}

$onboarding_candidates = $conn->query("SELECT c.*, ja.application_id, jo.title as job_title, d.department_name,
                                      o.onboarding_id, o.start_date, o.status as onboarding_status
                                      FROM candidates c 
                                      JOIN job_applications ja ON c.candidate_id = ja.candidate_id
                                      JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                      JOIN departments d ON jo.department_id = d.department_id
                                      JOIN onboarding o ON ja.application_id = o.application_id
                                      WHERE ja.status = 'Onboarding'
                                      ORDER BY o.start_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Onboarding - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title">🎯 Applicant Onboarding</h2>
                    <a href="onboarding_tasks.php" class="btn btn-primary">
                        <i class="fas fa-tasks"></i> Manage Tasks
                    </a>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $success_message ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($onboarding_candidates)): ?>
                    <?php foreach ($onboarding_candidates as $candidate): ?>
                        <?php
                        $tasks = $conn->prepare("SELECT otp.*, ot.task_name, ot.description
                                                FROM onboarding_task_progress otp
                                                JOIN onboarding_tasks ot ON otp.task_id = ot.task_id
                                                WHERE otp.onboarding_id = ?
                                                ORDER BY ot.task_name");
                        $tasks->execute([$candidate['onboarding_id']]);
                        $task_list = $tasks->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Debug: Get candidate's department
                        $debug_dept = $conn->prepare("SELECT jo.department_id, d.department_name FROM job_applications ja 
                                                     JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                                                     JOIN departments d ON jo.department_id = d.department_id
                                                     WHERE ja.application_id = ?");
                        $debug_dept->execute([$candidate['application_id']]);
                        $candidate_dept = $debug_dept->fetch(PDO::FETCH_ASSOC);
                        
                        // Debug: Get all tasks in system
                        $all_tasks_debug = $conn->query("SELECT task_id, task_name, department_id FROM onboarding_tasks ORDER BY task_name")->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Get department-specific tasks
                        $dept_tasks_query = $conn->prepare("SELECT ot.* FROM onboarding_tasks ot 
                                                           WHERE ot.department_id = ? OR ot.department_id IS NULL
                                                           ORDER BY ot.task_name");
                        $dept_tasks_query->execute([$candidate_dept['department_id']]);
                        $dept_tasks = $dept_tasks_query->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <?= htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']) ?>
                                    <small class="ml-2"><?= htmlspecialchars($candidate['job_title']) ?> - <?= htmlspecialchars($candidate['department_name']) ?></small>
                                </h5>
                                <small>Started: <?= $candidate['start_date'] ? date('M j, Y', strtotime($candidate['start_date'])) : 'Not started' ?></small>
                            </div>
                            <div class="card-body">
                                <?php if (empty($task_list)): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> No tasks assigned yet.
                                        <br><small><strong>Debug Info:</strong></small>
                                        <br><small>Candidate Dept ID: <?= $candidate_dept['department_id'] ?? 'Unknown' ?></small>
                                        <br><small>Candidate Dept Name: <?= $candidate_dept['department_name'] ?? 'Unknown' ?></small>
                                        <br><small>Available dept tasks: <?= count($dept_tasks) ?></small>
                                        <br><small>Total tasks in system: <?= count($all_tasks_debug) ?></small>
                                        <?php if (!empty($all_tasks_debug)): ?>
                                            <br><small>All tasks: 
                                            <?php foreach($all_tasks_debug as $t): ?>
                                                <?= $t['task_name'] ?> (Dept: <?= $t['department_id'] ?? 'NULL' ?>), 
                                            <?php endforeach; ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($dept_tasks)): ?>
                                            <br><small>Matching tasks: <?= implode(', ', array_column($dept_tasks, 'task_name')) ?></small>
                                            <form method="POST" class="mt-2">
                                                <input type="hidden" name="action" value="assign_tasks">
                                                <input type="hidden" name="onboarding_id" value="<?= $candidate['onboarding_id'] ?>">
                                                <input type="hidden" name="application_id" value="<?= $candidate['application_id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-plus"></i> Assign Department Tasks (<?= count($dept_tasks) ?>)
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($task_list as $task): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card border-<?= $task['status'] === 'Completed' ? 'success' : ($task['status'] === 'Failed' ? 'danger' : 'warning') ?>">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="mb-1"><?= htmlspecialchars($task['task_name']) ?></h6>
                                                            <span class="badge badge-<?= $task['status'] === 'Completed' ? 'success' : ($task['status'] === 'Failed' ? 'danger' : 'warning') ?>">
                                                                <?= $task['status'] ?>
                                                            </span>
                                                        </div>
                                                        <p class="text-muted small mb-2"><?= htmlspecialchars($task['description']) ?></p>
                                                        <?php if ($task['status'] === 'Pending'): ?>
                                                            <div class="btn-group btn-group-sm">
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="progress_id" value="<?= $task['progress_id'] ?>">
                                                                    <input type="hidden" name="onboarding_id" value="<?= $candidate['onboarding_id'] ?>">
                                                                    <button type="submit" name="action" value="complete_task" class="btn btn-success btn-sm">
                                                                        <i class="fas fa-check"></i> Complete
                                                                    </button>
                                                                </form>
                                                                <button type="button" class="btn btn-danger btn-sm ml-1" onclick="failTask(<?= $task['progress_id'] ?>, <?= $candidate['onboarding_id'] ?>)">
                                                                    <i class="fas fa-times"></i> Fail
                                                                </button>
                                                            </div>
                                                        <?php elseif ($task['completed_date']): ?>
                                                            <small class="text-muted">Date: <?= date('M j, Y', strtotime($task['completed_date'])) ?></small>
                                                        <?php endif; ?>
                                                        <?php if ($task['notes']): ?>
                                                            <div class="mt-2">
                                                                <small class="text-muted"><strong>Notes:</strong> <?= htmlspecialchars($task['notes']) ?></small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No candidates in onboarding</h4>
                        <p class="text-muted">Candidates will appear here when moved to onboarding status.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Fail Task Modal -->
    <div class="modal fade" id="failTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fail Task</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="progress_id" id="failProgressId">
                        <input type="hidden" name="onboarding_id" id="failOnboardingId">
                        <div class="form-group">
                            <label>Reason for failure:</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Enter reason..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="action" value="fail_task" class="btn btn-danger">Mark as Failed</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        function failTask(progressId, onboardingId) {
            $('#failProgressId').val(progressId);
            $('#failOnboardingId').val(onboardingId);
            $('#failTaskModal').modal('show');
        }
    </script>
</body>
</html>