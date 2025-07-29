<?php
include 'csrf.php';
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        $name = htmlspecialchars(trim($_POST['name']));
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $message = htmlspecialchars(trim($_POST['message']));
        if (!$name || !$email || !$message) {
            $error = 'All fields are required and email must be valid.';
        } else {
            // You can replace this with PHPMailer or your own mail logic
            $to = 'support@example.com';
            $subject = 'Contact Form Submission';
            $body = "Name: $name\nEmail: $email\nMessage: $message";
            $headers = "From: $email";
            if (mail($to, $subject, $body, $headers)) {
                $success = 'Thank you for contacting us! We will get back to you soon.';
            } else {
                $error = 'There was an error sending your message. Please try again later.';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact us - Project Management Website">
    <meta name="keywords" content="contact, support, project, management, website">
    <meta name="author" content="Your Name">
    <title>Contact Us</title>
    <link rel="stylesheet" href="common-styling.css">
</head>
<body>
<?php
$breadcrumbs = [
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Contact Us']
];
include 'sidebar-bootstrap-main/breadcrumb.php';
?>
<div class="container" style="max-width: 600px; margin: 2rem auto;">
    <h1>Contact Us</h1>
    <?php if ($success): ?>
        <div style="color: green;"> <?php echo $success; ?> </div>
    <?php elseif ($error): ?>
        <div style="color: red;"> <?php echo $error; ?> </div>
    <?php endif; ?>
    <form method="POST" action="" aria-label="Contact form">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div style="margin-bottom:1rem;">
            <label for="name">Name:</label><br>
            <input type="text" id="name" name="name" required style="width:100%;">
        </div>
        <div style="margin-bottom:1rem;">
            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" required style="width:100%;">
        </div>
        <div style="margin-bottom:1rem;">
            <label for="message">Message:</label><br>
            <textarea id="message" name="message" rows="5" required style="width:100%;"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send Message</button>
    </form>
</div>
</body>
</html> 