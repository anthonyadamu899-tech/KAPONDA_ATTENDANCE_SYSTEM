<?php

session_start();
require_once "../config/database.php";

/* ==============================
   ADMIN SECURITY
================================ */

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";


/* ==============================
   ADD CLASS
================================ */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $class_name = "";

    if (isset($_POST["class_name"])) {
        $class_name = trim($_POST["class_name"]);
    }


    /* ==============================
       VALIDATION
    ================================= */

    if ($class_name === "") {

        $error = "Class name ni lazima.";

    } else {


        /* ==============================
           CHECK DUPLICATE
        ================================= */

        $check_sql = "
            SELECT class_id
            FROM classes
            WHERE class_name = ?
            LIMIT 1
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
                "s",
                $class_name
            );

            mysqli_stmt_execute($check_stmt);

            $check_result =
                mysqli_stmt_get_result(
                    $check_stmt
                );

            $existing =
                mysqli_fetch_assoc(
                    $check_result
                );

            mysqli_stmt_close(
                $check_stmt
            );


            if ($existing) {

                $error =
                    "Class hii tayari ipo.";

            } else {


                /* ==============================
                   INSERT CLASS
                ================================= */

                $insert_sql = "
                    INSERT INTO classes
                    (
                        class_name,
                        status
                    )
                    VALUES
                    (
                        ?,
                        'active'
                    )
                ";

                $insert_stmt = mysqli_prepare(
                    $conn,
                    $insert_sql
                );

                if (!$insert_stmt) {

                    $error =
                        "Imeshindikana kutengeneza class.";

                } else {

                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "s",
                        $class_name
                    );


                    if (
                        mysqli_stmt_execute(
                            $insert_stmt
                        )
                    ) {

                        $message =
                            "Class imeongezwa successfully.";

                        $class_name = "";

                    } else {

                        $error =
                            "Class haijaongezwa: " .
                            mysqli_error($conn);
                    }

                    mysqli_stmt_close(
                        $insert_stmt
                    );
                }
            }
        }
    }
}


/* ==============================
   GET CLASSES
================================ */

$sql_classes = "
    SELECT
        class_id,
        class_name,
        status,
        created_at
    FROM classes
    ORDER BY class_name ASC
";

$result_classes =
    mysqli_query(
        $conn,
        $sql_classes
    );

if (!$result_classes) {

    die(
        "Error loading classes: " .
        mysqli_error($conn)
    );
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
    Manage Classes - KAPONDA
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
            Admin Panel - Class Management
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
            Class Management
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
             ADD CLASS
        =========================== -->

        <div class="panel">

            <h3>
                Add New Class
            </h3>

            <form method="POST">

                <div class="form-group">

                    <label>
                        Class Name
                    </label>

                    <input
                        type="text"
                        name="class_name"
                        placeholder="Example: Class 8"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    ➕ Add Class
                </button>

            </form>

        </div>


        <!-- ==========================
             ALL CLASSES
        =========================== -->

        <div class="panel">

            <h3>
                All Classes
            </h3>

            <div class="table-container">

                <?php if (
                    mysqli_num_rows(
                        $result_classes
                    ) > 0
                ): ?>

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Class Name
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Created At
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
                            $class =
                            mysqli_fetch_assoc(
                                $result_classes
                            )
                        ):

                        ?>

                            <tr>


                                <!-- NUMBER -->

                                <td>

                                    <?php
                                    echo $number;
                                    ?>

                                </td>


                                <!-- CLASS NAME -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $class[
                                                "class_name"
                                            ]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php

                                    if (
                                        $class["status"]
                                        === "active"
                                    ):

                                    ?>

                                        <span
                                            class="badge badge-active"
                                        >
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge badge-inactive"
                                        >
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CREATED AT -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $class[
                                            "created_at"
                                        ]
                                    );
                                    ?>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <div class="actions">

                                        <a
                                            href="view_class.php?class_id=<?php
                                            echo (int)
                                            $class[
                                                "class_id"
                                            ];
                                            ?>"
                                            class="btn btn-primary"
                                        >
                                            👁 View Class
                                        </a>


                                        <a
                                            href="edit_class.php?class_id=<?php
                                            echo (int)
                                            $class[
                                                "class_id"
                                            ];
                                            ?>"
                                            class="btn btn-warning"
                                        >
                                            ✏️ Edit
                                        </a>

                                    </div>

                                </td>

                            </tr>


                        <?php

                            $number++;

                        endwhile;

                        ?>

                        </tbody>

                    </table>

                <?php else: ?>


                    <div class="empty-state">

                        <h3>
                            Hakuna class bado.
                        </h3>

                        <p>
                            Tumia form ya juu kuongeza
                            class mpya.
                        </p>

                    </div>


                <?php endif; ?>

            </div>

        </div>


        <!-- ==========================
             INFORMATION
        =========================== -->

        <div class="panel">

            <h3>
                Class Management Information
            </h3>

            <p>
                <strong>Add Class</strong> hutengeneza
                class mpya na default yake ni
                <strong>Active</strong>.
            </p>

            <p>
                <strong>View Class</strong> itaonyesha
                Class Teacher, Subject Teachers,
                students na attendance za class hiyo.
            </p>

            <p>
                <strong>Edit</strong> inaruhusu kubadilisha
                jina au status ya class bila kufuta
                students, teachers au attendance records.
            </p>

            <p>
                Class yenye jina linalofanana na iliyopo
                haiwezi kuongezwa.
            </p>

        </div>

    </div>

</div>

</body>

</html>