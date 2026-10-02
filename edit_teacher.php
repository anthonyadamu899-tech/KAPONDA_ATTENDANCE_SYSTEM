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

$teacher_id = 0;

/* =========================
   GET TEACHER ID
========================= */

if (isset($_GET["teacher_id"]) && is_numeric($_GET["teacher_id"])) {
    $teacher_id = (int) $_GET["teacher_id"];
}

if ($teacher_id <= 0) {
    die("Invalid teacher ID.");
}

/* =========================
   GET TEACHER
========================= */

$sql = "
    SELECT
        t.teacher_id,
        t.user_id,
        t.teacher_name,
        t.phone,
        t.email,
        t.status AS teacher_status,
        u.username,
        u.status AS user_status
    FROM teachers t
    INNER JOIN users u
        ON t.user_id = u.user_id
    WHERE t.teacher_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $teacher_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$teacher = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$teacher) {
    die("Teacher not found.");
}

/* =========================
   FORM SUBMISSION
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $teacher_name = trim($_POST["teacher_name"]);
    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);
    $username = trim($_POST["username"]);
    $teacher_status = trim($_POST["teacher_status"]);
    $user_status = trim($_POST["user_status"]);

    /* =========================
       VALIDATION
    ========================= */

    if (
        $teacher_name === "" ||
        $username === ""
    ) {

        $error = "Teacher name na username ni lazima.";

    } elseif (
        $teacher_status !== "active" &&
        $teacher_status !== "inactive"
    ) {

        $error = "Teacher status si sahihi.";

    } elseif (
        $user_status !== "active" &&
        $user_status !== "inactive"
    ) {

        $error = "User status si sahihi.";

    } else {

        /* =========================
           CHECK USERNAME
        ========================= */

        $check_sql = "
            SELECT user_id
            FROM users
            WHERE username = ?
            AND user_id != ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare(
            $conn,
            $check_sql
        );

        if (!$check_stmt) {

            $error =
                "Imeshindikana kuangalia username.";

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "si",
                $username,
                $teacher["user_id"]
            );

            mysqli_stmt_execute($check_stmt);

            $check_result =
                mysqli_stmt_get_result(
                    $check_stmt
                );

            $existing_user =
                mysqli_fetch_assoc(
                    $check_result
                );

            mysqli_stmt_close($check_stmt);

            if ($existing_user) {

                $error =
                    "Username hiyo tayari inatumika.";

            } else {

                /* =========================
                   START TRANSACTION
                ========================= */

                mysqli_begin_transaction($conn);

                try {

                    /* =========================
                       UPDATE TEACHER
                    ========================= */

                    $teacher_sql = "
                        UPDATE teachers
                        SET
                            teacher_name = ?,
                            phone = ?,
                            email = ?,
                            status = ?
                        WHERE teacher_id = ?
                    ";

                    $teacher_stmt =
                        mysqli_prepare(
                            $conn,
                            $teacher_sql
                        );

                    if (!$teacher_stmt) {

                        throw new Exception(
                            "Teacher update failed."
                        );
                    }

                    mysqli_stmt_bind_param(
                        $teacher_stmt,
                        "ssssi",
                        $teacher_name,
                        $phone,
                        $email,
                        $teacher_status,
                        $teacher_id
                    );

                    if (
                        !mysqli_stmt_execute(
                            $teacher_stmt
                        )
                    ) {

                        mysqli_stmt_close(
                            $teacher_stmt
                        );

                        throw new Exception(
                            "Teacher update failed."
                        );
                    }

                    mysqli_stmt_close(
                        $teacher_stmt
                    );


                    /* =========================
                       UPDATE USER
                    ========================= */

                    $user_sql = "
                        UPDATE users
                        SET
                            username = ?,
                            status = ?
                        WHERE user_id = ?
                    ";

                    $user_stmt =
                        mysqli_prepare(
                            $conn,
                            $user_sql
                        );

                    if (!$user_stmt) {

                        throw new Exception(
                            "User update failed."
                        );
                    }

                    mysqli_stmt_bind_param(
                        $user_stmt,
                        "ssi",
                        $username,
                        $user_status,
                        $teacher["user_id"]
                    );

                    if (
                        !mysqli_stmt_execute(
                            $user_stmt
                        )
                    ) {

                        mysqli_stmt_close(
                            $user_stmt
                        );

                        throw new Exception(
                            "User update failed."
                        );
                    }

                    mysqli_stmt_close(
                        $user_stmt
                    );


                    /* =========================
                       COMMIT
                    ========================= */

                    mysqli_commit($conn);

                    $message =
                        "Teacher taarifa zimebadilishwa successfully.";


                    /* =========================
                       UPDATE DISPLAYED DATA
                    ========================= */

                    $teacher["teacher_name"] =
                        $teacher_name;

                    $teacher["phone"] =
                        $phone;

                    $teacher["email"] =
                        $email;

                    $teacher["username"] =
                        $username;

                    $teacher["teacher_status"] =
                        $teacher_status;

                    $teacher["user_status"] =
                        $user_status;

                } catch (Exception $e) {

                    mysqli_rollback($conn);

                    $error =
                        "Teacher hakubadilishwa. " .
                        $e->getMessage();
                }
            }
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
        Edit Teacher - KAPONDA
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
                ✏️ Edit Teacher
            </h1>

            <p>
                Badilisha taarifa za teacher na
                login account yake.
            </p>

        </div>


        <!-- =========================
             SUCCESS MESSAGE
        ========================== -->

        <?php if ($message !== ""): ?>

            <div class="alert alert-success">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <!-- =========================
             ERROR MESSAGE
        ========================== -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <!-- =========================
             EDIT FORM
        ========================== -->

        <div class="panel">

            <h2>
                Teacher Information
            </h2>

            <form method="POST">

                <div class="form-grid">

                    <!-- TEACHER NAME -->

                    <div class="form-group">

                        <label>
                            Teacher Name
                        </label>

                        <input
                            type="text"
                            name="teacher_name"
                            value="<?php
                            echo htmlspecialchars(
                                $teacher["teacher_name"]
                            );
                            ?>"
                            required
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label>
                            Phone
                        </label>

                        <input
                            type="text"
                            name="phone"
                            value="<?php
                            echo htmlspecialchars(
                                $teacher["phone"]
                            );
                            ?>"
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="<?php
                            echo htmlspecialchars(
                                $teacher["email"]
                            );
                            ?>"
                        >

                    </div>


                    <!-- USERNAME -->

                    <div class="form-group">

                        <label>
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            value="<?php
                            echo htmlspecialchars(
                                $teacher["username"]
                            );
                            ?>"
                            required
                        >

                    </div>


                    <!-- TEACHER STATUS -->

                    <div class="form-group">

                        <label>
                            Teacher Status
                        </label>

                        <select name="teacher_status">

                            <option
                                value="active"
                                <?php
                                if (
                                    $teacher["teacher_status"]
                                    === "active"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Active
                            </option>

                            <option
                                value="inactive"
                                <?php
                                if (
                                    $teacher["teacher_status"]
                                    === "inactive"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>


                    <!-- USER STATUS -->

                    <div class="form-group">

                        <label>
                            Login / User Status
                        </label>

                        <select name="user_status">

                            <option
                                value="active"
                                <?php
                                if (
                                    $teacher["user_status"]
                                    === "active"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Active
                            </option>

                            <option
                                value="inactive"
                                <?php
                                if (
                                    $teacher["user_status"]
                                    === "inactive"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>


                <!-- =========================
                     ACTION BUTTONS
                ========================== -->

                <div class="actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        💾 Save Changes
                    </button>

                    <a
                        href="manage_teachers.php"
                        class="btn btn-secondary"
                    >
                        ↩ Back to Teachers
                    </a>

                </div>

            </form>

        </div>


        <!-- =========================
             IMPORTANT INFORMATION
        ========================== -->

        <div class="panel">

            <h3>
                ℹ️ Important
            </h3>

            <p>
                Kubadilisha <strong>Teacher Status</strong>
                na <strong>Login/User Status</strong>
                ni vitu viwili tofauti.
            </p>

            <p>
                Assignment ya teacher kama
                <strong>Class Teacher</strong> au
                <strong>Subject Teacher</strong>
                haibadilishwi hapa.
            </p>

            <p>
                Ili kubadilisha assignment,
                tumia <strong>Manage Assignment</strong>
                kutoka kwenye Manage Teachers.
            </p>

        </div>

    </div>

</div>

</body>

</html>