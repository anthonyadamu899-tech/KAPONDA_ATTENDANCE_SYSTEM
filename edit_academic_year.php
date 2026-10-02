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

$academic_year_id = 0;

/* =========================
   GET ACADEMIC YEAR ID
========================= */

if (
    isset($_GET["academic_year_id"]) &&
    is_numeric($_GET["academic_year_id"])
) {
    $academic_year_id = (int) $_GET["academic_year_id"];
}

if ($academic_year_id <= 0) {
    die("Invalid Academic Year ID.");
}


/* =========================
   GET ACADEMIC YEAR
========================= */

$sql = "
    SELECT
        academic_year_id,
        year_name,
        start_date,
        end_date,
        status
    FROM academic_years
    WHERE academic_year_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $academic_year_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$year = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$year) {
    die("Academic Year not found.");
}


/* =========================
   UPDATE ACADEMIC YEAR
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $year_name = trim($_POST["year_name"]);
    $start_date = trim($_POST["start_date"]);
    $end_date = trim($_POST["end_date"]);


    /* =========================
       VALIDATION
    ========================= */

    if (
        $year_name === "" ||
        $start_date === "" ||
        $end_date === ""
    ) {

        $error =
            "Tafadhali jaza taarifa zote.";

    } elseif ($start_date >= $end_date) {

        $error =
            "Start date lazima iwe kabla ya End date.";

    } else {

        /* =========================
           CHECK DUPLICATE YEAR
        ========================= */

        $check_sql = "
            SELECT academic_year_id
            FROM academic_years
            WHERE year_name = ?
            AND academic_year_id != ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare(
            $conn,
            $check_sql
        );

        if (!$check_stmt) {

            $error =
                "Database error.";

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "si",
                $year_name,
                $academic_year_id
            );

            mysqli_stmt_execute($check_stmt);

            $check_result =
                mysqli_stmt_get_result($check_stmt);

            $existing =
                mysqli_fetch_assoc($check_result);

            mysqli_stmt_close($check_stmt);


            if ($existing) {

                $error =
                    "Academic Year hiyo tayari ipo.";

            } else {

                /* =========================
                   UPDATE
                ========================= */

                $update_sql = "
                    UPDATE academic_years
                    SET
                        year_name = ?,
                        start_date = ?,
                        end_date = ?
                    WHERE academic_year_id = ?
                ";

                $update_stmt = mysqli_prepare(
                    $conn,
                    $update_sql
                );

                if (!$update_stmt) {

                    $error =
                        "Imeshindikana ku-update.";

                } else {

                    mysqli_stmt_bind_param(
                        $update_stmt,
                        "sssi",
                        $year_name,
                        $start_date,
                        $end_date,
                        $academic_year_id
                    );

                    if (
                        mysqli_stmt_execute(
                            $update_stmt
                        )
                    ) {

                        $message =
                            "Academic Year updated successfully.";

                        /* =========================
                           UPDATE DISPLAY VALUES
                        ========================= */

                        $year["year_name"] =
                            $year_name;

                        $year["start_date"] =
                            $start_date;

                        $year["end_date"] =
                            $end_date;

                    } else {

                        $error =
                            "Academic Year haija-update.";
                    }

                    mysqli_stmt_close($update_stmt);
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
        Edit Academic Year - KAPONDA
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
            Edit Academic Year
        </p>

    </div>

    <div>
        Admin Panel
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
            Edit Academic Year
        </h2>


        <!-- =========================
             ALERTS
        ========================= -->

        <?php if ($message !== ""): ?>

            <div class="alert alert-success">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <!-- =========================
             CURRENT STATUS
        ========================= -->

        <div class="panel">

            <h3>
                Current Academic Year Information
            </h3>


            <p>

                <strong>
                    Current Status:
                </strong>

                <?php

                if (
                    $year["status"] === "active"
                ):

                ?>

                    <span class="badge badge-active">
                        Active
                    </span>

                <?php else: ?>

                    <span class="badge badge-inactive">
                        Closed
                    </span>

                <?php endif; ?>

            </p>


            <p style="color:#666;">

                Hapa unaweza kubadilisha jina la mwaka na
                tarehe zake. Status ya Academic Year
                haibadilishwi kwenye ukurasa huu.

            </p>

        </div>


        <!-- =========================
             EDIT FORM
        ========================= -->

        <div class="panel">

            <h3>
                Academic Year Details
            </h3>


            <form method="POST">

                <div class="form-grid">

                    <!-- YEAR NAME -->

                    <div class="form-group">

                        <label>
                            Year Name
                        </label>

                        <input
                            type="text"
                            name="year_name"
                            value="<?php
                            echo htmlspecialchars(
                                $year["year_name"]
                            );
                            ?>"
                            required
                        >

                    </div>


                    <!-- START DATE -->

                    <div class="form-group">

                        <label>
                            Start Date
                        </label>

                        <input
                            type="date"
                            name="start_date"
                            value="<?php
                            echo htmlspecialchars(
                                $year["start_date"]
                            );
                            ?>"
                            required
                        >

                    </div>


                    <!-- END DATE -->

                    <div class="form-group">

                        <label>
                            End Date
                        </label>

                        <input
                            type="date"
                            name="end_date"
                            value="<?php
                            echo htmlspecialchars(
                                $year["end_date"]
                            );
                            ?>"
                            required
                        >

                    </div>

                </div>


                <div class="actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        💾 Save Changes
                    </button>


                    <a
                        href="manage_academic_years.php"
                        class="btn btn-secondary"
                    >
                        ← Back
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
                    Year Name lazima isiwe duplicate.
                </li>

                <li>
                    Start Date lazima iwe kabla ya End Date.
                </li>

                <li>
                    Kuhariri Academic Year hakufuti
                    students wala attendance records.
                </li>

                <li>
                    Status ya Academic Year inabaki kama ilivyo.
                </li>

                <li>
                    Kubadilisha Active/Closed year kunapaswa
                    kufanyika kwa utaratibu wa Academic Year
                    management, si hapa.
                </li>

            </ul>

        </div>

    </div>

</div>

</body>

</html>