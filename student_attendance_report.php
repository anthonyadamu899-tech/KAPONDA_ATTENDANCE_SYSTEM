<?php
session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Admin Security
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$selected_student_id = "";
$start_date = date("Y-m-01");
$end_date = date("Y-m-d");

$message = "";

$rows = array();

$present = 0;
$absent = 0;
$permission = 0;

/*
|--------------------------------------------------------------------------
| Get Filters
|--------------------------------------------------------------------------
*/

if (isset($_GET["student_id"]) && is_numeric($_GET["student_id"])) {
    $selected_student_id = (int) $_GET["student_id"];
}

if (isset($_GET["start_date"]) && $_GET["start_date"] !== "") {
    $start_date = $_GET["start_date"];
}

if (isset($_GET["end_date"]) && $_GET["end_date"] !== "") {
    $end_date = $_GET["end_date"];
}

/*
|--------------------------------------------------------------------------
| Validate Date Range
|--------------------------------------------------------------------------
*/

if ($start_date > $end_date) {
    $message = "Start date cannot be greater than end date.";
}

/*
|--------------------------------------------------------------------------
| Get Active Students
|--------------------------------------------------------------------------
*/

$students_sql = "
    SELECT
        student_id,
        registration_no,
        first_name,
        middle_name,
        last_name
    FROM students
    WHERE status = 'active'
    ORDER BY first_name ASC, middle_name ASC, last_name ASC
";

$students_result = mysqli_query($conn, $students_sql);

/*
|--------------------------------------------------------------------------
| Get Attendance
|--------------------------------------------------------------------------
*/

if ($selected_student_id !== "" && $message === "") {

    $attendance_sql = "
        SELECT
            a.attendance_date,
            a.status AS attendance_status,

            s.registration_no,
            s.first_name,
            s.middle_name,
            s.last_name,

            c.class_name,

            u.username,
            u.role,

            t.teacher_name

        FROM attendance a

        INNER JOIN student_class_enrollment sce
            ON a.enrollment_id = sce.enrollment_id

        INNER JOIN students s
            ON sce.student_id = s.student_id

        INNER JOIN classes c
            ON sce.class_id = c.class_id

        LEFT JOIN users u
            ON a.marked_by = u.user_id

        LEFT JOIN teachers t
            ON u.user_id = t.user_id

        WHERE s.student_id = ?
        AND a.attendance_date BETWEEN ? AND ?

        ORDER BY a.attendance_date ASC
    ";

    $attendance_stmt = mysqli_prepare(
        $conn,
        $attendance_sql
    );

    if ($attendance_stmt) {

        mysqli_stmt_bind_param(
            $attendance_stmt,
            "iss",
            $selected_student_id,
            $start_date,
            $end_date
        );

        mysqli_stmt_execute($attendance_stmt);

        $attendance_result =
            mysqli_stmt_get_result($attendance_stmt);

        if ($attendance_result) {

            while (
                $row = mysqli_fetch_assoc(
                    $attendance_result
                )
            ) {

                $rows[] = $row;

                if (
                    $row["attendance_status"]
                    === "present"
                ) {
                    $present++;
                }

                if (
                    $row["attendance_status"]
                    === "absent"
                ) {
                    $absent++;
                }

                if (
                    $row["attendance_status"]
                    === "permission"
                ) {
                    $permission++;
                }
            }
        }

        mysqli_stmt_close($attendance_stmt);

    } else {

        $message =
            "Failed to prepare student attendance report.";
    }
}

/*
|--------------------------------------------------------------------------
| Calculate Attendance Percentage
|--------------------------------------------------------------------------
*/

$total_days = count($rows);

$attendance_percentage = 0;

if ($total_days > 0) {

    $attendance_percentage =
        ($present / $total_days) * 100;
}

/*
|--------------------------------------------------------------------------
| Selected Student Information
|--------------------------------------------------------------------------
*/

$selected_student_name = "";
$selected_registration_no = "";

