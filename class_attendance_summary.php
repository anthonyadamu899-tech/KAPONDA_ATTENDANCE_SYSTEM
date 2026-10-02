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

$selected_class_id = "";

$start_date = date("Y-m-01");
$end_date = date("Y-m-d");

$message = "";

$summary_rows = array();

$selected_class_name = "";

/*
|--------------------------------------------------------------------------
| Get Filters
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["class_id"]) &&
    is_numeric($_GET["class_id"])
) {
    $selected_class_id = (int) $_GET["class_id"];
}

if (
    isset($_GET["start_date"]) &&
    $_GET["start_date"] !== ""
) {
    $start_date = $_GET["start_date"];
}

if (
    isset($_GET["end_date"]) &&
    $_GET["end_date"] !== ""
) {
    $end_date = $_GET["end_date"];
}

/*
|--------------------------------------------------------------------------
| Validate Date Range
|--------------------------------------------------------------------------
*/

if ($start_date > $end_date) {

    $message =
        "Start date cannot be greater than end date.";
}

/*
|--------------------------------------------------------------------------
| Get Active Classes
|--------------------------------------------------------------------------
*/

$classes_sql = "
    SELECT
        class_id,
        class_name
    FROM classes
    WHERE status = 'active'
    ORDER BY class_name ASC
";

$classes_result = mysqli_query(
    $conn,
    $classes_sql
);

/*
|--------------------------------------------------------------------------
| Get Selected Class Name
|--------------------------------------------------------------------------
*/

if (
    $selected_class_id !== "" &&
    $message === ""
) {

    $class_name_sql = "
        SELECT
            class_name
        FROM classes
        WHERE class_id = ?
        AND status = 'active'
        LIMIT 1
    ";

    $class_name_stmt = mysqli_prepare(
        $conn,
        $class_name_sql
    );

    if ($class_name_stmt) {

        mysqli_stmt_bind_param(
            $class_name_stmt,
            "i",
            $selected_class_id
        );

        mysqli_stmt_execute(
            $class_name_stmt
        );

        $class_name_result =
            mysqli_stmt_get_result(
                $class_name_stmt
            );

        if ($class_name_result) {

            $class_data =
                mysqli_fetch_assoc(
                    $class_name_result
                );

            if ($class_data) {

                $selected_class_name =
                    $class_data["class_name"];
            }
        }

        mysqli_stmt_close(
            $class_name_stmt
        );
    }
}

/*
|--------------------------------------------------------------------------
| Get Class Attendance Summary
|--------------------------------------------------------------------------
*/

if (
    $selected_class_id !== "" &&
    $message === "" &&
    $selected_class_name !== ""
) {

    $summary_sql = "

        SELECT

            s.student_id,
            s.registration_no,
            s.first_name,
            s.middle_name,
            s.last_name,

            c.class_name,

            COUNT(a.attendance_id)
                AS total_records,

            SUM(
                CASE
                    WHEN a.status = 'present'
                    THEN 1
                    ELSE 0
                END
            ) AS present,

            SUM(
                CASE
                    WHEN a.status = 'absent'
                    THEN 1
                    ELSE 0
                END
            ) AS absent,

            SUM(
                CASE
                    WHEN a.status = 'permission'
                    THEN 1
                    ELSE 0
                END
            ) AS permission

        FROM student_class_enrollment sce

        INNER JOIN students s
            ON sce.student_id = s.student_id

        INNER JOIN classes c
            ON sce.class_id = c.class_id

        LEFT JOIN attendance a
            ON sce.enrollment_id = a.enrollment_id

            AND a.attendance_date
            BETWEEN ? AND ?

        WHERE sce.class_id = ?

        AND sce.status = 'active'

        AND s.status = 'active'

        GROUP BY

            s.student_id,
            s.registration_no,
            s.first_name,
            s.middle_name,
            s.last_name,
            c.class_name

        ORDER BY
            s.registration_no ASC
    ";

    $summary_stmt = mysqli_prepare(
        $conn,
        $summary_sql
    );

    if ($summary_stmt) {

        mysqli_stmt_bind_param(
            $summary_stmt,
            "ssi",
            $start_date,
            $end_date,
            $selected_class_id
        );

        mysqli_stmt_execute(
            $summary_stmt
        );

        $summary_result =
            mysqli_stmt_get_result(
                $summary_stmt
            );

        if ($summary_result) {

            while (
                $row =
                mysqli_fetch_assoc(
                    $summary_result
                )
            ) {

                $summary_rows[] = $row;
            }
        }

        mysqli_stmt_close(
            $summary_stmt
        );

    } else {

        $message =
            "Failed to prepare class attendance summary.";
    }
}

/*
|--------------------------------------------------------------------------
| Overall Class Totals
|--------------------------------------------------------------------------
*/

$class_total_students =
    count($summary_rows);

$class_present = 0;
$class_absent = 0;
$class_permission = 0;
$class_total_records = 0;

