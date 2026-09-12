const url='http://iflynepal.local/retreat-nepal/mindfulness/2-day-holistic-ayurvedic-rejuvenation-retreat/';
(async()=>{
 const t=await(await fetch('http://127.0.0.1:9337/json/new?'+encodeURIComponent(url),{method:'PUT'})).json();
 const ws=new WebSocket(t.webSocketDebuggerUrl); let id=0; const p=new Map();
 const send=(m,q)=>new Promise(r=>{const i=++id;p.set(i,r);ws.send(JSON.stringify({id:i,method:m,params:q||{}}));});
 ws.onmessage=e=>{const d=JSON.parse(e.data); if(d.id&&p.has(d.id)){p.get(d.id)(d.result);p.delete(d.id);}};
 await new Promise(r=>ws.onopen=r); await send('Page.enable');
 await send('Emulation.setDeviceMetricsOverride',{width:1440,height:1000,deviceScaleFactor:1,mobile:false});
 await new Promise(r=>setTimeout(r,6000));
 const res=await send('Runtime.evaluate',{expression:`
  (()=>{const n=document.querySelector('.iflynepal-pkg-annot--dates');
   if(!n) return 'MISSING';
   const w=document.querySelector('.iflynepal-pkg-booker'), cal=document.querySelector('.iflynepal-pkg-cal');
   const r=n.getBoundingClientRect(), b=w.getBoundingClientRect(), c=cal.getBoundingClientRect();
   const svg=n.querySelector('svg').getBoundingClientRect();
   const cs=getComputedStyle(n);
   return JSON.stringify({text:n.textContent.trim(), dir:cs.flexDirection, right:cs.right,
    note:[Math.round(r.left),Math.round(r.top),Math.round(r.right),Math.round(r.bottom)],
    cal:[Math.round(c.left),Math.round(c.top),Math.round(c.right)],
    arrowCentre:Math.round(svg.left+svg.width/2), gapToCalRight:Math.round(c.right-r.right),
    overlapsCal: r.bottom > c.top},null,1);})()`,returnByValue:true});
 console.log(res.result.value); ws.close(); process.exit(0);
})();
