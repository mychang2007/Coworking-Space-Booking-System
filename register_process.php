<?php
include 'db_connect.php';

if (isset($_POST['register_btn'])){
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password']; 

    $check_sql = "SELECT email FROM customer WHERE email = '$email'";
    $check_result = mysqli_query($conn,$check_sql);

    if (mysqli_num_rows($check_result)>0){
        echo "<script> 
                alert('Error: This email is already registered!'); 
                window.location.href='register.html';
              </script>";
    }else{
        $insert_sql = "INSERT INTO customer (fullname,email,password,phone) 
                       VALUES ('$fullname','$email','$password','$phone')";

        if (mysqli_query($conn,$insert_sql)){
            echo "<script>
                    alert('Registration successful! You can now login.'); 
                    window.location.href='login.html';
                  </script>";
        }else{
            echo "Error: ".mysqli_error($conn);
        }
    }
}
mysqli_close($conn);
?>