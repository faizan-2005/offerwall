// app.js - SPA router and UI components
const router = (function(){
  const routes = {};
  const root = document.getElementById('app');
  function render(html){ root.innerHTML = html; }

  function register(path, fn){ routes[path] = fn; }
  function path(){ return location.pathname || '/home'; }
  function navigate(to){ history.pushState({}, '', to); route(); }
  async function route(){
    const p = path();
    const fn = routes[p] || routes['/home'];
    try{ await fn(); }catch(e){ console.error(e); render('<div class="p-6">Error</div>'); }
  }

  window.addEventListener('popstate', route);
  return { register, navigate, route };
})();

// Components
function nav() {
  return `
  <div class="fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-white p-2 rounded-full shadow-lg flex gap-2">
    <button onclick="router.navigate('/home')" class="px-4 py-2">Home</button>
    <button onclick="router.navigate('/offers')" class="px-4 py-2">Offers</button>
    <button onclick="router.navigate('/wallet')" class="px-4 py-2">Wallet</button>
    <button onclick="router.navigate('/profile')" class="px-4 py-2">Profile</button>
  </div>`;
}

// Pages
router.register('/home', async ()=>{
  document.title = 'Home - OfferWall';
  const featured = await API.get('/offers/featured').catch(()=>({offers:[]}));
  const offersHtml = (featured.offers||[]).map(o=>`<div class="card p-4 m-2"> <div class="font-semibold">${o.title}</div><div class="text-sm">${o.reward} coins</div></div>`).join('');
  render(`
    <div class="fade-in">
      <header class="p-4 mb-4 rounded-lg card">
        <h1 class="text-2xl font-bold">Welcome to OfferWall</h1>
        <p class="text-sm text-slate-500">Complete tasks, earn rewards.</p>
      </header>
      <section class="grid grid-cols-1 gap-4">${offersHtml}</section>
    </div>` + nav());
});

router.register('/login', async ()=>{
  document.title = 'Login';
  render(`
    <div class="max-w-md mx-auto p-6 card fade-in">
      <h2 class="text-xl font-semibold mb-4">Login</h2>
      <input id="email" class="w-full p-2 border rounded mb-2" placeholder="Email">
      <input id="password" type="password" class="w-full p-2 border rounded mb-4" placeholder="Password">
      <button id="btn" class="w-full py-2 bg-indigo-600 text-white rounded">Login</button>
      <p class="text-sm mt-2">No account? <a href="#" onclick="router.navigate('/register')">Register</a></p>
    </div>`);
  document.getElementById('btn').addEventListener('click', async ()=>{
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    try{ await Auth.login(email,password); router.navigate('/home'); }catch(e){ alert(e.error||'Login failed'); }
  });
});

router.register('/register', async ()=>{
  document.title = 'Register';
  render(`
    <div class="max-w-md mx-auto p-6 card fade-in">
      <h2 class="text-xl font-semibold mb-4">Create account</h2>
      <input id="name" class="w-full p-2 border rounded mb-2" placeholder="Full name">
      <input id="email" class="w-full p-2 border rounded mb-2" placeholder="Email">
      <input id="password" type="password" class="w-full p-2 border rounded mb-4" placeholder="Password">
      <button id="btnr" class="w-full py-2 bg-green-600 text-white rounded">Register</button>
    </div>`);
  document.getElementById('btnr').addEventListener('click', async ()=>{
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    try{ await Auth.register(name,email,password); router.navigate('/home'); }catch(e){ alert(e.error||'Register failed'); }
  });
});

router.register('/offers', async ()=>{
  document.title = 'Offers';
  const r = await API.get('/offers').catch(()=>({offers:[]}));
  const list = (r.offers||[]).map(o=>`<div class="card p-4 hover:scale-[1.01] transition-transform" onclick="openOffer(${o.id})"><div class="flex justify-between"><div><div class="font-semibold">${o.title}</div><div class="text-sm text-slate-500">${o.category||''}</div></div><div class="text-indigo-600">+${o.reward}</div></div></div>`).join('');
  render(`<div class="p-2">${list}</div>`+nav());
});

window.openOffer = async function(id){
  const r = await API.get('/offers/'+id);
  const o = r.offer;
  render(`<div class="p-4">`+`<div class="card p-4"><h2 class="text-xl font-semibold">${o.title}</h2><p class="text-sm text-slate-600">${o.description}</p><div class="mt-4"><button onclick="startOffer('${o.id}')" class="px-4 py-2 bg-indigo-600 text-white rounded">Start Offer</button></div></div></div>`+nav());
}

window.startOffer = async function(offerId){
  const r = await API.post('/offers/start', {offer_id: offerId});
  // in real flow user redirected to offer URL with click_id
  alert('Offer started. Click ID: '+r.click_id);
}

router.register('/wallet', async ()=>{
  document.title = 'Wallet';
  const r = await API.get('/wallet').catch(()=>({balance:0,transactions:[]}));
  const txHtml = (r.transactions||[]).map(t=>`<div class="p-2 border-b"><div class="flex justify-between"><div>${t.type}</div><div>${t.amount}</div></div></div>`).join('');
  render(`<div class="p-4"><div class="card p-4 mb-4"><div class="text-sm">Balance</div><div class="text-2xl font-bold">${r.balance}</div></div><div class="card p-2">${txHtml}</div></div>`+nav());
});

router.register('/profile', async ()=>{
  document.title = 'Profile';
  render(`<div class="p-4 card"><h2 class="text-lg">Profile</h2><button onclick="Auth.logout()" class="mt-4 bg-red-500 text-white px-4 py-2 rounded">Logout</button></div>`+nav());
});

// initial navigation
if(location.pathname === '/' || location.pathname === '') history.replaceState({},'', '/home');
router.route();
