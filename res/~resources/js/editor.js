document.addEventListener("DOMContentLoaded", function(){
    const asInt = function(value){
        const int = parseInt(value, 10);
        return isNaN(int) ? null : int;
    };
    
    const wholeLineToggles = {
        "heading-1": "#",
        "heading-2": "##",
        "heading-3": "###",
        "task-unchecked": "[ ]",
        "task-checked": "[x]",
    };
    
    const wholeLineTogglesOrdered = Object.entries(wholeLineToggles).sort((a, b) => b[1].length - a[1].length);
    
    /**
     * @param {HTMLTextAreaElement} editor
     */
    const getActiveWholeLineToggle = function(editor){
        const text = editor.value;
        const lineStart = text.lastIndexOf("\n", editor.selectionStart - 1) + 1;
        
        for(const entry of wholeLineTogglesOrdered){
            const prefix = entry[1];
            
            if (text.substr(lineStart, prefix.length) === prefix){
                return entry[0];
            }
        }
        
        return null;
    };
    
    /**
     * @param {string} text
     * @param {number} start
     * @param {number} end
     * @returns Positions of first non-newline characters on each line at least partially included in the range, in the order from last line to first line.
     */
    const findLineStarts = function(text, start, end){
        start = Math.max(0, start);
        end = Math.max(0, end);
        
        const starts = [];
        let pos = end;
        
        while(pos >= start){
            const match = text.lastIndexOf("\n", pos);
            starts.push(match + 1);
            pos = match - 1;
        }
        
        return starts;
    };
    
    /**
     * @param {HTMLTextAreaElement} editor
     * @param {string} action
     */
    const applyWholeLineToggle = function(editor, action){
        let text = editor.value;
        let restoreCaretStart = editor.selectionStart;
        let restoreCaretEnd = editor.selectionEnd;
        
        let allMatchedLineActions = true;
        let removedAtLeastOneAction = false;
        
        const lineStarts = findLineStarts(text, editor.selectionStart - 1, editor.selectionEnd - 1);
        const firstLineStart = lineStarts[lineStarts.length - 1];
        
        for(const lineStart of lineStarts){
            for(const entry of wholeLineTogglesOrdered){
                const prefix = entry[1];
                
                if (text.substr(lineStart, prefix.length) === prefix){
                    let cutEnd = lineStart + prefix.length;
                    
                    while(cutEnd < text.length && text[cutEnd] === " "){
                        ++cutEnd;
                    }
                    
                    if (lineStart === firstLineStart){
                        if (restoreCaretStart < cutEnd){
                            restoreCaretStart = lineStart;
                        }
                        else{
                            restoreCaretStart -= cutEnd - lineStart;
                        }
                    }
                    
                    if (restoreCaretEnd < cutEnd){
                        restoreCaretEnd = lineStart;
                    }
                    else{
                        restoreCaretEnd -= cutEnd - lineStart;
                    }
                    
                    text = text.substring(0, lineStart) + text.substring(cutEnd);
                    
                    if (entry[0] !== action){
                        allMatchedLineActions = false;
                    }
                    
                    removedAtLeastOneAction = true;
                    break;
                }
            }
        }
        
        if (!removedAtLeastOneAction || !allMatchedLineActions){
            const insert = wholeLineToggles[action] + " ";
            
            for(const lineStart of findLineStarts(text, restoreCaretStart - 1, restoreCaretEnd - 1)){
                text = text.substring(0, lineStart) + insert + text.substring(lineStart);
            }
            
            restoreCaretStart += insert.length;
            restoreCaretEnd += insert.length * lineStarts.length;
        }
        
        editor.value = text;
        editor.setSelectionRange(restoreCaretStart, restoreCaretEnd, "none");
    };
    
    for(/** @type HTMLTextAreaElement */ const editor of document.querySelectorAll("textarea[data-markdown-editor]")){
        const updateHeight = function(){
            const prevWindowScroll = window.scrollY;
            
            editor.style.height = "0px";
            
            const style = window.getComputedStyle(editor);
            const padding = asInt(style.getPropertyValue("padding-top")) + asInt(style.getPropertyValue("padding-bottom"));
            
            const lineHeight = asInt(style.getPropertyValue("line-height")) || (1.2 * asInt(style.getPropertyValue("font-size")));
            const lineCount = Math.ceil((editor.scrollHeight - padding) / lineHeight);
            
            editor.style.height = Math.ceil(1 + padding + Math.max(7, Math.min(30, lineCount)) * lineHeight) + "px";
            editor.style.minHeight = Math.ceil(padding + lineHeight) + "px";
            
            window.scroll({ left: window.scrollX, top: prevWindowScroll });
        };
        
        const controls = editor.previousElementSibling;
        
        if (controls && controls.hasAttribute("data-markdown-editor-controls")){
            controls.removeAttribute("data-markdown-editor-controls");
            controls.classList.add("markdown-editor-controls");
            
            const buttons = controls.querySelectorAll("button");
            
            const updateButtons = function(){
                const currentWholeLineToggle = getActiveWholeLineToggle(editor);
                
                for(/** @type HTMLButtonElement */ const button of buttons){
                    if (button.getAttribute("data-editor-action-type")){
                        button.classList.toggle("active", currentWholeLineToggle === button.getAttribute("data-editor-action-value"));
                    }
                    else{
                        button.classList.toggle("active", false);
                    }
                }
            };
            
            const updateButtonsDelayed = function(){
                setTimeout(updateButtons, 0);
            };
            
            for(/** @type HTMLButtonElement */ const button of buttons){
                const actionType = button.getAttribute("data-editor-action-type");
                const actionValue = button.getAttribute("data-editor-action-value");
                
                button.addEventListener("click", function(){
                    if (actionType === "wholeline-toggle"){
                        applyWholeLineToggle(editor, actionValue);
                    }
                    
                    editor.focus();
                    updateButtons();
                });
            }
            
            editor.addEventListener("input", updateButtons);
            editor.addEventListener("keydown", updateButtonsDelayed);
            editor.addEventListener("mousedown", updateButtonsDelayed);
            editor.addEventListener("mouseup", updateButtons);
        }
        
        editor.addEventListener("input", updateHeight);
        updateHeight();
    }
});
