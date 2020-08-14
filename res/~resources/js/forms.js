document.addEventListener("DOMContentLoaded", function(){
    document.body.addEventListener("click", function(e){
        /** @var HTMLElement */
        const target = e.target;
        
        let ignoreMultiselect = target.closest("details.multiselect");
        
        if (ignoreMultiselect === null && target.tagName === "LABEL"){
            ignoreMultiselect = target.nextElementSibling;
        }
        
        for(/** @type HTMLDetailsElement */ const multiselect of document.querySelectorAll("details.multiselect")){
            if (multiselect !== ignoreMultiselect){
                multiselect.open = false;
            }
        }
    });
    
    for(/** @type HTMLDetailsElement */ const multiselect of document.querySelectorAll("details.multiselect")){
        const label = multiselect.previousElementSibling;
        
        label.addEventListener("click", function(){
            multiselect.open = !multiselect.open;
        });
        
        /**
         * @param {HTMLElement | Element} ele
         */
        function getText(ele){
            return ele.innerText || ele.textContent; // fallback for buggy browsers
        }
        
        const summary = multiselect.querySelector("summary");
        const summaryInitialText = getText(summary);
        
        const checkboxes = multiselect.getElementsByTagName("input");
        
        const updateCallback = function(){
            const checked = [];
            
            for(/** @type HTMLInputElement */ const cb of checkboxes){
                if (cb.checked){
                    // noinspection JSUnresolvedVariable
                    checked.push(getText(cb.nextElementSibling).trim());
                }
            }
            
            if (checked.length === 0){
                summary.innerText = summaryInitialText;
                summary.style.fontStyle = "italic";
            }
            else{
                summary.innerText = checked.join(", ");
                summary.style.fontStyle = "normal";
            }
        };
        
        const updateAll = function(){
            setTimeout(function(){
                for(/** @type HTMLInputElement */ const checkbox of checkboxes){
                    checkbox.dispatchEvent(new Event("change"));
                }
            }, 0);
        };
        
        for(/** @type HTMLInputElement */ const checkbox of checkboxes){
            checkbox.addEventListener("change", updateCallback);
            checkbox.dispatchEvent(new Event("change"));
            
            const form = checkbox.form;
            
            if (form !== null){
                form.addEventListener("reset", updateAll);
            }
        }
    }
    
    for(/** @type HTMLInputElement */ const range of document.querySelectorAll("input[type='number']")){
        const step = range.getAttribute("data-step");
        
        if (step !== null){
            range.addEventListener("focus", function(){
                range.step = step;
            });
            
            range.addEventListener("blur", function(){
                range.step = "";
            });
        }
    }
});
