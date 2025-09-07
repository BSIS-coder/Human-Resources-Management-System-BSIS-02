<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'dp.php';

$host = 'localhost';
$dbname = 'CC_HR';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_applicant':
                try {
                    $stmt = $pdo->prepare("INSERT INTO onboarding_tasks (task_name, description, department_id, task_type) VALUES (?, ?, ?, 'Administrative')");
                    $stmt->execute([$_POST['task_name'], $_POST['task_description'], $_POST['department_id']]);
                    $message = "Task added successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error adding task: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
            
            case 'add_employee':
                try {
                    $stmt = $pdo->prepare("INSERT INTO onboarding_tasks (task_name, description, task_type) VALUES (?, ?, 'Training')");
                    $stmt->execute([$_POST['task_name'], $_POST['task_description']]);
                    $message = "Task added successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error adding task: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
            
            case 'delete_task':
                try {
                    $stmt = $pdo->prepare("DELETE FROM onboarding_tasks WHERE task_id=?");
                    $stmt->execute([$_POST['task_id']]);
                    $message = "Task deleted successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error deleting task: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
        }
    }
}

// Fetch tasks with department info
$stmt = $pdo->query("SELECT ot.*, d.department_name FROM onboarding_tasks ot LEFT JOIN departments d ON ot.department_id = d.department_id WHERE ot.department_id IS NOT NULL ORDER BY d.department_name, ot.task_id DESC");
$applicantTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch general tasks (no department)
$stmt = $pdo->query("SELECT * FROM onboarding_tasks WHERE department_id IS NULL ORDER BY task_id DESC");
$employeeTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all departments
$stmt = $pdo->query("SELECT department_id, department_name FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding Task Management - HR System</title>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title">⚙️ Onboarding Task Management</h2>
                    <div>
                        <a href="onboarding.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Applicant Onboarding
                        </a>
                        <a href="employee_onboarding.php" class="btn btn-success ml-2">
                            <i class="fas fa-user-tie"></i> Employee Onboarding
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Applicant Tasks Card -->
                    <div class="col-md-6 mb-4">
                        <div class="card" style="border-radius: 15px; box-shadow: 0 5px 20px rgba(233, 30, 99, 0.1);">
                            <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #E91E63 0%, #F06292 100%); border-radius: 15px 15px 0 0;">
                                <h5 class="mb-0"><i class="fas fa-user-plus mr-2"></i>Applicant Onboarding Tasks</h5>
                                <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#addApplicantTaskModal">
                                    <i class="fas fa-plus mr-1"></i>Add Task
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Task</th>
                                                <th>Department</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($applicantTasks as $task): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($task['task_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($task['description']); ?></small>
                                                </td>
                                                <td><span class="badge badge-secondary"><?php echo htmlspecialchars($task['department_name']); ?></span></td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_task">
                                                        <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this task?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Tasks Card -->
                    <div class="col-md-6 mb-4">
                        <div class="card" style="border-radius: 15px; box-shadow: 0 5px 20px rgba(233, 30, 99, 0.1);">
                            <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #E91E63 0%, #F06292 100%); border-radius: 15px 15px 0 0;">
                                <h5 class="mb-0"><i class="fas fa-user-tie mr-2"></i>Employee Onboarding Tasks</h5>
                                <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#addEmployeeTaskModal">
                                    <i class="fas fa-plus mr-1"></i>Add Task
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Task</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($employeeTasks as $task): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($task['task_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($task['description']); ?></small>
                                                </td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_task">
                                                        <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this task?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Applicant Task Modal -->
    <div class="modal fade" id="addApplicantTaskModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Applicant Task</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_applicant">
                        <div class="form-group">
                            <label>Task Name:</label>
                            <input type="text" name="task_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description:</label>
                            <textarea name="task_description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Department:</label>
                            <select name="department_id" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <small class="text-muted">These tasks will be assigned to applicants from the selected department.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Task</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Employee Task Modal -->
    <div class="modal fade" id="addEmployeeTaskModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Employee Task</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_employee">
                        <div class="form-group">
                            <label>Task Name:</label>
                            <input type="text" name="task_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description:</label>
                            <textarea name="task_description" class="form-control" rows="3" required></textarea>
                        </div>
                        <small class="text-muted">These tasks will be assigned to all employees regardless of department.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Task</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>