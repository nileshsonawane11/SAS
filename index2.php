<?php
    session_start();
    require_once './Backend/config.php'; // your DB config
    define('REMEMBER_SECRET','CHANGE_THIS_TO_LONG_RANDOM_SECRET_123!@#');

    // Simple remember-me restore
    if (!isset($_SESSION['uid']) && !empty($_COOKIE['REMEMBERME'])) {

        $raw = base64_decode($_COOKIE['REMEMBERME']);
        if ($raw && strpos($raw,'::') !== false) {

            list($payload, $sig) = explode('::', $raw, 2);

            // verify HMAC signature
            if (hash_equals(hash_hmac('sha256',$payload,REMEMBER_SECRET), $sig)) {
                $data = json_decode($payload,true);

                if ($data && isset($data['uid']) && $data['exp'] > time()) {
                    // restore session
                    $_SESSION['uid'] = $data['uid'];
                    $_SESSION['login_time'] = time();
                }
            }
        }
    }

    // If session is valid, redirect to dashboard
    if (isset($_SESSION['uid'])) {
        header('Location: home.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supervision Allocation Auth</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
html, body {
    height: 100%;
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg,#1e3c72,#2a5298);
}

.fullscreen-row {
    height: 100vh; /* Full viewport height */
    margin: 0;
}

/* BRAND PANEL */
.brand-panel {
    background: linear-gradient(135deg,#2563eb,#1e40af);
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    overflow: hidden;
    padding: 20px;
    min-height: 100%;
}

.brand-text {
    opacity: 0;
    transform: translateY(50px);
    animation: fadeUp 1s forwards;
}

.brand-text:nth-child(1){animation-delay:0.5s;}
.brand-text:nth-child(2){animation-delay:1s;}

@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
}

/* AUTH CARD */
.auth-card {
    max-width: 400px;
    width: 90%;
    border-radius: 1rem;
    background: #ffffff;
    padding: 25px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    animation: fadeInCard 0.8s ease-in-out;
}

@keyframes fadeInCard{
    from { opacity: 0; transform: translateY(-20px);}
    to { opacity: 1; transform: translateY(0);}
}

/* COMMON */
.hidden{display:none;}
.input-icon{position:absolute;right:15px;top:50%;transform:translateY(-50%);cursor:pointer;color:#6c757d;}
.progress{height:6px;border-radius:3px;}
.toast-container{position:fixed;top:20px;right:20px;z-index:9999;}
h3{font-size:1.5rem;}
.container, 
.container-fluid, 
.container-lg, 
.container-md, 
.container-sm, 
.container-xl, 
.container-xxl {
    padding: 0;
}
/* RESPONSIVE */
@media(max-width: 1200px){
    .auth-card{max-width: 380px; padding: 22px;}
}
@media(max-width: 992px){
    .brand-panel{display:none;}
}
@media(max-width: 768px){
    .auth-card{max-width: 350px; padding: 20px;}
    h3{font-size:1.4rem;}
}
@media(max-width: 576px){
    .auth-card{max-width: 320px; padding: 18px;}
    h3{font-size:1.2rem;}
}
</style>
</head>

<body>

<div class="container-fluid h-100">
<div class="row fullscreen-row">

<!-- LEFT BRANDING -->
<div class="col-lg-6 d-none d-lg-flex brand-panel">
    <div>
        <div class="brand-text">
            <i class="fa-solid fa-building-columns fa-3x mb-2"></i>
            <h2>Supervision Allocation System</h2>
        </div>
        <div class="brand-text">
            <p></p>
        </div>
    </div>
</div>

<!-- AUTH CARD -->
<div class="col-lg-6 d-flex justify-content-center align-items-center bg-light">
    <div class="auth-card">

        <h3 id="title" class="text-center mb-4">Sign In</h3>
        <div id="alert"></div>

        <!-- LOGIN -->
        <div id="loginBox">
            <div class="form-floating mb-3">
                <input type="email" id="l_email" class="form-control" placeholder="Email" required>
                <label>Email</label>
            </div>
            <div class="form-floating mb-3 position-relative">
                <input type="password" id="l_pass" class="form-control" placeholder="Password" required>
                <label>Password</label>
                <span class="input-icon" onclick="togglePass('l_pass')">
                    <i class="fa fa-eye"></i>
                </span>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="remember">
                <label class="form-check-label small">
                    Remember me on this device
                </label>
            </div>

            <div class="d-flex justify-content-between mb-3 small text-muted">
                <span>Admin / Staff Login</span>
                <a href="#" class="text-decoration-none">Forgot password?</a>
            </div>

            <button id="loginBtn" class="btn btn-primary w-100 mb-3" onclick="login()">
                <span class="btn-text">Login</span>
                <span class="spinner-border spinner-border-sm d-none"></span>
            </button>

            <p class="text-center small">
                Don't have an account? <a href="#" onclick="toggle()">Register</a>
            </p>
        </div>

        <!-- REGISTER -->
        <div id="registerBox" class="hidden">
            <div class="form-floating mb-2">
                <input class="form-control" id="inst" placeholder="Institute Name" required>
                <label>Institute Name</label>
            </div>
            <div class="form-floating mb-2">
                <input class="form-control" id="s_name" placeholder="Admin Full Name" required>
                <label>Full Name</label>
            </div>
            <div class="form-floating mb-2">
                <input type="email" class="form-control" id="r_email" placeholder="Email" required>
                <label>Email</label>
            </div>

            <div class="form-floating position-relative mb-1">
                <input type="password" class="form-control" id="r_pass" placeholder="Password" oninput="strength()" required>
                <label>Password</label>
                <span class="input-icon" onclick="togglePass('r_pass')">
                    <i class="fa fa-eye"></i>
                </span>
            </div>

            <div class="progress mb-2">
                <div id="bar" class="progress-bar"></div>
            </div>
            <ul class="small text-muted mb-3">
                <li>Minimum 8 characters</li>
                <li>Include letters & numbers</li>
            </ul>

            <button id="regBtn" class="btn btn-success w-100 mb-3" onclick="register()">
                <span class="btn-text">Register</span>
                <span class="spinner-border spinner-border-sm d-none"></span>
            </button>

            <p class="text-center small">
                Already registered? <a href="#" onclick="toggle()">Login</a>
            </p>
        </div>

    </div>
</div>

</div>
</div>

<!-- Toast -->
<div class="toast-container" id="toast"></div>

<script>
function toggle(){
    loginBox.classList.toggle('hidden');
    registerBox.classList.toggle('hidden');
    title.innerText = loginBox.classList.contains('hidden') ? 'Create Account' : 'Sign In';
    alert.innerHTML='';
}

function togglePass(id){
    let f=document.getElementById(id);
    f.type = f.type==='password'?'text':'password';
}

function toastMsg(type,msg){
    let t = document.getElementById('toast');
    t.innerHTML = `<div class="toast show text-bg-${type} p-2">${msg}</div>`;
    setTimeout(()=>t.innerHTML='',3000);
}

function loading(btn,on){
    btn.disabled = on;
    btn.querySelector('.spinner-border').classList.toggle('d-none',!on);
    btn.querySelector('.btn-text').classList.toggle('d-none',on);
}

function send(data){
    console.log(data)
    return fetch('./Backend/auth.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(data)
    }).then(r=>r.json());
}

function login(){
    loading(loginBtn,true);
    send({
        action:'login',
        email:l_email.value,
        password:l_pass.value,
        remember: remember.checked ? 1 : 0
    })
    .then(r=>{
        loading(loginBtn,false);
        if(r.status==='ok'){
            toastMsg('success','Login successful');

            // Redirect after short delay to show toast
            setTimeout(()=>{
                window.location.href = 'home.php';
            }, 500);
        } else {
            toastMsg('danger',r.msg);
        }
    });
}

function register(){
    loading(regBtn,true);
    send({
        action:'register',
        inst:inst.value,
        name:s_name.value,
        email:r_email.value,
        password:r_pass.value
    }).then(r=>{
        console.log(r)
        loading(regBtn,false);
        r.status==='ok' 
        ? (toastMsg('success','Registered successfully'), toggle()) 
        : toastMsg('danger',r.msg);
    });
}

function strength(){
    let v = r_pass.value.length;
    bar.style.width = Math.min(v*12.5,100)+'%';
    bar.className='progress-bar bg-'+(v<6?'danger':v<10?'warning':'success');
}
</script>

</body>
</html>