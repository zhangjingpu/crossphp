(function(){reinit();function reinit(){var iframe=document.getElementById("mainFrame");try{var bHeight=iframe.contentWindow.document.body.scrollHeight;var dHeight=iframe.contentWindow.document.documentElement.scrollHeight;var height=Math.max(bHeight,dHeight);iframe.height=height}catch(ex){}setTimeout(function(){reinit()},200)}}());