<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['customer_id'])){
    echo "<script>
            alert('Error: Please login first to make a booking.');
            window.location.href='login.html';
          </script>";
    exit(); 
}

if (isset($_POST['book_btn'])){
    $customer_id = $_SESSION['customer_id'];
    $workspace_id = $_POST['workspace_id'];
    $booking_date = $_POST['booking_date'];

    //QR CODE
    $booking_token = uniqid('QR_'); 
    $status = 'Pending'; //DEFAULT PENDING STATUS
    $insert_booking = "INSERT INTO booking (customer_id,workspace_id,booking_date,booking_token,status) 
                       VALUES ('$customer_id','$workspace_id','$booking_date','$booking_token','$status')";

    if (mysqli_query($conn, $insert_booking)){
        echo "<script>
                alert('Booking Successful! Please check your QR Code in My Bookings.'); 
                window.location.href='my_booking.php'; 
              </script>";
    }else{
        echo "Error: ".mysqli_error($conn);
    }
}
mysqli_close($conn);
?>