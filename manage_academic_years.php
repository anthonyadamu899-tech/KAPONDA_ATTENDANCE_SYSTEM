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

/* =========================
   DEFAULT FORM VALUES
========================= */

$year_name = "";
$start_date = "";
$end_date = "";

/* =========================
   ADD ACADEMIC YEAR
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $year_name = trim($_POST["year_name"]);
    $start_date = trim($_POST["start_date"]);
    $end_date = trim($_POST["end_date"]);

    if (
        $year_name === "" ||
        $start_date === "" ||
        $end_date === ""
    ) {

        $error = "Tafadhali jaza taarifa zote.";

    } elseif ($start_date >= $end_date) {

        $error = "Start date lazima iwe kabla ya End date.";

    } else {

        /* =========================
           CHECK DUPLICATE YEAR
        ========================= */

        $check_sql = "
            SELECT academic_year_id
            FROM academic_years
            WHERE year_name = ?
            LIMIT 1
        ";

        $check_stmt = mysqli_prepare($conn, $check_sql);

        if (!$check_stmt) {

            $error = "Database error.";

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "s",
                $year_name
            );

            mysqli_stmt_execute($check_stmt);

            $check_result = mysqli_stmt_get_result($check_stmt);

            $existing = mysqli_fetch_assoc($check_result);

            mysqli_stmt_close($check_stmt);

            if ($existing) {

                $error = "Academic Year hii tayari ipo.";

            } else {

                /* =========================
                   TRANSACTION
                ========================= */

                mysqli_begin_transaction($conn);

                try {

                    /* =========================
                       CLOSE CURRENT ACTIVE YEAR
                    ========================= */

                    $close_sql = "
                        UPDATE academic_years
                        SET status = 'closed'
                        WHERE status = 'active'
                    ";

                    if (!mysqli_query($conn, $close_sql)) {

                        throw new Exception(
                            "Failed to close current year."
                        );
                    }

                    /* =========================
                       INSERT NEW YEAR
                    ========================= */

                    $insert_sql = "
                        INSERT INTO academic_years
                        (
                            year_name,
                            start_date,
                            end_date,
                            status
                        )
                        VALUES
                        (?, ?, ?, 'active')
                    ";

                    $insert_stmt = mysqli_prepare(
                        $conn,
                        $insert_sql
                    );

                    if (!$insert_stmt) {

                        throw new Exception(
                            "Academic year statement failed."
                        );
                    }

                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "sss",
                        $year_name,
                        $start_date,
                        $end_date
                    );

                    if (!mysqli_stmt_execute($insert_stmt)) {

                        mysqli_stmt_close($insert_stmt);

                        throw new Exception(
                            "Academic year insertion failed."
                        );
                    }

                    mysqli_stmt_close($insert_stmt);

                    /* =========================
                       COMMIT
                    ========================= */

                    mysqli_commit($conn);

                    $message =
                        "Academic Year imeongezwa na kuwa active.";

                    $year_name = "";
                    $start_date = "";
                    $end_date = "";

                } catch (Exception $e) {

                    mysqli_rollback($conn);

                    $error =
                        "Academic Year haijaongezwa. " .
                        $e->getMessage();
                }
            }
        }
    }
}

/* =========================
   GET ALL ACADEMIC YEARS
========================= */

$sql_years = "
    SELECT
        academic_year_id,
        year_name,
        start_date,
        end_date,
        status
    FROM academic_years
    ORDER BY start_date DESC
";

$result_years = mysqli_query($conn, $sql_years);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Academic Years - KAPONDA</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<!-- =========================
     HEADER
========================= -->

<div class="header">

    <div>
        <h1>KAPONDA EDUCATION ACADEMY</h1>

        <p>
            Academic Year Management
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
            Academic Years
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
             ADD ACADEMIC YEAR
        ========================= -->

        <div class="panel">

            <h3>
                Add Academic Year
            </h3>

            <p style="color:#666;">
                Adding a new academic year will automatically close
                the currently active academic year.
            </p>


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
                            placeholder="Example: 2027"
                            value="<?php
                            echo htmlspecialchars($year_name);
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
                            echo htmlspecialchars($start_date);
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
                            echo htmlspecialchars($end_date);
                            ?>"
                            required
                        >

                    </div>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    ➕ Add Academic Year
                </button>

            </form>

        </div>


        <!-- =========================
             ACADEMIC YEAR LIST
        ========================= -->

        <div class="panel">

            <h3>
                Academic Year List
            </h3>


            <?php if (
                $result_years &&
                mysqli_num_rows($result_years) > 0
            ): ?>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Year
                                </th>

                                <th>
                                    Start Date
                                </th>

                                <th>
                                    End Date
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        $number = 1;

                        while (
                            $year =
                            mysqli_fetch_assoc($result_years)
                        ):

                        ?>

                            <tr>

                                <!-- NUMBER -->

                                <td>

                                    <?php
                                    echo $number;
                                    ?>

                                </td>


                                <!-- YEAR -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $year["year_name"]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <!-- START DATE -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $year["start_date"]
                                    );
                                    ?>

                                </td>


                                <!-- END DATE -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $year["end_date"]
                                    );
                                    ?>

                                </td>


                                <!-- STATUS -->

                                <td>

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

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="edit_academic_year.php?academic_year_id=<?php
                                        echo (int)
                                            $year["academic_year_id"];
                                        ?>"
                                        class="btn btn-warning"
                                    >
                                        ✏ Edit
                                    </a>

                                </td>

                            </tr>

                        <?php

                            $number++;

                        endwhile;

                        ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>

                <div class="empty-state">

                    <p>
                        Hakuna Academic Year bado.
                    </p>

                </div>

            <?php endif; ?>

        </div>


        <!-- =========================
             INFORMATION
        ========================= -->

        <div class="panel">

            <h3>
                Academic Year Information
            </h3>

            <ul>

                <li>
                    Academic Year moja tu ndiyo inaweza kuwa
                    <strong>Active</strong> kwa wakati mmoja.
                </li>

                <li>
                    Unapoongeza Academic Year mpya, mwaka uliokuwa
                    Active utawekwa kuwa <strong>Closed</strong>.
                </li>

                <li>
                    Kufunga Academic Year hakufuti students,
                    enrollment au attendance records.
                </li>

                <li>
                    Taarifa za miaka iliyopita zitaendelea kubaki
                    kwa ajili ya history na reports.
                </li>

            </ul>

        </div>

    </div>

</div>

</body>

</html>