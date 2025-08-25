<?php
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'dp.php';

// Fetch shifts from the database
function getShifts() {
    global $conn;
    try {
        $sql = "SELECT * FROM shifts ORDER BY shift_name";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Calculate shift duration
function calculateDuration($start_time, $end_time) {
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $interval = $start->diff($end);
    return $interval->format('%h hours %i minutes');
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addShift'])) {
        // Add new shift
        $name = $_POST['shiftName'];
        $start_time = $_POST['startTime'];
        $end_time = $_POST['endTime'];
        $description = $_POST['shiftDescription'];

        try {
            $sql = "INSERT INTO shifts (shift_name, start_time, end_time, description) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $start_time, $end_time, $description]);
            
            // Redirect to refresh the page and show the new shift
            header("Location: shifts.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error adding shift: " . $e->getMessage();
        }
    } elseif (isset($_POST['editShift'])) {
        // Edit existing shift
        $id = $_POST['shiftId'];
        $name = $_POST['shiftName'];
        $start_time = $_POST['startTime'];
        $end_time = $_POST['endTime'];
        $description = $_POST['shiftDescription'];

        try {
            $sql = "UPDATE shifts SET shift_name = ?, start_time = ?, end_time = ?, description = ? WHERE shift_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $start_time, $end_time, $description, $id]);
            
            // Redirect to refresh the page
            header("Location: shifts.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error updating shift: " . $e->getMessage();
        }
    } elseif (isset($_POST['deleteShift'])) {
        // Delete shift
        $id = $_POST['shiftId'];

        try {
            $sql = "DELETE FROM shifts WHERE shift_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            // Redirect to refresh the page
            header("Location: shifts.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error deleting shift: " . $e->getMessage();
        }
    }
}

$shifts = getShifts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shifts - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <style>
        .section-title {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .shift-card {
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 1px solid var(--border-light);
        }
        
        .shift-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-medium);
        }
        
        .shift-status {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <h2 class="section-title">Shifts Management</h2>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-clock mr-2"></i>Shifts Overview</h5>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#addShiftModal">
                                    <i class="fas fa-plus mr-2"></i>Add Shift
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Shift Name</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Duration</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($shifts)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        <i class="fas fa-clock fa-2x mb-2"></i>
                                                        <p>No shifts found. Add your first shift using the button above.</p>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($shifts as $shift): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($shift['shift_name']); ?></td>
                                                    <td><?php echo date('h:i A', strtotime($shift['start_time'])); ?></td>
                                                    <td><?php echo date('h:i A', strtotime($shift['end_time'])); ?></td>
                                                    <td><?php echo calculateDuration($shift['start_time'], $shift['end_time']); ?></td>
                                                    <td><span class="shift-status badge badge-success">Active</span></td>
                                                    <td>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="shiftId" value="<?php echo $shift['shift_id']; ?>">
                                                            <button type="button" class="btn btn-sm btn-outline-primary mr-2" data-toggle="modal" data-target="#editShiftModal" 
                                                                    data-id="<?php echo $shift['shift_id']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($shift['shift_name']); ?>"
                                                                    data-starttime="<?php echo $shift['start_time']; ?>"
                                                                    data-endtime="<?php echo $shift['end_time']; ?>"
                                                                    data-description="<?php echo htmlspecialchars($shift['description']); ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="shiftId" value="<?php echo $shift['shift_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" name="deleteShift" onclick="return confirm('Are you sure you want to delete this shift?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar mr-2"></i>Shift Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center mb-4">
                                    <div class="col-4">
                                        <h4 class="text-primary"><?php echo count($shifts); ?></h4>
                                        <small class="text-muted">Total Shifts</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-success"><?php echo count($shifts); ?></h4>
                                        <small class="text-muted">Active Shifts</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-danger">0</h4>
                                        <small class="text-muted">Inactive Shifts</small>
                                    </div>
                                </div>
                                <?php if (!empty($shifts)): ?>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-success" style="width: 100%">Active (100%)</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" style="width: 0%">Inactive (0%)</div>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>No shift data available for statistics.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Shift Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <strong>Note:</strong> Ensure to assign shifts based on employee availability and preferences.
                                </div>
                                <div class="alert alert-warning">
                                    <strong>Warning:</strong> Inactive shifts will not be assigned to employees.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Shift Modal -->
    <div class="modal fade" id="addShiftModal" tabindex="-1" role="dialog" aria-labelledby="addShiftModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addShiftModalLabel">Add New Shift</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="shiftName">Shift Name</label>
                            <input type="text" class="form-control" name="shiftName" id="shiftName" placeholder="Enter shift name" required>
                        </div>
                        <div class="form-group">
                            <label for="startTime">Start Time</label>
                            <input type="time" class="form-control" name="startTime" id="startTime" required>
                        </div>
                        <div class="form-group">
                            <label for="endTime">End Time</label>
                            <input type="time" class="form-control" name="endTime" id="endTime" required>
                        </div>
                        <div class="form-group">
                            <label for="shiftDescription">Description</label>
                            <textarea class="form-control" name="shiftDescription" id="shiftDescription" rows="3" placeholder="Enter shift description"></textarea>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="addShift">Save Shift</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Shift Modal -->
    <div class="modal fade" id="editShiftModal" tabindex="-1" role="dialog" aria-labelledby="editShiftModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editShiftModalLabel">Edit Shift</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="shiftId" id="editShiftId">
                        <div class="form-group">
                            <label for="editShiftName">Shift Name</label>
                            <input type="text" class="form-control" name="shiftName" id="editShiftName" placeholder="Enter shift name" required>
                        </div>
                        <div class="form-group">
                            <label for="editStartTime">Start Time</label>
                            <input type="time" class="form-control" name="startTime" id="editStartTime" required>
                        </div>
                        <div class="form-group">
                            <label for="editEndTime">End Time</label>
                            <input type="time" class="form-control" name="endTime" id="editEndTime" required>
                        </div>
                        <div class="form-group">
                            <label for="editShiftDescription">Description</label>
                            <textarea class="form-control" name="shiftDescription" id="editShiftDescription" rows="3" placeholder="Enter shift description"></textarea>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="editShift">Update Shift</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#editShiftModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var name = button.data('name');
                var startTime = button.data('starttime');
                var endTime = button.data('endtime');
                var description = button.data('description');
                
                var modal = $(this);
                modal.find('#editShiftId').val(id);
                modal.find('#editShiftName').val(name);
                modal.find('#editStartTime').val(startTime);
                modal.find('#editEndTime').val(endTime);
                modal.find('#editShiftDescription').val(description);
            });
        });
    </script>
</body>
</html>
