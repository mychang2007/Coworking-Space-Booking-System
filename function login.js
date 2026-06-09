function login(){

    let email = document.getElementById("email").value;
    let password = document.getElementById("password").value;

    if(email ==="" || password ===""){
        alert("Please Enter Your Email & Password!");
        return false;
    }

    return true;
    
}