document.addEventListener("DOMContentLoaded", function(){
    const months = [ "Jan", "Feb", "Mar",
                     "Apr", "May", "Jun",
                     "Jul", "Aug", "Sep",
                     "Oct", "Nov", "Dec" ];
    
    function pad(n){
        return n.toString().padStart(2, "0");
    }
    
    for(/** @type HTMLElement */ const time of document.getElementsByTagName("time")){
        const type = time.getAttribute("data-kind");
        const date = new Date(Date.parse(time.getAttribute("datetime")));
        
        const datePart = pad(date.getDate()) + " " + months[date.getMonth()] + " " + date.getFullYear();
        
        if (type === "date"){
            time.innerText = datePart;
        }
        else if (type === "datetime"){
            time.innerText = datePart + ", " + pad(date.getHours()) + ":" + pad(date.getMinutes());
        }
    }
});
