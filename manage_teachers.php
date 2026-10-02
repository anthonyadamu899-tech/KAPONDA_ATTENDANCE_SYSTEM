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

$error = "";

/* =========================
   GET ALL TEACHERS
========================= */

$sql = "
    SELECT
        t.teacher_id,
        t.teacher_name,
        t.phone,
        t.email,
        t.status AS teacher_status,

        u.user_id,
        u.username,
        u.status AS user_status,

        tc.teacher_class_id,
        tc.class_id,
        tc.assignment_role,
        tc.assigned_date,
        tc.status AS assignment_status,

        c.class_name

    FROM teachers t

    INNER JOIN users u
        ON t.user_id = u.user_id

    LEFT JOIN teacher_classes tc
        ON t.teacher_id = tc.teacher_id
        AND tc.status = 'active'

    LEFT JOIN classes c
        ON tc.class_id = c.class_id

    ORDER BY
        t.teacher_name ASC,
        c.class_name ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    $error = "Imeshindikana kupata taarifa za walimu.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Teachers - KAPONDA</title>

    <link rel="stylesheet" href="../assets/css/style.css">

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
                👨‍🏫 Manage Teachers
            </h1>

            <p>
                Manage teacher accounts, assignments and status.
            </p>

        </div>


        <!-- =========================
             ERROR
        ========================== -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <!-- =========================
             ACTION
        ========================== -->

        <div class="panel">

            <div class="actions">

                <a
                    href="add_teacher.php"
                    class="btn btn-primary"
                >
                    ➕ Add New Teacher
                </a>

                <a
                    href="assign_teacher.php"
                    class="btn btn-success"
                >
                    📚 Assign Teacher
                </a>

            </div>

        </div>


        <!-- =========================
             TEACHERS TABLE
        ========================== -->

        <div class="panel">

            <h2>
                Registered Teachers
            </h2>

            <?php if ($result && mysqli_num_rows($result) > 0): ?>

                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>Teacher Name</th>

                                <th>Username</th>

                                <th>Phone</th>

                                <th>Email</th>

                                <th>Class</th>

                                <th>Assignment</th>

                                <th>Teacher Status</th>

                                <th>Login Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php

                        $number = 1;

                        while ($teacher = mysqli_fetch_assoc($result)):

                        ?>

                            <tr>

                                <!-- NUMBER -->

                                <td>

                                    <?php
                                    echo $number;
                                    ?>

                                </td>


                                <!-- TEACHER NAME -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $teacher["teacher_name"]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <!-- USERNAME -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $teacher["username"]
                                    );
                                    ?>

                                </td>


                                <!-- PHONE -->

                                <td>

                                    <?php

                                    if (
                                        $teacher["phone"] !== null &&
                                        $teacher["phone"] !== ""
                                    ) {

                                        echo htmlspecialchars(
                                            $teacher["phone"]
                                        );

                                    } else {

                                        echo "-";

                                    }

                                    ?>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <?php

                                    if (
                                        $teacher["email"] !== null &&
                                        $teacher["email"] !== ""
                                    ) {

                                        echo htmlspecialchars(
                                            $teacher["email"]
                                        );

                                    } else {

                                        echo "-";

                                    }

                                    ?>

                                </td>


                                <!-- CLASS -->

                                <td>

                                    <?php

                                    if (
                                        $teacher["class_name"] !== null
                                    ) {

                                        echo htmlspecialchars(
                                            $teacher["class_name"]
                                        );

                                    } else {

                                        echo "Not Assigned";

                                    }

                                    ?>

                                </td>


                                <!-- ASSIGNMENT -->

                                <td>

                                    <?php

                                    if (
                                        $teacher["assignment_role"]
                                        === "class_teacher"
                                    ) {

                                        echo '<span class="badge badge-active">
                                                Class Teacher
                                              </span>';

                                    } elseif (
                                        $teacher["assignment_role"]
                                        === "subject_teacher"
                                    ) {

                                        echo '<span class="badge badge-permission">
                                                Subject Teacher
                                              </span>';

                                    } else {

                                        echo "Not Assigned";

                                    }

                                    ?>

                                </td>


                                <!-- TEACHER STATUS -->

                                <td>

                                    <?php

                                    if (
                                        $teacher["teacher_status"]
                                        === "active"
                                    ) {

                                        echo '<span class="badge badge-active">
                                                Active
                                              </span>';

                                    } else {

                                        echo '<span class="badge badge-inactive">
                                                Inactive
                                              </span>';

                                    }

                                    ?>

                                </td>


                                <!-- LOGIN STATUS -->

                                <td>

                                    <?php

                                    if (
                                        $teacher["user_status"]
                                        === "active"
                                    ) {

                                        echo '<span class="badge badge-active">
                                                Active
                                              </span>';

                                    } else {

                                        echo '<span class="badge badge-inactive">
                                                Inactive
                                              </span>';

                                    }

                                    ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="actions">

                                        <a
                                            href="edit_teacher.php?teacher_id=<?php
                                            echo (int) $teacher["teacher_id"];
                                            ?>"
                                            class="btn btn-primary"
                                        >
                                            ✏️ Edit
                                        </a>


                                        <?php if (
                                            $teacher["teacher_class_id"]
                                            !== null
                                        ): ?>

                                            <a
                                                href="edit_assignment.php?teacher_class_id=<?php
                                                echo (int)
                                                    $teacher["teacher_class_id"];
                                                ?>"
                                                class="btn btn-warning"
                                            >
                                                ⚙️ Assignment
                                            </a>

                                        <?php else: ?>

                                            <a
                                                href="assign_teacher.php"
                                                class="btn btn-success"
                                            >
                                                📚 Assign
                                            </a>

                                        <?php endif; ?>

                                    </div>

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

                    <h3>
                        No Teachers Found
                    </h3>

                    <p>
                        Hakuna teacher aliyesajiliwa bado.
                    </p>

                    <a
                        href="add_teacher.php"
                        class="btn btn-primary"
                    >
                        ➕ Add First Teacher
                    </a>

                </div>

            <?php endif; ?>

        </div>


        <!-- =========================
             TEACHER MANAGEMENT FLOW
        ========================== -->

        <div class="panel">

            <h2>
                Teacher Management Flow
            </h2>

            <div class="form-grid">

                <div>

                    <h3>1. Add Teacher</h3>

                    <p>
                        Tengeneza teacher account na
                        login credentials.
                    </p>

                </div>


                <div>

                    <h3>2. Assign Teacher</h3>

                    <p>
                        Muweke teacher kwenye class
                        kama Class Teacher au Subject Teacher.
                    </p>

                </div>


                <div>

                    <h3>3. Manage Assignment</h3>

                    <p>
                        Badilisha Class Teacher kuwa
                        Subject Teacher, au kinyume chake,
                        na pia unaweza kuondoa assignment.
                    </p>

                </div>


                <div>

                    <h3>4. Edit Teacher</h3>

                    <p>
                        Badilisha jina, simu, email,
                        username au status ya teacher.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>