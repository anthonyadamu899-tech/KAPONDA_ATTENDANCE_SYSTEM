<?php

session_start();
require_once "../config/database.php";

/* ==============================
   ADMIN CHECK
================================ */

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}


/* ==============================
   CHECK CLASS ID
================================ */

if (
    !isset($_GET["class_id"]) ||
    !is_numeric($_GET["class_id"])
) {
    die("Class haijachaguliwa.");
}

$class_id = (int) $_GET["class_id"];

$message = "";
$error = "";


/* ==============================
   GET CLASS
================================ */

$sql = "
    SELECT
        class_id,
        class_name,
        status,
        created_at
    FROM classes
    WHERE class_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $class_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$class = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$class) {
    die("Class haipo.");
}


/* ==============================
   UPDATE CLASS
================================ */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $class_name = "";
    $status = "";

    if (isset($_POST["class_name"])) {
        $class_name = trim($_POST["class_name"]);
    }

    if (isset($_POST["status"])) {
        $status = trim($_POST["status"]);
    }


    /* ==============================
       VALIDATION
    ================================= */

    if ($class_name === "") {

        $error = "Tafadhali weka jina la class.";

    } elseif (
        $status !== "active" &&
        $status !== "inactive"
    ) {

        $error = "Status si sahihi.";

    } else {


        /* ==============================
           CHECK DUPLICATE CLASS NAME
        ================================= */

        $check_sql = "
            SELECT class_id
            FROM classes
            WHERE class_name = ?
            AND class_id != ?
        ";

        $check_stmt = mysqli_prepare(
            $conn,
            $check_sql
        );

        if (!$check_stmt) {

            $error =
                "Database error: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "si",
                $class_name,
                $class_id
            );

            mysqli_stmt_execute($check_stmt);

            $check_result = mysqli_stmt_get_result(
                $check_stmt
            );


            if (
                mysqli_num_rows(
                    $check_result
                ) > 0
            ) {

                $error =
                    "Class yenye jina hilo tayari ipo.";

            } else {


                /* ==============================
                   UPDATE CLASS
                ================================= */

                $update_sql = "
                    UPDATE classes
                    SET
                        class_name = ?,
                        status = ?
                    WHERE class_id = ?
                ";

                $update_stmt = mysqli_prepare(
                    $conn,
                    $update_sql
                );

                if (!$update_stmt) {

                    $error =
                        "Database error: " .
                        mysqli_error($conn);

                } else {

                    mysqli_stmt_bind_param(
                        $update_stmt,
                        "ssi",
                        $class_name,
                        $status,
                        $class_id
                    );


                    if (
                        mysqli_stmt_execute(
                            $update_stmt
                        )
                    ) {

                        $message =
                            "Class imebadilishwa kikamilifu.";

                        /*
                        Update displayed information
                        */

                        $class["class_name"] =
                            $class_name;

                        $class["status"] =
                            $status;

                    } else {

                        $error =
                            "Class haikuweza kubadilishwa: " .
                            mysqli_error($conn);
                    }

                    mysqli_stmt_close(
                        $update_stmt
                    );
                }
            }

            mysqli_stmt_close(
                $check_stmt
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
    Edit Class - KAPONDA
</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css"
>

</head>

<body>


<!-- ==============================
     HEADER
================================ -->

<div class="header">

    <div>

        <h1>
            KAPONDA EDUCATION ACADEMY
        </h1>

        <p>
            Admin Panel - Edit Class
        </p>

    </div>


    <div>

        <a
            href="../logout.php"
            class="btn btn-danger"
        >
            Logout
        </a>

    </div>

</div>


<!-- ==============================
     LAYOUT
================================ -->

<div class="layout">


    <!-- ==========================
         SIDEBAR
    =========================== -->

    <?php include "menu.php"; ?>


    <!-- ==========================
         CONTENT
    =========================== -->

    <div class="content">

        <h2 class="page-title">
            Edit Class
        </h2>


        <!-- ==========================
             SUCCESS MESSAGE
        =========================== -->

        <?php if ($message !== ""): ?>

            <div class="alert alert-success">

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- ==========================
             ERROR MESSAGE
        =========================== -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars(
                    $error
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- ==========================
             CLASS INFORMATION
        =========================== -->

        <div class="panel">

            <h3>
                Class Information
            </h3>

            <div class="form-grid">

                <div>

                    <strong>
                        Class ID
                    </strong>

                    <p>
                        <?php
                        echo (int) $class["class_id"];
                        ?>
                    </p>

                </div>


                <div>

                    <strong>
                        Created At
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $class["created_at"]
                        );
                        ?>
                    </p>

                </div>

            </div>

        </div>


        <!-- ==========================
             EDIT FORM
        =========================== -->

        <div class="panel">

            <h3>
                Update Class
            </h3>

            <form method="POST">


                <!-- CLASS NAME -->

                <div class="form-group">

                    <label>
                        Class Name
                    </label>

                    <input
                        type="text"
                        name="class_name"
                        value="<?php
                        echo htmlspecialchars(
                            $class["class_name"]
                        );
                        ?>"
                        required
                    >

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select
                        name="status"
                        required
                    >

                        <option
                            value="active"
                            <?php

                            if (
                                $class["status"]
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
                                $class["status"]
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


                <!-- BUTTONS -->

                <div class="actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        💾 Save Changes
                    </button>


                    <a
                        href="manage_classes.php"
                        class="btn btn-secondary"
                    >
                        ← Back to Classes
                    </a>

                </div>

            </form>

        </div>


        <!-- ==========================
             IMPORTANT INFORMATION
        =========================== -->

        <div class="panel">

            <h3>
                Important Information
            </h3>

            <p>
                Class name lazima iwe unique.
            </p>

            <p>
                Ukiweka class kuwa
                <strong>Inactive</strong>,
                haitapatikana kwenye sehemu zinazochagua
                active classes.
            </p>

            <p>
                Kubadilisha jina la class hakufuti
                students, teachers au attendance records
                zinazohusiana na class hiyo.
            </p>

        </div>


        <!-- BACK BUTTON -->

        <a
            href="manage_classes.php"
            class="btn btn-secondary"
        >
            ← Back to Class Management
        </a>

    </div>

</div>

</body>

</html>