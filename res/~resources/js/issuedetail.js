document.addEventListener("DOMContentLoaded", function(){
    function updateProgressBar(ele, progress){
        if (ele === null || progress === null){
            return;
        }
        
        const eleValue = ele.querySelector("div.value");
        const eleLabel = ele.querySelector("span");
        
        const progressPerc = progress + "%";
        
        eleValue.setAttribute("data-value", progress);
        eleValue.style.width = progressPerc;
        eleLabel.innerText = progressPerc;
    }
    
    for(/** @type HTMLInputElement */ const task of document.querySelectorAll("input[name='Tasks[]']")){
        task.addEventListener("change", function(){
            const form = task.form;
            const data = new FormData(form);
            
            for(const field of form.elements){
                if (field.disabled && field.checked){
                    data.append(field.name, field.value);
                }
            }
            
            // noinspection JSCheckFunctionSignatures
            fetch(form.action, {
                method: "POST",
                body: new URLSearchParams(data),
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/x-www-form-urlencoded"
                }
            }).then(response => {
                task.disabled = false;
                
                if (response.ok){
                    response.json().then(data => {
                        if (data.length === 0){
                            alert("Error updating issue status.");
                            return;
                        }
                        
                        const label = document.querySelector("label[for='" + task.id + "']");
                        const text = label.textContent.trim();
                        label.innerHTML = task.checked ? "<del>" + text + "</del>" : text;
                        
                        document.querySelector("[data-title='Status'] > span").outerHTML = data["issue_status"];
                        updateProgressBar(document.querySelector("[data-title='Progress'] > .progress-bar"), data["issue_progress"]);
                        updateProgressBar(document.querySelector("#active-milestone > .progress-bar"), data["active_milestone"]);
                    });
                }
            });
            
            task.disabled = true;
        });
    }
    
    for(/** @type HTMLElement */ const ele of document.querySelectorAll("[data-task-submit]")){
        ele.style.display = "none";
    }
});
