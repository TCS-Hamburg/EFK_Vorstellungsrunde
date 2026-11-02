"use strict";
(() => {
 const form=document.querySelector('#survey'), fields=[...form.querySelectorAll('textarea')], button=document.querySelector('#submit'), message=document.querySelector('#message');
 let round='',busy=false,locked=false;
 const key='tcs-intro-submitted:'+location.pathname.replace(/index\.php$/,'');
 function notice(text,error=false){message.textContent=text;message.className='notice'+(error?' error':'');message.hidden=!text;}
 function toggle(disabled){fields.forEach(el=>el.disabled=disabled);button.disabled=disabled;}
 function lock(){locked=true;toggle(true);button.textContent='Gespeichert';document.querySelector('#progress').textContent='Teilnahme abgeschlossen';notice('Vielen Dank. Ihre Antworten wurden gespeichert. Eine erneute Abgabe ist in dieser Runde nicht möglich.');}
 function progress(){if(locked)return;document.querySelector('#progress').textContent=`${fields.filter(el=>el.value.trim()).length} von ${fields.length} Fragen beantwortet`;button.disabled=busy||fields.some(el=>!el.value.trim());}
 fields.forEach(el=>el.addEventListener('input',progress));
 async function request(options){const r=await fetch('api.php',{cache:'no-store',...options});let data;try{data=await r.json();}catch(_){throw new Error('Keine gültige Serverantwort. Bitte PHP auf dem Webspace prüfen.');}if(!r.ok)throw new Error(data.error||'Anfrage fehlgeschlagen.');return data;}
 form.addEventListener('submit',async e=>{
  e.preventDefault();if(busy||locked||fields.some(el=>!el.value.trim()))return;
  const answers=Object.fromEntries(fields.map(el=>[el.name,el.value.trim()]));busy=true;toggle(true);button.textContent='Wird gespeichert …';notice('');
  try{await request({method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf:document.querySelector('#csrf').value,round,answers})});try{localStorage.setItem(key,round);}catch(_){}lock();}
  catch(e){notice(e.message+' Ihre Eingaben bleiben auf dieser Seite erhalten.',true);toggle(false);button.textContent='Absenden';}
  finally{busy=false;progress();}
 });
 request().then(data=>{round=data.round;form.hidden=false;notice('');let previous=false;try{previous=localStorage.getItem(key)===round;}catch(_){}if(data.submitted||previous)lock();}).catch(e=>notice(e.message,true));
})();
