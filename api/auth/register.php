<?php
require_once '../config.php';

$input = json_decode(file_get_contents('php://input'), true);

$required_fields = ['full_name', 'university_email', 'contact', 'address', 'faculty', 'year_of_study', 'password'];
APIValidator::validateRequired($input, $required_fields);
APIValidator::validateEmail($input['university_email']);

// Check if email already exists
$check_email = $conn->prepare("SELECT student_id FROM student WHERE university_email = ?");
$check_email->execute([$input['university_email']]);

if($check_email->rowCount() > 0) {
    APIResponse::error('Email already registered');
}

// Check if contact already exists
$check_contact = $conn->prepare("SELECT student_id FROM student WHERE contact = ?");
$check_contact->execute([$input['contact']]);

if($check_contact->rowCount() > 0) {
    APIResponse::error('Phone number already registered');
}

try {
    $conn->beginTransaction();
    
    // Hash password
    $hashed_password = password_hash($input['password'], PASSWORD_DEFAULT);
    
    // Insert into student table
    $insert_student = $conn->prepare("
        INSERT INTO student 
        (full_name, university_email, contact, address, faculty, year_of_study, password) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $insert_student->execute([
        $input['full_name'],
        $input['university_email'],
        $input['contact'],
        $input['address'],
        $input['faculty'],
        $input['year_of_study'],
        $hashed_password
    ]);
    
    $student_id = $conn->lastInsertId();
    
    // Insert into user_account table
    $insert_account = $conn->prepare("
        INSERT INTO user_account (university_email) 
        VALUES (?)
    ");
    
    $insert_account->execute([$input['university_email']]);
    
    $conn->commit();
    
    APIResponse::success(['user_id' => $student_id], 'Registration successful');
    
} catch (Exception $e) {
    $conn->rollBack();
    APIResponse::error('Registration failed: ' . $e->getMessage(), 500);
}
?>