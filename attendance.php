<?php
session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| 1. Teacher pekee
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
| 2. Pokea class_id
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

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| 3. Tafuta teacher anayehusishwa na user huyu
|--------------------------------------------------------------------------
*/
$sql_teacher = "
    SELECT
        teacher_id,
        teacher_name,
        user_id
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
    die("Teacher account haijapatikana.");
}

$teacher_id = (int) $teacher["teacher_id"];

/*
|--------------------------------------------------------------------------
| 4. Hakikisha teacher ni Class Teacher wa class hii
|--------------------------------------------------------------------------
*/
$sql_check = "
    SELECT
        tc.teacher_class_id,
        c.class_name
    FROM teacher_classes tc
    JOIN classes c
        ON tc.class_id = c.class_id
    WHERE tc.teacher_id = ?
    AND tc.class_id = ?
    AND tc.assignment_role = 'class_teacher'
    AND tc.status = 'active'
    AND c.status = 'active'
";

$stmt_check = mysqli_prepare($conn, $sql_check);

mysqli_stmt_bind_param(
    $stmt_check,
    "ii",
    $teacher_id,
    $class_id
);

mysqli_stmt_execute($stmt_check);

$result_check = mysqli_stmt_get_result($stmt_check);

$class = mysqli_fetch_assoc($result_check);

/*
|--------------------------------------------------------------------------
| 5. Subject Teacher haruhusiwi ku-mark
|--------------------------------------------------------------------------
*/
if (!$class) {
    die("Hauruhusiwi ku-mark attendance ya class hii.");
}

/*
|--------------------------------------------------------------------------
| 6. Attendance date
|--------------------------------------------------------------------------
*/
if (isset($_POST["attendance_date"])) {

    $attendance_date = $_POST["attendance_date"];

} elseif (isset($_GET["date"])) {

    $attendance_date = $_GET["date"];

} else {

    $attendance_date = date("Y-m-d");
}

/*
|--------------------------------------------------------------------------
| 7. Tafuta wanafunzi wa class
|--------------------------------------------------------------------------
*/
$sql_students = "
    SELECT
        e.enrollment_id,
        s.registration_no,
        s.first_name,
        s.middle_name,
        s.last_name
    FROM student_class_enrollment e
    JOIN students s
        ON e.student_id = s.student_id
    WHERE e.class_id = ?
    AND e.status = 'active'
    AND s.status = 'active'
    ORDER BY s.registration_no
";

$stmt_students = mysqli_prepare($conn, $sql_students);

mysqli_stmt_bind_param(
    $stmt_students,
    "i",
    $class_id
);

mysqli_stmt_execute($stmt_students);

$students = mysqli_stmt_get_result($stmt_students);

/*
|--------------------------------------------------------------------------
| 8. Get existing attendance
|--------------------------------------------------------------------------
*/
$existing_attendance = array();

$sql_existing = "
    SELECT
        a.enrollment_id,
        a.status
    FROM attendance a
    JOIN student_class_enrollment e
        ON a.enrollment_id = e.enrollment_id
    WHERE a.attendance_date = ?
    AND e.class_id = ?
";

$stmt_existing = mysqli_prepare($conn, $sql_existing);

mysqli_stmt_bind_param(
    $stmt_existing,
    "si",
    $attendance_date,
    $class_id
);

mysqli_stmt_execute($stmt_existing);

$result_existing = mysqli_stmt_get_result($stmt_existing);

while ($row = mysqli_fetch_assoc($result_existing)) {

    $existing_attendance[
        $row["enrollment_id"]
    ] = $row["status"];
}