if ($selected_student_id !== "") {

    $student_info_sql = "
        SELECT
            registration_no,
            first_name,
            middle_name,
            last_name
        FROM students
        WHERE student_id = ?
        LIMIT 1
    ";

    $student_info_stmt = mysqli_prepare(
        $conn,
        $student_info_sql
    );

    if ($student_info_stmt) {

        mysqli_stmt_bind_param(
            $student_info_stmt,
            "i",
            $selected_student_id
        );

        mysqli_stmt_execute(
            $student_info_stmt
        );

        $student_info_result =
            mysqli_stmt_get_result(
                $student_info_stmt
            );

        if ($student_info_result) {

            $student_info =
                mysqli_fetch_assoc(
                    $student_info_result
                );

            if ($student_info) {

                $selected_registration_no =
                    $student_info["registration_no"];

                $selected_student_name =
                    $student_info["first_name"] . " " .
                    $student_info["middle_name"] . " " .
                    $student_info["last_name"];
            }
        }

        mysqli_stmt_close(
            $student_info_stmt
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

    <title>Student Attendance Report</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <?php include "menu.php"; ?>


    <!-- MAIN CONTENT -->

    <div class="content">

        <div class="header">

            <div>

                <h1>
                    Student Attendance Report
                </h1>

                <p>
                    View attendance records for an individual student.
                </p>

            </div>

        </div>


        <h2 class="page-title">
            Student Attendance Report
        </h2>


        <?php if ($message !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <!-- FILTER PANEL -->

        <div class="panel">

            <h3>
                Report Filter
            </h3>

            <form
                method="GET"
                action="student_attendance_report.php"
            >

                <div class="form-grid">


                    <!-- STUDENT -->

                    <div class="form-group">

                        <label for="student_id">
                            Student
                        </label>

                        <select
                            name="student_id"
                            id="student_id"
                            required
                        >

                            <option value="">
                                -- Select Student --
                            </option>

                            <?php

                            if ($students_result) {

                                while (
                                    $student =
                                    mysqli_fetch_assoc(
                                        $students_result
                                    )
                                ) {

                                    $selected = "";

                                    if (
                                        $selected_student_id !== "" &&
                                        $selected_student_id ==
                                        $student["student_id"]
                                    ) {

                                        $selected = "selected";
                                    }

                                    $student_name =
                                        $student["first_name"] . " " .
                                        $student["middle_name"] . " " .
                                        $student["last_name"];

                                    ?>

                                    <option
                                        value="<?php
                                        echo (int)
                                            $student["student_id"];
                                        ?>"
                                        <?php echo $selected; ?>
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $student["registration_no"] .
                                            " - " .
                                            $student_name
                                        );

                                        ?>

                                    </option>

                                    <?php
                                }
                            }

                            ?>

                        </select>

                    </div>


                    <!-- START DATE -->

                    <div class="form-group">

                        <label for="start_date">
                            From
                        </label>

                        <input
                            type="date"
                            name="start_date"
                            id="start_date"
                            value="<?php
                            echo htmlspecialchars(
                                $start_date
                            );
                            ?>"
                            required
                        >

                    </div>


                    <!-- END DATE -->

                    <div class="form-group">

                        <label for="end_date">
                            To
                        </label>

                        <input
                            type="date"
                            name="end_date"
                            id="end_date"
                            value="<?php
                            echo htmlspecialchars(
                                $end_date
                            );
                            ?>"
                            required
                        >

                    </div>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    View Report
                </button>

            </form>

        </div>


        <?php if ($selected_student_id !== ""): ?>


            <!-- STUDENT INFORMATION -->

            <div class="panel">

                <h2>
                    <?php
                    echo htmlspecialchars(
                        $selected_student_name
                    );
                    ?>
                </h2>

                <p>

                    <strong>
                        Registration No:
                    </strong>

                    <?php
                    echo htmlspecialchars(
                        $selected_registration_no
                    );
                    ?>

                </p>

                <p>

                    <strong>
                        Period:
                    </strong>

                    <?php
                    echo htmlspecialchars(
                        $start_date
                    );
                    ?>

                    to

                    <?php
                    echo htmlspecialchars(
                        $end_date
                    );
                    ?>

                </p>

            </div>


            <!-- SUMMARY CARDS -->

            <div class="cards">


                <!-- TOTAL -->

                <div class="card">

                    <h3>
                        Total Records
                    </h3>

                    <div class="number">

                        <?php
                        echo $total_days;
                        ?>

                    </div>

                </div>


                <!-- PRESENT -->

                <div class="card">

                    <h3>
                        Present
                    </h3>

                    <div class="number">

                        <?php
                        echo $present;
                        ?>

                    </div>

                </div>


                <!-- ABSENT -->

                <div class="card">

                    <h3>
                        Absent
                    </h3>

                    <div class="number">

                        <?php
                        echo $absent;
                        ?>

                    </div>

                </div>


                <!-- PERMISSION -->

                <div class="card">

                    <h3>
                        Permission
                    </h3>

                    <div class="number">

                        <?php
                        echo $permission;
                        ?>

                    </div>

                </div>


                <!-- PERCENTAGE -->

                <div class="card">

                    <h3>
                        Attendance %
                    </h3>

                    <div class="number">

                        <?php

                        echo number_format(
                            $attendance_percentage,
                            2
                        );

                        ?>%

                    </div>

                </div>

            </div>


            <!-- ATTENDANCE DETAILS -->

            <div class="panel">

                <h3>
                    Attendance Details
                </h3>


                <?php if ($total_days > 0): ?>

                    <div class="table-container">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Class
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Marked By
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php

                                $number = 1;

                                foreach (
                                    $rows as $row
                                ):

                                ?>

                                    <tr>

                                        <td>

                                            <?php
                                            echo $number;
                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $row[
                                                    "attendance_date"
                                                ]
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $row[
                                                    "class_name"
                                                ]
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            if (
                                                $row[
                                                    "attendance_status"
                                                ]
                                                === "present"
                                            ) {

                                                echo
                                                '<span class="badge badge-present">
                                                    Present
                                                </span>';

                                            } elseif (
                                                $row[
                                                    "attendance_status"
                                                ]
                                                === "absent"
                                            ) {

                                                echo
                                                '<span class="badge badge-absent">
                                                    Absent
                                                </span>';

                                            } elseif (
                                                $row[
                                                    "attendance_status"
                                                ]
                                                === "permission"
                                            ) {

                                                echo
                                                '<span class="badge badge-permission">
                                                    Permission
                                                </span>';

                                            } else {

                                                echo htmlspecialchars(
                                                    $row[
                                                        "attendance_status"
                                                    ]
                                                );
                                            }

                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            /*
                                            |--------------------------------------------------------------------------
                                            | Marked By
                                            |--------------------------------------------------------------------------
                                            */

                                            if (
                                                isset(
                                                    $row[
                                                        "teacher_name"
                                                    ]
                                                ) &&
                                                $row[
                                                    "teacher_name"
                                                ] !== null &&
                                                $row[
                                                    "teacher_name"
                                                ] !== ""
                                            ) {

                                                echo htmlspecialchars(
                                                    $row[
                                                        "teacher_name"
                                                    ]
                                                );

                                            } elseif (
                                                isset(
                                                    $row["role"]
                                                ) &&
                                                $row["role"]
                                                === "admin"
                                            ) {

                                                echo "Admin";

                                            } elseif (
                                                isset(
                                                    $row["username"]
                                                ) &&
                                                $row["username"]
                                                !== null &&
                                                $row["username"]
                                                !== ""
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

                                <?php

                                $number++;

                                endforeach;

                                ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <h3>
                            No Attendance Records
                        </h3>

                        <p>
                            No attendance records were found
                            for this student within the selected period.
                        </p>

                    </div>

                <?php endif; ?>

            </div>


        <?php else: ?>


            <!-- INITIAL STATE -->

            <div class="panel">

                <div class="empty-state">

                    <h3>
                        Select Student
                    </h3>

                    <p>
                        Select a student and date range
                        to view the attendance report.
                    </p>

                </div>

            </div>


        <?php endif; ?>

    </div>

</div>

</body>

</html>