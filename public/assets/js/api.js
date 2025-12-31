// api.js - simple fetch wrapper
const API = (function(){
  const base = '/api';
  function json(res){
    if (!res.ok) return res.json().then(e=>Promise.reject(e));
    return res.json();
  }
  function authHeaders(){
    const t = localStorage.getItem('token');
    return t ? { 'Authorization': 'Bearer '+t } : {};
  }
  return {
    get: (path)=> fetch(base+path, {headers: {...authHeaders()}}).then(json),
    post: (path, body)=> fetch(base+path, {method:'POST',headers:{'Content-Type':'application/json',...authHeaders()},body:JSON.stringify(body)}).then(json),
    put: (path, body)=> fetch(base+path, {method:'PUT',headers:{'Content-Type':'application/json',...authHeaders()},body:JSON.stringify(body)}).then(json),
    del: (path)=> fetch(base+path, {method:'DELETE',headers:{...authHeaders()}}).then(json)
  };
})();
