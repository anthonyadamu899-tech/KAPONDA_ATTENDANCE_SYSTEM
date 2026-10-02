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
   MARK NOTIFICATION AS READ
========================= */

if (
    isset($_GET["read"]) &&
    is_numeric($_GET["read"])
) {

    $notification_id = (int) $_GET["read"];

    $update_sql = "
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = ?
    ";

    $update_stmt = mysqli_prepare(
        $conn,
        $update_sql
    );

    if ($update_stmt) {

        mysqli_stmt_bind_param(
            $update_stmt,
            "i",
            $notification_id
        );

        if (mysqli_stmt_execute($update_stmt)) {

            mysqli_stmt_close($update_stmt);

            header("Location: notifications.php");
            exit();

        } else {

            $error =
                "Notification haijaweza ku-mark as read.";

            mysqli_stmt_close($update_stmt);
        }

    } else {

        $error =
            "Database error.";
    }
}


/* =========================
   GET NOTIFICATIONS
========================= */

$sql = "
    SELECT
        n.notification_id,
        n.title,
        n.message,
        n.notification_type,
        n.is_read,
        n.created_at,

        s.registration_no,
        s.first_name,
        s.middle_name,
        s.last_name,

        c.class_name

    FROM notifications n

    LEFT JOIN students s
        ON n.related_student_id = s.student_id

    LEFT JOIN classes c
        ON n.related_class_id = c.class_id

    ORDER BY n.created_at DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {

    die(
        "Database error: " .
        mysqli_error($conn)
    );
}


/* =========================
   COUNT NOTIFICATIONS
========================= */

$total_notifications = mysqli_num_rows($result);

$unread_notifications = 0;

mysqli_data_seek($result, 0);

while (
    $notification_count =
    mysqli_fetch_assoc($result)
) {

    if (
        (int) $notification_count["is_read"] === 0
    ) {

        $unread_notifications++;
    }
}

mysqli_data_seek($result, 0);

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
        Notifications - KAPONDA
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
            Admin Notifications
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
            Notifications
        </h2>


        <!-- =========================
             ALERT
        ========================= -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <!-- =========================
             SUMMARY CARDS
        ========================= -->

        <div class="cards">

            <div class="card">

                <h3>
                    Total Notifications
                </h3>

                <div class="number">
                    <?php
                    echo $total_notifications;
                    ?>
                </div>

            </div>


            <div class="card">

                <h3>
                    Unread Notifications
                </h3>

                <div class="number">
                    <?php
                    echo $unread_notifications;
                    ?>
                </div>

            </div>

        </div>


        <!-- =========================
             NOTIFICATION LIST
        ========================= -->

        <div class="panel">

            <h3>
                Notification List
            </h3>


            <?php if (
                $total_notifications > 0
            ): ?>


                <?php while (
                    $notification =
                    mysqli_fetch_assoc($result)
                ): ?>


                    <div
                        class="panel"
                        style="
                            border-left:
                            5px solid
                            <?php
                            if (
                                (int) $notification["is_read"] === 0
                            ) {
                                echo "#1f3c88";
                            } else {
                                echo "#cccccc";
                            }
                            ?>;
                            margin-bottom:15px;
                        "
                    >


                        <!-- =========================
                             TITLE
                        ========================= -->

                        <div
                            style="
                                display:flex;
                                justify-content:space-between;
                                align-items:center;
                                gap:10px;
                                flex-wrap:wrap;
                            "
                        >

                            <h3 style="margin-bottom:5px;">

                                <?php
                                echo htmlspecialchars(
                                    $notification["title"]
                                );
                                ?>

                            </h3>


                            <?php if (
                                (int) $notification["is_read"] === 0
                            ): ?>

                                <span class="badge badge-active">
                                    NEW
                                </span>

                            <?php else: ?>

                                <span class="badge">
                                    READ
                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- =========================
                             MESSAGE
                        ========================= -->

                        <p>

                            <?php
                            echo htmlspecialchars(
                                $notification["message"]
                            );
                            ?>

                        </p>


                        <!-- =========================
                             STUDENT
                        ========================= -->

                        <?php if (
                            $notification["registration_no"] !== null
                        ): ?>

                            <p>

                                <strong>
                                    Student:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $notification["registration_no"]
                                );
                                ?>

                                -

                                <?php

                                echo htmlspecialchars(
                                    $notification["first_name"]
                                );

                                if (
                                    $notification["middle_name"] !== ""
                                ) {

                                    echo " " .
                                        htmlspecialchars(
                                            $notification["middle_name"]
                                        );
                                }

                                echo " " .
                                    htmlspecialchars(
                                        $notification["last_name"]
                                    );

                                ?>

                            </p>

                        <?php endif; ?>


                        <!-- =========================
                             CLASS
                        ========================= -->

                        <?php if (
                            $notification["class_name"] !== null
                        ): ?>

                            <p>

                                <strong>
                                    Class:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $notification["class_name"]
                                );
                                ?>

                            </p>

                        <?php endif; ?>


                        <!-- =========================
                             TYPE
                        ========================= -->

                        <p>

                            <strong>
                                Type:
                            </strong>

                            <?php
                            echo htmlspecialchars(
                                $notification["notification_type"]
                            );
                            ?>

                        </p>


                        <!-- =========================
                             DATE
                        ========================= -->

                        <p>

                            <small>

                                Created:
                                <?php
                                echo htmlspecialchars(
                                    $notification["created_at"]
                                );
                                ?>

                            </small>

                        </p>


                        <!-- =========================
                             ACTION
                        ========================= -->

                        <?php if (
                            (int) $notification["is_read"] === 0
                        ): ?>

                            <a
                                href="notifications.php?read=<?php
                                echo (int)
                                    $notification["notification_id"];
                                ?>"
                                class="btn btn-primary"
                            >
                                ✓ Mark as Read
                            </a>

                        <?php else: ?>

                            <span
                                class="badge badge-inactive"
                            >
                                Already Read
                            </span>

                        <?php endif; ?>


                    </div>


                <?php endwhile; ?>


            <?php else: ?>

                <div class="empty-state">

                    <h3>
                        Hakuna Notifications
                    </h3>

                    <p>
                        Kwa sasa hakuna notification yoyote.
                    </p>

                </div>

            <?php endif; ?>

        </div>


        <!-- =========================
             INFORMATION
        ========================= -->

        <div class="panel">

            <h3>
                Notification Information
            </h3>

            <ul>

                <li>
                    Notifications zinaonekana hapa
                    wakati Class Teacher anaongeza student.
                </li>

                <li>
                    Notification mpya inaonekana kama
                    <strong>NEW</strong>.
                </li>

                <li>
                    Admin anaweza kubonyeza
                    <strong>Mark as Read</strong>.
                </li>

                <li>
                    Notification ikisha-read, inabaki kwenye
                    system kwa ajili ya history.
                </li>

            </ul>

        </div>


    </div>

</div>

</body>

</html>