<?php
session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| ADMIN SECURITY
|--------------------------------------------------------------------------
*/
if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/
$message = "";

$selected_class_id = "";
$selected_date = date("Y-m-d");

$attendance_rows = array();

$present = 0;
$absent = 0;
$permission = 0;

/*
|--------------------------------------------------------------------------
| GET FILTER VALUES
|--------------------------------------------------------------------------
*/
if (
    isset($_GET["class_id"]) &&
    is_numeric($_GET["class_id"])
) {
    $selected_class_id = (int) $_GET["class_id"];
}

if (
    isset($_GET["date"]) &&
    $_GET["date"] !== ""
) {
    $selected_date = $_GET["date"];
}

/*
|--------------------------------------------------------------------------
| GET ACTIVE CLASSES
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
| GET ATTENDANCE DATA
|--------------------------------------------------------------------------
*/
if ($selected_class_id !== "") {

    $attendance_sql = "
        SELECT
            a.attendance_id,
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

        WHERE sce.class_id = ?
        AND a.attendance_date = ?

        ORDER BY s.registration_no ASC
    ";

    $attendance_stmt = mysqli_prepare(
        $conn,
        $attendance_sql
    );

    if ($attendance_stmt) {

        mysqli_stmt_bind_param(
            $attendance_stmt,
            "is",
            $selected_class_id,
            $selected_date
        );

        mysqli_stmt_execute(
            $attendance_stmt
        );

        $attendance_result =
            mysqli_stmt_get_result(
                $attendance_stmt
            );

        if ($attendance_result) {

            while (
                $row =
                mysqli_fetch_assoc(
                    $attendance_result
                )
            ) {

                $attendance_rows[] = $row;

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

        mysqli_stmt_close(
            $attendance_stmt
        );

    } else {

        $message =
            "Failed to prepare attendance report.";
    }
}

$total_records = count(
    $attendance_rows
);

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
        Attendance Report - KAPONDA
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .report-filter {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .report-filter .form-group {
            margin-bottom: 0;
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 20px;
        }

        .report-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
            border-left: 5px solid #1f3c88;
        }

        .report-card h3 {
            margin: 0 0 8px;
            color: #555;
            font-size: 14px;
        }

        .report-card .number {
            font-size: 30px;
            font-weight: bold;
            color: #172b4d;
        }

        .report-card.present-card {
            border-left-color: #198754;
        }

        .report-card.absent-card {
            border-left-color: #dc3545;
        }

        .report-card.permission-card {
            border-left-color: #f0ad4e;
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

        .report-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .report-heading h2 {
            margin: 0;
        }

        .report-heading p {
            margin: 5px 0 0;
            color: #777;
            font-size: 14px;
        }

        .marked-by {
            font-weight: 600;
        }

        @media (max-width: 900px) {

            .report-summary {
                grid-template-columns: repeat(2, 1fr);
            }

            .report-filter {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 600px) {

            .report-summary {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<!-- HEADER -->
<div class="header">

    <div>

        <h1>
            KAPONDA EDUCATION ACADEMY
        </h1>

        <p>
            Admin Portal
        </p>

    </div>

    <div>

        <strong>
            Attendance Report
        </strong>

    </div>

</div>


<!-- MAIN LAYOUT -->
<div class="layout">

    <!-- SIDEBAR -->
    <?php include "menu.php"; ?>


    <!-- CONTENT -->
    <div class="content">

        <h1 class="page-title">
            📋 Daily Attendance Report
        </h1>


        <!-- MESSAGE -->
        <?php if ($message !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- FILTER -->
        <div class="panel">

            <h2>
                Report Filter
            </h2>

            <p>
                Chagua class na tarehe kuona attendance
                ya siku hiyo.
            </p>

            <form
                method="GET"
                action="attendance_report.php"
            >

                <div class="report-filter">

                    <!-- CLASS -->
                    <div class="form-group">

                        <label for="class_id">
                            Select Class
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


                    <!-- DATE -->
                    <div class="form-group">

                        <label for="date">
                            Attendance Date
                        </label>

                        <input
                            type="date"
                            name="date"
                            id="date"
                            value="<?php
                            echo htmlspecialchars(
                                $selected_date
                            );
                            ?>"
                            required
                        >

                    </div>


                    <!-- BUTTON -->
                    <div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            🔍 View Report
                        </button>

                    </div>

                </div>

            </form>

        </div>


        <?php if ($selected_class_id !== ""): ?>


            <!-- REPORT HEADER -->
            <div class="panel">

                <div class="report-heading">

                    <div>

                        <h2>
                            Attendance Report
                        </h2>

                        <p>

                            Date:

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $selected_date
                                );
                                ?>
                            </strong>

                        </p>

                    </div>

                    <div>

                        <a
                            href="attendance_report.php"
                            class="btn btn-secondary"
                        >
                            Clear Filter
                        </a>

                    </div>

                </div>

            </div>


            <!-- SUMMARY -->
            <div class="report-summary">

                <div class="report-card">

                    <h3>
                        Total Records
                    </h3>

                    <div class="number">
                        <?php
                        echo $total_records;
                        ?>
                    </div>

                </div>


                <div class="report-card present-card">

                    <h3>
                        Present
                    </h3>

                    <div class="number">
                        <?php
                        echo $present;
                        ?>
                    </div>

                </div>


                <div class="report-card absent-card">

                    <h3>
                        Absent
                    </h3>

                    <div class="number">
                        <?php
                        echo $absent;
                        ?>
                    </div>

                </div>


                <div class="report-card permission-card">

                    <h3>
                        Permission
                    </h3>

                    <div class="number">
                        <?php
                        echo $permission;
                        ?>
                    </div>

                </div>

            </div>


            <!-- ATTENDANCE TABLE -->
            <div class="panel">

                <h2>
                    Attendance Details
                </h2>


                <?php if ($total_records > 0): ?>

                    <div class="table-container">

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
                                        Date
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
                                    $attendance_rows
                                    as $row
                                ):

                                    $student_name =
                                        $row["first_name"];

                                    if (
                                        !empty(
                                            $row["middle_name"]
                                        )
                                    ) {

                                        $student_name .=
                                            " " .
                                            $row["middle_name"];
                                    }

                                    if (
                                        !empty(
                                            $row["last_name"]
                                        )
                                    ) {

                                        $student_name .=
                                            " " .
                                            $row["last_name"];
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
                                                    $row[
                                                        "registration_no"
                                                    ]
                                                );
                                                ?>

                                            </strong>

                                        </td>


                                        <!-- STUDENT -->
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
                                                $row[
                                                    "class_name"
                                                ]
                                            );
                                            ?>

                                        </td>


                                        <!-- DATE -->
                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $row[
                                                    "attendance_date"
                                                ]
                                            );
                                            ?>

                                        </td>


                                        <!-- STATUS -->
                                        <td>

                                            <?php

                                            if (
                                                $row[
                                                    "attendance_status"
                                                ] === "present"
                                            ) {

                                                echo '
                                                <span class="
                                                    status-badge
                                                    status-present
                                                ">
                                                    Present
                                                </span>
                                                ';

                                            } elseif (
                                                $row[
                                                    "attendance_status"
                                                ] === "absent"
                                            ) {

                                                echo '
                                                <span class="
                                                    status-badge
                                                    status-absent
                                                ">
                                                    Absent
                                                </span>
                                                ';

                                            } elseif (
                                                $row[
                                                    "attendance_status"
                                                ] === "permission"
                                            ) {

                                                echo '
                                                <span class="
                                                    status-badge
                                                    status-permission
                                                ">
                                                    Permission
                                                </span>
                                                ';

                                            } else {

                                                echo htmlspecialchars(
                                                    $row[
                                                        "attendance_status"
                                                    ]
                                                );
                                            }

                                            ?>

                                        </td>


                                        <!-- MARKED BY -->
                                        <td>

                                            <span class="marked-by">

                                                <?php

                                                if (
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
                                                    $row[
                                                        "role"
                                                    ] === "admin"
                                                ) {

                                                    echo "Admin";

                                                } elseif (
                                                    $row[
                                                        "username"
                                                    ] !== null &&
                                                    $row[
                                                        "username"
                                                    ] !== ""
                                                ) {

                                                    echo htmlspecialchars(
                                                        $row[
                                                            "username"
                                                        ]
                                                    );

                                                } else {

                                                    echo "Unknown";
                                                }

                                                ?>

                                            </span>

                                        </td>

                                    </tr>


                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <h3>
                            No Attendance Records
                        </h3>

                        <p>

                            Hakuna attendance records
                            zilizopatikana kwa class hii
                            tarehe

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $selected_date
                                );
                                ?>
                            </strong>.

                        </p>

                    </div>

                <?php endif; ?>

            </div>


        <?php else: ?>


            <!-- INITIAL STATE -->
            <div class="panel">

                <div class="empty-state">

                    <h2>
                        📋 Attendance Report
                    </h2>

                    <p>
                        Select a class and date
                        kuanza kuona attendance report.
                    </p>

                </div>

            </div>


        <?php endif; ?>


    </div>

</div>

</body>

</html>