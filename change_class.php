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
   GET ENROLLMENT ID
================================ */

if (!isset($_GET["enrollment_id"]) || !is_numeric($_GET["enrollment_id"])) {
    die("Invalid enrollment ID.");
}

$enrollment_id = (int) $_GET["enrollment_id"];

$message = "";
$error = "";


/* ==============================
   FUNCTION: GET ENROLLMENT
================================ */

function getEnrollment($conn, $enrollment_id)
{
    $sql = "
        SELECT
            e.enrollment_id,
            e.student_id,
            e.class_id,
            e.academic_year_id,
            e.status,

            s.registration_no,
            s.first_name,
            s.middle_name,
            s.last_name,

            c.class_name,

            ay.year_name

        FROM student_class_enrollment e

        JOIN students s
            ON e.student_id = s.student_id

        JOIN classes c
            ON e.class_id = c.class_id

        JOIN academic_years ay
            ON e.academic_year_id = ay.academic_year_id

        WHERE e.enrollment_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $enrollment_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $enrollment = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $enrollment;
}


/* ==============================
   GET CURRENT ENROLLMENT
================================ */

$enrollment = getEnrollment(
    $conn,
    $enrollment_id
);

if (!$enrollment) {
    die("Enrollment not found.");
}


/* ==============================
   GET ACTIVE CLASSES
================================ */

$sql_classes = "
    SELECT
        class_id,
        class_name
    FROM classes
    WHERE status = 'active'
    ORDER BY class_name ASC
";

$classes_result = mysqli_query(
    $conn,
    $sql_classes
);

if (!$classes_result) {
    die(
        "Error loading classes: " .
        mysqli_error($conn)
    );
}


/* ==============================
   CHANGE CLASS
================================ */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["class_id"]) ||
        !is_numeric($_POST["class_id"])
    ) {

        $error = "Please select a valid class.";

    } else {

        $new_class_id = (int) $_POST["class_id"];


        /* ==============================
           CHECK CLASS
        ================================= */

        $sql_check_class = "
            SELECT
                class_id,
                class_name
            FROM classes
            WHERE class_id = ?
            AND status = 'active'
        ";

        $stmt_class = mysqli_prepare(
            $conn,
            $sql_check_class
        );

        if (!$stmt_class) {

            $error = "Database error: " . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_class,
                "i",
                $new_class_id
            );

            mysqli_stmt_execute($stmt_class);

            $class_result = mysqli_stmt_get_result(
                $stmt_class
            );

            $selected_class = mysqli_fetch_assoc(
                $class_result
            );

            mysqli_stmt_close($stmt_class);


            if (!$selected_class) {

                $error = "Selected class does not exist.";

            } elseif (
                $new_class_id ===
                (int) $enrollment["class_id"]
            ) {

                $error = "Student is already assigned to this class.";

            } else {

                /* ==============================
                   UPDATE ENROLLMENT
                ================================= */

                $sql_update = "
                    UPDATE student_class_enrollment
                    SET class_id = ?
                    WHERE enrollment_id = ?
                ";

                $stmt_update = mysqli_prepare(
                    $conn,
                    $sql_update
                );

                if (!$stmt_update) {

                    $error =
                        "Database error: " .
                        mysqli_error($conn);

                } else {

                    mysqli_stmt_bind_param(
                        $stmt_update,
                        "ii",
                        $new_class_id,
                        $enrollment_id
                    );

                    if (
                        mysqli_stmt_execute(
                            $stmt_update
                        )
                    ) {

                        $message =
                            "Student class changed successfully.";

                        /*
                        Update displayed enrollment
                        */

                        $enrollment = getEnrollment(
                            $conn,
                            $enrollment_id
                        );

                    } else {

                        $error =
                            "Failed to change student class: " .
                            mysqli_error($conn);
                    }

                    mysqli_stmt_close(
                        $stmt_update
                    );
                }
            }
        }
    }

    /*
    Reload classes because the previous
    result may already have been consumed.
    */

    mysqli_data_seek(
        $classes_result,
        0
    );
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
    Change Student Class - KAPONDA
