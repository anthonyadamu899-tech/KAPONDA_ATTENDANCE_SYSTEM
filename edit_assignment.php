<?php

session_start();
require_once "../config/database.php";

/* =========================
   ADMIN SECURITY
========================= */

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$message = "";
$error = "";

$teacher_class_id = 0;

/* =========================
   GET ASSIGNMENT ID
========================= */

if (
    isset($_GET["teacher_class_id"]) &&
    is_numeric($_GET["teacher_class_id"])
) {
    $teacher_class_id = (int) $_GET["teacher_class_id"];
}

if ($teacher_class_id <= 0) {
    die("Invalid assignment ID.");
}

/* =========================
   GET ASSIGNMENT
========================= */

$sql = "
    SELECT
        tc.teacher_class_id,
        tc.teacher_id,
        tc.class_id,
        tc.assignment_role,
        tc.assigned_date,
        tc.status,

        t.teacher_name,

        c.class_name

    FROM teacher_classes tc

    INNER JOIN teachers t
        ON tc.teacher_id = t.teacher_id

    INNER JOIN classes c
        ON tc.class_id = c.class_id

    WHERE tc.teacher_class_id = ?

    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $teacher_class_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$assignment = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$assignment) {
    die("Teacher assignment not found.");
}

/* =========================
   CHANGE ROLE
========================= */

if (isset($_POST["save_role"])) {

    $new_role = trim($_POST["assignment_role"]);

    if (
        $new_role !== "class_teacher" &&
        $new_role !== "subject_teacher"
    ) {

        $error = "Assignment role si sahihi.";

    } else {

        /*
         * Kama tunataka kumfanya
         * Class Teacher, kwanza hakikisha
         * hakuna Class Teacher mwingine active.
         */

        if ($new_role === "class_teacher") {

            $check_sql = "
                SELECT teacher_class_id
                FROM teacher_classes
                WHERE class_id = ?
                AND assignment_role = 'class_teacher'
                AND status = 'active'
                AND teacher_class_id != ?
                LIMIT 1
            ";

            $check_stmt = mysqli_prepare(
                $conn,
                $check_sql
            );

            if (!$check_stmt) {

                $error =
                    "Imeshindikana kuangalia Class Teacher.";

            } else {

                mysqli_stmt_bind_param(
                    $check_stmt,
                    "ii",
                    $assignment["class_id"],
                    $teacher_class_id
                );

                mysqli_stmt_execute($check_stmt);

                $check_result =
                    mysqli_stmt_get_result(
                        $check_stmt
                    );

                $existing_class_teacher =
                    mysqli_fetch_assoc(
                        $check_result
                    );

                mysqli_stmt_close($check_stmt);

                if ($existing_class_teacher) {

                    $error =
                        "Class hii tayari ina Class Teacher mwingine active.";
                }
            }
        }


        /* =========================
           UPDATE ROLE
        ========================= */

        if ($error === "") {

            $update_sql = "
                UPDATE teacher_classes
                SET
                    assignment_role = ?,
                    status = 'active'
                WHERE teacher_class_id = ?
            ";

            $update_stmt = mysqli_prepare(
                $conn,
                $update_sql
            );

            if (!$update_stmt) {

                $error =
                    "Imeshindikana kubadilisha assignment.";

            } else {

                mysqli_stmt_bind_param(
                    $update_stmt,
                    "si",
                    $new_role,
                    $teacher_class_id
                );

                if (
                    mysqli_stmt_execute(
                        $update_stmt
                    )
                ) {

                    $message =
                        "Assignment imebadilishwa successfully.";

                    $assignment["assignment_role"] =
                        $new_role;

                    $assignment["status"] =
                        "active";

                } else {

                    $error =
                        "Assignment haikubadilishwa.";
                }

                mysqli_stmt_close(
                    $update_stmt
                );
            }
        }
    }
}


/* =========================
   REMOVE ASSIGNMENT
========================= */

