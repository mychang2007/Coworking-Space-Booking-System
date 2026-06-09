function calculatePrice(){

    let worksoace = document.getElementById("workspace").value;

    let plan = document.getElementById("rental_plan").value;

    let price = 0;

    if(workspace ==="Single Room"){
        price = 10;
    }

    else if(workspace ==="Discussion Room"){
        price = 30;
    }

    else if(workspace ==="Private Office"){
        if(plan ==="Weekly"){
            price = 400;
        }
        else if(plan ==="Monthly"){
            price = 1000;
        }
        else if(plan ==="Yearly"){
            price = 11000;
        }
    }

    document.getElementById("price").innerHTML = "Total Price : RM" + price;

}