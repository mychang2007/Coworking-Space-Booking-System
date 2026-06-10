function Booking(){

    let workspace = document.getElementById("workspace").value;

    if(workspace === ""){
        alert("Please select workspace");
        return false;
    }

    if(workspace === "Private Office"){

        let plan = document.getElementById("rental_plan").value;

        if(plan === ""){
            alert("Please select rental plan");
            return false;
        }
    }else{

    let date = document.getElementById("booking_date").value;
    let startTime = document.getElementById("start_time").value;

    if(date === ""){
        alert("Please Select Booking Date!");
        return false;
    }

    if(startTime === ""){
        alert("Please Select Start Time");
        return false;
    }
    }
    return true;

}