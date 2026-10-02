<?php
session_start();
require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| 1. Teacher login
|--------------------------------------------------------------------------
*/
if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "teacher"
) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. Class ID
|--------------------------------------------------------------------------
*/
if (
    !isset($_GET["class_id"]) ||
    !is_numeric($_GET["class_id"])
) {
    die("Class haijachaguliwa.");
}

$class_id = (int) $_GET["class_id"];
$user_id = (int) $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| 3. Tafuta teacher
|--------------------------------------------------------------------------
*/
$sql_teacher = "
    SELECT
        teacher_id,
        teacher_name
    FROM teachers
    WHERE user_id = ?
    AND status = 'active'
";

$stmt_teacher = mysqli_prepare($conn, $sql_teacher);

mysqli_stmt_bind_param(
    $stmt_teacher,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt_teacher);

$result_teacher = mysqli_stmt_get_result($stmt_teacher);

$teacher = mysqli_fetch_assoc($result_teacher);

if (!$teacher) {
    die("Teacher account does not exist.");
}

$teacher_id = $teacher["teacher_id"];
$teacher_name = $teacher["teacher_name"];

/*
|--------------------------------------------------------------------------
| 4. Check teacher assignment
| Both Class Teacher and Subject Teacher can view
|--------------------------------------------------------------------------
*/
$sql_access = "
    SELECT
        tc.assignment_role,
        c.class_name
    FROM teacher_classes tc
    JOIN classes c
        ON tc.class_id = c.class_id
    WHERE tc.teacher_id = ?
    AND tc.class_id = ?
    AND tc.status = 'active'
    AND c.status = 'active'
    AND tc.assignment_role IN (
        'class_teacher',
        'subject_teacher'
    )
";

$stmt_access = mysqli_prepare($conn, $sql_access);

mysqli_stmt_bind_param(
    $stmt_access,
    "ii",
    $teacher_id,
    $class_id
);

mysqli_stmt_execute($stmt_access);

$result_access = mysqli_stmt_get_result($stmt_access);

$access = mysqli_fetch_assoc($result_access);

if (!$access) {
    die("Huna ruhusa ya kuona class hii.");
}

$class_name = $access["class_name"];
$assignment_role = $access["assignment_role"];

/*
|--------------------------------------------------------------------------
| 5. Attendance date
|--------------------------------------------------------------------------
*/
$attendance_date = date("Y-m-d");

if (
    isset($_GET["date"]) &&
    $_GET["date"] !== ""
) {
    $attendance_date = $_GET["date"];
}

/*
|--------------------------------------------------------------------------
| 6. Get students
|--------------------------------------------------------------------------
*/
$sql_students = "
    SELECT
        s.student_id,
        s.registration_no,
        s.first_name,
        s.middle_name,
        s.last_name,
        e.enrollment_id
    FROM student_class_enrollment e
    JOIN students s
        ON e.student_id = s.student_id
    WHERE e.class_id = ?
    AND e.status = 'active'
    AND s.status = 'active'
    ORDER BY s.first_name, s.last_name
";

$stmt_students = mysqli_prepare(
    $conn,
    $sql_students
);

mysqli_stmt_bind_param(
    $stmt_students,
    "i",
    $class_id
);

mysqli_stmt_execute($stmt_students);

$students = mysqli_stmt_get_result($stmt_students);

/*
|--------------------------------------------------------------------------
| 7. Get attendance
|--------------------------------------------------------------------------
| marked_by -> users.user_id
| users.user_id -> teachers.user_id
|--------------------------------------------------------------------------
*/
$sql_attendance = "
    SELECT
        a.attendance_date,
        a.status,
        a.marked_by,

        s.registration_no,
        s.first_name,
        s.middle_name,
        s.last_name,

        u.username,
        u.role,

        t.teacher_name

    FROM attendance a

    JOIN student_class_enrollment e
        ON a.enrollment_id = e.enrollment_id

    JOIN students s
        ON e.student_id = s.student_id

    LEFT JOIN users u
        ON a.marked_by = u.user_id

    LEFT JOIN teachers t
        ON u.user_id = t.user_id

    WHERE e.class_id = ?
    AND a.attendance_date = ?

    ORDER BY s.first_name ASC
";

$stmt_attendance = mysqli_prepare(
    $conn,
    $sql_attendance
);

mysqli_stmt_bind_param(
    $stmt_attendance,
    "is",
    $class_id,
    $attendance_date
);

mysqli_stmt_execute($stmt_attendance);

$attendance = mysqli_stmt_get_result($stmt_attendance);

/*
|--------------------------------------------------------------------------
| 8. Attendance counters
|--------------------------------------------------------------------------
*/
$present = 0;
$absent = 0;
$permission = 0;

$attendance_rows = array();

while ($row = mysqli_fetch_assoc($attendance)) {

    $attendance_rows[] = $row;

    if ($row["status"] === "present") {
        $present++;
    }

    if ($row["status"] === "absent") {
        $absent++;
    }

    if ($row["status"] === "permission") {
        $permission++;
    }
}

