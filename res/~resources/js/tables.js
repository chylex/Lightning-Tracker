document.addEventListener("DOMContentLoaded", function(){
    /**
     * @param {HTMLTableElement} table
     */
    function canUseFixedLayout(table){
        const head = table.tHead;
        return head !== null && head.rows.length > 0 && !Array.from(table.rows).some(row => row.parentElement.tagName !== "TFOOT" && Array.from(row.cells).some(cell => cell.colSpan > 1));
    }
    
    /**
     * @param {HTMLTableElement} table
     */
    function resetTableLayout(table){
        table.style.tableLayout = "";
        
        for(/** @type HTMLTableRowElement */ const row of table.rows){
            if (row.parentElement.tagName === "TFOOT"){
                continue;
            }
            
            for(const cell of row.cells){
                if (cell.style.width !== ""){
                    cell.style.width = "0px";
                }
            }
        }
    }
    
    /**
     * @param {HTMLTableElement} table
     */
    function recalculateTableLayout(table){
        const calculatedWidths = Array.from(table.tHead.rows[0].cells, /** @type HTMLTableCellElement */obj => obj.style.width === "" ? null : obj.offsetWidth);
        
        for(/** @type HTMLTableRowElement */ const row of table.rows){
            if (row.parentElement.tagName === "TFOOT"){
                continue;
            }
            
            const cells = row.cells;
            
            for(let i = 0; i < cells.length; i++){
                const cell = cells[i];
                const width = calculatedWidths[i];
                
                if (width === null){
                    cell.style.overflow = "hidden";
                    cell.style.textOverflow = "ellipsis";
                    cell.title = cell.innerText.trim();
                }
                else{
                    cell.style.width = width + "px";
                }
            }
        }
        
        table.style.tableLayout = "fixed";
    }
    
    for(/** @type HTMLTableElement */ const table of document.getElementsByTagName("table")){
        if (!canUseFixedLayout(table)){
            continue;
        }
        
        // noinspection JSUnresolvedVariable
        const onFontsLoaded = (document.fonts && document.fonts.ready) || Promise.resolve();
        
        onFontsLoaded.then(function(){
            recalculateTableLayout(table);
            
            let isMutating = false;
            
            const observer = new MutationObserver(function(){
                if (isMutating || !canUseFixedLayout(table)){
                    return;
                }
                
                // noinspection ReuseOfLocalVariableJS
                isMutating = true;
                
                resetTableLayout(table);
                recalculateTableLayout(table);
                
                setTimeout(function(){
                    // noinspection ReuseOfLocalVariableJS
                    isMutating = false;
                }, 0);
            });
            
            observer.observe(table, {
                subtree: true,
                childList: true,
                characterData: true,
            });
        });
    }
    
    /**
     * @param {Element} field
     */
    function clearFormField(field){
        const tag = field.tagName;
        const type = field.getAttribute("type");
        
        if (tag === "TEXTAREA" || tag === "SELECT" || (tag === "INPUT" && (type === "text" || type === "password" || type === "email"))){
            field.value = "";
        }
        else if (tag === "INPUT" && type === "checkbox"){
            field.checked = false;
        }
    }
    
    for(/** @type HTMLElement */ const filtering of document.querySelectorAll("details.filtering")){
        const form = filtering.closest("form");
        const clear = filtering.querySelector("button[data-clear-form]");
        
        if (form !== null && clear !== null){
            clear.addEventListener("click", function(e){
                e.preventDefault();
                form.reset();
                
                for(const field of form.elements){
                    clearFormField(field);
                }
            });
        }
    }
});
