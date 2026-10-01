<?php
// add_student.php - Handle Adding a New Student Record
require_once 'auth_check.php';
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = trim($_POST['student_id'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $course     = trim($_POST['course'] ?? '');
    $year_level = intval($_POST['year_level'] ?? 0);
    $status     = trim($_POST['status'] ?? 'Regular');

    // Validation
    if (empty($student_id) || empty($first_name) || empty($last_name) || empty($email) || empty($course) || $year_level <= 0) {
        $_SESSION['error'] = "All required fields must be filled out correctly.";
        header("Location: dashboard.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format. Please provide a valid email address.";
        header("Location: dashboard.php");
        exit();
    }

    // Check Student ID uniqueness using prepared statement
    $checkSql = "SELECT id FROM students WHERE student_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $student_id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $_SESSION['error'] = "Student ID '$student_id' already exists. Please use a unique Student ID.";
        $checkStmt->close();
        header("Location: dashboard.php");
        exit();
    }
    $checkStmt->close();

    // Insert student record using prepared statement
    $insertSql = "INSERT INTO students (student_id, first_name, last_name, email, course, year_level, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("sssssis", $student_id, $first_name, $last_name, $email, $course, $year_level, $status);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Student record added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add student record: " . $conn->error;
    }

    $stmt->close();
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>