$total_attendance = $present + $absent + $permission;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo htmlspecialchars($class_name); ?>
        - Class View
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .class-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-box {
            background: #ffffff;
            padding: 18px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
        }

        .info-box small {
            display: block;
            color: #777;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .info-box strong {
            color: #172b4d;
            font-size: 16px;
        }

        .role-class-teacher {
            background: #d1e7dd;
            color: #0f5132;
        }

        .role-subject-teacher {
            background: #cff4fc;
            color: #055160;
        }

        .attendance-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
        }

        .summary-card h3 {
            margin: 0 0 8px;
            font-size: 14px;
            color: #666;
        }

        .summary-number {
            font-size: 28px;
            font-weight: bold;
        }

        .summary-total .summary-number {
            color: #1f3c88;
        }

        .summary-present .summary-number {
            color: #198754;
        }

        .summary-absent .summary-number {
            color: #dc3545;
        }

        .summary-permission .summary-number {
            color: #d39e00;
        }

        .date-form {
            display: flex;
            align-items: end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .date-form .form-group {
            margin-bottom: 0;
            min-width: 220px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-present {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-absent {
            background: #f8d7da;
            color: #842029;
        }

        .status-permission {
            background: #fff3cd;
            color: #664d03;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 900px) {

            .class-info-grid {
                grid-template-columns: 1fr;
            }

            .attendance-summary {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .attendance-summary {
                grid-template-columns: 1fr;
            }

            .date-form {
                display: block;
            }

            .date-form .form-group {
                margin-bottom: 12px;
                width: 100%;
            }

            .date-form button {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<!-- =========================================================
     HEADER
========================================================= -->

<div class="header">

    <div>
        <h1>KAPONDA EDUCATION ACADEMY</h1>

        <p>
            Teacher Portal
        </p>
    </div>

    <div>
        <strong>
            <?php echo htmlspecialchars($teacher_name); ?>
        </strong>
    </div>

</div>


<!-- =========================================================
     MAIN LAYOUT
========================================================= -->

<div class="layout">

    <!-- SIDEBAR -->
    <?php include "menu.php"; ?>


    <!-- CONTENT -->
    <div class="content">

        <div style="margin-bottom: 15px;">

            <a
                href="dashboard.php"
                class="btn btn-secondary"
            >
                ← Back to Dashboard
            </a>

        </div>


        <!-- PAGE TITLE -->

        <h1 class="page-title">

            <?php echo htmlspecialchars($class_name); ?>

            - Class View

        </h1>


        <!-- =================================================
             CLASS INFORMATION
        ================================================== -->

        <div class="class-info-grid">

            <div class="info-box">

                <small>Class</small>

                <strong>
                    <?php echo htmlspecialchars($class_name); ?>
                </strong>

            </div>


            <div class="info-box">

                <small>Teacher</small>

                <strong>
                    <?php echo htmlspecialchars($teacher_name); ?>
                </strong>

            </div>


            <div class="info-box">

                <small>Your Role</small>

                <?php if ($assignment_role === "class_teacher"): ?>

                    <span class="badge role-class-teacher">
                        Class Teacher
                    </span>

                <?php else: ?>

                    <span class="badge role-subject-teacher">
                        Subject Teacher
                    </span>

                <?php endif; ?>

            </div>

        </div>


        <!-- =================================================
             STUDENTS
        ================================================== -->

        <div class="panel">

            <h2>
                Students
            </h2>

            <p style="color:#777;">
                Students currently enrolled in
                <strong>
                    <?php echo htmlspecialchars($class_name); ?>
                </strong>.
            </p>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Registration No</th>

                            <th>Student Name</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php

                    $number = 1;

                    if (mysqli_num_rows($students) > 0):

                    ?>

                        <?php while (
                            $student = mysqli_fetch_assoc($students)
                        ): ?>

                            <tr>

                                <td>
                                    <?php echo $number++; ?>
                                </td>

                                <td>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $student["registration_no"]
                                        );
                                        ?>
                                    </strong>

                                </td>

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $student["first_name"] . " " .
                                        $student["middle_name"] . " " .
                                        $student["last_name"]
                                    );

                                    ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="3"
                                class="empty-state"
                            >

                                Hakuna wanafunzi katika class hii.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- =================================================
             ATTENDANCE
        ================================================== -->

        <div class="panel">

            <h2>
                Attendance
            </h2>

            <p style="color:#777;">
                View attendance records for the selected date.
            </p>


            <!-- DATE FILTER -->

            <form
                method="GET"
                class="date-form"
            >

                <input
                    type="hidden"
                    name="class_id"
                    value="<?php echo $class_id; ?>"
                >


                <div class="form-group">

                    <label for="date">
                        Select Date
                    </label>

                    <input
                        type="date"
                        id="date"
                        name="date"
                        value="<?php echo htmlspecialchars($attendance_date); ?>"
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    View Attendance
                </button>

            </form>

        </div>


        <!-- =================================================
             ATTENDANCE SUMMARY
        ================================================== -->

        <div class="attendance-summary">


            <div class="summary-card summary-total">

                <h3>
                    Total Records
                </h3>

                <div class="summary-number">
                    <?php echo $total_attendance; ?>
                </div>

            </div>


            <div class="summary-card summary-present">

                <h3>
                    Present
                </h3>

                <div class="summary-number">
                    <?php echo $present; ?>
                </div>

            </div>


            <div class="summary-card summary-absent">

                <h3>
                    Absent
                </h3>

                <div class="summary-number">
                    <?php echo $absent; ?>
                </div>

            </div>


            <div class="summary-card summary-permission">

                <h3>
                    Permission
                </h3>

                <div class="summary-number">
                    <?php echo $permission; ?>
                </div>

            </div>

        </div>


        <!-- =================================================
             ATTENDANCE TABLE
        ================================================== -->

        <div class="panel">

            <div style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:15px;
                flex-wrap:wrap;
            ">

                <div>

                    <h2 style="margin-bottom:5px;">
                        Attendance History
                    </h2>

                    <p style="
                        margin:0;
                        color:#777;
                    ">

                        Date:
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $attendance_date
                            );
                            ?>
                        </strong>

                    </p>

                </div>


                <?php if (
                    $assignment_role === "class_teacher"
                ): ?>

                    <a
                        href="attendance.php?class_id=<?php echo $class_id; ?>&date=<?php echo urlencode($attendance_date); ?>"
                        class="btn btn-primary"
                    >
                        📝 Mark / Update Attendance
                    </a>

                <?php endif; ?>

            </div>


            <br>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>Date</th>

                            <th>Registration No</th>

                            <th>Student Name</th>

                            <th>Status</th>

                            <th>Marked By</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (
                        count($attendance_rows) > 0
                    ): ?>

                        <?php foreach (
                            $attendance_rows as $row
                        ): ?>

                            <tr>

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $row["attendance_date"]
                                    );

                                    ?>

                                </td>


                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $row["registration_no"]
                                        );

                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $row["first_name"] . " " .
                                        $row["middle_name"] . " " .
                                        $row["last_name"]
                                    );

                                    ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if (
                                        $row["status"] === "present"
                                    ): ?>

                                        <span class="status-badge status-present">
                                            Present
                                        </span>

                                    <?php elseif (
                                        $row["status"] === "absent"
                                    ): ?>

                                        <span class="status-badge status-absent">
                                            Absent
                                        </span>

                                    <?php elseif (
                                        $row["status"] === "permission"
                                    ): ?>

                                        <span class="status-badge status-permission">
                                            Permission
                                        </span>

                                    <?php else: ?>

                                        <span class="badge">
                                            <?php
                                            echo htmlspecialchars(
                                                $row["status"]
                                            );
                                            ?>
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- MARKED BY -->

                                <td>

                                    <?php

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Display person who marked attendance
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        $row["teacher_name"] !== null &&
                                        $row["teacher_name"] !== ""
                                    ) {

                                        echo htmlspecialchars(
                                            $row["teacher_name"]
                                        );

                                    } elseif (
                                        $row["role"] === "admin"
                                    ) {

                                        echo "Admin";

                                    } elseif (
                                        $row["username"] !== null &&
                                        $row["username"] !== ""
                                    ) {

                                        echo htmlspecialchars(
                                            $row["username"]
                                        );

                                    } else {

                                        echo "Unknown";

                                    }

                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="5"
                                class="empty-state"
                            >

                                Hakuna attendance iliyorekodiwa
                                kwa tarehe hii.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- =================================================
             CLASS TEACHER ACTIONS
        ================================================== -->

        <?php if (
            $assignment_role === "class_teacher"
        ): ?>

            <div class="panel">

                <h2>
                    Class Teacher Actions
                </h2>

                <p style="color:#777;">
                    Kama Class Teacher unaweza kusimamia
                    attendance na kuongeza mwanafunzi.
                </p>


                <div class="action-buttons">

                    <a
                        href="attendance.php?class_id=<?php echo $class_id; ?>"
                        class="btn btn-primary"
                    >
                        📝 Mark Attendance
                    </a>


                    <a
                        href="add_student.php?class_id=<?php echo $class_id; ?>"
                        class="btn btn-success"
                    >
                        ➕ Add Student
                    </a>

                </div>

            </div>

        <?php else: ?>

            <!-- SUBJECT TEACHER -->

            <div class="panel">

                <h2>
                    Subject Teacher
                </h2>

                <div class="alert alert-info">

                    Una ruhusa ya kuona wanafunzi na
                    attendance ya class hii.

                    <br><br>

                    <strong>
                        Huna ruhusa ya ku-mark au ku-update attendance.
                    </strong>

                </div>

            </div>

        <?php endif; ?>


    </div>

</div>

</body>
</html>