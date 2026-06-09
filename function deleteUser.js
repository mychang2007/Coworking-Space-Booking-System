function deleteUser(id){

    if(confirm("Delete This User?")){
        window.location = "deleteUser.php?id" + id;

    }
    
}