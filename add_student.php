<?php

session_start();

require_once "../config/database.php";

/* =========================
   TEACHER SECURITY
========================= */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "teacher"
) {
    header("Location: ../login.php");
    exit();
}


/* =========================
   CLASS ID
========================= */

if (
    !isset($_GET["class_id"]) ||
    !is_numeric($_GET["class_id"])
) {
    die("Invalid class ID.");
}

$class_id = (int) $_GET["class_id"];
$user_id = (int) $_SESSION["user_id"];

$message = "";
$error = "";


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

$stmt_teacher = mysqli_prepare(
    $conn,
    $sql_teacher
);

if (!$stmt_teacher) {
    die("Database error.");
}

mysqli_stmt_bind_param(
    $stmt_teacher,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt_teacher);

$result_teacher = mysqli_stmt_get_result(
    $stmt_teacher
);

$teacher = mysqli_fetch_assoc(
    $result_teacher
);

mysqli_stmt_close($stmt_teacher);

if (!$teacher) {
    die("Teacher account not found.");
}

$teacher_id = (int) $teacher["teacher_id"];


/* =========================
   VERIFY CLASS TEACHER
========================= */

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

    LIMIT 1
";

$stmt_check = mysqli_prepare(
    $conn,
    $sql_check
);

if (!$stmt_check) {
    die("Database error.");
}

mysqli_stmt_bind_param(
    $stmt_check,
    "ii",
    $teacher_id,
    $class_id
);

mysqli_stmt_execute($stmt_check);

$result_check = mysqli_stmt_get_result(
    $stmt_check
);

$class_info = mysqli_fetch_assoc(
    $result_check
);

mysqli_stmt_close($stmt_check);

if (!$class_info) {

    die(
        "Huna ruhusa ya kuongeza mwanafunzi kwenye class hii."
    );
}

$class_name = $class_info["class_name"];


/* =========================
   GET ACTIVE ACADEMIC YEAR
========================= */

$sql_year = "
    SELECT
        academic_year_id,
        year_name
    FROM academic_years
    WHERE status = 'active'
    ORDER BY academic_year_id DESC
    LIMIT 1
";

$result_year = mysqli_query(
    $conn,
    $sql_year
);

if (!$result_year) {
    die("Database error.");
}

$academic_year = mysqli_fetch_assoc(
    $result_year
);

if (!$academic_year) {
    die("Hakuna Academic Year active.");
}

$academic_year_id =
    (int) $academic_year["academic_year_id"];


/* =========================
   DEFAULT FORM VALUES
========================= */

$registration_no = "";
$first_name = "";
$middle_name = "";
$last_name = "";
$gender = "";
$date_of_birth = "";


