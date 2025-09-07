<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

$success_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_application':
                $application_id = $_POST['application_id'];
                
                $app_stmt = $conn->prepare("SELECT candidate_id FROM job_applications WHERE application_id = ?");
                $app_stmt->execute([$application_id]);
                $app_data = $app_stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Screening' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                $stmt = $conn->prepare("UPDATE candidates SET source = 'Screening' WHERE candidate_id = ?");
                $stmt->execute([$app_data['candidate_id']]);

                $job_stmt = $conn->prepare("SELECT job_opening_id FROM job_applications WHERE application_id = ?");
                $job_stmt->execute([$application_id]);
                $job_data = $job_stmt->fetch(PDO::FETCH_ASSOC);
                
                $first_stage_stmt = $conn->prepare("SELECT stage_id, stage_name FROM interview_stages WHERE job_opening_id = ? ORDER BY stage_order LIMIT 1");
                $first_stage_stmt->execute([$job_data['job_opening_id']]);
                $first_stage = $first_stage_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($first_stage) {
                    $stmt = $conn->prepare("UPDATE job_applications SET status = 'Interview' WHERE application_id = ?");
                    $stmt->execute([$application_id]);
                    
                    $stmt = $conn->prepare("UPDATE candidates SET source = ? WHERE candidate_id = ?");
                    $stmt->execute([$first_stage['stage_name'], $app_data['candidate_id']]);
                    
                    $stmt = $conn->prepare("INSERT INTO interviews (application_id, stage_id, schedule_date, duration, interview_type, status) VALUES (?, ?, NOW(), 60, 'Interview', 'Rescheduled')");
                    $stmt->execute([$application_id, $first_stage['stage_id']]);
                    
                    $success_message = "✅ Application approved and moved to Interview stage!";
                } else {
                    $success_message = "✅ Application approved and moved to Screening!";
                }
                break;
                
            case 'reject_candidate':
                $application_id = $_POST['application_id'];
                
                $app_stmt = $conn->prepare("SELECT candidate_id FROM job_applications WHERE application_id = ?");
                $app_stmt->execute([$application_id]);
                $app_data = $app_stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $conn->prepare("DELETE FROM interviews WHERE application_id = ?");
                $stmt->execute([$application_id]);
                
                $stmt = $conn->prepare("UPDATE job_applications SET status = 'Rejected' WHERE application_id = ?");
                $stmt->execute([$application_id]);
                

                
                $success_message = "❌ Application rejected!";
                break;
                
            case 'update_assessment':
                $application_id = $_POST['application_id'];
                $assessment_score = $_POST['assessment_score'];
                
                $stmt = $conn->prepare("UPDATE job_applications SET assessment_scores = ? WHERE application_id = ?");
                $stmt->execute([json_encode(['overall_score' => $assessment_score]), $application_id]);
                
                $success_message = "📊 Assessment score updated!";
                break;
        }
    }
}

$show_archived = isset($_GET['archived']) && $_GET['archived'] == '1';

if ($show_archived) {
    $applications_query = "SELECT ja.*, c.first_name, c.last_name, c.email, c.phone, c.current_position, c.resume_filename,
                           jo.title as job_title, d.department_name
                           FROM job_applications ja 
                           JOIN candidates c ON ja.candidate_id = c.candidate_id 
                           JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                           JOIN departments d ON jo.department_id = d.department_id
                           WHERE ja.status IN ('Hired', 'Rejected')";
} else {
    $applications_query = "SELECT ja.*, c.first_name, c.last_name, c.email, c.phone, c.current_position, c.resume_filename,
                           jo.title as job_title, d.department_name
                           FROM job_applications ja 
                           JOIN candidates c ON ja.candidate_id = c.candidate_id 
                           JOIN job_openings jo ON ja.job_opening_id = jo.job_opening_id
                           JOIN departments d ON jo.department_id = d.department_id
                           WHERE ja.status IN ('Applied', 'Screening', 'Interview', 'Assessment', 'Onboarding', 'Offer')";
}

