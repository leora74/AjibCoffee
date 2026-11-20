
<?php
session_start();
require_once __DIR__ . "/conn.php";

if (isset($_SESSION["id_admin"])) {
    header("Location: ../HTML/dashboard_admin.html");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = mysqli_prepare(
        $connection,
        "SELECT id_admin, username_admin, email_admin, password
         FROM admin
         WHERE email_admin = ?
         LIMIT 1"
    );

    if (!$stmt) {
        die("Query gagal: " . mysqli_error($connection));
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($result);

    if ($admin) {
        // Mendukung password hash maupun teks biasa.
        // Setelah berhasil, password teks biasa diubah menjadi hash.
        $passwordValid = password_verify(
            $password,
            $admin["password"]
        );

        if (
            !$passwordValid &&
            hash_equals((string) $admin["password"], $password)
        ) {
            $passwordValid = true;

            $hashBaru = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $update = mysqli_prepare(
                $connection,
                "UPDATE admin SET password = ? WHERE id_admin = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "si",
                $hashBaru,
                $admin["id_admin"]
            );

            mysqli_stmt_execute($update);
            mysqli_stmt_close($update);
        }

        if ($passwordValid) {
            session_regenerate_id(true);

            $_SESSION["id_admin"] = $admin["id_admin"];
            $_SESSION["username_admin"] = $admin["username_admin"];

            mysqli_stmt_close($stmt);

            header("Location: ../HTML/dashboard_admin.html");
            exit;
        }
    }

    $error = "Email atau password salah.";
    mysqli_stmt_close($stmt);
}
?>


