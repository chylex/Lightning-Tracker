document.addEventListener("DOMContentLoaded", function(){
    const multiselects = document.querySelectorAll("details.multiselect");
    
    if (multiselects.length > 0){
        document.body.addEventListener("click", function(e){
            /** @var HTMLElement */
            const target = e.target;
            
            let ignoreMultiselect = target.closest("details.multiselect");
            
            if (ignoreMultiselect === null && target.tagName === "LABEL"){
                ignoreMultiselect = target.nextElementSibling;
            }
            
            for(/** @type HTMLDetailsElement */ const multiselect of multiselects){
                if (multiselect !== ignoreMultiselect){
                    multiselect.open = false;
                }
            }
        });
        
        // global multiselect text widths used for overflow slicing
        const emptyPair = [ "", 0 ];
        const memoizedPairs = new Map();
        
        for(/** @type HTMLDetailsElement */ const multiselect of multiselects){
            const summary = multiselect.querySelector("summary");
            const summaryInitialText = getText(summary);
            
            const label = multiselect.previousElementSibling;
            
            label.addEventListener("click", function(){
                summary.click();
            });
            
            /**
             * @param {HTMLElement | Element} ele
             */
            function getText(ele){
                return ele.innerText || ele.textContent; // fallback for buggy browsers
            }
            
            function getSpanClass(ele){
                const child = ele.childElementCount === 1 ? ele.children[0] : null;
                
                if (child && child.tagName === "SPAN" && child.classList.contains("missing")){
                    return "missing";
                }
                else{
                    return null;
                }
            }
            
            const checkboxes = multiselect.getElementsByTagName("input");
            
            const updateCallback = function(){
                const checked = [];
                const styles = [];
                
                for(/** @type HTMLInputElement */ const cb of checkboxes){
                    if (cb.checked){
                        const labelEle = cb.nextElementSibling;
                        
                        checked.push(getText(labelEle).trim());
                        styles.push(getSpanClass(labelEle));
                    }
                }
                
                if (checked.length === 0){
                    summary.innerText = summaryInitialText;
                    summary.style.fontStyle = "italic";
                    return;
                }
                
                if (summary.clientWidth > 0){
                    const wasClosed = !multiselect.open;
                    
                    if (wasClosed){
                        multiselect.open = true;
                    }
                    
                    const testEle = document.createElement("summary");
                    testEle.style.position = "absolute";
                    testEle.style.overflow = "visible";
                    summary.insertAdjacentElement("afterend", testEle);
                    
                    const targetWidth = summary.clientWidth - 7;
                    
                    function getTextWidth(text){
                        testEle.innerText = text;
                        return testEle.clientWidth;
                    }
                    
                    while(getTextWidth(checked.join(", ")) >= targetWidth){
                        const pairs = checked.map(str => {
                            const sliced = Array.from(str).slice(0, -1).join("").trim(); // unicode
                            
                            if (sliced.length === 0){
                                return emptyPair;
                            }
                            
                            let pair = memoizedPairs.get(sliced);
                            
                            if (!pair){
                                memoizedPairs.set(sliced, pair = [ sliced, getTextWidth(sliced) ]);
                            }
                            
                            return pair;
                        });
                        
                        let longestIndex = 0;
                        
                        for(let index = 1; index < pairs.length; index++){
                            if (pairs[index][1] > pairs[longestIndex][1]){
                                longestIndex = index;
                            }
                        }
                        
                        if (pairs[longestIndex] === emptyPair){
                            break;
                        }
                        
                        checked[longestIndex] = pairs[longestIndex][0];
                    }
                    
                    testEle.remove();
                    
                    if (wasClosed){
                        multiselect.open = false;
                    }
                }
                
                summary.innerHTML = checked.map((text, index) => styles[index] === null ? text : "<span class=\"" + styles[index] + "\">" + text + "</span>").join(", ");
                summary.style.fontStyle = "normal";
            };
            
            const updateDelayed = function(){
                setTimeout(updateCallback, 0);
            };
            
            for(/** @type HTMLInputElement */ const checkbox of checkboxes){
                checkbox.addEventListener("change", updateCallback);
                
                const form = checkbox.form;
                
                if (form !== null){
                    form.addEventListener("reset", updateDelayed);
                }
            }
            
            // noinspection JSUnresolvedVariable
            const onFontsLoaded = (document.fonts && document.fonts.ready) || Promise.resolve();
            
            onFontsLoaded.then(function(){
                updateCallback();
                
                if (summary.clientWidth === 0 && "IntersectionObserver" in window){
                    const observer = new IntersectionObserver(function(){
                        updateCallback();
                        observer.disconnect();
                    });
                    
                    observer.observe(summary);
                }
            });
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
