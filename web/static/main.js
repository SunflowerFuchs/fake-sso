(function (){
    const legacyCopyText = (text) => {
        console.log("Falling back to legacy api")

        const textArea = document.createElement("textarea");

        // Place in the top-left corner of screen regardless of scroll position.
        textArea.style.position = 'fixed';
        textArea.style.top = "0";
        textArea.style.left = "0";

        // Ensure it has a small width and height. Setting to 1px / 1em
        // doesn't work as this gives a negative w/h on some browsers.
        textArea.style.width = '2em';
        textArea.style.height = '2em';

        // We don't need padding, reducing the size if it does flash render.
        textArea.style.padding = "0";

        // Clean up any borders.
        textArea.style.border = 'none';
        textArea.style.outline = 'none';
        textArea.style.boxShadow = 'none';

        // Avoid flash of the white box if rendered for any reason.
        textArea.style.background = 'transparent';

        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try{
            const success = document.execCommand('copy');
            if (!success) {
                throw new Error()
            }
        } catch (e) {
            console.log('Could not copy text')
        }

        document.body.removeChild(textArea);
    }

    const copyText = (text) => {
        console.log("Trying to copy text")

        if (!navigator.clipboard) {
            legacyCopyText(text)
            return;
        }

        navigator.clipboard.writeText(text).catch(() => legacyCopyText(text));
    }

    const setActiveFlow = (flowName) => {
        const desc = document.getElementById("FlowDescriptions");
        const flows = desc.children;

        for (let i = 0; i < flows.length; i++) {
            const isSelected = flows[i].id === flowName;
            flows[i].style.display = isSelected ? 'block' : 'none';
        }
    }

    const onReady = () => {
        const elems = document.getElementsByClassName("copyable");
        for (let i = 0; i < elems.length; i++) {
            elems[i].addEventListener("click", function (e) {
                copyText(e.target.innerText);
            })
        }

        document.getElementById("FlowSelector").addEventListener("change", function (e) {
            setActiveFlow(e.target.value);
        })
    }

    // Could be wrapped for a document ready check, but I skipped it for simplicity’s sake
    onReady()
})()