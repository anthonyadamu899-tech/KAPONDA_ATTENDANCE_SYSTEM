<?php

session_start();

require_once "../config/database.php";


/* =========================
   TEACHER AUTHENTICATION
   ========================= */

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "teacher") {

    header("Location: ../login.php");
    exit;
}


$user_id = (int) $_SESSION["user_id"];


/* =========================
   GET TEACHER
   ========================= */

$sql_teacher = "
    SELECT
        teacher_id,
        teacher_name
    FROM teachers
    WHERE user_id = ?
    AND status = 'active'
    LIMIT 1
";

$stmt_teacher = mysqli_prepare($conn, $sql_teacher);

$teacher_id = 0;
$teacher_name = "Teacher";

if ($stmt_teacher) {

    mysqli_stmt_bind_param(
        $stmt_teacher,
        "i",
        $user_id
    );

    mysqli_stmt_execute($stmt_teacher);

    $teacher_result =
        mysqli_stmt_get_result($stmt_teacher);

    if ($teacher_result) {

        $teacher_data =
            mysqli_fetch_assoc($teacher_result);

        if ($teacher_data) {

            $teacher_id =
                (int) $teacher_data["teacher_id"];

            $teacher_name =
                $teacher_data["teacher_name"];
        }
    }

    mysqli_stmt_close($stmt_teacher);
}


/* =========================
   GET ASSIGNED CLASSES
   ========================= */

$classes = array();

if ($teacher_id > 0) {

    $sql_classes = "
        SELECT
            tc.teacher_class_id,
            tc.class_id,
            tc.assignment_role,
            c.class_name
        FROM teacher_classes tc
        JOIN classes c
            ON tc.class_id = c.class_id
        WHERE tc.teacher_id = ?
        AND tc.status = 'active'
        AND c.status = 'active'
        ORDER BY c.class_name ASC
    ";

    $stmt_classes =
        mysqli_prepare($conn, $sql_classes);

    if ($stmt_classes) {

        mysqli_stmt_bind_param(
            $stmt_classes,
            "i",
            $teacher_id
        );

        mysqli_stmt_execute($stmt_classes);

        $classes_result =
            mysqli_stmt_get_result($stmt_classes);

        if ($classes_result) {

            while (
                $class = mysqli_fetch_assoc(
                    $classes_result
                )
            ) {

                $classes[] = $class;
            }
        }

        mysqli_stmt_close($stmt_classes);
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Teacher Dashboard - KAPONDA
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>


<!-- =========================
     HEADER
     ========================= -->

<div class="header">

    <div>

        <h1>
            KAPONDA EDUCATION ACADEMY
        </h1>

        <p>
            Attendance Management System
        </p>

    </div>

    <div class="header-user">

        <span class="user-label">
            <?php echo htmlspecialchars($teacher_name); ?>
        </span>

    </div>

</div>


<!-- =========================
     LAYOUT
     ========================= -->

<div class="layout">


    <!-- SIDEBAR -->

    <?php include "menu.php"; ?>


    <!-- CONTENT -->

    <main class="content">


        <h2 class="page-title">
            Teacher Dashboard
        </h2>


        <div class="panel">

            <h2>
                Welcome,
                <?php echo htmlspecialchars($teacher_name); ?>
            </h2>

            <p>
                Below are the classes assigned to you.
            </p>

        </div>


        <!-- CLASSES -->

        <?php if (count($classes) === 0): ?>

            <div class="panel empty-state">

                <h3>
                    No Class Assigned
                </h3>

                <p>
                    You currently have no active
                    class assignment.
                </p>

            </div>

        <?php else: ?>


            <div class="cards">


                <?php foreach ($classes as $class): ?>


                    <div class="card">


                        <h2>

                            <?php
                            echo htmlspecialchars(
                                $class["class_name"]
                            );
                            ?>

                        </h2>


                        <p>

                            <?php
                            if (
                                $class["assignment_role"]
                                === "class_teacher"
                            ) {
                            ?>

                                <span class="badge badge-active">
                                    Class Teacher
                                </span>

                            <?php
                            } else {
                            ?>

                                <span class="badge">
                                    Subject Teacher
                                </span>

                            <?php
                            }
                            ?>

                        </p>


                        <div class="actions">


                            <!-- BOTH ROLES -->

                            <a
                                href="view_class.php?class_id=<?php echo (int) $class["class_id"]; ?>"
                                class="btn btn-primary"
                            >
                                👁 View Students & Attendance
                            </a>


                            <?php
                            if (
                                $class["assignment_role"]
                                === "class_teacher"
                            ) {
                            ?>


                                <a
                                    href="add_student.php?class_id=<?php echo (int) $class["class_id"]; ?>"
                                    class="btn btn-success"
                                >
                                    ➕ Add Student
                                </a>


                                <a
                                    href="attendance.php?class_id=<?php echo (int) $class["class_id"]; ?>"
                                    class="btn btn-warning"
                                >
                                    📝 Mark Attendance
                                </a>


                            <?php
                            }
                            ?>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </main>

</div>

</body>

</html>