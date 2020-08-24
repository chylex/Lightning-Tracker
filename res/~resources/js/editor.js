document.addEventListener("DOMContentLoaded", function(){
    const asInt = function(value){
        const int = parseInt(value, 10);
        return isNaN(int) ? null : int;
    };
    
    for(/** @type HTMLTextAreaElement */ const editor of document.querySelectorAll("textarea[data-markdown-editor]")){
        const updateHeight = function(){
            const prevWindowScroll = window.scrollY;
            
            editor.style.height = "0px";
            
            const style = window.getComputedStyle(editor);
            const padding = asInt(style.getPropertyValue("padding-top")) + asInt(style.getPropertyValue("padding-bottom"));
            
            const lineHeight = asInt(style.getPropertyValue("line-height")) || (1.2 * asInt(style.getPropertyValue("font-size")));
            const lineCount = Math.ceil((editor.scrollHeight - padding) / lineHeight);
            
            editor.style.height = Math.ceil(1 + padding + Math.max(1, Math.min(30, lineCount)) * lineHeight) + "px";
            editor.style.minHeight = Math.ceil(padding + lineHeight) + "px";
            
            window.scroll({ left: window.scrollX, top: prevWindowScroll });
        };
        
        editor.addEventListener("input", updateHeight);
        updateHeight();
    }
});
