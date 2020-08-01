window.onload = () => {

    let timeoutArray = [];
    let intervalArray = [];

    //alert(window.location);

    document.addEventListener('click',(e)=>{
        let eventClick;
		if (!e) { e = window.event; }
		if (e.target) { eventClick = e.target; }
		else if (e.srcElement) { eventClick = e.srcElement; }
		if (eventClick.nodeType == 3) // pour contourner le bug de Safari
		{ eventClick = eventClick.parentNode; }
        if(typeof(document.getElementById('download'))!=='undefined') {
            if(eventClick.nodeName == 'A') { //get(0).tagName
                document.getElementById('info-wtr').innerHTML = '<span class="text-success">L\'archive est en cous de téléchargement...</span><br><br><span class="text-primary">Nous vous remercions d\'avoir utilisé WeTranfersCustom !</span><br><br>Redirection sur l\'accueil dans <span id="counter">10</span> secondes.';
                let interval = setInterval(()=>{
                    document.getElementById('counter').innerText = parseInt(document.getElementById('counter').innerText)-1;
                },1000);
                intervalArray.push(interval);
                let timeout = setTimeout(()=>{
                    let indexI = intervalArray.indexOf(interval);
                    intervalArray.splice(indexI,1);
                    clearInterval(interval);
                    let urlCourante = document.location.href; let urlVoulue="";
                    if(urlCourante.indexOf('?')===-1) { urlVoulue = urlCourante.replace(/download\/([a-zA-Z\d]+)\/([a-zA-Z\d]+)(\/delete(\/\d+)?)?(\.[a-zA-Z]+|\/)?$/,''); } else { urlVoulue = urlCourante.replace(/download\.php(\?[^\?]*)?$/,''); }
                    //alert(urlVoulue);
                    document.location.href=urlVoulue+"index.php";
                },10000);
                timeoutArray.push(timeout);
            }
        }
    });

};

window.onbeforeunload = () => {
    // suppression timeout et interval
    let lenI = intervalArray.length;
    if(lenI>0) {
        for(let i = 0; i <= lenI-1; i++) {
            if(typeof(intervalArray[i])!=='undefined') {
                clearInterval(intervalArray[i]);
            }
        }
    }
    let lenT = timeoutArray.length;
    if(lenT>0) {
        for(let j = 0; j <= lenT-1; j++) {
            if(typeof(timeoutArray[j])!=='undefined') {
                clearTimeout(timeoutArray[j]);
            }
        }
    }
};

$(document).ready(function () {
    bsCustomFileInput.init();
    /*let browse_text = document.getElementById('customFileLangHTML').getAttribute('data-browse');
    if(browse_text=="") { browse_text = 'Parcourir'; } 
    let element = document.querySelector('.custom-file-label');
    let style = window.getComputedStyle(element, '::after');
    //style.setProperty('content',browse_text);
    alert(style.getPropertyValue('content'));*/
});