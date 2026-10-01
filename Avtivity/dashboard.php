<?php
// dashboard.php - Student Record System Dashboard
require_once 'auth_check.php';
require_once 'db_connection.php';

// Retrieve search and filter inputs
$search        = trim($_GET['search'] ?? '');
$filter_course = trim($_GET['filter_course'] ?? '');
$filter_year   = trim($_GET['filter_year'] ?? '');
$filter_status = trim($_GET['filter_status'] ?? '');

// Statistics queries
$totalStudents = 0;
$regularCount  = 0;
$irregularCount = 0;

$countRes = $conn->query("SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'Regular' THEN 1 ELSE 0 END) AS regular_count,
    SUM(CASE WHEN status = 'Irregular' THEN 1 ELSE 0 END) AS irregular_count
FROM students");

if ($countRes && $row = $countRes->fetch_assoc()) {
    $totalStudents  = (int)($row['total'] ?? 0);
    $regularCount   = (int)($row['regular_count'] ?? 0);
    $irregularCount = (int)($row['irregular_count'] ?? 0);
}

// Build dynamic student list query with prepared statement
$sql = "SELECT * FROM students WHERE 1=1";
$params = [];
$types  = "";

if (!empty($search)) {
    $sql .= " AND (student_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ?)";
    $searchWildcard = "%" . $search . "%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types   .= "ssss";
}

if (!empty($filter_course)) {
    $sql .= " AND course = ?";
    $params[] = $filter_course;
    $types   .= "s";
}

if (!empty($filter_year)) {
    $sql .= " AND year_level = ?";
    $params[] = intval($filter_year);
    $types   .= "i";
}

if (!empty($filter_status)) {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
    $types   .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$studentsResult = $stmt->get_result();

$studentsList = [];
while ($row = $studentsResult->fetch_assoc()) {
    $studentsList[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Record System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            background: #ffffff;
        }
        .table th {
            font-weight: 600;
            background-color: #212529;
            color: #ffffff;
            vertical-align: middle;
        }
        .table td {
            vertical-align: middle;
        }
        .action-btns .btn {
            padding: 4px 10px;
            font-size: 0.85rem;
            margin: 2px;
        }
    </style>
</head>
<body>

<!-- Top Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <i class="bi bi-mortarboard-fill me-2 fs-4"></i>
            Student Record System
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active fw-semibold" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center text-white">
                <div class="me-3">
                    <i class="bi bi-person-circle fs-5 me-1 text-white-50"></i>
                    Welcome, <strong class="text-white"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></strong>
                </div>
                <a href="logout.php" class="btn btn-outline-light btn-sm px-3">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4">

    <!-- Flash Messages (Success & Error Alerts) -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div><?= htmlspecialchars($_SESSION['success']); ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div><?= htmlspecialchars($_SESSION['error']); ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Welcome & Stats Overview Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Student Records Dashboard</h3>
            <p class="text-muted mb-0">Manage, update, and monitor student academic records</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="bi bi-person-plus-fill me-1"></i> Add New Student
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Students</div>
                        <h3 class="fw-bold mb-0 text-dark"><?= $totalStudents; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="card stat-card bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Regular Students</div>
                        <h3 class="fw-bold mb-0 text-dark"><?= $regularCount; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">
            <div class="card stat-card bg-white p-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-person-exclamation"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Irregular Students</div>
                        <h3 class="fw-bold mb-0 text-dark"><?= $irregularCount; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card (Optional Challenge Implemented) -->
    <div class="card card-custom mb-4">
        <div class="card-body p-3">
            <form method="GET" action="dashboard.php" class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-lg-4 col-md-12">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search by Student ID or Name..." value="<?= htmlspecialchars($search); ?>">
                    </div>
                </div>

                <!-- Filter Course -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <select class="form-select" name="filter_course">
                        <option value="">All Courses</option>
                        <option value="BSIT" <?= ($filter_course === 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                        <option value="BSCS" <?= ($filter_course === 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
                        <option value="BSIS" <?= ($filter_course === 'BSIS') ? 'selected' : ''; ?>>BSIS</option>
                        <option value="BSEMC" <?= ($filter_course === 'BSEMC') ? 'selected' : ''; ?>>BSEMC</option>
                        <option value="BSBA" <?= ($filter_course === 'BSBA') ? 'selected' : ''; ?>>BSBA</option>
                        <option value="BSCPE" <?= ($filter_course === 'BSCPE') ? 'selected' : ''; ?>>BSCPE</option>
                    </select>
                </div>

                <!-- Filter Year Level -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <select class="form-select" name="filter_year">
                        <option value="">All Year Levels</option>
                        <option value="1" <?= ($filter_year === '1') ? 'selected' : ''; ?>>Year 1</option>
                        <option value="2" <?= ($filter_year === '2') ? 'selected' : ''; ?>>Year 2</option>
                        <option value="3" <?= ($filter_year === '3') ? 'selected' : ''; ?>>Year 3</option>
                        <option value="4" <?= ($filter_year === '4') ? 'selected' : ''; ?>>Year 4</option>
                    </select>
                </div>

                <!-- Filter Status -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <select class="form-select" name="filter_status">
                        <option value="">All Statuses</option>
                        <option value="Regular" <?= ($filter_status === 'Regular') ? 'selected' : ''; ?>>Regular</option>
                        <option value="Irregular" <?= ($filter_status === 'Irregular') ? 'selected' : ''; ?>>Irregular</option>
                    </select>
                </div>

                <!-- Submit & Clear Buttons -->
                <div class="col-lg-2 col-md-12 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel-fill"></i> Filter
                    </button>
                    <?php if (!empty($search) || !empty($filter_course) || !empty($filter_year) || !empty($filter_status)): ?>
                        <a href="dashboard.php" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Student Records Table -->
    <div class="card card-custom">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="bi bi-card-list me-2 text-primary"></i>Student Records List
            </h5>
            <span class="badge bg-secondary">
                Showing <?= count($studentsList); ?> record(s)
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th class="text-center">Year Level</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($studentsList) > 0): ?>
                            <?php foreach ($studentsList as $student): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-primary">
                                        <?= htmlspecialchars($student['student_id']); ?>
                                    </td>
                                    <td class="fw-semibold">
                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </td>
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($student['email']); ?>" class="text-decoration-none text-muted">
                                            <?= htmlspecialchars($student['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($student['course']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?= htmlspecialchars($student['year_level']); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($student['status'] === 'Regular'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                Regular
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                Irregular
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3 action-btns">
                                        <!-- Edit Button (Opens Modal or Page) -->
                                        <a href="edit_student.php?id=<?= $student['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Edit Student">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>

                                        <!-- Delete Button (Triggers Delete Confirmation Modal) -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal<?= $student['id']; ?>"
                                                title="Delete Student">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>

                                <!-- Delete Confirmation Modal for Student ID: <?= $student['student_id']; ?> -->
                                <div class="modal fade" id="deleteModal<?= $student['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $student['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title fw-bold" id="deleteModalLabel<?= $student['id']; ?>">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="delete_student.php">
                                                <div class="modal-body text-center py-4">
                                                    <div class="text-danger mb-3" style="font-size: 48px;">
                                                        <i class="bi bi-trash3"></i>
                                                    </div>
                                                    <h5 class="fw-bold mb-2">Are you sure you want to delete this student record?</h5>
                                                    <p class="text-muted mb-3">This action cannot be undone. All information for this student will be removed.</p>
                                                    <div class="p-3 bg-light rounded text-start border">
                                                        <div><strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']); ?></div>
                                                        <div><strong>Full Name:</strong> <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                                        <div><strong>Course & Year:</strong> <?= htmlspecialchars($student['course'] . ' - Year ' . $student['year_level']); ?></div>
                                                    </div>
                                                    <!-- Student Primary Key -->
                                                    <input type="hidden" name="id" value="<?= $student['id']; ?>">
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="bi bi-x-circle me-1"></i> Cancel
                                                    </button>
                                                    <button type="submit" class="btn btn-danger fw-semibold">
                                                        <i class="bi bi-trash-fill me-1"></i> Yes, Delete
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                                    <h5>No student records found.</h5>
                                    <p class="small mb-0">Try clearing your filters or add a new student record.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="addStudentModalLabel">
                    <i class="bi bi-person-plus-fill me-2"></i>Add New Student Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="add_student.php">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Student ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="student_id" placeholder="e.g. 2026006" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Course / Program <span class="text-danger">*</span></label>
                            <select class="form-select" name="course" required>
                                <option value="" disabled selected>Select Course</option>
                                <option value="BSIT">BSIT - Bachelor of Science in Information Technology</option>
                                <option value="BSCS">BSCS - Bachelor of Science in Computer Science</option>
                                <option value="BSIS">BSIS - Bachelor of Science in Information Systems</option>
                                <option value="BSEMC">BSEMC - BS in Entertainment and Multimedia Computing</option>
                                <option value="BSBA">BSBA - Bachelor of Science in Business Administration</option>
                                <option value="BSCPE">BSCPE - Bachelor of Science in Computer Engineering</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" placeholder="e.g. John" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" placeholder="e.g. Doe" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" placeholder="e.g. john.doe@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Year Level <span class="text-danger">*</span></label>
                            <select class="form-select" name="year_level" required>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Enrollment Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="Regular" selected>Regular</option>
                                <option value="Irregular">Irregular</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-plus-circle me-1"></i> Save Student Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
