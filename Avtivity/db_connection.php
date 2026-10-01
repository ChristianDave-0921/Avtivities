<?php
// db_connection.php - Database Connection Configuration

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "student_db";

// 1. Initial connection to MySQL server
$conn = new mysqli($servername, $username, $password);

// Check server connection
if ($conn->connect_error) {
    die("Database Server Connection Failed: " . $conn->connect_error);
}

// 2. Ensure database exists
$createDbSql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($createDbSql)) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// 3. Ensure `users` table exists
$createUsersTable = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($createUsersTable);

// 4. Ensure `students` table exists
$createStudentsTable = "CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(50) NOT NULL UNIQUE,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `course` VARCHAR(100) NOT NULL,
    `year_level` INT NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Regular',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($createStudentsTable);

// 5. Seed default admin user if table is empty
$checkAdmin = $conn->query("SELECT id FROM `users` WHERE `username` = 'admin'");
if ($checkAdmin && $checkAdmin->num_rows === 0) {
    $defaultPass = password_hash("password123", PASSWORD_DEFAULT);
    $adminStmt = $conn->prepare("INSERT INTO `users` (`username`, `password`, `full_name`, `email`) VALUES (?, ?, ?, ?)");
    $adminUser = "admin";
    $adminName = "Administrator";
    $adminEmail = "admin@studentrecords.edu";
    $adminStmt->bind_param("ssss", $adminUser, $defaultPass, $adminName, $adminEmail);
    $adminStmt->execute();
    $adminStmt->close();
}

// 6. Seed sample students if students table is empty
$checkStudents = $conn->query("SELECT id FROM `students`");
if ($checkStudents && $checkStudents->num_rows === 0) {
    $seedSql = "INSERT INTO `students` (`student_id`, `first_name`, `last_name`, `email`, `course`, `year_level`, `status`) VALUES
        ('2026001', 'Juan', 'Dela Cruz', 'juan.delacruz@example.com', 'BSIT', 3, 'Regular'),
        ('2026002', 'Maria', 'Santos', 'maria.santos@example.com', 'BSCS', 2, 'Regular'),
        ('2026003', 'Mark', 'Reyes', 'mark.reyes@example.com', 'BSIT', 4, 'Irregular'),
        ('2026004', 'Angela', 'Aquino', 'angela.aquino@example.com', 'BSIS', 1, 'Regular'),
        ('2026005', 'John Paul', 'Garcia', 'jp.garcia@example.com', 'BSEMC', 2, 'Regular');";
    $conn->query($seedSql);
}
?>
