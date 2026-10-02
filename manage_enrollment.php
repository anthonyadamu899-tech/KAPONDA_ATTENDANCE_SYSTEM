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
   GET ALL ENROLLMENTS
================================ */

$sql = "
    SELECT
        e.enrollment_id,
        s.student_id,
        s.registration_no,
        s.first_name,
        s.middle_name,
        s.last_name,
        c.class_name,
        ay.year_name,
        e.enrollment_date,
        e.status

    FROM student_class_enrollment e

    JOIN students s
        ON e.student_id = s.student_id

    JOIN classes c
        ON e.class_id = c.class_id

    JOIN academic_years ay
        ON e.academic_year_id = ay.academic_year_id

    ORDER BY
        ay.year_name DESC,
        c.class_name ASC,
        s.first_name ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error loading enrollments: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Enrollment - KAPONDA</title>

<link rel="stylesheet" href="../assets/css/style.css">

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
            Admin Panel - Student Enrollment
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

    <!-- ==========================
         SIDEBAR
    =========================== -->

    <?php include "menu.php"; ?>


    <!-- ==========================
         CONTENT
    =========================== -->

    <div class="content">

        <h2 class="page-title">
            Student Enrollment Management
        </h2>


        <!-- ==========================
             ACTION BUTTONS
        =========================== -->

        <div class="panel">

            <h3>
                Enrollment Management
            </h3>

            <p>
                Manage students and their class enrollment.
            </p>

            <div class="actions">

                <a
                    href="add_student.php"
                    class="btn btn-primary"
                >
                    ➕ Add Student
                </a>

                <a
                    href="manage_students.php"
                    class="btn btn-secondary"
                >
                    👨‍🎓 Manage Students
                </a>

            </div>

        </div>


        <!-- ==========================
             ENROLLMENT TABLE
        =========================== -->

        <div class="panel">

            <h3>
                All Student Enrollments
            </h3>

            <div class="table-container">

                <?php if (mysqli_num_rows($result) > 0): ?>

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Registration No.
                                </th>

                                <th>
                                    Student Name
                                </th>

                                <th>
                                    Class
                                </th>

                                <th>
                                    Academic Year
                                </th>

                                <th>
                                    Enrollment Date
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php

                        $number = 1;

                        while ($row = mysqli_fetch_assoc($result)):

                            $student_name = trim(
                                $row["first_name"] . " " .
                                $row["middle_name"] . " " .
                                $row["last_name"]
                            );

                        ?>

                            <tr>

                                <!-- NUMBER -->

                                <td>
                                    <?php echo $number; ?>
                                </td>


                                <!-- REGISTRATION -->

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $row["registration_no"]
                                    );
                                    ?>
                                </td>


                                <!-- STUDENT NAME -->

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $student_name
                                    );
                                    ?>
                                </td>


                                <!-- CLASS -->

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $row["class_name"]
                                    );
                                    ?>
                                </td>


                                <!-- ACADEMIC YEAR -->

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $row["year_name"]
                                    );
                                    ?>
                                </td>


                                <!-- ENROLLMENT DATE -->

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $row["enrollment_date"]
                                    );
                                    ?>
                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php

                                    if ($row["status"] === "active"):

                                    ?>

                                        <span class="badge badge-active">
                                            Active
                                        </span>

                                    <?php

                                    elseif ($row["status"] === "completed"):

                                    ?>

                                        <span class="badge badge-present">
                                            Completed
                                        </span>

                                    <?php

                                    elseif ($row["status"] === "transferred"):

                                    ?>

                                        <span class="badge badge-permission">
                                            Transferred
                                        </span>

                                    <?php

                                    else:

                                    ?>

                                        <span class="badge badge-inactive">
                                            <?php
                                            echo htmlspecialchars(
                                                $row["status"]
                                            );
                                            ?>
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="actions">

                                        <a
                                            href="change_class.php?enrollment_id=<?php echo (int) $row["enrollment_id"]; ?>"
                                            class="btn btn-warning"
                                        >
                                            Change Class
                                        </a>

                                        <a
                                            href="edit_student.php?student_id=<?php echo (int) $row["student_id"]; ?>"
                                            class="btn btn-secondary"
                                        >
                                            Edit Student
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php

                            $number++;

                        endwhile;

                        ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty-state">

                        <h3>
                            No Student Enrollment Found
                        </h3>

                        <p>
                            Hakuna student enrollment iliyopatikana
                            kwenye mfumo.
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </div>


        <!-- ==========================
             INFORMATION
        =========================== -->

        <div class="panel">

            <h3>
                Enrollment Information
            </h3>

            <p>
                <strong>Change Class</strong> hutumika
                kubadilisha class ambayo mwanafunzi ame-enroll.
            </p>

            <p>
                <strong>Edit Student</strong> hutumika
                kubadilisha taarifa binafsi za mwanafunzi.
            </p>

            <p>
                Enrollment records zinabaki kwenye database
                ili kuhifadhi history ya mwanafunzi.
            </p>

        </div>


        <!-- BACK -->

        <a
            href="dashboard.php"
            class="btn btn-secondary"
        >
            ← Back to Dashboard
        </a>

    </div>

</div>

</body>

</html>