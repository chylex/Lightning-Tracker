"use strict";

if (!("open" in document.createElement("details"))){
    window.addEventListener("click", function(e){
        var target = e.target;
        
        if (target.tagName !== "SUMMARY"){
            return;
        }
        
        var details = target.parentElement;
        
        if (details.tagName !== "DETAILS"){
            return;
        }
        
        if (details.hasAttribute("open")){
            details.removeAttribute("open");
            details.open = false;
        }
        else{
            details.setAttribute("open", "");
            details.open = true;
        }
    });
}