$stmt = $conn->query($applications_query . " ORDER BY ja.application_date DESC");
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'Applied' => count(array_filter($applications, function($a) { return $a['status'] == 'Applied'; })),
    'Screening' => count(array_filter($applications, function($a) { return $a['status'] == 'Screening'; })),
    'Interview' => count(array_filter($applications, function($a) { return $a['status'] == 'Interview'; })),
    'Assessment' => count(array_filter($applications, function($a) { return $a['status'] == 'Assessment'; })),
    'Onboarding' => count(array_filter($applications, function($a) { return $a['status'] == 'Onboarding'; })),
    'Offer' => count(array_filter($applications, function($a) { return $a['status'] == 'Offer'; })),
    'Hired' => count(array_filter($applications, function($a) { return $a['status'] == 'Hired'; })),
    'Rejected' => count(array_filter($applications, function($a) { return $a['status'] == 'Rejected'; }))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applications - HR Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=rose">
    <style>
        .application-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(233, 30, 99, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(233, 30, 99, 0.15);
        }
        
        .application-header {
            background: linear-gradient(135deg, #E91E63 0%, #F06292 100%);
            color: white;
            padding: 20px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .filter-toolbar {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php include 'navigation.php'; ?>
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title">📋 Job Applications Management</h2>
                    <div>
                        <a href="?archived=0" class="btn btn-primary <?php echo !$show_archived ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> Active
                        </a>
                        <a href="?archived=1" class="btn btn-secondary <?php echo $show_archived ? 'active' : ''; ?> ml-2">
                            <i class="fas fa-archive"></i> Archived
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-warning">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['Applied']; ?></h3>
                                <p class="stats-label">Applied</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-info">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['Screening']; ?></h3>
                                <p class="stats-label">Screening</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-primary">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['Interview']; ?></h3>
                                <p class="stats-label">Interview</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-secondary">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['Assessment']; ?></h3>
                                <p class="stats-label">Assessment</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['Hired']; ?></h3>
                                <p class="stats-label">Hired</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card card">
                            <div class="card-body text-center">
                                <div class="activity-icon bg-danger">
                                    <i class="fas fa-times"></i>
                                </div>
                                <h3 class="stats-number"><?php echo $stats['Rejected']; ?></h3>
                                <p class="stats-label">Rejected</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Applications Grid -->
                <?php if (count($applications) > 0): ?>
                    <div class="row">
                        <?php foreach($applications as $app): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="application-card" style="cursor: pointer;" onclick="showApplicationDetails(<?php echo $app['application_id']; ?>)">
                                    <div class="application-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h6>
                                                <small class="opacity-75"><?php echo htmlspecialchars($app['job_title']); ?></small>
                                            </div>
                                            <?php
                                            $status_colors = [
                                                'Applied' => 'warning',
                                                'Screening' => 'info', 
                                                'Interview' => 'primary',
                                                'Assessment' => 'secondary',
                                                'Onboarding' => 'info',
                                                'Offer' => 'success',
                                                'Hired' => 'success',
                                                'Rejected' => 'danger'
                                            ];
                                            $color = $status_colors[$app['status']] ?? 'secondary';
                                            ?>
                                            <span class="status-badge bg-<?php echo $color; ?> text-white">
                                                <?php echo htmlspecialchars($app['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2"><i class="fas fa-building mr-2 text-muted"></i><?php echo htmlspecialchars($app['department_name']); ?></p>
                                        <p class="mb-2"><i class="fas fa-calendar mr-2 text-muted"></i><?php echo date('M d, Y', strtotime($app['application_date'])); ?></p>
                                        <p class="mb-0"><i class="fas fa-clock mr-2 text-muted"></i><?php echo floor((time() - strtotime($app['application_date'])) / 86400); ?> days</p>
                                        
                                        <?php 
                                        $assessment_data = json_decode($app['assessment_scores'], true);
                                        $current_score = $assessment_data['overall_score'] ?? '';
                                        if ($current_score): ?>
                                            <div class="mt-2">
                                                <span class="badge badge-info">Score: <?php echo $current_score; ?>%</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No Applications Found</h5>
                            <p class="text-muted">
                                <?php echo $show_archived ? 'No archived applications available.' : 'No active applications at the moment.'; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Application Detail Modal -->
    <div class="modal fade" id="applicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Assessment</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <style>
        .assessment-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            border: 2px solid #E91E63;
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #E91E63 0%, #F06292 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: white;
            box-shadow: 0 8px 25px rgba(233, 30, 99, 0.3);
            transition: all 0.3s ease;
        }
        
        .score-number {
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
        }
        
        .score-label {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .category {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .category-label {
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
            color: #333;
        }
        
        .star-rating {
            display: flex;
            gap: 5px;
        }
        
        .star {
            font-size: 24px;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.3;
        }
        
        .star:hover,
        .star.active {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .star.active {
            filter: drop-shadow(0 0 5px #ffd700);
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function showApplicationDetails(applicationId) {
            // Find application data
            const applications = <?php echo json_encode($applications); ?>;
            const app = applications.find(a => a.application_id == applicationId);
            
            if (!app) return;
            
            const assessmentData = app.assessment_scores ? JSON.parse(app.assessment_scores) : {};
            const currentScore = assessmentData.overall_score || '';
            
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-user mr-2"></i>Candidate Information</h6>
                        <p><strong>Name:</strong> ${app.first_name} ${app.last_name}</p>
                        <p><strong>Email:</strong> ${app.email}</p>
                        <p><strong>Phone:</strong> ${app.phone || 'Not provided'}</p>
                        <p><strong>Current Position:</strong> ${app.current_position || 'Not specified'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-briefcase mr-2"></i>Application Details</h6>
                        <p><strong>Position:</strong> ${app.job_title}</p>
                        <p><strong>Department:</strong> ${app.department_name}</p>
                        <p><strong>Applied:</strong> ${new Date(app.application_date).toLocaleDateString()}</p>
                        <p><strong>Status:</strong> <span class="badge badge-secondary">${app.status}</span></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-12">
                        <h6><i class="fas fa-star mr-2"></i>HR Assessment</h6>
                        <form method="POST" id="assessmentForm">
                            <input type="hidden" name="action" value="update_assessment">
                            <input type="hidden" name="application_id" value="${app.application_id}">
                            <input type="hidden" name="assessment_score" id="hiddenScore" value="${currentScore}">
                            
                            <div class="assessment-container mb-4">
                                <div class="score-display text-center mb-3">
                                    <div class="score-circle" id="scoreCircle">
                                        <span class="score-number" id="scoreNumber">${currentScore || 0}</span>
                                        <small class="score-label">Score</small>
                                    </div>
                                </div>
                                
                                <div class="rating-categories">
                                    <div class="category mb-3">
                                        <label class="category-label">📋 Qualifications Match</label>
                                        <div class="star-rating" data-category="qualifications">
                                            <span class="star" data-value="1">⭐</span>
                                            <span class="star" data-value="2">⭐</span>
                                            <span class="star" data-value="3">⭐</span>
                                            <span class="star" data-value="4">⭐</span>
                                            <span class="star" data-value="5">⭐</span>
                                        </div>
                                    </div>
                                    
                                    <div class="category mb-3">
                                        <label class="category-label">💼 Experience Level</label>
                                        <div class="star-rating" data-category="experience">
                                            <span class="star" data-value="1">⭐</span>
                                            <span class="star" data-value="2">⭐</span>
                                            <span class="star" data-value="3">⭐</span>
                                            <span class="star" data-value="4">⭐</span>
                                            <span class="star" data-value="5">⭐</span>
                                        </div>
                                    </div>
                                    
                                    <div class="category mb-3">
                                        <label class="category-label">🎯 Cultural Fit</label>
                                        <div class="star-rating" data-category="culture">
                                            <span class="star" data-value="1">⭐</span>
                                            <span class="star" data-value="2">⭐</span>
                                            <span class="star" data-value="3">⭐</span>
                                            <span class="star" data-value="4">⭐</span>
                                            <span class="star" data-value="5">⭐</span>
                                        </div>
                                    </div>
                                    
                                    <div class="category mb-3">
                                        <label class="category-label">💬 Communication Skills</label>
                                        <div class="star-rating" data-category="communication">
                                            <span class="star" data-value="1">⭐</span>
                                            <span class="star" data-value="2">⭐</span>
                                            <span class="star" data-value="3">⭐</span>
                                            <span class="star" data-value="4">⭐</span>
                                            <span class="star" data-value="5">⭐</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save mr-2"></i>Save Assessment
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-end">
                    ${app.status === 'Applied' ? `
                        <form method="POST" style="display: inline;" class="mr-2">
                            <input type="hidden" name="action" value="approve_application">
                            <input type="hidden" name="application_id" value="${app.application_id}">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check mr-1"></i>Approve Application
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="reject_candidate">
                            <input type="hidden" name="application_id" value="${app.application_id}">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this application?')">
                                <i class="fas fa-times mr-1"></i>Reject Application
                            </button>
                        </form>
                    ` : `<span class="text-muted">Application is in ${app.status} stage</span>`}
                </div>
            `;
            
            document.getElementById('modalContent').innerHTML = content;
            $('#applicationModal').modal('show');
            
            // Initialize star rating system
            setTimeout(() => {
                initializeStarRating();
            }, 100);
        }
        
        function initializeStarRating() {
            const ratings = {
                qualifications: 0,
                experience: 0,
                culture: 0,
                communication: 0
            };
            
            // Handle star clicks
            document.querySelectorAll('.star').forEach(star => {
                star.addEventListener('click', function() {
                    const category = this.parentElement.dataset.category;
                    const value = parseInt(this.dataset.value);
                    
                    ratings[category] = value;
                    
                    // Update visual stars
                    const categoryStars = this.parentElement.querySelectorAll('.star');
                    categoryStars.forEach((s, index) => {
                        if (index < value) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                    
                    // Calculate overall score
                    updateOverallScore(ratings);
                });
                
                // Hover effects
                star.addEventListener('mouseenter', function() {
                    const value = parseInt(this.dataset.value);
                    const categoryStars = this.parentElement.querySelectorAll('.star');
                    categoryStars.forEach((s, index) => {
                        if (index < value) {
                            s.style.opacity = '1';
                        } else {
                            s.style.opacity = '0.3';
                        }
                    });
                });
                
                star.addEventListener('mouseleave', function() {
                    const category = this.parentElement.dataset.category;
                    const activeValue = ratings[category];
                    const categoryStars = this.parentElement.querySelectorAll('.star');
                    categoryStars.forEach((s, index) => {
                        if (index < activeValue) {
                            s.style.opacity = '1';
                        } else {
                            s.style.opacity = '0.3';
                        }
                    });
                });
            });
        }
        
        function updateOverallScore(ratings) {
            const total = Object.values(ratings).reduce((sum, rating) => sum + rating, 0);
            const average = total / Object.keys(ratings).length;
            const percentage = Math.round((average / 5) * 100);
            
            document.getElementById('scoreNumber').textContent = percentage;
            document.getElementById('hiddenScore').value = percentage;
            
            // Update circle color based on score
            const circle = document.getElementById('scoreCircle');
            if (percentage >= 80) {
                circle.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
            } else if (percentage >= 60) {
                circle.style.background = 'linear-gradient(135deg, #ffc107 0%, #e0a800 100%)';
            } else if (percentage >= 40) {
                circle.style.background = 'linear-gradient(135deg, #fd7e14 0%, #e55a4e 100%)';
            } else {
                circle.style.background = 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';
            }
        }
    </script>
</body>
</html>