function register(){

    let name = document.getElementById("fullname").value;
    let email = document.getElementById("email").value;
    let email = document.getElementById("password").value;
    let phone = document.getElementById("phone").value;

    if(name === ""){
        alert("Please Enter Your Full Name!");
        return false;
    }

    if(password.length < 4){
        alert("Password must over 4 characters!");
        return false;
    }

    if(!/^01\d{8}$/.test(phone)){
        alert("Please Enter 10-Digit Phone Number!");
        return false;
    }

    return true;

}