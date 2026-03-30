<?php
// ... after your DB connection ...

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $date = $_POST['appointment_date']; // e.g., '2026-03-10'
    $time = $_POST['appointment_time']; // e.g., '14:30'
    $service = $_POST['service_name'];

    // Combine Date and Time into one string
    $appointment_datetime = $date . ' ' . $time;
    
    // Create timestamps for comparison
    $appointment_timestamp = strtotime($appointment_datetime);
    $current_timestamp = time();
    
    // Calculate the difference in seconds, then convert to hours
    $seconds_diff = $appointment_timestamp - $current_timestamp;
    $hours_diff = $seconds_diff / 3600;

    // VALIDATION: Check if it's less than 16 hours away
    if ($hours_diff < 16) {
        // Stop the process and alert the user
        echo "<script>
                alert('Incomplete lead time. Appointments must be reserved at least 16 hours in advance.');
                window.history.back();
              </script>";
        exit();
    }

    // ... Proceed with your existing INSERT INTO appointments query ...
}
?>