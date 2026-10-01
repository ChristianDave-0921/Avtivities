<?php
// delete_student.php - Secure Student Record Deletion
require_once 'auth_check.php';
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" || isset($_GET['id'])) {
    $id = intval($_POST['id'] ?? ($_GET['id'] ?? 0));

    if ($id > 0) {
        // Prepare SQL using student primary key
        $deleteSql = "DELETE FROM students WHERE id = ?";
        if ($stmt = $conn->prepare($deleteSql)) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['success'] = "Student record deleted successfully.";
                } else {
                    $_SESSION['error'] = "Student record not found or already deleted.";
                }
            } else {
                $_SESSION['error'] = "Failed to delete student record: " . $conn->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Database query preparation failed: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Invalid student record ID.";
    }
}

header("Location: dashboard.php");
exit();
?>
