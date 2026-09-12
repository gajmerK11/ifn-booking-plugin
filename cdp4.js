const url='http://iflynepal.local/retreat-nepal/mindfulness/2-day-holistic-ayurvedic-rejuvenation-retreat/';
(async()=>{
 const t=await(await fetch('http://127.0.0.1:9336/json/new?'+encodeURIComponent(url),{method:'PUT'})).json();
 const ws=new WebSocket(t.webSocketDebuggerUrl); let id=0; const p=new Map();
 const send=(m,q)=>new Promise(r=>{const i=++id;p.set(i,r);ws.send(JSON.stringify({id:i,method:m,params:q||{}}));});
 ws.onmessage=e=>{const d=JSON.parse(e.data); if(d.id&&p.has(d.id)){p.get(d.id)(d.result);p.delete(d.id);}};
 await new Promise(r=>ws.onopen=r); await send('Page.enable');
 await new Promise(r=>setTimeout(r,6000));
 const res=await send('Runtime.evaluate',{expression:`
  JSON.stringify({n:document.querySelectorAll('.iflynepal-pkg-tick').length, rows:[...document.querySelectorAll('.iflynepal-pkg-check-list')].map(ul=>{
    const tk=ul.querySelector('.iflynepal-pkg-tick'); if(!tk) return null;
    const cs=getComputedStyle(tk), r=tk.getBoundingClientRect();
    return {sec:(ul.closest('section,aside')||{}).id||'aside', x:ul.classList.contains('iflynepal-pkg-check-list--x'),
            bg:cs.backgroundColor, fg:cs.color, border:cs.borderTopWidth+' '+cs.borderTopColor,
            box:Math.round(r.width)+'x'+Math.round(r.height)};
  }).filter(Boolean)},null,1)`,returnByValue:true});
 console.log(res.result.value); ws.close(); process.exit(0);
})();
