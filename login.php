<?php

session_start();

require_once "config/database.php";

$error = "";


/* =========================
   LOGIN PROCESS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);


    if ($username === "" || $password === "") {

        $error = "Please enter username and password.";

    } else {

        $sql = "
            SELECT
                user_id,
                username,
                password,
                role,
                status
            FROM users
            WHERE username = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) {

                $user = mysqli_fetch_assoc($result);


                /* =========================
                   ACCOUNT STATUS
                ========================= */

                if ($user["status"] !== "active") {

                    $error = "Your account is inactive.";

                }


                /* =========================
                   PASSWORD CHECK
                ========================= */

                elseif ($password !== $user["password"]) {

                    $error = "Invalid username or password.";

                }


                /* =========================
                   ROLE CHECK
                ========================= */

                elseif (
                    $user["role"] !== "admin" &&
                    $user["role"] !== "teacher"
                ) {

                    $error = "Invalid user role.";

                }


                /* =========================
                   LOGIN SUCCESS
                ========================= */

                else {

                    /*
                     * Generate a new session ID
                     * after successful authentication.
                     */
                    session_regenerate_id(true);


                    $_SESSION["user_id"] =
                        (int) $user["user_id"];

                    $_SESSION["username"] =
                        $user["username"];

                    $_SESSION["role"] =
                        $user["role"];


                    /* =========================
                       REDIRECT BY ROLE
                    ========================= */

                    if ($user["role"] === "admin") {

                        header(
                            "Location: admin/dashboard.php"
                        );

                        exit();

                    }


                    if ($user["role"] === "teacher") {

                        header(
                            "Location: teacher/dashboard.php"
                        );

                        exit();
                    }
                }

            } else {

                $error = "Invalid username or password.";
            }

            mysqli_stmt_close($stmt);

        } else {

            $error = "Database error. Please try again.";
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
        Login - KAPONDA EDUCATION ACADEMY
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


<div class="login-page">

    <div class="login-box">
        <div class="login-logo">
    <img src="assets/images/logo.png" alt="Kaponda Education Academy Logo">
</div>

        <h1>
            KAPONDA EDUCATION ACADEMY
        </h1>


        <h2 style="text-align: center;">
            Attendance Management System
        </h2>


        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
        >

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter username"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter password"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >
                🔐 Login
            </button>

        </form>


        <p
            style="
                text-align:center;
                margin-top:20px;
                color:#777;
                font-size:13px;
            "
        >
            KAPONDA Education Academy<br>
            Attendance Management System
        </p>

    </div>

</div>


</body>

</html>