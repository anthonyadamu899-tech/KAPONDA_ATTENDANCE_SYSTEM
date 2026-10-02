<?php

session_start();
require_once "../config/database.php";

/*
=====================================================
ADMIN ONLY
=====================================================
*/

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

/*
=====================================================
ADD TEACHER
=====================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $teacher_name = isset($_POST["teacher_name"])
        ? trim($_POST["teacher_name"])
        : "";

    $phone = isset($_POST["phone"])
        ? trim($_POST["phone"])
        : "";

    $email = isset($_POST["email"])
        ? trim($_POST["email"])
        : "";

    $username = isset($_POST["username"])
        ? trim($_POST["username"])
        : "";

    $password = isset($_POST["password"])
        ? trim($_POST["password"])
        : "";

    $confirm_password = isset($_POST["confirm_password"])
        ? trim($_POST["confirm_password"])
        : "";


    /*
    =====================================================
    VALIDATION
    =====================================================
    */

    if (
        $teacher_name === "" ||
        $username === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $error = "Tafadhali jaza sehemu zote muhimu.";

    } elseif ($password !== $confirm_password) {

        $error = "Password na Confirm Password hazifanani.";

    } elseif (strlen($password) < 6) {

        $error = "Password lazima iwe na angalau characters 6.";

    } else {

        /*
        =================================================
        CHECK USERNAME
        =================================================
        */

        $sql_check = "
            SELECT user_id
            FROM users
            WHERE username = ?
        ";

        $stmt_check = mysqli_prepare(
            $conn,
            $sql_check
        );

        mysqli_stmt_bind_param(
            $stmt_check,
            "s",
            $username
        );

        mysqli_stmt_execute($stmt_check);

        $result_check = mysqli_stmt_get_result(
            $stmt_check
        );

        if (mysqli_num_rows($result_check) > 0) {

            $error = "Username hiyo tayari ipo.";

            mysqli_stmt_close($stmt_check);

        } else {

            mysqli_stmt_close($stmt_check);

            /*
            =============================================
            START TRANSACTION
            =============================================
            */

            mysqli_begin_transaction($conn);

            try {

                /*
                =========================================
                1. CREATE USER ACCOUNT
                =========================================
                */

                $sql_user = "
                    INSERT INTO users
                    (
                        username,
                        password,
                        role,
                        status
                    )
                    VALUES
                    (?, ?, 'teacher', 'active')
                ";

                $stmt_user = mysqli_prepare(
                    $conn,
                    $sql_user
                );

                if (!$stmt_user) {
                    throw new Exception(
                        "User account haikuweza kutengenezwa."
                    );
                }

                mysqli_stmt_bind_param(
                    $stmt_user,
                    "ss",
                    $username,
                    $password
                );

                if (!mysqli_stmt_execute($stmt_user)) {

                    throw new Exception(
                        "User account haikuweza kutengenezwa."
                    );
                }

                $user_id = mysqli_insert_id($conn);

                mysqli_stmt_close($stmt_user);


                /*
                =========================================
                2. CREATE TEACHER RECORD
                =========================================
                */

                $sql_teacher = "
                    INSERT INTO teachers
                    (
                        user_id,
                        teacher_name,
                        phone,
                        email,
                        status
                    )
                    VALUES
                    (?, ?, ?, ?, 'active')
                ";

                $stmt_teacher = mysqli_prepare(
                    $conn,
                    $sql_teacher
                );

                if (!$stmt_teacher) {
                    throw new Exception(
                        "Teacher record haikuweza kutengenezwa."
                    );
                }

                mysqli_stmt_bind_param(
                    $stmt_teacher,
                    "isss",
                    $user_id,
                    $teacher_name,
                    $phone,
                    $email
                );

                if (!mysqli_stmt_execute($stmt_teacher)) {

                    throw new Exception(
                        "Teacher record haikuweza kutengenezwa."
                    );
                }

                mysqli_stmt_close($stmt_teacher);


                /*
                =========================================
                COMMIT
                =========================================
                */

                mysqli_commit($conn);

                $message =
                    "Teacher ameongezwa kikamilifu. " .
                    "Username: " .
                    htmlspecialchars($username);

            } catch (Exception $e) {

                /*
                =========================================
                ROLLBACK
                =========================================
                */

                mysqli_rollback($conn);

                $error = $e->getMessage();
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

    <title>Add Teacher - KAPONDA</title>

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
            Admin Panel - Add Teacher
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
            Add New Teacher
        </h2>


        <!-- SUCCESS MESSAGE -->

        <?php if ($message !== ""): ?>

            <div class="alert alert-success">

                <?php echo $message; ?>

            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <div class="panel">

            <h3>
                Teacher Information
            </h3>

            <form method="POST">

                <div class="form-grid">

                    <!-- TEACHER NAME -->

                    <div class="form-group">

                        <label for="teacher_name">
                            Teacher Name *
                        </label>

                        <input
                            type="text"
                            id="teacher_name"
                            name="teacher_name"
                            value="<?php
                            if (isset($_POST["teacher_name"])) {
                                echo htmlspecialchars(
                                    $_POST["teacher_name"]
                                );
                            }
                            ?>"
                            required
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label for="phone">
                            Phone
                        </label>

                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            value="<?php
                            if (isset($_POST["phone"])) {
                                echo htmlspecialchars(
                                    $_POST["phone"]
                                );
                            }
                            ?>"
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label for="email">
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php
                            if (isset($_POST["email"])) {
                                echo htmlspecialchars(
                                    $_POST["email"]
                                );
                            }
                            ?>"
                        >

                    </div>

                </div>


                <hr>


                <h3>
                    Login Account
                </h3>


                <p>
                    Teacher huyu atatumia taarifa hizi
                    kuingia kwenye mfumo.
                </p>


                <div class="form-grid">

                    <!-- USERNAME -->

                    <div class="form-group">

                        <label for="username">
                            Username *
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?php
                            if (isset($_POST["username"])) {
                                echo htmlspecialchars(
                                    $_POST["username"]
                                );
                            }
                            ?>"
                            required
                        >

                    </div>


                    <!-- PASSWORD -->

                    <div class="form-group">

                        <label for="password">
                            Password *
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                        >

                    </div>


                    <!-- CONFIRM PASSWORD -->

                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm Password *
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            required
                        >

                    </div>

                </div>


                <br>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    ➕ Add Teacher
                </button>


                <a
                    href="manage_teachers.php"
                    class="btn btn-secondary"
                >
                    Back to Teachers
                </a>

            </form>

        </div>


        <!-- INFORMATION -->

        <div class="panel">

            <h3>
                Important
            </h3>

            <ul>

                <li>
                    Username lazima iwe unique.
                </li>

                <li>
                    Password lazima iwe na angalau
                    characters 6.
                </li>

                <li>
                    Teacher mpya atawekwa kwenye status
                    <strong>active</strong>.
                </li>

                <li>
                    Role ya account itawekwa kuwa
                    <strong>teacher</strong>.
                </li>

                <li>
                    Baada ya teacher kuongezwa,
                    unaweza kumpeleka kwenye
                    <strong>Assign Teacher</strong>
                    kumpa class.
                </li>

            </ul>

        </div>

    </div>

</div>

</body>

</html>