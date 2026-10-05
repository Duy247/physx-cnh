(() => {
  const page = document.querySelector('[data-graph-page]');
  if (!page || !window.go) return;
  fetch('/data/graphs.json', { credentials: 'same-origin' }).then((response) => {
    if (!response.ok) throw new Error('Không thể tải dữ liệu sơ đồ.');
    return response.json();
  }).then((graphs) => {
    const graph = graphs[page.dataset.graphPage];
    if (!graph) throw new Error('Không tìm thấy sơ đồ.');
    const go = window.go;
    const $ = go.GraphObject.make;
    const host = page.querySelector('[data-diagram]');
    const shell = page.querySelector('[data-graph-shell]');
    const detail = page.querySelector('[data-detail]');
    const diagram = $(go.Diagram, host, { initialAutoScale: go.AutoScale.Uniform, initialContentAlignment: go.Spot.Center, padding:42, 'animationManager.initialAnimationStyle':go.AnimationStyle.None, 'toolManager.mouseWheelBehavior':go.WheelMode.Zoom, 'undoManager.isEnabled':false, allowCopy:false,allowDelete:false,allowInsert:false,allowLink:false,allowMove:false });
    diagram.nodeTemplate = $(go.Node,'Auto',{isShadowed:true,shadowBlur:8,shadowOffset:new go.Point(0,4),shadowColor:'#6f918a55',cursor:'pointer'},new go.Binding('location','loc',go.Point.parse),$(go.Shape,'RoundedRectangle',{stroke:'#356d70',strokeWidth:1.5,fill:'#d9ebe7'},new go.Binding('fill','type',(type)=>type==='Start'?'#b9ddd3':type==='End'?'#ebc5b8':'#d9ebe7'),new go.Binding('figure','type',(type)=>type==='Start'||type==='End'?'Circle':'RoundedRectangle')),$(go.TextBlock,{margin:9,maxSize:new go.Size(180,NaN),textAlign:'center',wrap:go.Wrap.Fit,stroke:'#153f45',font:'600 14px sans-serif'},new go.Binding('text','text')));
    diagram.linkTemplate = $(go.Link,{curve:go.Curve.Bezier,adjusting:go.LinkAdjusting.Stretch,fromShortLength:7,toShortLength:9},new go.Binding('curviness','curviness'),$(go.Shape,{stroke:'#527a7a',strokeWidth:1.6},new go.Binding('strokeDashArray','progress',(progress)=>progress?[]:[5,6]),new go.Binding('opacity','progress',(progress)=>progress?1:.5)),$(go.Shape,{toArrow:'Standard',fill:'#527a7a',stroke:null,scale:1.15}),$(go.Panel,'Auto',$(go.Shape,'RoundedRectangle',{fill:'#efd584',stroke:'#6f7e71'}),$(go.TextBlock,{margin:3,maxSize:new go.Size(120,NaN),textAlign:'center',font:'11px sans-serif'},new go.Binding('text','text'))));
    diagram.addDiagramListener('ObjectSingleClicked',(event)=>{ const data=event.subject?.part?.data;if(!data?.text)return;page.querySelector('[data-detail-title]').textContent=data.text;page.querySelector('[data-detail-content]').innerHTML=graph.descriptions[data.text]?.content||'Chưa có mô tả chi tiết cho mục này.';detail.hidden=false; });
    diagram.model=go.Model.fromJson(graph.model);
    page.querySelector('[data-detail-close]').addEventListener('click',()=>{detail.hidden=true;});
    const fullscreen=page.querySelector('[data-fullscreen]');
    fullscreen.addEventListener('click',async()=>{if(document.fullscreenElement===shell)await document.exitFullscreen();else await shell.requestFullscreen();});
    document.addEventListener('fullscreenchange',()=>{fullscreen.innerHTML=document.fullscreenElement===shell?'<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 3v5H3M16 3v5h5M8 21v-5H3M16 21v-5h5"/></svg>':'<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"/></svg>';setTimeout(()=>diagram.requestUpdate(),50);});
  }).catch((error)=>{ page.querySelector('[data-diagram]').innerHTML=`<p class="graph-error">${error.message}</p>`; });
})();
