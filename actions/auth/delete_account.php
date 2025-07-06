<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../Classes/SessionMessage.php';
require_once __DIR__ . '/../../Classes/Auth.php';
require_once __DIR__ . '/../../Classes/User.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    SessionMessage::set('danger', 'You must be logged in to delete your account.');
    header('Location: ' . BASE_PATH . '/templates/home.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user']['id'] ?? null;

    if (!$userId) {
        SessionMessage::set('danger', 'User ID not found in session.');
        header('Location: ' . BASE_PATH . '/templates/profile.php');
        exit();
    }

    $userObj = new User();
    $currentUser = $userObj->getById($userId);

    if (!$currentUser) {
        SessionMessage::set('danger', 'User account not found.');
        header('Location: ' . BASE_PATH . '/templates/profile.php');
        exit();
    }

    try {
        if ($userObj->delete($userId)) {
            $auth->logout(); 
            SessionMessage::set('success', 'Your account has been successfully deleted.');
            header('Location: ' . BASE_PATH . '/templates/home.php');
            exit();
        } else {
            SessionMessage::set('danger', 'Failed to delete account.');
            header('Location: ' . BASE_PATH . '/templates/profile.php');
            exit();
        }
    } catch (Exception $e) {
        error_log("Error deleting user account: " . $e->getMessage());
        SessionMessage::set('danger', 'An error occurred while deleting your account: ' . $e->getMessage());
        header('Location: ' . BASE_PATH . '/templates/profile.php');
        exit();
    }
} else {
    SessionMessage::set('danger', 'Invalid request method.');
    header('Location: ' . BASE_PATH . '/templates/profile.php');
    exit();
} 