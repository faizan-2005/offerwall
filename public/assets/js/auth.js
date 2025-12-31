// auth.js - login/register helpers
const Auth = (function(){
  function saveToken(t){ localStorage.setItem('token', t); }
  function logout(){ localStorage.removeItem('token'); window.router.navigate('/login'); }
  async function login(email, password){
    const r = await API.post('/auth/login', {email, password});
    saveToken(r.token);
    return r;
  }
  async function register(name,email,password){
    const r = await API.post('/auth/register', {name,email,password});
    saveToken(r.token);
    return r;
  }
  return { login, register, logout, saveToken };
})();
