'use strict';
// يُكشف للصفحات المحلية الخاصة بالـ Launcher فقط (file://) — لا يُكشف أي شيء لصفحات نظام JOUD.
const { contextBridge, ipcRenderer } = require('electron');
if (location.protocol === 'file:') {
  contextBridge.exposeInMainWorld('joudDesktop', {
    onStatus: (cb) => ipcRenderer.on('status', (_e, text) => cb(String(text))),
    onError: (cb) => ipcRenderer.on('error-info', (_e, info) => cb(info)),
    retry: () => ipcRenderer.send('retry'),
    quit: () => ipcRenderer.send('quit'),
    openConfig: () => ipcRenderer.send('open-config'),
  });
}
