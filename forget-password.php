<?php
    session_start();
    include './Backend/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>forgot password</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Montserrat', sans-serif;
    }

    :root {

        /* Primary Colors */
        --primary: #7c3aed;
        --primary-dark: #6d28d9;
        --primary-light: #8b5cf6;

        /* Gradients */
        --primary-gradient: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        --secondary-gradient: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);

        /* Layout Colors */
        --background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
        --sidebar-bg: #1e293b;
        --sidebar-active: #334155;
        --card-bg: #ffffff;

        /* Text Colors */
        --text-dark: #1e293b;
        --text-light: #64748b;

        /* UI Elements */
        --border-color: #e2e8f0;
        --nav-fill: #ffffff;
        --svg-fill: black;

        /* Effects */
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06);

        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
                    0 4px 6px -2px rgba(0, 0, 0, 0.05);

        /* Radius */
        --radius: 12px;
        --radius-sm: 8px;

        /* Animation */
        --transition: all 0.3s ease;

    }

    body {
        background: var(--background);
        color: var(--text-dark);
    }

    .container form {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 5px;
    }

    .otptxt {
        font-size: 13px;
        margin: 10px;
        color: var(--text-dark);
    }

    .toogle-pass {
        width: 100%;
        gap: 9px;
        font-size: 12px;
        display: flex;
        align-items: center;
        flex-direction: row;
        color: var(--text-dark);
    }

    .otp-container {
        display: flex;
        justify-content: space-between;
        gap: 5px;
        width: 100%;
    }

    .error {
        display: none;
        color: var(--primary-red);
        width: 100%;
        font-size: 12px;
        margin: 5px;
    }

    .otp-btn {
        width: 100%;
        margin: 9px;
        color: var(--primary-red-light);
        font-size: 13px;
        cursor: pointer;
        display: none;
    }

    .otp {
        width: 100%;
        display: none;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }

    .otp-container input {
        text-align: center;
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        color: var(--text-dark);
    }

    .submit-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    .return {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-direction: row;
    }

    .return svg {
        cursor: pointer;
        filter: var(--invert);
    }

    #showPass {
        cursor: pointer;
        filter: var(--invert);
    }

    h1 {
        margin-bottom: 60px;
        color: var(--text-dark);
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

    @media (min-width:601px) {
        body {
            backdrop-filter: blur(10px);
            display: flex;
            background-repeat: no-repeat;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-attachment: fixed;
            background-position: center;
            background-size: cover;
            flex-direction: column;
            height: 100vh;
            user-select: none;
        }

        .container {
            display: flex;
            background-color: var(--card-bg);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
            position: relative;
            overflow: hidden;
            width: 100%;
            height: 100vh;
            max-width: 100%;
            min-height: 480px;
            align-items: center;
            justify-content: space-between;
            flex-direction: column;
            border: 1px solid var(--border-color);
            padding: 40px;
        }

        .container button {
            background: var(--primary-gradient);
            color: #fff;
            font-size: 12px;
            padding: 10px 45px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 10px;
            cursor: pointer;
        }

        .container input[type="text"],
        [type="email"],
        [type="password"],
        select {
            background-color: var(--nav-fill);
            border: 1px solid var(--border-color);
            margin: 8px 0;
            padding: 10px 15px;
            font-size: 13px;
            border-radius: 8px;
            width: 100%;
            outline: none;
            height: 45px;
            overflow: hidden;
            color: var(--text-dark);
        }

        .container form {
            max-width: 450px;
        }
    }

    @media(max-width: 601px) {
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            height: 100vh;
        }

        .container {
            display: flex;
            background-color: var(--card-bg);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
            position: relative;
            overflow: hidden;
            width: 768px;
            z-index: 0;
            max-width: 100%;
            min-height: 480px;
            padding: 40px 40px;
            height: 100vh;
            align-items: center;
            justify-content: space-between;
            flex-direction: column;
            border: 1px solid var(--border-color);
        }

        .container button {
            background: var(--primary-gradient);
            color: #fff;
            font-size: 12px;
            padding: 10px 45px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 10px;
            cursor: pointer;
            width: 100%;
            height: 45px;
        }

        .container input[type="text"],
        [type="email"],
        [type="password"],
        select {
            background-color: var(--nav-fill);
            border: 1px solid var(--border-color);
            margin: 8px 0;
            padding: 10px 15px;
            font-size: 15px;
            border-radius: 8px;
            width: 100%;
            outline: none;
            height: 45px;
            overflow: hidden;
            color: var(--text-dark);
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="return" onclick="goBack()">
            <div><svg width="26" height="24" viewBox="0 0 26 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M25.25 12.75H3.81247L13 21.9375L11.845 23.25L0.469971 11.875L11.845 0.5L13 1.8125L3.81247 11H25.25V12.75Z" fill="black"/>
                </svg>
            </div>
            <div></div>
        </div>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" >
            <h1>Reset Password</h1>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
            <div id="error-email" class="error"></div>
            <button type="button" name="forgot" onclick="send_otp(event)" id="sendOTP" disabled style="opacity: 0.5;">Request OTP</button>
            <div class="otp">
                <label class="otptxt" for="otp">Enter OTP : </label>
                <div class="otp-container">
                    <input type="text" name="otp1" maxlength="1" id="otp1" oninput="moveFocus(this, 'otp2', 'next')" onkeydown="handleBackspace(event, this, 'otp1')" />
                    <input type="text" name="otp2" maxlength="1" id="otp2" oninput="moveFocus(this, 'otp3', 'next')" onkeydown="handleBackspace(event, this, 'otp2')" />
                    <input type="text" name="otp3" maxlength="1" id="otp3" oninput="moveFocus(this, 'otp4', 'next')" onkeydown="handleBackspace(event, this, 'otp3')" />
                    <input type="text" name="otp4" maxlength="1" id="otp4" onkeydown="handleBackspace(event, this, 'otp4')" />
                </div>
                <div id="error-otp" class="error"></div>
                <div class="otp-btn" id="otp-btn"></div>
            </div>
                <input type="password" name="password" placeholder="Password"  id="password" class='password'>
                <input type="password" name="password2" placeholder="Re-Enter Password"  id="password2" class='password'>
                    <div id="error-password" class="error"></div>
                <div class='toogle-pass'><input type="checkbox" id="showPass" onclick="showPassword()"> Show Password</div>
                <div id="error-empty" class="error"></div>
        </form>
        <div class="submit-btn">
            <button onclick="reset(event)" type="submit" id="signup-btn" name="reset_pass">Reset Password</button>
        </div>
    </div>
    <div class="toast-container" id="toast"></div>
    <script>
        function goBack() {
            window.history.back();
        }

        function showPassword() {
            var passwordInputs = document.querySelectorAll(".password");
            var checkbox = document.getElementById("showPass");

            passwordInputs.forEach(function(input) {
                input.type = checkbox.checked ? "text" : "password";
            });
        }

        function toastMsg(type,msg){
            let t = document.getElementById('toast');
            t.innerHTML = `<div class="toast show text-bg-${type} p-2">${msg}</div>`;
            setTimeout(()=>t.innerHTML='',3000);
        }

        let reset = (e)=>{
            e.preventDefault();
        
            let email = document.getElementById('email').value;
            let otp1 = document.getElementById('otp1').value;
            let otp2 = document.getElementById('otp2').value;
            let otp3 = document.getElementById('otp3').value;
            let otp4 = document.getElementById('otp4').value;
            let otp = otp1 + otp2 + otp3 + otp4;
            let password = document.getElementById('password').value;
            let password2 = document.getElementById('password2').value;
            let data = {
                'email': email,
                'otp': otp,
                'password': password,
                'password2': password2,
            }

            fetch("./Backend/reset_password.php", {
                method: 'POST',
                body: JSON.stringify(data),
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then((data)=>{

                if(data.status === 200){
                    toastMsg('success', 'Password Reset Successfully!');
                    window.history.back();
                    
                }else{
                    toastMsg('danger', data.message);
                }

                console.log(data.message);
            })
            .catch(error => console.log(error));

        };
        
        let send_otp =(e)=>{
            e.preventDefault();
            let email = document.getElementById('email').value;
            let send_btn = document.getElementById('sendOTP'); 
            send_btn.innerText = 'Proccessing...';
            let data = {
                'role': '',
                'email': email,
                'fname': '',
                'lname': '',
                'for' : 'forgot'
            }
            data = JSON.stringify(data);
            e.preventDefault();
            fetch('./OTP-mail.php', {
                        method: 'POST',
                        body: data,
                        headers: {
                            'Content-type': 'application/json; charset=UTF-8'
                        }
                    })
            .then((response) => response.json())
            .then((data) => {

                        if(data.status == "error"){

                            if(!send_btn){
                                console.error("Send OTP button not found");
                                return;
                            }

                            send_btn.innerText = 'Request OTP';
                            send_btn.setAttribute('disabled', 'true');
                            send_btn.style.opacity = '0.5';

                            toastMsg('danger', data.message);
                        }else{
                            sent();
                            toastMsg('success', `OTP sent successfully! on ${email}`);
                            send_btn.innerText = 'Request OTP'; 
                        }
                        console.log(data);
                    })
            .catch();
        }

        function sent(){
                
                let otp_container = document.querySelector('.otp');
                let send_again_btn = document.getElementById('otp-btn');
                let send_btn = document.getElementById('sendOTP'); 

                if(!send_btn){
                    console.error("Send OTP button not found");
                    return;
                }

                otp_container.style.display = 'flex';
                send_again_btn.style.display = 'block';
                send_btn.setAttribute('disabled', 'true');
                send_btn.style.opacity = '0.5';

                let waitTime = 59;
                send_again_btn.innerHTML = '00:' + waitTime;

                let countdown = setInterval(() => {
                    waitTime--;
                    send_again_btn.innerHTML = '00:' + (waitTime < 10 ? '0' + waitTime : waitTime);

                    if (waitTime <= 0) {
                        clearInterval(countdown);
                        send_again_btn.disabled = false;
                        send_again_btn.innerHTML = "<span class='sendagain' onclick='send_otp(event)'>Resend OTP</span>";
                    }
                }, 1000);
            };

             function moveFocus(current, nextId, direction) {
                if (direction === 'next' && current.value.length === 1) {
                    
                    const nextInput = document.getElementById(nextId);
                    if (nextInput) nextInput.focus();
                }
            };
            function handleBackspace(event, current, currentId) {
                if (event.key === "Backspace" && current.value === "") {
                    const prevInput = current.previousElementSibling;
                    if (prevInput) prevInput.focus();
                }
            };

            setInterval(() => {
                let send_btn = document.querySelector('#sendOTP');
                let email2 = document.getElementById('email');
                email2.addEventListener('input', function () {
                    var email = email2.value;
                    var isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                    send_btn.removeAttribute('disabled');
                    send_btn.style.opacity = '1';
                });
           },10); 

           // Disable right-click
 // document.addEventListener('contextmenu', event => event.preventDefault());

  // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
  document.onkeydown = function(e) {
    if(e.keyCode == 123) return false; // F12
    if(e.ctrlKey && e.shiftKey && (e.keyCode == 'I'.charCodeAt(0))) return false;
    if(e.ctrlKey && e.shiftKey && (e.keyCode == 'J'.charCodeAt(0))) return false;
    if(e.ctrlKey && (e.keyCode == 'U'.charCodeAt(0))) return false;
  }
    </script>
</body>
</html>