/* =========================
   ADD STUDENT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $registration_no =
        trim($_POST["registration_no"]);

    $first_name =
        trim($_POST["first_name"]);

    $middle_name =
        trim($_POST["middle_name"]);

    $last_name =
        trim($_POST["last_name"]);

    $gender =
        trim($_POST["gender"]);

    $date_of_birth =
        trim($_POST["date_of_birth"]);


    /* =========================
       VALIDATION
    ========================= */

    if (
        $registration_no === "" ||
        $first_name === "" ||
        $last_name === "" ||
        $gender === ""
    ) {

        $error =
            "Tafadhali jaza sehemu zote muhimu.";

    } elseif (
        $gender !== "Male" &&
        $gender !== "Female"
    ) {

        $error =
            "Gender si sahihi.";

    } else {

        /* =========================
           CHECK REGISTRATION NUMBER
        ========================= */

        $check_student_sql = "
            SELECT
                student_id
            FROM students
            WHERE registration_no = ?
            LIMIT 1
        ";

        $check_student_stmt =
            mysqli_prepare(
                $conn,
                $check_student_sql
            );

        if (!$check_student_stmt) {

            $error =
                "Database error.";

        } else {

            mysqli_stmt_bind_param(
                $check_student_stmt,
                "s",
                $registration_no
            );

            mysqli_stmt_execute(
                $check_student_stmt
            );

            $check_student_result =
                mysqli_stmt_get_result(
                    $check_student_stmt
                );

            if (
                mysqli_num_rows(
                    $check_student_result
                ) > 0
            ) {

                $error =
                    "Registration number hiyo tayari ipo.";

            } else {

                /* =========================
                   TRANSACTION
                ========================= */

                mysqli_begin_transaction($conn);

                try {

                    /* =========================
                       1. INSERT STUDENT
                    ========================= */

                    $student_sql = "
                        INSERT INTO students
                        (
                            registration_no,
                            first_name,
                            middle_name,
                            last_name,
                            gender,
                            date_of_birth,
                            status
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            NULLIF(?, ''),
                            'active'
                        )
                    ";

                    $student_stmt =
                        mysqli_prepare(
                            $conn,
                            $student_sql
                        );

                    if (!$student_stmt) {

                        throw new Exception(
                            "Student statement failed."
                        );
                    }

                    mysqli_stmt_bind_param(
                        $student_stmt,
                        "ssssss",
                        $registration_no,
                        $first_name,
                        $middle_name,
                        $last_name,
                        $gender,
                        $date_of_birth
                    );

                    if (
                        !mysqli_stmt_execute(
                            $student_stmt
                        )
                    ) {

                        mysqli_stmt_close(
                            $student_stmt
                        );

                        throw new Exception(
                            "Student hakuweza kuongezwa."
                        );
                    }

                    $student_id =
                        mysqli_insert_id($conn);

                    mysqli_stmt_close(
                        $student_stmt
                    );


                    /* =========================
                       2. CREATE ENROLLMENT
                    ========================= */

                    $enrollment_sql = "
                        INSERT INTO student_class_enrollment
                        (
                            student_id,
                            class_id,
                            academic_year_id,
                            enrollment_date,
                            status
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            CURDATE(),
                            'active'
                        )
                    ";

                    $enrollment_stmt =
                        mysqli_prepare(
                            $conn,
                            $enrollment_sql
                        );

                    if (!$enrollment_stmt) {

                        throw new Exception(
                            "Enrollment statement failed."
                        );
                    }

                    mysqli_stmt_bind_param(
                        $enrollment_stmt,
                        "iii",
                        $student_id,
                        $class_id,
                        $academic_year_id
                    );

                    if (
                        !mysqli_stmt_execute(
                            $enrollment_stmt
                        )
                    ) {

                        mysqli_stmt_close(
                            $enrollment_stmt
                        );

                        throw new Exception(
                            "Student enrollment haikuweza kutengenezwa."
                        );
                    }

                    mysqli_stmt_close(
                        $enrollment_stmt
                    );


                    /* =========================
                       3. CREATE ADMIN NOTIFICATION
                    ========================= */

                    $student_full_name =
                        $first_name;

                    if ($middle_name !== "") {

                        $student_full_name .=
                            " " . $middle_name;
                    }

                    $student_full_name .=
                        " " . $last_name;


                    $notification_title =
                        "New Student Added";

                    $notification_message =
                        $teacher["teacher_name"] .
                        " added " .
                        $student_full_name .
                        " (" .
                        $registration_no .
                        ") to " .
                        $class_name .
                        ".";


                    $notification_sql = "
                        INSERT INTO notifications
                        (
                            title,
                            message,
                            notification_type,
                            related_student_id,
                            related_class_id,
                            created_by
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            'student_added',
                            ?,
                            ?,
                            ?
                        )
                    ";

                    $notification_stmt =
                        mysqli_prepare(
                            $conn,
                            $notification_sql
                        );

                    if (!$notification_stmt) {

                        throw new Exception(
                            "Notification statement failed."
                        );
                    }

                    mysqli_stmt_bind_param(
                        $notification_stmt,
                        "ssiii",
                        $notification_title,
                        $notification_message,
                        $student_id,
                        $class_id,
                        $user_id
                    );

                    if (
                        !mysqli_stmt_execute(
                            $notification_stmt
                        )
                    ) {

                        mysqli_stmt_close(
                            $notification_stmt
                        );

                        throw new Exception(
                            "Notification haikuweza kutengenezwa."
                        );
                    }

                    mysqli_stmt_close(
                        $notification_stmt
                    );


                    /* =========================
                       COMMIT
                    ========================= */

                    mysqli_commit($conn);

                    $message =
                        "Student ameongezwa kwenye " .
                        $class_name .
                        " na Admin amepewa notification.";


                    /* =========================
                       CLEAR FORM
                    ========================= */

                    $registration_no = "";
                    $first_name = "";
                    $middle_name = "";
                    $last_name = "";
                    $gender = "";
                    $date_of_birth = "";

                } catch (Exception $e) {

                    mysqli_rollback($conn);

                    $error =
                        $e->getMessage();
                }
            }

            mysqli_stmt_close(
                $check_student_stmt
            );
        }
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
        Add Student - KAPONDA
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
            Teacher Panel
        </p>

    </div>

    <div>

        <?php
        echo htmlspecialchars(
            $teacher["teacher_name"]
        );
        ?>

    </div>

</div>


<!-- =========================
     LAYOUT
========================= -->

<div class="layout">

    <?php include "menu.php"; ?>


    <!-- =========================
         CONTENT
    ========================= -->

    <div class="content">

        <h2 class="page-title">
            Add Student
        </h2>


        <!-- =========================
             CLASS INFORMATION
        ========================= -->

        <div class="cards">

            <div class="card">

                <h3>
                    Teacher
                </h3>

                <div style="font-size:18px;font-weight:bold;">

                    <?php
                    echo htmlspecialchars(
                        $teacher["teacher_name"]
                    );
                    ?>

                </div>

            </div>


            <div class="card">

                <h3>
                    Class
                </h3>

                <div class="number">

                    <?php
                    echo htmlspecialchars(
                        $class_name
                    );
                    ?>

                </div>

            </div>


            <div class="card">

                <h3>
                    Academic Year
                </h3>

                <div class="number">

                    <?php
                    echo htmlspecialchars(
                        $academic_year["year_name"]
                    );
                    ?>

                </div>

            </div>

        </div>


        <!-- =========================
             ALERTS
        ========================= -->

        <?php if ($message !== ""): ?>

            <div class="alert alert-success">

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $message
                    );
                    ?>

                </strong>

            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $error
                    );
                    ?>

                </strong>

            </div>

        <?php endif; ?>


        <!-- =========================
             ADD STUDENT FORM
        ========================= -->

        <div class="panel">

            <h3>
                Student Information
            </h3>

            <form method="POST">

                <div class="form-grid">

                    <!-- REGISTRATION NUMBER -->

                    <div class="form-group">

                        <label>
                            Registration Number
                        </label>

                        <input
                            type="text"
                            name="registration_no"
                            value="<?php
                            echo htmlspecialchars(
                                $registration_no
                            );
                            ?>"
                            required
                        >

                    </div>


                    <!-- FIRST NAME -->

                    <div class="form-group">

                        <label>
                            First Name
                        </label>

                        <input
                            type="text"
                            name="first_name"
                            value="<?php
                            echo htmlspecialchars(
                                $first_name
                            );
                            ?>"
                            required
                        >

                    </div>


                    <!-- MIDDLE NAME -->

                    <div class="form-group">

                        <label>
                            Middle Name
                        </label>

                        <input
                            type="text"
                            name="middle_name"
                            value="<?php
                            echo htmlspecialchars(
                                $middle_name
                            );
                            ?>"
                        >

                    </div>


                    <!-- LAST NAME -->

                    <div class="form-group">

                        <label>
                            Last Name
                        </label>

                        <input
                            type="text"
                            name="last_name"
                            value="<?php
                            echo htmlspecialchars(
                                $last_name
                            );
                            ?>"
                            required
                        >

                    </div>


                    <!-- GENDER -->

                    <div class="form-group">

                        <label>
                            Gender
                        </label>

                        <select
                            name="gender"
                            required
                        >

                            <option value="">
                                Select Gender
                            </option>

                            <option
                                value="Male"
                                <?php
                                if ($gender === "Male") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                <?php
                                if ($gender === "Female") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Female
                            </option>

                        </select>

                    </div>


                    <!-- DATE OF BIRTH -->

                    <div class="form-group">

                        <label>
                            Date of Birth
                        </label>

                        <input
                            type="date"
                            name="date_of_birth"
                            value="<?php
                            echo htmlspecialchars(
                                $date_of_birth
                            );
                            ?>"
                        >

                    </div>

                </div>


                <!-- =========================
                     BUTTONS
                ========================= -->

                <div class="actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        ➕ Add Student
                    </button>


                    <a
                        href="view_class.php?class_id=<?php
                        echo $class_id;
                        ?>"
                        class="btn btn-secondary"
                    >
                        ← Back to Class
                    </a>

                </div>

            </form>

        </div>


        <!-- =========================
             INFORMATION
        ========================= -->

        <div class="panel">

            <h3>
                Important Information
            </h3>

            <ul>

                <li>
                    Ni <strong>Class Teacher</strong> pekee
                    anayeruhusiwa kuongeza student.
                </li>

                <li>
                    Student ata-enrolliwa moja kwa moja
                    kwenye <strong><?php echo htmlspecialchars($class_name); ?></strong>.
                </li>

                <li>
                    Academic Year inayotumika ni
                    <strong><?php echo htmlspecialchars($academic_year["year_name"]); ?></strong>.
                </li>

                <li>
                    Admin atapata notification baada ya
                    student kuongezwa successfully.
                </li>

                <li>
                    Student, enrollment na notification
                    vina-save kwa transaction moja.
                </li>

            </ul>

        </div>

    </div>

</div>

</body>

</html>