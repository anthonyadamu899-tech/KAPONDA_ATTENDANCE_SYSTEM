<?php

session_start();
require_once "../config/database.php";

/* ==============================
   ADMIN CHECK
================================ */

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

/* ==============================
   GET STUDENT ID
================================ */

if (!isset($_GET["student_id"]) || !is_numeric($_GET["student_id"])) {
    die("Student haijachaguliwa.");
}

$student_id = (int) $_GET["student_id"];

$message = "";
$message_type = "";

/* ==============================
   GET STUDENT
================================ */

$sql_student = "
    SELECT
        student_id,
        registration_no,
        first_name,
        middle_name,
        last_name,
        gender,
        date_of_birth,
        status
    FROM students
    WHERE student_id = ?
";

$stmt_student = mysqli_prepare($conn, $sql_student);

if (!$stmt_student) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt_student,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt_student);

$result_student = mysqli_stmt_get_result($stmt_student);

$student = mysqli_fetch_assoc($result_student);

mysqli_stmt_close($stmt_student);

if (!$student) {
    die("Student hajapatikana.");
}

/* ==============================
   UPDATE STUDENT
================================ */

if (isset($_POST["update_student"])) {

    $registration_no = trim($_POST["registration_no"]);
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $gender = trim($_POST["gender"]);
    $date_of_birth = trim($_POST["date_of_birth"]);
    $status = trim($_POST["status"]);

    /* ==============================
       VALIDATION
    ================================= */

    if (
        $registration_no === "" ||
        $first_name === "" ||
        $last_name === "" ||
        $gender === ""
    ) {

        $message = "Tafadhali jaza taarifa zote muhimu.";
        $message_type = "error";

    } elseif (
        $gender !== "Male" &&
        $gender !== "Female"
    ) {

        $message = "Gender si sahihi.";
        $message_type = "error";

    } elseif (
        $status !== "active" &&
        $status !== "inactive"
    ) {

        $message = "Student status si sahihi.";
        $message_type = "error";

    } else {

        /* ==============================
           CHECK DUPLICATE REGISTRATION
        ================================= */

        $check_sql = "
            SELECT student_id
            FROM students
            WHERE registration_no = ?
            AND student_id != ?
        ";

        $check_stmt = mysqli_prepare(
            $conn,
            $check_sql
        );

        if (!$check_stmt) {

            $message = "Database error: " . mysqli_error($conn);
            $message_type = "error";

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "si",
                $registration_no,
                $student_id
            );

            mysqli_stmt_execute($check_stmt);

            $check_result = mysqli_stmt_get_result(
                $check_stmt
            );

            if (mysqli_num_rows($check_result) > 0) {

                $message = "Registration number hiyo tayari inatumika.";
                $message_type = "error";

            } else {

                /* ==============================
                   UPDATE STUDENT
                ================================= */

                $update_sql = "
                    UPDATE students
                    SET
                        registration_no = ?,
                        first_name = ?,
                        middle_name = ?,
                        last_name = ?,
                        gender = ?,
                        date_of_birth = ?,
                        status = ?
                    WHERE student_id = ?
                ";

                $update_stmt = mysqli_prepare(
                    $conn,
                    $update_sql
                );

                if (!$update_stmt) {

                    $message = "Database error: " . mysqli_error($conn);
                    $message_type = "error";

                } else {

                    mysqli_stmt_bind_param(
                        $update_stmt,
                        "sssssssi",
                        $registration_no,
                        $first_name,
                        $middle_name,
                        $last_name,
                        $gender,
                        $date_of_birth,
                        $status,
                        $student_id
                    );

                    if (mysqli_stmt_execute($update_stmt)) {

                        $message = "Student information updated successfully.";
                        $message_type = "success";

                        /* Update displayed values */

                        $student["registration_no"] = $registration_no;
                        $student["first_name"] = $first_name;
                        $student["middle_name"] = $middle_name;
                        $student["last_name"] = $last_name;
                        $student["gender"] = $gender;
                        $student["date_of_birth"] = $date_of_birth;
                        $student["status"] = $status;

                    } else {

                        $message = "Update failed: " . mysqli_error($conn);
                        $message_type = "error";
                    }

                    mysqli_stmt_close($update_stmt);
                }
            }

            mysqli_stmt_close($check_stmt);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Student - KAPONDA</title>

<link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<!-- ==============================
     HEADER
================================ -->

<div class="header">

    <div>
        <h1>KAPONDA EDUCATION ACADEMY</h1>
        <p>Admin Panel - Edit Student</p>
    </div>

    <div>
        <a href="../logout.php" class="btn btn-danger">
            Logout
        </a>
    </div>

</div>


<!-- ==============================
     LAYOUT
================================ -->

<div class="layout">

    <!-- SIDEBAR -->

    <?php include "menu.php"; ?>


    <!-- CONTENT -->

    <div class="content">

        <h2 class="page-title">
            Edit Student
        </h2>


        <!-- MESSAGE -->

        <?php if ($message !== ""): ?>

            <div class="alert
                <?php
                if ($message_type === "success") {
                    echo "alert-success";
                } else {
                    echo "alert-danger";
                }
                ?>
            ">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <!-- STUDENT INFORMATION -->

        <div class="panel">

            <h3>
                Student Information
            </h3>

            <form method="POST">

                <div class="form-grid">

                    <!-- REGISTRATION NUMBER -->

                    <div class="form-group">

                        <label>
                            Registration Number
                        </label>

                        <input
                            type="text"
                            name="registration_no"
                            value="<?php echo htmlspecialchars($student["registration_no"]); ?>"
                            required
                        >

                    </div>


                    <!-- FIRST NAME -->

                    <div class="form-group">

                        <label>
                            First Name
                        </label>

                        <input
                            type="text"
                            name="first_name"
                            value="<?php echo htmlspecialchars($student["first_name"]); ?>"
                            required
                        >

                    </div>


                    <!-- MIDDLE NAME -->

                    <div class="form-group">

                        <label>
                            Middle Name
                        </label>

                        <input
                            type="text"
                            name="middle_name"
                            value="<?php echo htmlspecialchars($student["middle_name"]); ?>"
                        >

                    </div>


                    <!-- LAST NAME -->

                    <div class="form-group">

                        <label>
                            Last Name
                        </label>

                        <input
                            type="text"
                            name="last_name"
                            value="<?php echo htmlspecialchars($student["last_name"]); ?>"
                            required
                        >

                    </div>


                    <!-- GENDER -->

                    <div class="form-group">

                        <label>
                            Gender
                        </label>

                        <select
                            name="gender"
                            required
                        >

                            <option value="">
                                Select Gender
                            </option>

                            <option
                                value="Male"
                                <?php
                                if ($student["gender"] === "Male") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                <?php
                                if ($student["gender"] === "Female") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Female
                            </option>

                        </select>

                    </div>


                    <!-- DATE OF BIRTH -->

                    <div class="form-group">

                        <label>
                            Date of Birth
                        </label>

                        <input
                            type="date"
                            name="date_of_birth"
                            value="<?php echo htmlspecialchars($student["date_of_birth"]); ?>"
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="form-group">

                        <label>
                            Student Status
                        </label>

                        <select
                            name="status"
                            required
                        >

                            <option
                                value="active"
                                <?php
                                if ($student["status"] === "active") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Active
                            </option>

                            <option
                                value="inactive"
                                <?php
                                if ($student["status"] === "inactive") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="actions">

                    <button
                        type="submit"
                        name="update_student"
                        class="btn btn-primary"
                    >
                        💾 Update Student
                    </button>

                    <a
                        href="manage_students.php"
                        class="btn btn-secondary"
                    >
                        ← Back to Students
                    </a>

                </div>

            </form>

        </div>


        <!-- INFORMATION -->

        <div class="panel">

            <h3>
                Important Information
            </h3>

            <p>
                Editing student information does not delete
                the student's attendance records.
            </p>

            <p>
                Changing the student status to
                <strong>Inactive</strong>
                will make the student inactive, but
                previous records remain in the database.
            </p>

            <p>
                Registration number must be unique.
            </p>

        </div>

    </div>

</div>

</body>

</html>