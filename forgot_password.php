<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
include('connection/connection.php');

function is_strong_password($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format!'); window.history.back();</script>";
        exit;
    }

    // Check email existence
    $stmt = $con->prepare("
        SELECT 'student' as type, registration_no as id FROM student WHERE email = ? 
        UNION 
        SELECT 'faculty' as type, fid as id FROM faculty WHERE email = ? 
        UNION 
        SELECT 'admin' as type, id FROM adminlogin WHERE aemail = ?
    ");
    $stmt->bind_param("sss", $email, $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userType = $row['type'];
        $userId = $row['id'];
        
        // Generate a unique token
        $token = bin2hex(random_bytes(32)); // 64 character hexadecimal string
        
        // Set token expiration (e.g., 24 hours from now)
        $expiryTime = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Store the token in a password_reset_tokens table
        $insertTokenQuery = "INSERT INTO password_reset_tokens (user_type, user_id, token, expiry_time) 
                            VALUES (?, ?, ?, ?)";
        $insertStmt = $con->prepare($insertTokenQuery);
        $insertStmt->bind_param("siss", $userType, $userId, $token, $expiryTime);
        
        if ($insertStmt->execute()) {
            $mail = new PHPMailer(true);
            
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'mujprojectallocationsystem@gmail.com'; // Your Gmail
                $mail->Password = 'qicn zpdu mxgg pdri'; // Use App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS encryption
                $mail->Port = 587; // TLS port is 587

                $mail->setFrom('mujprojectallocationsystem@gmail.com', 'Admin');
                $mail->addAddress($email);

                // Embed the logo after initializing PHPMailer
                $mail->AddEmbeddedImage('../muj/photo/manipallogo.png', 'logo', 'manipallogo.png');

                $mail->isHTML(true);
                $mail->Subject = "Password Reset Request";

                // Generate the reset URL (change to your actual domain)
                $resetUrl = "http://192.168.1.111/muj/reset_password.php?token=" . urlencode($token);

                $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1'>
                    <title>Password Reset</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f4f4f4;
                            margin: 0;
                            padding: 0;
                        }
                        .container {
                            max-width: 480px;
                            margin: 20px auto;
                            background-color: #ffffff;
                            padding: 25px;
                            border-radius: 6px;
                            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                            text-align: center;
                        }
                        .logo {
                            width: 150px;
                            height:80px;
                            margin-bottom: 15px;
                        }
                        .header {
                            color: #1a237e;
                            font-size: 20px;
                            margin-bottom: 15px;
                        }
                        .content {
                            color: #424242;
                            font-size: 14px;
                            margin-bottom: 20px;
                            text-align: left;
                            margin-top:20px;
                        }
                        .button {
                            display: inline-block;
                            background-color:orange;
                            color: #ffffff !important;
                            padding: 12px 24px;
                            border-radius: 4px;
                            text-decoration: none;
                            font-weight: bold;
                            margin: 20px 0;
                        }
                        .note {
                            color: #757575;
                            font-size: 12px;
                            margin-top: 15px;
                        }
                        .footer {
                            color: #757575;
                            font-size: 12px;
                            text-align: center;
                            margin-top: 15px;
                            padding-top: 10px;
                            border-top: 1px solid #eeeeee;
                        }
                    </style>
                </head>
                <body>
                    <div class='container m-5'>
                        <img src='cid:logo' alt='Manipal University Jaipur' class='logo'>
                        <h2 class='header'>Password Reset Request</h2>
                        <div class='content'>
                            <p>Dear User,</p>
                            <p>We received a request to reset your password. Click the button below to create a new password:</p>
                            <div style='text-align: center;'>
                                <a href='{$resetUrl}' class='button'>Reset Password</a>
                            </div>
                            <p class='note'><strong>Note:</strong> This link will expire in 24 hours.</p>
                            <p>If you didn't request this password reset, please ignore this email or contact IT support if you have concerns.</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated email. Please do not reply.</p>
                            <p>© " . date('Y') . " Manipal University Jaipur | All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>";

                $mail->send();
                echo "<script>alert('Password reset link sent to your email!'); window.location.href='index.php';</script>";
            } catch (Exception $e) {
                error_log("Mail Error: " . $mail->ErrorInfo);
                echo "<script>alert('Email sending failed. Check your email configuration.'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Token generation failed.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Email not found!'); window.history.back();</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Manipal University Jaipur</title>

  
<!------------------------------------ boostrap 5 files ---------------------->
<link rel="stylesheet" href="../muj/bootstrap-5.0.2-dist/css/bootstrap.min.css">
<script src="../muj/bootstrap-5.0.2-dist/js/bootstrap.min.js"></script>

    <!-- Favicon -->
    <link rel="icon" href="photo/muj-title-logo.png" type="image/png">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  
    <!-- Icon Libraries -->
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/feather-icons/dist/feather.min.css">


    <!-- Favicon -->
    <link rel="icon" href="../photo/muj-title-logo.png" type="image/png">
 

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .card {
            width: 100%;
            max-width: 400px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #1a237e;
            color: white;
            text-align: center;
            padding: 1.5rem 1rem;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .logo {
            max-width: 250px;
        }
        .btn-primary {
            background-color: #1a237e;
            border-color: #1a237e;
            transition: 0.3s;
        }
        .btn-primary:hover {
            background-color: #0e1859;
            border-color: #0e1859;
        }
    </style>
</head>
<body>
    <div class="card p-3">
        <div class="card-header">
            <img src="photo/manipallogo.png" alt="Manipal University Jaipur" class="logo mb-2">
            <h5 class="mb-0">Forgot Password</h5>
        </div>
        <div class="card-body">
            <div class="text-center">
                <i class="fas fa-key fa-2x text-muted mb-3"></i>
                <p class="text-muted">Enter your registered Outlook email to receive a password reset link.</p>
            </div>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@outlook.com" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-paper-plane me-2"></i> Send Reset Link
                </button>
            </form>
        </div>
        <div class="card-footer text-center bg-white border-0">
            <a href="../muj/index.php" class="text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>