/*
|--------------------------------------------------------------------------
| 9. Save / Update Attendance
|--------------------------------------------------------------------------
*/
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["save_attendance"])
) {

    $attendance_date = $_POST["attendance_date"];

    if (isset($_POST["attendance"])) {

        $attendance_data = $_POST["attendance"];

    } else {

        $attendance_data = array();
    }

    if (empty($attendance_data)) {

        $error = "Tafadhali chagua attendance ya wanafunzi.";

    } else {

        $saved = 0;
        $updated = 0;
        $errors = 0;

        foreach (
            $attendance_data as $enrollment_id => $status
        ) {

            $enrollment_id = (int) $enrollment_id;

            /*
            |--------------------------------------------------------------------------
            | Hakikisha enrollment ni ya class hii
            |--------------------------------------------------------------------------
            */
            $check_enrollment_sql = "
                SELECT
                    e.enrollment_id
                FROM student_class_enrollment e
                JOIN students s
                    ON e.student_id = s.student_id
                WHERE e.enrollment_id = ?
                AND e.class_id = ?
                AND e.status = 'active'
                AND s.status = 'active'
                LIMIT 1
            ";

            $check_enrollment_stmt = mysqli_prepare(
                $conn,
                $check_enrollment_sql
            );

            mysqli_stmt_bind_param(
                $check_enrollment_stmt,
                "ii",
                $enrollment_id,
                $class_id
            );

            mysqli_stmt_execute(
                $check_enrollment_stmt
            );

            $enrollment_result =
                mysqli_stmt_get_result(
                    $check_enrollment_stmt
                );

            if (
                mysqli_num_rows(
                    $enrollment_result
                ) !== 1
            ) {

                $errors++;
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Allowed statuses
            |--------------------------------------------------------------------------
            */
            $allowed_statuses = array(
                "present",
                "absent",
                "permission"
            );

            if (
                !in_array(
                    $status,
                    $allowed_statuses,
                    true
                )
            ) {

                $errors++;
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Angalia kama attendance tayari ipo
            |--------------------------------------------------------------------------
            */
            $check_sql = "
                SELECT
                    attendance_id
                FROM attendance
                WHERE enrollment_id = ?
                AND attendance_date = ?
            ";

            $check_stmt = mysqli_prepare(
                $conn,
                $check_sql
            );

            mysqli_stmt_bind_param(
                $check_stmt,
                "is",
                $enrollment_id,
                $attendance_date
            );

            mysqli_stmt_execute(
                $check_stmt
            );

            $check_result =
                mysqli_stmt_get_result(
                    $check_stmt
                );

            /*
            |--------------------------------------------------------------------------
            | UPDATE
            |--------------------------------------------------------------------------
            */
            if (
                mysqli_num_rows(
                    $check_result
                ) > 0
            ) {

                $existing =
                    mysqli_fetch_assoc(
                        $check_result
                    );

                /*
                |--------------------------------------------------------------------------
                | marked_by = USERS.user_id
                | NOT teachers.teacher_id
                |--------------------------------------------------------------------------
                */
                $update_sql = "
                    UPDATE attendance
                    SET
                        status = ?,
                        marked_by = ?
                    WHERE attendance_id = ?
                ";

                $update_stmt = mysqli_prepare(
                    $conn,
                    $update_sql
                );

                mysqli_stmt_bind_param(
                    $update_stmt,
                    "sii",
                    $status,
                    $user_id,
                    $existing["attendance_id"]
                );

                if (
                    mysqli_stmt_execute(
                        $update_stmt
                    )
                ) {

                    $updated++;

                } else {

                    $errors++;
                }

            } else {

                /*
                |--------------------------------------------------------------------------
                | INSERT
                |--------------------------------------------------------------------------
                */
                $insert_sql = "
                    INSERT INTO attendance
                    (
                        enrollment_id,
                        attendance_date,
                        status,
                        marked_by
                    )
                    VALUES (?, ?, ?, ?)
                ";

                $insert_stmt = mysqli_prepare(
                    $conn,
                    $insert_sql
                );

                /*
                |--------------------------------------------------------------------------
                | marked_by = user_id
                |--------------------------------------------------------------------------
                */
                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "issi",
                    $enrollment_id,
                    $attendance_date,
                    $status,
                    $user_id
                );

                if (
                    mysqli_stmt_execute(
                        $insert_stmt
                    )
                ) {

                    $saved++;

                } else {

                    $errors++;
                }
            }
        }

        $message =
            "Attendance imehifadhiwa. " .
            "New: " . $saved .
            " | Updated: " . $updated .
            " | Errors: " . $errors;
    }

    /*
    |--------------------------------------------------------------------------
    | Reload existing attendance baada ya save
    |--------------------------------------------------------------------------
    */
    $existing_attendance = array();

    $sql_existing_after = "
        SELECT
            a.enrollment_id,
            a.status
        FROM attendance a
        JOIN student_class_enrollment e
            ON a.enrollment_id = e.enrollment_id
        WHERE a.attendance_date = ?
        AND e.class_id = ?
    ";

    $stmt_existing_after = mysqli_prepare(
        $conn,
        $sql_existing_after
    );

    mysqli_stmt_bind_param(
        $stmt_existing_after,
        "si",
        $attendance_date,
        $class_id
    );

    mysqli_stmt_execute(
        $stmt_existing_after
    );

    $result_existing_after =
        mysqli_stmt_get_result(
            $stmt_existing_after
        );

    while (
        $row = mysqli_fetch_assoc(
            $result_existing_after
        )
    ) {

        $existing_attendance[
            $row["enrollment_id"]
        ] = $row["status"];
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

    <title>Mark Attendance - KAPONDA</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>
        .attendance-table input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .attendance-table th,
        .attendance-table td {
            text-align: center;
            vertical-align: middle;
        }

        .attendance-table th:nth-child(3),
        .attendance-table td:nth-child(3) {
            text-align: left;
        }

        .student-name {
            font-weight: 600;
        }

        .date-box {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }

        .date-box .form-group {
            margin-bottom: 0;
            min-width: 220px;
        }

        .attendance-legend {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .attendance-legend span {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .legend-present {
            background: #d1e7dd;
            color: #0f5132;
        }

        .legend-absent {
            background: #f8d7da;
            color: #842029;
        }

        .legend-permission {
            background: #fff3cd;
            color: #664d03;
        }

        .attendance-submit {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .attendance-info {
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 700px) {
            .attendance-table {
                min-width: 700px;
            }

            .date-box {
                display: block;
            }

            .date-box .form-group {
                margin-bottom: 10px;
            }
        }
    </style>

</head>

<body>

<!-- HEADER -->
<div class="header">

    <div>
        <h1>KAPONDA EDUCATION ACADEMY</h1>

        <p>
            Teacher Portal
        </p>
    </div>

    <div>
        <strong>
            <?php
            echo htmlspecialchars(
                $teacher["teacher_name"]
            );
            ?>
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
            📝 Mark Attendance
        </h1>


        <!-- CLASS INFORMATION -->
        <div class="cards">

            <div class="card">

                <h3>Teacher</h3>

                <div class="number" style="font-size:20px;">
                    <?php
                    echo htmlspecialchars(
                        $teacher["teacher_name"]
                    );
                    ?>
                </div>

            </div>


            <div class="card">

                <h3>Class</h3>

                <div class="number" style="font-size:20px;">
                    <?php
                    echo htmlspecialchars(
                        $class["class_name"]
                    );
                    ?>
                </div>

            </div>


            <div class="card">

                <h3>Attendance Date</h3>

                <div class="number" style="font-size:20px;">
                    <?php
                    echo htmlspecialchars(
                        $attendance_date
                    );
                    ?>
                </div>

            </div>


            <div class="card">

                <h3>Students</h3>

                <div class="number" style="font-size:20px;">

                    <?php
                    echo mysqli_num_rows($students);
                    ?>

                </div>

            </div>

        </div>


        <!-- SUCCESS MESSAGE -->
        <?php if ($message != ""): ?>

            <div class="alert alert-success">

                <strong>
                    <?php
                    echo htmlspecialchars($message);
                    ?>
                </strong>

            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->
        <?php if ($error != ""): ?>

            <div class="alert alert-danger">

                <strong>
                    <?php
                    echo htmlspecialchars($error);
                    ?>
                </strong>

            </div>

        <?php endif; ?>


        <!-- ATTENDANCE FORM -->
        <div class="panel">

            <h2>
                Students Attendance
            </h2>

            <p class="attendance-info">
                Chagua tarehe, kisha chagua status ya kila mwanafunzi.
            </p>


            <!-- DATE -->
            <form method="POST">

                <div class="date-box">

                    <div class="form-group">

                        <label for="attendance_date">
                            Attendance Date
                        </label>

                        <input
                            type="date"
                            id="attendance_date"
                            name="attendance_date"
                            value="<?php
                            echo htmlspecialchars(
                                $attendance_date
                            );
                            ?>"
                            required
                        >

                    </div>

                    <div>

                        <button
                            type="submit"
                            name="save_attendance"
                            class="btn btn-primary"
                        >
                            💾 Save Attendance
                        </button>

                    </div>

                </div>


                <!-- LEGEND -->
                <div class="attendance-legend">

                    <span class="legend-present">
                        ✓ Present
                    </span>

                    <span class="legend-absent">
                        ✕ Absent
                    </span>

                    <span class="legend-permission">
                        R Permission
                    </span>

                </div>


                <!-- TABLE -->
                <div class="table-container">

                    <table class="attendance-table">

                        <thead>

                            <tr>

                                <th>No.</th>

                                <th>
                                    Registration
                                </th>

                                <th>
                                    Student Name
                                </th>

                                <th>
                                    PRESENT ✓
                                </th>

                                <th>
                                    ABSENT ✕
                                </th>

                                <th>
                                    PERMISSION
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php

                            $number = 1;

                            ?>

                            <?php if (mysqli_num_rows($students) > 0): ?>

                                <?php while (
                                    $student =
                                    mysqli_fetch_assoc($students)
                                ): ?>

                                    <?php

                                    $student_name =
                                        $student["first_name"];

                                    if (
                                        !empty(
                                            $student["middle_name"]
                                        )
                                    ) {
                                        $student_name .=
                                            " " .
                                            $student["middle_name"];
                                    }

                                    if (
                                        !empty(
                                            $student["last_name"]
                                        )
                                    ) {
                                        $student_name .=
                                            " " .
                                            $student["last_name"];
                                    }

                                    $enrollment_id =
                                        (int)
                                        $student["enrollment_id"];

                                    ?>

                                    <tr>

                                        <td>
                                            <?php
                                            echo $number++;
                                            ?>
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

                                            <span class="student-name">

                                                <?php
                                                echo htmlspecialchars(
                                                    $student_name
                                                );
                                                ?>

                                            </span>

                                        </td>


                                        <!-- PRESENT -->
                                        <td>

                                            <input
                                                type="radio"
                                                name="attendance[<?php echo $enrollment_id; ?>]"
                                                value="present"

                                                <?php

                                                if (
                                                    isset(
                                                        $existing_attendance[
                                                            $enrollment_id
                                                        ]
                                                    ) &&
                                                    $existing_attendance[
                                                        $enrollment_id
                                                    ] === "present"
                                                ) {
                                                    echo " checked";
                                                }

                                                ?>

                                                required
                                            >

                                        </td>


                                        <!-- ABSENT -->
                                        <td>

                                            <input
                                                type="radio"
                                                name="attendance[<?php echo $enrollment_id; ?>]"
                                                value="absent"

                                                <?php

                                                if (
                                                    isset(
                                                        $existing_attendance[
                                                            $enrollment_id
                                                        ]
                                                    ) &&
                                                    $existing_attendance[
                                                        $enrollment_id
                                                    ] === "absent"
                                                ) {
                                                    echo " checked";
                                                }

                                                ?>

                                            >

                                        </td>


                                        <!-- PERMISSION -->
                                        <td>

                                            <input
                                                type="radio"
                                                name="attendance[<?php echo $enrollment_id; ?>]"
                                                value="permission"

                                                <?php

                                                if (
                                                    isset(
                                                        $existing_attendance[
                                                            $enrollment_id
                                                        ]
                                                    ) &&
                                                    $existing_attendance[
                                                        $enrollment_id
                                                    ] === "permission"
                                                ) {
                                                    echo " checked";
                                                }

                                                ?>

                                            >

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="6"
                                        class="empty-state"
                                    >
                                        Hakuna wanafunzi active
                                        waliopatikana kwenye class hii.
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- SUBMIT -->
                <?php if (mysqli_num_rows($students) > 0): ?>

                    <div class="attendance-submit">

                        <div class="attendance-info">

                            <strong>
                                Note:
                            </strong>

                            Attendance ya tarehe hii
                            inaweza ku-updatewa baadaye.

                        </div>

                        <button
                            type="submit"
                            name="save_attendance"
                            class="btn btn-success"
                        >
                            💾 Save / Update Attendance
                        </button>

                    </div>

                <?php endif; ?>

            </form>

        </div>


        <!-- BACK -->
        <div class="panel">

            <a
                href="view_class.php?class_id=<?php echo $class_id; ?>"
                class="btn btn-secondary"
            >
                ← Back to Class
            </a>

        </div>

    </div>

</div>

</body>

</html>