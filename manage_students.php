<?php

session_start();
require_once "../config/database.php";

/*
=====================================================
ADMIN SECURITY
=====================================================
*/

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}


/*
=====================================================
GET STUDENTS
=====================================================

We want the student's enrollment for the
current active academic year.
=====================================================
*/

$sql = "
    SELECT
        s.student_id,
        s.registration_no,
        s.first_name,
        s.middle_name,
        s.last_name,
        s.gender,
        s.status AS student_status,

        c.class_name,

        ay.year_name,

        e.status AS enrollment_status

    FROM students s

    LEFT JOIN student_class_enrollment e
        ON s.student_id = e.student_id
        AND e.academic_year_id = (
            SELECT academic_year_id
            FROM academic_years
            WHERE status = 'active'
            ORDER BY academic_year_id DESC
            LIMIT 1
        )

    LEFT JOIN classes c
        ON e.class_id = c.class_id

    LEFT JOIN academic_years ay
        ON e.academic_year_id = ay.academic_year_id

    ORDER BY s.student_id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die(
        "Query failed: " .
        mysqli_error($conn)
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
        Manage Students - KAPONDA
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>


<!-- HEADER -->

<div class="header">

    <div>

        <h1>
            KAPONDA EDUCATION ACADEMY
        </h1>

        <p>
            Admin Panel - Student Management
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
            Manage Students
        </h2>


        <!-- ACTIONS -->

        <div class="panel">

            <div class="actions">

                <a
                    href="add_student.php"
                    class="btn btn-primary"
                >
                    ➕ Add Student
                </a>

                <a
                    href="manage_enrollment.php"
                    class="btn btn-secondary"
                >
                    🏫 Manage Enrollment
                </a>

            </div>

        </div>


        <!-- STUDENTS TABLE -->

        <div class="panel">

            <h3>
                Student List
            </h3>

            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Registration No
                            </th>

                            <th>
                                Student Name
                            </th>

                            <th>
                                Gender
                            </th>

                            <th>
                                Class
                            </th>

                            <th>
                                Academic Year
                            </th>

                            <th>
                                Student Status
                            </th>

                            <th>
                                Enrollment Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (
                        mysqli_num_rows($result) > 0
                    ): ?>

                        <?php
                        $number = 1;
                        ?>


                        <?php while (
                            $student =
                            mysqli_fetch_assoc($result)
                        ): ?>

                            <?php

                            /*
                            =================================
                            BUILD STUDENT FULL NAME
                            =================================
                            */

                            $student_name =
                                trim(
                                    $student["first_name"] .
                                    " " .
                                    $student["middle_name"] .
                                    " " .
                                    $student["last_name"]
                                );


                            /*
                            =================================
                            STUDENT STATUS BADGE
                            =================================
                            */

                            $student_status =
                                strtolower(
                                    $student["student_status"]
                                );

                            $student_badge =
                                $student_status === "active"
                                ? "badge-active"
                                : "badge-inactive";


                            /*
                            =================================
                            ENROLLMENT STATUS BADGE
                            =================================
                            */

                            $enrollment_status =
                                $student["enrollment_status"];

                            if (
                                $enrollment_status !== null &&
                                strtolower(
                                    $enrollment_status
                                ) === "active"
                            ) {

                                $enrollment_badge =
                                    "badge-active";

                            } else {

                                $enrollment_badge =
                                    "badge-inactive";
                            }

                            ?>

                            <tr>

                                <!-- NUMBER -->

                                <td>

                                    <?php
                                    echo $number++;
                                    ?>

                                </td>


                                <!-- REGISTRATION -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $student[
                                                "registration_no"
                                            ]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <!-- NAME -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $student_name
                                    );
                                    ?>

                                </td>


                                <!-- GENDER -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $student["gender"]
                                    );
                                    ?>

                                </td>


                                <!-- CLASS -->

                                <td>

                                    <?php if (
                                        $student["class_name"]
                                        !== null
                                    ): ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $student[
                                                "class_name"
                                            ]
                                        );
                                        ?>

                                    <?php else: ?>

                                        <span class="badge badge-inactive">
                                            Not Assigned
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACADEMIC YEAR -->

                                <td>

                                    <?php if (
                                        $student["year_name"]
                                        !== null
                                    ): ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $student[
                                                "year_name"
                                            ]
                                        );
                                        ?>

                                    <?php else: ?>

                                        N/A

                                    <?php endif; ?>

                                </td>


                                <!-- STUDENT STATUS -->

                                <td>

                                    <span
                                        class="badge <?php
                                        echo $student_badge;
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $student[
                                                "student_status"
                                            ]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- ENROLLMENT STATUS -->

                                <td>

                                    <?php if (
                                        $enrollment_status
                                        !== null
                                    ): ?>

                                        <span
                                            class="badge <?php
                                            echo $enrollment_badge;
                                            ?>"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $enrollment_status
                                            );
                                            ?>

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge badge-inactive"
                                        >
                                            Not Enrolled
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <div class="actions">

                                        <a
                                            href="edit_student.php?student_id=<?php
                                            echo (int)
                                                $student[
                                                    "student_id"
                                                ];
                                            ?>"
                                            class="btn btn-primary"
                                        >
                                            Edit
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>


                    <?php else: ?>

                        <tr>

                            <td
                                colspan="9"
                                class="empty-state"
                            >

                                No students found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- INFORMATION -->

        <div class="panel">

            <h3>
                Student Management
            </h3>

            <ul>

                <li>
                    <strong>Add Student</strong>
                    hutengeneza student na enrollment
                    yake kwenye active academic year.
                </li>

                <li>
                    <strong>Edit</strong>
                    hutumika kubadilisha taarifa za
                    student.
                </li>

                <li>
                    <strong>Manage Enrollment</strong>
                    hutumika kusimamia class/enrollment.
                </li>

                <li>
                    Attendance records hazifutwi wakati
                    taarifa za student zinabadilishwa.
                </li>

            </ul>

        </div>

    </div>

</div>

</body>

</html>