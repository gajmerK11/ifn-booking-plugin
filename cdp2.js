const url='http://iflynepal.local/retreat-nepal/mindfulness/2-day-holistic-ayurvedic-rejuvenation-retreat/';
(async()=>{
 const t=await(await fetch('http://127.0.0.1:9334/json/new?'+encodeURIComponent(url),{method:'PUT'})).json();
 const ws=new WebSocket(t.webSocketDebuggerUrl); let id=0; const p=new Map();
 const send=(m,q)=>new Promise(r=>{const i=++id;p.set(i,r);ws.send(JSON.stringify({id:i,method:m,params:q||{}}));});
 ws.onmessage=e=>{const d=JSON.parse(e.data); if(d.id&&p.has(d.id)){p.get(d.id)(d.result);p.delete(d.id);}};
 await new Promise(r=>ws.onopen=r); await send('Page.enable');
 await new Promise(r=>setTimeout(r,3500));
 const res=await send('Runtime.evaluate',{expression:`
  JSON.stringify([...document.querySelectorAll('.iflynepal-pkg-check-list')].map((ul,i)=>{
    const tk=ul.querySelector('.iflynepal-pkg-tick'); if(!tk) return null;
    const cs=getComputedStyle(tk);
    return {i, cls:ul.className, sec:(ul.closest('section,aside')||{}).id||'aside', bg:cs.backgroundColor, fg:cs.color};
  }).filter(Boolean),null,1)`,returnByValue:true});
 console.log(res.result.value); ws.close(); process.exit(0);
})();
