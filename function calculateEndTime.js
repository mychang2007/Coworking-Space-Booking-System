function calculateEndTime(){

    let startTime = 
    document.getElementById("start_time").value;

    if(startTime !==""){

        let parts = startTime.split(":");
        let hour = parseInt(parts[0]);
        let minute = parts[1];

        hour += 4;

        if(hour >= 24){
            hour -= 24;
        }

       document.getElementById("end_time").value = hour.toString().padStart(2,"0") + ":" + minute;

    }

}