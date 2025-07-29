<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $redirect);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin Dashboard - Project Management Website">
    <meta name="keywords" content="admin, dashboard, project, management, student, faculty">
    <meta name="author" content="Your Name">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../common-styling.css">
    <!-- ... other links ... -->
</head>
<body>
<?php
$breadcrumbs = [
    ['label' => 'Dashboard']
];
include '../sidebar-bootstrap-main/breadcrumb.php';
?>
<form class="form-inline my-2 my-lg-0" method="GET" action="search.php" style="margin-bottom:1rem;">
    <input class="form-control mr-sm-2" type="search" name="q" placeholder="Search..." aria-label="Search">
    <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
</form>
<!-- Example image with alt text -->
<img src="../photo/avatar.png" alt="Admin Avatar" style="display:none;" /> 