<?php
// edit_student.php - Edit Student Form & Update Record Handler
require_once 'auth_check.php';
require_once 'db_connection.php';

$error = "";

// 1. Handle Form Submission (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id         = intval($_POST['id'] ?? 0);
    $student_id = trim($_POST['student_id'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $course     = trim($_POST['course'] ?? '');
    $year_level = intval($_POST['year_level'] ?? 0);
    $status     = trim($_POST['status'] ?? 'Regular');

    // Validation
    if ($id <= 0 || empty($student_id) || empty($first_name) || empty($last_name) || empty($email) || empty($course) || $year_level <= 0) {
        $error = "All required fields must be filled out.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Validate Student ID uniqueness (excluding current student record)
        $checkSql = "SELECT id FROM students WHERE student_id = ? AND id != ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("si", $student_id, $id);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "Student ID '$student_id' is already assigned to another student. Student ID must remain unique.";
            $checkStmt->close();
        } else {
            $checkStmt->close();

            // Prepared statement to update student record
            $updateSql = "UPDATE students SET student_id = ?, first_name = ?, last_name = ?, email = ?, course = ?, year_level = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("sssssisi", $student_id, $first_name, $last_name, $email, $course, $year_level, $status, $id);

            if ($stmt->execute()) {
                $stmt->close();
                $_SESSION['success'] = "Student record updated successfully.";
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Failed to update record: " . $conn->error;
                $stmt->close();
            }
        }
    }
}

// 2. Handle Displaying Current Information (GET)
$student_id_val = "";
$first_name_val = "";
$last_name_val  = "";
$email_val      = "";
$course_val     = "";
$year_level_val = 1;
$status_val     = "Regular";
$id_val         = 0;

$id_param = intval($_GET['id'] ?? ($_POST['id'] ?? 0));

if ($id_param > 0) {
    $fetchSql = "SELECT * FROM students WHERE id = ?";
    $fetchStmt = $conn->prepare($fetchSql);
    $fetchStmt->bind_param("i", $id_param);
    $fetchStmt->execute();
    $result = $fetchStmt->get_result();

    if ($result && $result->num_rows === 1) {
        $student = $result->fetch_assoc();
        $id_val         = $student['id'];
        $student_id_val = $student['student_id'];
        $first_name_val = $student['first_name'];
        $last_name_val  = $student['last_name'];
        $email_val      = $student['email'];
        $course_val     = $student['course'];
        $year_level_val = $student['year_level'];
        $status_val     = $student['status'];
    } else {
        $_SESSION['error'] = "Student record not found.";
        header("Location: dashboard.php");
        exit();
    }
    $fetchStmt->close();
} else {
    $_SESSION['error'] = "Invalid student record ID.";
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Student Record System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            background: #ffffff;
        }
        .card-header-custom {
            background-color: #0d6efd;
            color: #ffffff;
            border-radius: 12px 12px 0 0 !important;
            padding: 16px 24px;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-mortarboard-fill me-2"></i>Student Record System
        </a>
        <div class="d-flex align-items-center">
            <span class="text-white-50 me-3 small">
                Logged in as: <strong class="text-white"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></strong>
            </span>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <a href="logout.php" class="btn btn-danger btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">

            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Student Record</li>
                </ol>
            </nav>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <div><?= htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-header card-header-custom d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-pencil-square me-2"></i>Edit Student Information
                    </h5>
                    <span class="badge bg-light text-dark">ID: <?= htmlspecialchars($student_id_val); ?></span>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="edit_student.php">
                        <!-- Hidden primary key ID -->
                        <input type="hidden" name="id" value="<?= htmlspecialchars($id_val); ?>">

                        <div class="row g-3">
                            <!-- Student ID -->
                            <div class="col-md-6">
                                <label for="student_id" class="form-label">
                                    Student ID <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                    <input type="text" class="form-control" id="student_id" name="student_id" 
                                           value="<?= htmlspecialchars($student_id_val); ?>" required>
                                </div>
                                <div class="form-text">Must be unique for each student.</div>
                            </div>

                            <!-- Course -->
                            <div class="col-md-6">
                                <label for="course" class="form-label">
                                    Course / Program <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-journal-code"></i></span>
                                    <select class="form-select" id="course" name="course" required>
                                        <option value="" disabled>Select Course</option>
                                        <option value="BSIT" <?= ($course_val === 'BSIT') ? 'selected' : ''; ?>>BSIT - Bachelor of Science in Information Technology</option>
                                        <option value="BSCS" <?= ($course_val === 'BSCS') ? 'selected' : ''; ?>>BSCS - Bachelor of Science in Computer Science</option>
                                        <option value="BSIS" <?= ($course_val === 'BSIS') ? 'selected' : ''; ?>>BSIS - Bachelor of Science in Information Systems</option>
                                        <option value="BSEMC" <?= ($course_val === 'BSEMC') ? 'selected' : ''; ?>>BSEMC - BS in Entertainment and Multimedia Computing</option>
                                        <option value="BSBA" <?= ($course_val === 'BSBA') ? 'selected' : ''; ?>>BSBA - Bachelor of Science in Business Administration</option>
                                        <option value="BSCPE" <?= ($course_val === 'BSCPE') ? 'selected' : ''; ?>>BSCPE - Bachelor of Science in Computer Engineering</option>
                                    </select>
                                </div>
                            </div>

                            <!-- First Name -->
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">
                                    First Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?= htmlspecialchars($first_name_val); ?>" required>
                                </div>
                            </div>

                            <!-- Last Name -->
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">
                                    Last Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?= htmlspecialchars($last_name_val); ?>" required>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-12">
                                <label for="email" class="form-label">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($email_val); ?>" required>
                                </div>
                                <div class="form-text">Must be a valid email format (e.g., student@example.com).</div>
                            </div>

                            <!-- Year Level -->
                            <div class="col-md-6">
                                <label for="year_level" class="form-label">
                                    Year Level <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                    <select class="form-select" id="year_level" name="year_level" required>
                                        <option value="1" <?= ($year_level_val == 1) ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2" <?= ($year_level_val == 2) ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3" <?= ($year_level_val == 3) ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4" <?= ($year_level_val == 4) ? 'selected' : ''; ?>>4th Year</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-6">
                                <label for="status" class="form-label">
                                    Enrollment Status <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-toggle-on"></i></span>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="Regular" <?= ($status_val === 'Regular') ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Irregular" <?= ($status_val === 'Irregular') ? 'selected' : ''; ?>>Irregular</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="dashboard.php" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                <i class="bi bi-check2-circle me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
