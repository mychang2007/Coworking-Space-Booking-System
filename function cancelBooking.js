function cancelBooking(){

    let cancel = confirm("Are You Sure To Cancel This Booking?");

    if(cancel){
        window.location = "cancelBooking.php?id=" + IdleDeadline;
        
    }

}