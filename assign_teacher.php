<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$message = "";
$message_type = "";

/*
=====================================================
GET ACTIVE TEACHERS
=====================================================
*/

$teachers = mysqli_query(
    $conn,
    "SELECT teacher_id, teacher_name
     FROM teachers
     WHERE status = 'active'
     ORDER BY teacher_name ASC"
);

/*
=====================================================
GET ACTIVE CLASSES
=====================================================
*/

$classes = mysqli_query(
    $conn,
    "SELECT class_id, class_name
     FROM classes
     WHERE status = 'active'
     ORDER BY class_id ASC"
);

/*
=====================================================
ASSIGN TEACHER
=====================================================
*/

if (isset($_POST["assign_teacher"])) {

    $teacher_id = isset($_POST["teacher_id"])
        ? (int) $_POST["teacher_id"]
        : 0;

    $class_id = isset($_POST["class_id"])
        ? (int) $_POST["class_id"]
        : 0;

    $assignment_role = isset($_POST["assignment_role"])
        ? $_POST["assignment_role"]
        : "";

    $allowed_roles = array(
        "class_teacher",
        "subject_teacher"
    );

    /*
    =====================================================
    BASIC VALIDATION
    =====================================================
    */

    if ($teacher_id <= 0 || $class_id <= 0) {

        $message = "Please select teacher and class.";
        $message_type = "danger";

    } elseif (!in_array($assignment_role, $allowed_roles, true)) {

        $message = "Invalid assignment role.";
        $message_type = "danger";

    } else {

        /*
        =====================================================
        CHECK WHETHER TEACHER IS ALREADY ASSIGNED
        TO THIS CLASS
        =====================================================
        */

        $check_sql = "
            SELECT teacher_class_id
            FROM teacher_classes
            WHERE teacher_id = ?
            AND class_id = ?
            AND status = 'active'
        ";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        mysqli_stmt_bind_param(
            $check_stmt,
            "ii",
            $teacher_id,
            $class_id
        );

        mysqli_stmt_execute($check_stmt);

        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {

            $message = "Teacher tayari amepewa class hii.";
            $message_type = "warning";

            mysqli_stmt_close($check_stmt);

        } else {

            mysqli_stmt_close($check_stmt);

            /*
            =================================================
            IF CLASS TEACHER
            CHECK WHETHER CLASS ALREADY HAS ONE
            =================================================
            */

            if ($assignment_role === "class_teacher") {

                $class_teacher_sql = "
                    SELECT teacher_class_id
                    FROM teacher_classes
                    WHERE class_id = ?
                    AND assignment_role = 'class_teacher'
                    AND status = 'active'
                ";

                $class_teacher_stmt = mysqli_prepare(
                    $conn,
                    $class_teacher_sql
                );

                mysqli_stmt_bind_param(
                    $class_teacher_stmt,
                    "i",
                    $class_id
                );

                mysqli_stmt_execute($class_teacher_stmt);

                $class_teacher_result = mysqli_stmt_get_result(
                    $class_teacher_stmt
                );

                if (mysqli_num_rows($class_teacher_result) > 0) {

                    $message = "Class hii tayari ina Class Teacher.";
                    $message_type = "warning";

                    mysqli_stmt_close($class_teacher_stmt);

                } else {

                    mysqli_stmt_close($class_teacher_stmt);

                    /*
                    =============================================
                    INSERT CLASS TEACHER
                    =============================================
                    */

                    $insert_sql = "
                        INSERT INTO teacher_classes
                        (
                            teacher_id,
                            class_id,
                            assignment_role,
                            assigned_date,
                            status
                        )
                        VALUES
                        (?, ?, ?, CURDATE(), 'active')
                    ";

                    $insert_stmt = mysqli_prepare(
                        $conn,
                        $insert_sql
                    );

                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "iis",
                        $teacher_id,
                        $class_id,
                        $assignment_role
                    );

                    if (mysqli_stmt_execute($insert_stmt)) {

                        $message = "Class Teacher assigned successfully.";
                        $message_type = "success";

                    } else {

                        $message = "Assignment failed: " .
                            mysqli_error($conn);

                        $message_type = "danger";
                    }

                    mysqli_stmt_close($insert_stmt);
                }

            } else {

                /*
                =================================================
                SUBJECT TEACHER
                =================================================

                Subject Teachers wengi wanaruhusiwa
                kwenye class moja.
                =================================================
                */

                $insert_sql = "
                    INSERT INTO teacher_classes
                    (
                        teacher_id,
                        class_id,
                        assignment_role,
                        assigned_date,
                        status
                    )
                    VALUES
                    (?, ?, ?, CURDATE(), 'active')
                ";

                $insert_stmt = mysqli_prepare(
                    $conn,
                    $insert_sql
                );

                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "iis",
                    $teacher_id,
                    $class_id,
                    $assignment_role
                );

                if (mysqli_stmt_execute($insert_stmt)) {

                    $message = "Subject Teacher assigned successfully.";
                    $message_type = "success";

                } else {

                    $message = "Assignment failed: " .
                        mysqli_error($conn);

                    $message_type = "danger";
                }

                mysqli_stmt_close($insert_stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Assign Teacher - KAPONDA</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<!-- HEADER -->

<div class="header">

    <div>
        <h1>KAPONDA EDUCATION ACADEMY</h1>

        <p>
            Admin Panel - Assign Teacher
        </p>
    </div>

</div>


<!-- LAYOUT -->

<div class="layout">

    <!-- SIDEBAR -->

    <?php include "menu.php"; ?>


    <!-- CONTENT -->

    <div class="content">

        <h2 class="page-title">
            Assign Teacher to Class
        </h2>


        <?php if ($message !== ""): ?>

            <div class="alert alert-<?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <!-- ASSIGNMENT FORM -->

        <div class="panel">

            <h3>
                Teacher Assignment
            </h3>

            <p>
                Assign a teacher to a class as either
                <strong>Class Teacher</strong> or
                <strong>Subject Teacher</strong>.
            </p>


            <form method="POST">

                <div class="form-grid">

                    <!-- TEACHER -->

                    <div class="form-group">

                        <label for="teacher_id">
                            Teacher
                        </label>

                        <select
                            name="teacher_id"
                            id="teacher_id"
                            required
                        >

                            <option value="">
                                Select Teacher
                            </option>

                            <?php while (
                                $teacher = mysqli_fetch_assoc($teachers)
                            ): ?>

                                <option
                                    value="<?php echo (int) $teacher["teacher_id"]; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $teacher["teacher_name"]
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <!-- CLASS -->

                    <div class="form-group">

                        <label for="class_id">
                            Class
                        </label>

                        <select
                            name="class_id"
                            id="class_id"
                            required
                        >

                            <option value="">
                                Select Class
                            </option>

                            <?php while (
                                $class = mysqli_fetch_assoc($classes)
                            ): ?>

                                <option
                                    value="<?php echo (int) $class["class_id"]; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $class["class_name"]
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <!-- ROLE -->

                    <div class="form-group">

                        <label for="assignment_role">
                            Assignment Role
                        </label>

                        <select
                            name="assignment_role"
                            id="assignment_role"
                            required
                        >

                            <option value="">
                                Select Role
                            </option>

                            <option value="class_teacher">
                                Class Teacher
                            </option>

                            <option value="subject_teacher">
                                Subject Teacher
                            </option>

                        </select>

                    </div>

                </div>


                <button
                    type="submit"
                    name="assign_teacher"
                    class="btn btn-primary"
                >
                    Assign Teacher
                </button>


                <a
                    href="manage_teachers.php"
                    class="btn btn-secondary"
                >
                    Back to Teachers
                </a>

            </form>

        </div>


        <!-- RULES -->

        <div class="panel">

            <h3>
                Assignment Rules
            </h3>

            <ul>

                <li>
                    One class can have only
                    <strong>one active Class Teacher</strong>.
                </li>

                <li>
                    A class can have
                    <strong>multiple Subject Teachers</strong>.
                </li>

                <li>
                    One teacher can be assigned to
                    <strong>multiple classes</strong>.
                </li>

                <li>
                    The same teacher cannot be assigned
                    twice to the same class while the assignment
                    is active.
                </li>

                <li>
                    Assignment can later be changed from
                    <strong>Class Teacher</strong> to
                    <strong>Subject Teacher</strong>, or vice versa,
                    through Teacher Management.
                </li>

            </ul>

        </div>

    </div>

</div>

</body>

</html>