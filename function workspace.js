function workspace(){
    let zone = document.getElementById("zone_id").value;

    let workspace = document.getElementById("workspace_name").value;

    if(workspace ===""){
        alert("Workspace name required!");
        return false;
    }

    return true;
    
}