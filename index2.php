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
:root {
    --primary: #7c3aed;
    --primary-dark: #6d28d9;
    --primary-light: #8b5cf6;
    --primary-gradient: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    --secondary-gradient: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    --sidebar-bg: #1e293b;
    --sidebar-active: #334155;
    --card-bg: #ffffff;
    --text-dark: #1e293b;
    --text-light: #64748b;
    --border: #e2e8f0;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --radius: 12px;
    --radius-sm: 8px;
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}

html, body {
    height: 100%;
    margin: 0;
    background: #f4f6f8;
    color: var(--text-dark);
}

.fullscreen-row {
    height: 100vh;
    margin: 0;
}

/* BRAND PANEL - Updated with theme */
.brand-panel {
    background: var(--primary-gradient);
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    overflow: hidden;
    padding: 40px;
    min-height: 100%;
    position: relative;
}

.brand-panel::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%236d28d9' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
    opacity: 0.2;
}

.brand-text {
    opacity: 0;
    transform: translateY(50px);
    animation: fadeUp 1s forwards;
    z-index: 1;
}

.brand-text:nth-child(1) { animation-delay: 0.5s; }
.brand-text:nth-child(2) { animation-delay: 1s; }

@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
}

.brand-panel i {
    font-size: 4rem;
    margin-bottom: 20px;
    background: white;
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.brand-panel h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: white;
}

.brand-panel p {
    font-size: 1.1rem;
    opacity: 0.9;
    max-width: 500px;
    line-height: 1.6;
}

/* AUTH CARD - Updated with theme */
.auth-card {
    max-width: 450px;
    width: 90%;
    border-radius: var(--radius);
    background: var(--card-bg);
    padding: 40px;
    box-shadow: var(--shadow-lg);
    animation: fadeInCard 0.8s ease-in-out;
    transition: var(--transition);
}

.auth-card:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

@keyframes fadeInCard {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

#title {
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 30px;
    text-align: center;
}

/* Form elements */
.form-floating {
    position: relative;
    margin-bottom: 1.5rem;
}

.form-control {
    border: 2px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 1rem 0.75rem;
    font-size: 1rem;
    transition: var(--transition);
    background: #f8fafc;
}

.form-control:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    background: white;
}

.form-label {
    color: var(--text-light);
    font-weight: 500;
}

.input-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: var(--text-light);
    z-index: 2;
    transition: var(--transition);
}

.input-icon:hover {
    color: var(--primary);
}

/* Buttons */
.btn {
    padding: 0.875rem 1.5rem;
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 1rem;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.btn-primary {
    background: var(--primary-gradient);
    color: white;
}

.btn-primary:hover {
    background: var(--secondary-gradient);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-success:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* Progress bar */
.progress {
    height: 6px;
    border-radius: 3px;
    background-color: var(--border);
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-bar {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.bg-danger { background: linear-gradient(to right, #ef4444, #dc2626); }
.bg-warning { background: linear-gradient(to right, #f59e0b, #d97706); }
.bg-success { background: linear-gradient(to right, #10b981, #059669); }

/* Checkbox */
.form-check-input {
    width: 1.1em;
    height: 1.1em;
    margin-top: 0.2em;
    border: 2px solid var(--border);
    transition: var(--transition);
}

.form-check-input:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

/* Links */
a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
}

a:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

/* Toast */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    background: var(--card-bg);
    border: none;
    border-radius: var(--radius-sm);
    box-shadow: var(--shadow-lg);
    padding: 1rem 1.5rem;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.text-bg-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.text-bg-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

/* Alert */
#alert {
    margin-bottom: 1.5rem;
}

.hidden {
    display: none !important;
}

/* Spinner */
.spinner-border {
    width: 1.2rem;
    height: 1.2rem;
    border-width: 0.2em;
}

/* Responsive */
@media (max-width: 1200px) {
    .auth-card {
        max-width: 420px;
        padding: 35px;
    }
}

@media (max-width: 992px) {
    .brand-panel {
        display: none;
    }
    
    .fullscreen-row {
        align-items: center;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .auth-card {
        max-width: 380px;
        padding: 30px;
    }
    
    #title {
        font-size: 1.6rem;
    }
    
    .brand-panel h2 {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    html, body {
        padding: 15px;
    }
    
    .auth-card {
        max-width: 100%;
        padding: 25px;
        box-shadow: var(--shadow);
    }
    
    #title {
        font-size: 1.4rem;
        margin-bottom: 25px;
    }
    
    .btn {
        padding: 0.75rem 1.25rem;
    }
}

/* List styling */
ul.small.text-muted {
    padding-left: 1.2rem;
    margin-bottom: 1.5rem;
}

ul.small.text-muted li {
    margin-bottom: 0.3rem;
    color: var(--text-light);
    font-size: 0.9rem;
}

.row>* {
    padding-right: 0;
    padding-left: 0;
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