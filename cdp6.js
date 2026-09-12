const url='http://iflynepal.local/retreat-nepal/mindfulness/2-day-holistic-ayurvedic-rejuvenation-retreat/';
(async()=>{
 const t=await(await fetch('http://127.0.0.1:9338/json/new?'+encodeURIComponent(url),{method:'PUT'})).json();
 const ws=new WebSocket(t.webSocketDebuggerUrl); let id=0; const p=new Map();
 const send=(m,q)=>new Promise(r=>{const i=++id;p.set(i,r);ws.send(JSON.stringify({id:i,method:m,params:q||{}}));});
 ws.onmessage=e=>{const d=JSON.parse(e.data); if(d.id&&p.has(d.id)){p.get(d.id)(d.result);p.delete(d.id);}};
 await new Promise(r=>ws.onopen=r); await send('Page.enable');
 await send('Emulation.setDeviceMetricsOverride',{width:1440,height:1000,deviceScaleFactor:1,mobile:false});
 await new Promise(r=>setTimeout(r,6000));
 const res=await send('Runtime.evaluate',{expression:`
  (()=>{const items=[...document.querySelectorAll('.iflynepal-pkg-glance-item')];
   const rows={};
   items.forEach(el=>{const r=el.getBoundingClientRect(); const k=Math.round(r.top);
     (rows[k]=rows[k]||[]).push(el.querySelector('small')?el.querySelector('small').textContent:'FILLER');});
   const ico=document.querySelector('use[href="#ifnpkg-i-level"]');
   const sym=document.querySelector('#ifnpkg-i-level');
   return JSON.stringify({count:items.length, rows, iconUsed:!!ico, symbolPresent:!!sym,
     iconBox: ico? (()=>{const b=ico.closest('svg').getBoundingClientRect(); return Math.round(b.width)+'x'+Math.round(b.height);})():null},null,1);})()`,returnByValue:true});
 console.log(res.result.value); ws.close(); process.exit(0);
})();
