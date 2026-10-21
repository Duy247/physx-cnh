<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Shared note · physx-cnh</title>
<link rel="stylesheet" href="/css/common.css">
<style>
body{height:auto;overflow:auto;color:#1d1d52}main{max-width:1000px;margin:auto;padding:24px 16px}a{color:inherit}h1{margin-bottom:8px}.hint{color:#48485e;line-height:1.5}textarea{box-sizing:border-box;width:100%;min-height:60vh;resize:vertical;padding:18px;border:1px solid #9399ad;border-radius:10px;background:white;color:#202030;font:17px/1.6 system-ui,sans-serif}button{padding:10px 16px;border:0;border-radius:6px;background:#1d1d52;color:white;cursor:pointer}button:disabled{opacity:.5;cursor:default}.toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin:14px 0}#conflict{padding:16px;background:#fff0ce;border-radius:8px}#remote{min-height:180px;margin:12px 0} [hidden]{display:none!important}
</style>
</head>
<body><main>
<a href="/">← physx-cnh</a>
<h1>Shared note</h1>
<p class="hint">A public canvas anyone can edit. Changes save automatically. Keep private information out of this note. Maximum 5 MB (5,242,880 bytes) of UTF-8 text.</p>
<label for="canvas">Your canvas</label>
<div class="toolbar"><button id="save" disabled>Save</button><span id="status" role="status" aria-live="polite">Loading…</span></div>
<textarea id="canvas" disabled spellcheck="false" placeholder="Write something together…"></textarea>
<section id="conflict" hidden aria-label="Conflicting changes">
<p>The note changed elsewhere. Your edits are still in the canvas. Copy or merge the latest text below, then choose “Save merged canvas”, or replace your edits with “Load latest”.</p>
<label for="remote">Latest saved text (read only)</label>
<textarea id="remote" readonly></textarea>
<div class="toolbar"><button id="merge">Save merged canvas</button><button id="reload">Load latest</button></div>
</section>
</main><script>
'use strict';
const canvas = document.querySelector('#canvas');
const saveButton = document.querySelector('#save');
const status = document.querySelector('#status');
const conflictBox = document.querySelector('#conflict');
const remote = document.querySelector('#remote');
const api = '/note/api.php';
let version = null, dirty = false, saving = false, timer, latest = null, polling = false;
function message(text) { status.textContent = text; }
function accept(data) {
    canvas.value = data.text;
    version = data.version;
    dirty = false;
    latest = null;
    conflictBox.hidden = true;
    message('Saved · version ' + version);
}
function conflict(data) {
    latest = data;
    remote.value = data.text;
    conflictBox.hidden = false;
    message('Conflict · review the latest note below');
    clearTimeout(timer);
}
async function poll() {
    if (polling || saving) return;
    polling = true;
    const startVersion = version;
    try {
        const response = await fetch(api, {cache:'no-store'});
        if (!response.ok) throw new Error();
        const data = await response.json();
        if (saving || version !== startVersion) return;
        if (version === null) {
            accept(data); canvas.disabled = false; saveButton.disabled = false;
        } else if (data.version !== version) {
            if (dirty || document.activeElement === canvas || latest) conflict(data);
            else accept(data);
        } else if (!dirty && !latest) message('Saved · version ' + version);
    } catch (_) { message('Offline · unable to check the note; edits stay here'); }
    finally { polling = false; }
}
async function save() {
    clearTimeout(timer);
    if (saving || version === null || latest || !dirty) return;
    const text = canvas.value;
    if (new TextEncoder().encode(text).length > 5242880) {
        message('Too large · maximum 5 MB (5,242,880 bytes) of UTF-8 text'); return;
    }
    saving = true; message('Saving…');
    try {
        const response = await fetch(api, {method:'PUT', headers:{'Content-Type':'application/json'}, body:JSON.stringify({text, base_version:version})});
        const data = await response.json();
        if (response.status === 409) { conflict(data); return; }
        if (!response.ok) { message(data.error || 'Save failed · try again'); return; }
        version = data.version;
        dirty = canvas.value !== text;
        message(dirty ? 'Unsaved changes' : 'Saved · version ' + version);
        if (dirty) timer = setTimeout(save, 1500);
    } catch (_) { message('Offline · edits stay here; press Save to retry'); }
    finally { saving = false; }
}
canvas.addEventListener('input', () => {
    dirty = true; clearTimeout(timer);
    if (!latest) { message('Unsaved changes'); timer = setTimeout(save, 1500); }
});
saveButton.addEventListener('click', save);
document.querySelector('#reload').addEventListener('click', () => { if (latest) accept(latest); });
document.querySelector('#merge').addEventListener('click', () => {
    if (!latest) return;
    version = latest.version; latest = null; conflictBox.hidden = true; dirty = true; save();
});
window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
window.addEventListener('online', poll);
poll(); setInterval(poll, 5000);
</script></body></html>