</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css"
>

</head>

<body>


<!-- ==============================
     HEADER
================================ -->

<div class="header">

    <div>

        <h1>
            KAPONDA EDUCATION ACADEMY
        </h1>

        <p>
            Admin Panel - Change Student Class
        </p>

    </div>

    <div>

        <a
            href="../logout.php"
            class="btn btn-danger"
        >
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
            Change Student Class
        </h2>


        <!-- ==========================
             SUCCESS MESSAGE
        =========================== -->

        <?php if ($message !== ""): ?>

            <div class="alert alert-success">

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- ==========================
             ERROR MESSAGE
        =========================== -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars(
                    $error
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- ==========================
             STUDENT INFORMATION
        =========================== -->

        <div class="panel">

            <h3>
                Student Information
            </h3>

            <div class="form-grid">


                <!-- REGISTRATION -->

                <div>

                    <strong>
                        Registration No.
                    </strong>

                    <p>

                        <?php
                        echo htmlspecialchars(
                            $enrollment[
                                "registration_no"
                            ]
                        );
                        ?>

                    </p>

                </div>


                <!-- STUDENT NAME -->

                <div>

                    <strong>
                        Student Name
                    </strong>

                    <p>

                        <?php

                        $student_name = trim(
                            $enrollment["first_name"] .
                            " " .
                            $enrollment["middle_name"] .
                            " " .
                            $enrollment["last_name"]
                        );

                        echo htmlspecialchars(
                            $student_name
                        );

                        ?>

                    </p>

                </div>


                <!-- ACADEMIC YEAR -->

                <div>

                    <strong>
                        Academic Year
                    </strong>

                    <p>

                        <?php
                        echo htmlspecialchars(
                            $enrollment[
                                "year_name"
                            ]
                        );
                        ?>

                    </p>

                </div>


                <!-- CURRENT CLASS -->

                <div>

                    <strong>
                        Current Class
                    </strong>

                    <p>

                        <span class="badge badge-active">

                            <?php
                            echo htmlspecialchars(
                                $enrollment[
                                    "class_name"
                                ]
                            );
                            ?>

                        </span>

                    </p>

                </div>


                <!-- ENROLLMENT STATUS -->

                <div>

                    <strong>
                        Enrollment Status
                    </strong>

                    <p>

                        <?php

                        if (
                            $enrollment["status"]
                            === "active"
                        ):

                        ?>

                            <span
                                class="badge badge-active"
                            >
                                Active
                            </span>

                        <?php else: ?>

                            <span
                                class="badge badge-inactive"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $enrollment[
                                        "status"
                                    ]
                                );
                                ?>

                            </span>

                        <?php endif; ?>

                    </p>

                </div>

            </div>

        </div>


        <!-- ==========================
             CHANGE CLASS FORM
        =========================== -->

        <div class="panel">

            <h3>
                Select New Class
            </h3>

            <form method="POST">


                <div class="form-group">

                    <label>
                        New Class
                    </label>

                    <select
                        name="class_id"
                        required
                    >

                        <option value="">
                            -- Select Class --
                        </option>


                        <?php

                        while (
                            $class =
                            mysqli_fetch_assoc(
                                $classes_result
                            )
                        ):

                        ?>

                            <option
                                value="<?php
                                echo (int)
                                $class["class_id"];
                                ?>"
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


                <!-- BUTTONS -->

                <div class="actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        🔄 Change Class
                    </button>

                    <a
                        href="manage_enrollment.php"
                        class="btn btn-secondary"
                    >
                        ← Back to Enrollment
                    </a>

                </div>

            </form>

        </div>


        <!-- ==========================
             IMPORTANT INFORMATION
        =========================== -->

        <div class="panel">

            <h3>
                Important Information
            </h3>

            <p>
                Changing the class does not delete
                the student's attendance records.
            </p>

            <p>
                The student's enrollment record will
                be updated to the selected class.
            </p>

            <p>
                Make sure you select the correct class
                before saving the change.
            </p>

        </div>

    </div>

</div>

</body>

</html>