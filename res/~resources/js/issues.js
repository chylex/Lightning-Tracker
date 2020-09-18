document.addEventListener("DOMContentLoaded", function(){
    const newIssueLink = document.getElementById("New-Issue");
    
    const issueTypes = [
        [
            "Feature",
            "/feature",
            "icon-hammer",
            "#009d00"
        ], [
            "Enhancement",
            "/enhancement",
            "icon-wand",
            "#a29e17"
        ], [
            "Bug",
            "/bug",
            "icon-bug",
            "#c17008"
        ], [
            "Crash",
            "/crash",
            "icon-fire",
            "#d21007"
        ], [
            "Task",
            "/task",
            "icon-clock",
            "#757575"
        ]
    ];
    
    newIssueLink.addEventListener("click", function(e){
        e.preventDefault();
        newIssueLink.blur();
        
        const overlay = document.createElement("div");
        overlay.id = "New-Issue-Overlay";
        
        const inner = document.createElement("div");
        inner.id = "New-Issue-Inner";
        
        const title = document.createElement("div");
        title.id = "New-Issue-Title";
        
        const heading = document.createElement("h2");
        heading.innerText = "New Issue";
        
        const close = document.createElement("span");
        close.classList.add("icon", "icon-circle-cross", "icon-color-gray");
        
        const types = document.createElement("div");
        types.id = "New-Issue-Types";
        
        for(const issueType of issueTypes){
            const link = document.createElement("a");
            link.href = newIssueLink.getAttribute("href") + issueType[1];
            link.style.border = "5px solid " + issueType[3];
            link.style.setProperty("--issue-type-hover-color", issueType[3] + "20");
            link.innerHTML = "<span class='icon " + issueType[2] + "' style='color: " + issueType[3] + ";'></span><p>" + issueType[0] + "</p>";
            types.appendChild(link);
        }
        
        title.appendChild(heading);
        title.appendChild(close);
        inner.appendChild(title);
        inner.appendChild(types);
        overlay.appendChild(inner);
        document.body.appendChild(overlay);
        
        window.getComputedStyle(overlay).opacity;
        overlay.style.opacity = "1";
        
        let isClosing = false;
        
        function triggerClose(){
            if (isClosing){
                return;
            }
            
            // noinspection ReuseOfLocalVariableJS
            isClosing = false;
            overlay.style.opacity = "0";
            
            document.body.removeEventListener("click", onBodyClick);
            document.body.removeEventListener("keydown", onBodyKeyDown);
            
            setTimeout(function(){
                overlay.remove();
            }, 275);
        }
        
        const onBodyClick = function(){
            triggerClose();
        };
        
        const onBodyKeyDown = function(e){
            if (e.key === "Escape"){
                triggerClose();
            }
        };
        
        setTimeout(function(){
            document.body.addEventListener("click", onBodyClick);
            document.body.addEventListener("keydown", onBodyKeyDown);
        }, 0);
        
        inner.addEventListener("click", function(e){
            e.stopPropagation();
        });
        
        close.addEventListener("click", triggerClose);
    });
});
