<?php
session_start(); //To let the system remember who log in
include 'db_connect.php';

if (isset($_POST['login_btn'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $login_sql = "SELECT * FROM customer WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn,$login_sql);

    if (mysqli_num_rows($result) == 1){
        $row = mysqli_fetch_assoc($result);
        $_SESSION['customer_id'] = $row['customer_id'];
        $_SESSION['fullname'] = $row['fullname'];
        $_SESSION['email'] = $row['email'];

        echo "<script>
                alert('Login successful! Welcome back, ".$row['fullname'] . ".'); 
                window.location.href='workspace_list.php'; 
              </script>";
    }else{
        echo "<script>
                alert('Error: Invalid Email or Password!'); 
                window.location.href='login.html';
              </script>";
    }
}
mysqli_close($conn);
?>