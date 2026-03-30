<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service = $_POST['service'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $user_id = 1; // Temporary: In a real system, get this from session $_SESSION['user_id']

    $sql = "INSERT INTO appointments (user_id, service_name, appointment_date, appointment_time) 
            VALUES ('$user_id', '$service', '$date', '$time')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Booking Successful!'); window.location.href='index.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>