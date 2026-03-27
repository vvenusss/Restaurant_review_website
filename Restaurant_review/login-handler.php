<?php
session_start();
require_once __DIR__ . '/includes/restaurant-db.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin-dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    $errorMessage = '';
    $connection = getDatabaseConnection($errorMessage);

    if ($connection) {
        $stmt = $connection->prepare('SELECT idusers, name, email, userType FROM users WHERE email = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($userId, $userName, $userEmail, $userRole);
            $userFound = $stmt->fetch();
            $stmt->close();

            if ($userFound) {
                $_SESSION['idusers'] = (int) $userId;
                $_SESSION['email'] = $userEmail;
                $_SESSION['name'] = $userName;
                $_SESSION['role'] = $userRole;

                $connection->close();
                if ($_SESSION['role'] === 'admin') {
                    header('Location: admin-dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            }
        }

        $connection->close();
    }
}

header('Location: login.php');
exit();