foreach (
    $summary_rows as $row
) {

    $class_present +=
        (int) $row["present"];

    $class_absent +=
        (int) $row["absent"];

    $class_permission +=
        (int) $row["permission"];

    $class_total_records +=
        (int) $row["total_records"];
}

/*
|--------------------------------------------------------------------------
| Class Attendance Percentage
|--------------------------------------------------------------------------
*/

$class_attendance_percentage = 0;

if ($class_total_records > 0) {

    $class_attendance_percentage =
        (
            $class_present /
            $class_total_records
        ) * 100;
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
        Class Attendance Summary
    </title>

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
                    Class Attendance Summary
                </h1>

                <p>
                    View attendance performance for a class.
                </p>

            </div>

        </div>


        <h2 class="page-title">
            Class Attendance Summary
        </h2>


        <?php if ($message !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <!-- FILTER -->

        <div class="panel">

            <h3>
                Report Filter
            </h3>

            <form
                method="GET"
                action="class_attendance_summary.php"
            >

                <div class="form-grid">


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
                                -- Select Class --
                            </option>

                            <?php

                            if ($classes_result) {

                                while (
                                    $class =
                                    mysqli_fetch_assoc(
                                        $classes_result
                                    )
                                ) {

                                    $selected = "";

                                    if (
                                        $selected_class_id !== "" &&
                                        $selected_class_id ==
                                        $class["class_id"]
                                    ) {

                                        $selected =
                                            "selected";
                                    }

                                    ?>

                                    <option
                                        value="<?php
                                        echo (int)
                                            $class["class_id"];
                                        ?>"
                                        <?php
                                        echo $selected;
                                        ?>
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $class["class_name"]
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
                    View Summary
                </button>

            </form>

        </div>


        <?php
        if (
            $selected_class_id !== "" &&
            $selected_class_name !== ""
        ):
        ?>


            <!-- CLASS INFORMATION -->

            <div class="panel">

                <h2>

                    <?php

                    echo htmlspecialchars(
                        $selected_class_name
                    );

                    ?>

                </h2>

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


                <!-- STUDENTS -->

                <div class="card">

                    <h3>
                        Students
                    </h3>

                    <div class="number">

                        <?php
                        echo $class_total_students;
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
                        echo $class_present;
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
                        echo $class_absent;
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
                        echo $class_permission;
                        ?>

                    </div>

                </div>


                <!-- ATTENDANCE -->

                <div class="card">

                    <h3>
                        Attendance %
                    </h3>

                    <div class="number">

                        <?php

                        echo number_format(
                            $class_attendance_percentage,
                            2
                        );

                        ?>%

                    </div>

                </div>

            </div>


            <!-- STUDENT SUMMARY TABLE -->

            <div class="panel">

                <h3>
                    Student Attendance Summary
                </h3>


                <?php
                if ($class_total_students > 0):
                ?>


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
                                        Total Records
                                    </th>

                                    <th>
                                        Present
                                    </th>

                                    <th>
                                        Absent
                                    </th>

                                    <th>
                                        Permission
                                    </th>

                                    <th>
                                        Attendance %
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php

                                $number = 1;

                                foreach (
                                    $summary_rows
                                    as $row
                                ):

                                    $student_name =
                                        $row["first_name"] .
                                        " " .
                                        $row["middle_name"] .
                                        " " .
                                        $row["last_name"];

                                    $student_total =
                                        (int)
                                        $row["total_records"];

                                    $student_present =
                                        (int)
                                        $row["present"];

                                    $student_absent =
                                        (int)
                                        $row["absent"];

                                    $student_permission =
                                        (int)
                                        $row["permission"];

                                    $student_percentage =
                                        0;

                                    if (
                                        $student_total > 0
                                    ) {

                                        $student_percentage =
                                            (
                                                $student_present /
                                                $student_total
                                            ) * 100;
                                    }

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
                                                    "registration_no"
                                                ]
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $student_name
                                            );

                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo $student_total;
                                            ?>

                                        </td>


                                        <td>

                                            <span
                                                class="badge badge-present"
                                            >

                                                <?php
                                                echo $student_present;
                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="badge badge-absent"
                                            >

                                                <?php
                                                echo $student_absent;
                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="badge badge-permission"
                                            >

                                                <?php
                                                echo $student_permission;
                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <strong>

                                                <?php

                                                echo number_format(
                                                    $student_percentage,
                                                    2
                                                );

                                                ?>%

                                            </strong>

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
                            No Active Students
                        </h3>

                        <p>
                            No active students were found
                            in this class.
                        </p>

                    </div>


                <?php endif; ?>

            </div>


        <?php else: ?>


            <!-- INITIAL STATE -->

            <div class="panel">

                <div class="empty-state">

                    <h3>
                        Select Class
                    </h3>

                    <p>
                        Select a class and date range
                        to view the attendance summary.
                    </p>

                </div>

            </div>


        <?php endif; ?>

    </div>

</div>

</body>

</html>