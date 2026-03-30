<?php
include 'db.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    // 1. Fetch appointment and user details first
    $getInfo = "SELECT a.*, u.email, u.fullname 
                FROM appointments a 
                JOIN users u ON a.user_id = u.id 
                WHERE a.id = $id";
    $infoResult = $conn->query($getInfo);
    $data = $infoResult->fetch_assoc();

    // 2. Update the status in the database
    $sql = "UPDATE appointments SET status = '$status' WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        
        // 3. If confirmed, send the email
        if ($status == 'confirmed') {
            $to = $data['email'];
            $subject = "Your Appointment at Doctor EC is Confirmed!";
            
            // Lively HTML Email Template
            $message = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <div style='background: #ea580c; padding: 20px; text-align: center;'>
                    <h1 style='color: white;'>Doctor EC Dental Clinic</h1>
                </div>
                <div style='padding: 20px; border: 1px solid #eee;'>
                    <h2>Hello " . $data['fullname'] . ",</h2>
                    <p>Good news! Your appointment for <strong>" . $data['service_name'] . "</strong> has been confirmed.</p>
                    <p><strong>Date:</strong> " . $data['appointment_date'] . "<br>
                       <strong>Time:</strong> " . $data['appointment_time'] . "</p>
                    <p>Please arrive 15 minutes early. We look forward to seeing your bright smile!</p>
                </div>
            </body>
            </html>
            ";

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: <noreply@doctorec.com>" . "\r\n";

            mail($to, $subject, $message, $headers);
        }

        header("Location: admin.php?success=1");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>