if (isset($_POST["remove_assignment"])) {

    $remove_sql = "
        UPDATE teacher_classes
        SET status = 'inactive'
        WHERE teacher_class_id = ?
    ";

    $remove_stmt = mysqli_prepare(
        $conn,
        $remove_sql
    );

    if (!$remove_stmt) {

        $error =
            "Imeshindikana kuondoa assignment.";

    } else {

        mysqli_stmt_bind_param(
            $remove_stmt,
            "i",
            $teacher_class_id
        );

        if (
            mysqli_stmt_execute(
                $remove_stmt
            )
        ) {

            $message =
                "Teacher ameondolewa kwenye class hii.";

            $assignment["status"] =
                "inactive";

        } else {

            $error =
                "Assignment haijaondolewa.";
        }

        mysqli_stmt_close(
            $remove_stmt
        );
    }
}

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
        Edit Teacher Assignment - KAPONDA
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<div class="layout">

    <?php include "menu.php"; ?>

    <div class="content">

        <!-- =========================
             PAGE HEADER
        ========================== -->

        <div class="panel">

            <h1 class="page-title">
                ⚙️ Edit Teacher Assignment
            </h1>

            <p>
                Manage role ya teacher kwenye class hii.
            </p>

        </div>


        <!-- =========================
             MESSAGES
        ========================== -->

        <?php if ($message !== ""): ?>

            <div class="alert alert-success">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <!-- =========================
             TEACHER INFORMATION
        ========================== -->

        <div class="panel">

            <h2>
                Teacher Assignment Information
            </h2>

            <div class="form-grid">

                <!-- TEACHER -->

                <div>

                    <h3>
                        Teacher
                    </h3>

                    <p>
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $assignment["teacher_name"]
                            );
                            ?>
                        </strong>
                    </p>

                </div>


                <!-- CLASS -->

                <div>

                    <h3>
                        Class
                    </h3>

                    <p>
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $assignment["class_name"]
                            );
                            ?>
                        </strong>
                    </p>

                </div>


                <!-- ASSIGNED DATE -->

                <div>

                    <h3>
                        Assigned Date
                    </h3>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $assignment["assigned_date"]
                        );
                        ?>
                    </p>

                </div>


                <!-- CURRENT ROLE -->

                <div>

                    <h3>
                        Current Role
                    </h3>

                    <p>

                        <?php

                        if (
                            $assignment["assignment_role"]
                            === "class_teacher"
                        ) {

                            echo '<span class="badge badge-active">
                                    Class Teacher
                                  </span>';

                        } else {

                            echo '<span class="badge badge-permission">
                                    Subject Teacher
                                  </span>';
                        }

                        ?>

                    </p>

                </div>


                <!-- STATUS -->

                <div>

                    <h3>
                        Assignment Status
                    </h3>

                    <p>

                        <?php

                        if (
                            $assignment["status"]
                            === "active"
                        ) {

                            echo '<span class="badge badge-active">
                                    Active
                                  </span>';

                        } else {

                            echo '<span class="badge badge-inactive">
                                    Inactive
                                  </span>';
                        }

                        ?>

                    </p>

                </div>

            </div>

        </div>


        <!-- =========================
             CHANGE ROLE
        ========================== -->

        <div class="panel">

            <h2>
                Change Assignment Role
            </h2>

            <p>
                Unaweza kubadilisha teacher kutoka
                <strong>Class Teacher</strong> kwenda
                <strong>Subject Teacher</strong> au kinyume chake.
            </p>

            <form method="POST">

                <div class="form-group">

                    <label>
                        Assignment Role
                    </label>

                    <select
                        name="assignment_role"
                        required
                    >

                        <option
                            value="class_teacher"
                            <?php

                            if (
                                $assignment["assignment_role"]
                                === "class_teacher"
                            ) {

                                echo "selected";
                            }

                            ?>
                        >
                            Class Teacher
                        </option>

                        <option
                            value="subject_teacher"
                            <?php

                            if (
                                $assignment["assignment_role"]
                                === "subject_teacher"
                            ) {

                                echo "selected";
                            }

                            ?>
                        >
                            Subject Teacher
                        </option>

                    </select>

                </div>


                <button
                    type="submit"
                    name="save_role"
                    class="btn btn-primary"
                >
                    💾 Save Role
                </button>

            </form>

        </div>


        <!-- =========================
             REMOVE ASSIGNMENT
        ========================== -->

        <div class="panel">

            <h2>
                Remove Assignment
            </h2>

            <div class="alert alert-warning">

                <strong>Notice:</strong>

                Kuondoa assignment
                hakutafuta teacher wala class.

                Assignment itawekwa
                <strong>inactive</strong>
                ili history ibaki kwenye database.

            </div>

            <form method="POST">

                <button
                    type="submit"
                    name="remove_assignment"
                    class="btn btn-danger"
                    onclick="
                        return confirm(
                            'Una uhakika unataka kuondoa assignment hii?'
                        );
                    "
                >
                    🗑 Remove Assignment
                </button>

            </form>

        </div>


        <!-- =========================
             NAVIGATION
        ========================== -->

        <div class="panel">

            <div class="actions">

                <a
                    href="manage_teachers.php"
                    class="btn btn-secondary"
                >
                    ↩ Back to Manage Teachers
                </a>

                <a
                    href="assign_teacher.php"
                    class="btn btn-success"
                >
                    📚 Assign Another Teacher
                </a>

            </div>

        </div>

    </div>

</div>

</body>

</html>