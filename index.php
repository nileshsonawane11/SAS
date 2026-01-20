<?php
// require './Backend/auth_guard.php';
include './Backend/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Dashboard</title>
    <style>
        :root{
            --gradient-bg: linear-gradient(120deg, rgb(245 200 255), rgba(174, 0, 255, 1));
            --menu-bg: #34495E;
            --btn-bg:linear-gradient(270deg, rgb(232 127 255), rgba(174, 0, 255, 1));
        }
        *{
            margin: 0;
            padding: 0;
            font-family:Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }
        body{
            height: 100vh;
            width: 100%;
            display: flex;
            flex-direction: row;
        }
        .menu{
            height: 100vh;
            width: 30%;
            background-color: var(--menu-bg);
            color: white;
            overflow: hidden;
        }
        .admin-details{
            height: 115px;
            display: flex;
            gap: 15px;
            flex-direction: column;
            padding: 25px;
            align-items: flex-start;
            justify-content: space-between;
            border-bottom: 1px solid;
        }
        .admin-details h4{
            font-size: 25px;
        }
        .logo-name{
            display: flex;
            width: 100%;
            align-items: center;
            flex-direction: row;
            gap: 15px;
        }
        .logo-name img{
            height: 50px;
            width: 50px;
            overflow: hidden;
            border-radius: 50%;
            object-fit: contain;
        }
        .logo-name .admin-name{
            font-size: 20px;
        }
        .menu-items{
            padding: 40px 0px 20px 0px;
            display: flex;
            gap: 10px;
            flex-direction: column;
            align-items: flex-start;
        }
        .items{
            font-size: 22px;
            width: 100%;
            display: flex;
            gap: 10px;
            padding: 15px;
            transition: all 0.3s ease-in-out;
            flex-direction: row;
            align-items: center;
            cursor: pointer;
        }
        .items.active{
            background: #23313f;
            border-left: 10px solid;
        }
        .container{
            width: 100%;
            overflow: hidden;
        }
        .nav{
            height: 50px;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: white;
        }
        .nav-part{
            width: 100%;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .nav-part h3{
            padding-left: 30px;
        }
        .nav-part:first-child{
            font-size: 27px;
        }
        .nav-part:nth-child(2){
            font-size: 20px;
        }
        .nav-part:last-child{
            justify-content: flex-end;
        }
        .card{
            background: var(--gradient-bg);
            height: 100dvh;
            display: flex;
            flex-direction: column;
            width: 100%;
            position: relative;
            overflow: auto;
        }
        .count-bar{
            display: flex;
            padding: 30px;
            flex-direction: row;
            justify-content: space-evenly;
            align-items: center;
        }
        .count-container{
            width: 230px;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 25px;
            font-size: 20px;
            padding: 35px;
            background: #ffff;
            border-radius: 15px;
        }
        .count-txt{
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            gap: 7px;
            font-weight: 600;
        }
        .count{
            font-size: 35px;
        }
        .identity{
            height: 60px;
            width: 60px;
            display: flex;
            border-radius: 15px;
            background: var(--gradient-bg);
            justify-content: center;
            align-items: center;
        }
        .pls {
            display: inline-flex;
            position: fixed;
            right: 25px;
            bottom: 25px;
            align-items: center;
            gap: 10px;
            background: #ffffffff;
            padding: 12px 14px;
            border-radius: 40px;
            cursor: pointer;
            overflow: hidden;
            width: 25px;
            transition: width 0.4s ease;
        }

        .pls:hover {
            width: 200px;
        }

        .icon {
            min-width: 26px;
        }

        .text {
            color: #000000ff;
            font-weight: bold;
            white-space: nowrap;
            transform: translateX(40px);
            opacity: 0;
            transition: all 0.4s ease;
        }
        .pls:hover .text {
            transform: translateX(0);
            opacity: 1;
        }
        .add-container{
            padding: 25px;
            display: flex;
            gap: 5px;
            flex-direction: column;
        }
        .add-staff,
        .add-slot,
        .Add_block,
        .delete-selected,
        .delete-selected_blocks,
        .add-block,
        .add-schedule,
        .Add_staff{
            width: 100%;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            border: none;
            font-size: 20px;
            padding: 10px;
            gap: 10px;
            color: #ffff;
            background: var(--btn-bg);
            cursor: pointer;
        }
        .table-wrapper {
            margin-bottom: 90px;      /* height of scroll area */
            overflow-y: auto;
            border: 1px solid #ddd;
        }
        .staff-table {
            width: 100%;
            border-collapse: collapse;
        }
        .staff-table thead tr th {
            position: sticky;
            top: 0;
            background: #d371ff;
            z-index: 2;
        }
        .staff-table th,
        .staff-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .staff-table td {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        .icon-cell {
            cursor: pointer;
        }
        .form-container{
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .task-form{
            padding: 15px;
            width: 400px;
            background: #fff;
            display: flex;
            border-radius: 15px;
            gap: 20px;
            flex-direction: column;
            justify-content: center;
        }
        .inputfield{
            display: flex;
            flex-direction: column;
            gap: 5px;
        }.inputfield input{
            padding-left: 10px;
        }
        select{
            cursor: pointer;
        }
        .inputfield input,
        .inputfield select,
        .inputfield textarea{
            height: 40px;
            width: 100%;
            padding: 0 10px;
            font-size: 17px;
            border: 1px solid #000;
            border-radius: 5px;
            outline: none;
            box-sizing: border-box;
        }
        .inputfield textarea{
            max-width: 100%;
            min-width: 100%;
            max-height: 200px;
            min-height: 40px;
        }
        .required{
            color: #ff0000ff;
        }
        .staff-form{
            height: 90%;
            width: 90%;
            border-radius: 15px;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            padding: 20px;
        }
        .staff-form-heading{
            font-size: 30px;
        }
        .staff-form-body{
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 20px;
        }
        .form-row{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        #block_dialog,
        #staff_dialog,
        #slot_dialog {
            position: absolute;
            top: 55%;
            left: 60%;
            transform: translate(-50%, -50%);
            border: none;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 320px;
        }
        #block_dialog::backdrop,
        #staff_dialog::backdrop {
            background: rgba(0,0,0,0.4);
        }
        .task{
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-direction: row;
        }
        .cancel_btn{
            color:#000;
            background: none;
            border:0.5px solid black;
        }
        dialog .inputfield{
            gap: 20px;
        }
        .staff_dialog_form{
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .error{
            color: red;
            font-family: fangsong;
        }
        .block-data{
            padding: 15px;
            border-radius: 15px;
            display: grid;
            grid-template-columns: 10% 50% 15% 15% 10%;
            background: #fff;
            justify-items: center;
            align-items: center;
        }
        .block-serial{
            cursor: pointer;
        }
        .block-data:first-child{
            position: sticky;
            top: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .courses{
            display: flex;
            align-content: center;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            flex-direction: row;
            gap: 15px;
        }
        .blk-no{
            padding: 5px;
            font-size: 20px;
            height: 40px;
            font-weight: 600;
            display: flex;
            width: 40px;
            background: var(--gradient-bg);
            border-radius: 50%;
            color: #fff;
            align-items: center;
            justify-content: center;
        }
        .c-list{
            padding: 5px;
            border-radius: 7px;
        }
        .history-list{
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
            overflow: auto;
            padding: 15px;
        }
        #blk-no{
            color: red;
        }
        .fullscreen,
        .exit-fullscreen {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: none;
            background: #111827;            /* dark slate */
            color: #ffffff;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px rgba(0,0,0,0.25);
            transition: all 0.25s ease;
            z-index: 9999;
        }

        /* Exit button slightly shifted */
        .exit-fullscreen {
            right: 64px;
        }

        /* Hover effect */
        .fullscreen:hover,
        .exit-fullscreen:hover {
            background: #2563eb;           /* blue */
            transform: scale(1.08);
        }

        /* Active click */
        .fullscreen:active,
        .exit-fullscreen:active {
            transform: scale(0.95);
        }

        /* Icon size */
        .fullscreen i,
        .exit-fullscreen i {
            font-size: 18px;
        }

        /* Hide exit button initially */
        .exit-fullscreen {
            display: none;
        }
    </style>
</head>
<body>
    <div class="menu">
        <div class="admin-details">
            <h4>Admin Panel</h4>
            <div class="logo-name">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB8AAAAcCAMAAACu5JSlAAABCFBMVEVHcEzh2pXr55Dm3pHg24bY0JvQx5GkmY/l3ojw7pG4roDs627r5Ifq5Y/u7pTq4LD09I/p53Du7Wjp5Jf18Wjr5I/Pzp/u7Zb19ZL7+4D8+nnd7LLk7637/Ij594f084vj7qXw7YrXpFfs3Hz+8fb19IDt9Jn3+ZDv6qL36O7m753fw2b87vLkzG3j13bv5nvq86Pd7Kns86j0+Zvo4Ir8/HT491Lt3dXw4uTiqln591zg7rzr2Wvs5Jz09GTetGPUyMPWkkjjy3fm2r797uvb4p7m2N7a5cbPlkfZfkHT4tC1paja0I10vcz8xsNIw8uHd3nKgTqM2ar/VUv5fmn0+QD8q6Dv8QCaD+/iAAAAGXRSTlMAj2+hSTclCl2QGcXAr/767qjggO7qiM/XQWcBFgAAAhJJREFUKJGVkoeO4jAQhgkQ6lIX2LWd2I7TewKh9w7br7//mxwHAu6ETrobyZI1n0fze/6Jxf4r6lL9b4jn+ETZxX49wSdStziBgczYfLfv9yOA72/4g+zK3otG19GCuerdTXmfLShqEaHZCiVpLnN/0Ay32K/1YSCsPUopQmPW55JXnCb+zmoK1LM92bdfAgtFjBSvPCsyD6G1LBOjaxDZRYHuzcX4hafkXSvUXH9pmo7ZW2IbhcZUSl/bqz4SotHYXHX63xVTciNBUMFVQAG2tOGoZCrqj69f3pxeFFmhCLJnzKnAampg7DjFb59f3xxnLNIWhOpZwB2AJKTQMFfm+/vryulJUKMYwuxvnNJ2SVmZSs9xHGUMEAJXHrtTWwECxa4zmz0/95yupAcWAJcZ8+lYATVJWzKV2cdG6ZE20qwEHz+blOYecg16ULzsbjbdbglqAVqWcOEygGQZZxpaCGBbKpF2W6N0ZgDCn8uLQGJiYUstS4cQ6EQIt5LICMCnF8n7sr9gvvEkBJYVClRDgiS6e1/PZi4G6dO5rBpPQzQMh1tDFadMfLhMv1ZN8rrMMMRFw2gWMVA/TUk8l69VjnzSGQzyuUeMRduWRyN7BIBaTsUng87g2KB6wPlJMg6BZ9vMdj2As7lqrTLoTE57nDk4markucKjjkUglrONSv5XKnPdgOM/DyeXSqVPt3+Mn/JjTapygFkhAAAAAElFTkSuQmCC" alt="">
                <label for="" class="admin-name">GPNashik</label>
            </div>
        </div>
        <div class="menu-items">
            <div class="items active" data-page="dashboard.php"><svg width="30" height="30" viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M24.375 16.875V5.625H39.375V16.875H24.375ZM5.625 24.375V5.625H20.625V24.375H5.625ZM24.375 39.375V20.625H39.375V39.375H24.375ZM5.625 39.375V28.125H20.625V39.375H5.625Z" fill="#D6D6D6"/>
            </svg><span>Dashboard</span></div>
            <div class="items" data-page="manage_staff.php"><svg width="30" height="30" viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.5 10.3125C24.2405 10.3125 25.9097 11.0039 27.1404 12.2346C28.3711 13.4653 29.0625 15.1345 29.0625 16.875C29.0625 18.6155 28.3711 20.2847 27.1404 21.5154C25.9097 22.7461 24.2405 23.4375 22.5 23.4375C20.7595 23.4375 19.0903 22.7461 17.8596 21.5154C16.6289 20.2847 15.9375 18.6155 15.9375 16.875C15.9375 15.1345 16.6289 13.4653 17.8596 12.2346C19.0903 11.0039 20.7595 10.3125 22.5 10.3125ZM9.375 15C10.425 15 11.4 15.2812 12.2438 15.7875C11.9625 18.4687 12.75 21.1312 14.3625 23.2125C13.425 25.0125 11.55 26.25 9.375 26.25C7.88316 26.25 6.45242 25.6574 5.39752 24.6025C4.34263 23.5476 3.75 22.1168 3.75 20.625C3.75 19.1332 4.34263 17.7024 5.39752 16.6475C6.45242 15.5926 7.88316 15 9.375 15ZM35.625 15C37.1168 15 38.5476 15.5926 39.6025 16.6475C40.6574 17.7024 41.25 19.1332 41.25 20.625C41.25 22.1168 40.6574 23.5476 39.6025 24.6025C38.5476 25.6574 37.1168 26.25 35.625 26.25C33.45 26.25 31.575 25.0125 30.6375 23.2125C32.2716 21.1017 33.0303 18.4428 32.7562 15.7875C33.6 15.2812 34.575 15 35.625 15ZM10.3125 34.2187C10.3125 30.3375 15.7688 27.1875 22.5 27.1875C29.2312 27.1875 34.6875 30.3375 34.6875 34.2187V37.5H10.3125V34.2187ZM0 37.5V34.6875C0 32.0812 3.54375 29.8875 8.34375 29.25C7.2375 30.525 6.5625 32.2875 6.5625 34.2187V37.5H0ZM45 37.5H38.4375V34.2187C38.4375 32.2875 37.7625 30.525 36.6562 29.25C41.4562 29.8875 45 32.0812 45 34.6875V37.5Z" fill="#D6D6D6"/>
            </svg><span>Manage Staff</span></div>
            <div class="items" data-page="manage_block.php"><svg width="30" height="30" viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19.8712 3.76873L36.7462 7.51873C36.9587 7.5612 37.1499 7.67593 37.2873 7.84342C37.4247 8.01092 37.4999 8.22083 37.5 8.43748V36.5625C37.4999 36.7791 37.4247 36.989 37.2873 37.1565C37.1499 37.324 36.9587 37.4388 36.7462 37.4812L19.8712 41.2312C19.7353 41.2584 19.595 41.2551 19.4605 41.2215C19.326 41.1879 19.2006 41.1249 19.0933 41.0371C18.9861 40.9492 18.8996 40.8386 18.8403 40.7133C18.7809 40.588 18.7501 40.4511 18.75 40.3125V4.68748C18.7501 4.54884 18.7809 4.41193 18.8403 4.28664C18.8996 4.16134 18.9861 4.05077 19.0933 3.9629C19.2006 3.87502 19.326 3.81203 19.4605 3.77845C19.595 3.74488 19.7353 3.74156 19.8712 3.76873ZM16.875 7.49998V37.5H8.90625C8.56643 37.5 8.23811 37.3769 7.98201 37.1535C7.7259 36.9302 7.55934 36.6216 7.51312 36.285L7.5 36.0937V8.90623C7.50001 8.56641 7.62308 8.23809 7.84644 7.98199C8.0698 7.72589 8.37834 7.55932 8.715 7.51311L8.90625 7.49998H16.875ZM24.375 20.625C23.8777 20.625 23.4008 20.8225 23.0492 21.1742C22.6975 21.5258 22.5 22.0027 22.5 22.5C22.5 22.9973 22.6975 23.4742 23.0492 23.8258C23.4008 24.1774 23.8777 24.375 24.375 24.375C24.8723 24.375 25.3492 24.1774 25.7008 23.8258C26.0525 23.4742 26.25 22.9973 26.25 22.5C26.25 22.0027 26.0525 21.5258 25.7008 21.1742C25.3492 20.8225 24.8723 20.625 24.375 20.625Z" fill="#D6D6D6"/>
            </svg><span>Manage Blocks</span></div>
            <!-- <div class="items" data-page="manage_slot.php"><svg width="25" height="25" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0_2061_2)">
            <path d="M10.0004 0.400024C7.45431 0.400024 5.01251 1.41145 3.21217 3.2118C1.41182 5.01215 0.400391 7.45395 0.400391 10C0.400456 11.2607 0.648833 12.509 1.13134 13.6737C1.61384 14.8384 2.32103 15.8967 3.21252 16.7881C4.10401 17.6795 5.16234 18.3866 6.32709 18.869C7.49184 19.3513 8.7402 19.5996 10.0009 19.5995C11.2616 19.5995 12.5099 19.3511 13.6746 18.8686C14.8393 18.3861 15.8976 17.6789 16.789 16.7874C17.6804 15.8959 18.3874 14.8376 18.8698 13.6728C19.3522 12.5081 19.6005 11.2597 19.6004 9.99902C19.6004 4.69802 15.3014 0.400024 10.0004 0.400024ZM10.0004 17.599C9.00234 17.599 8.01407 17.4024 7.092 17.0205C6.16992 16.6386 5.3321 16.0788 4.62638 15.373C3.92065 14.6673 3.36084 13.8295 2.97891 12.9074C2.59697 11.9853 2.40039 10.9971 2.40039 9.99902C2.40039 9.00098 2.59697 8.0127 2.97891 7.09063C3.36084 6.16856 3.92065 5.33074 4.62638 4.62501C5.3321 3.91929 6.16992 3.35948 7.092 2.97754C8.01407 2.5956 9.00234 2.39902 10.0004 2.39902V10L16.7924 6.60402C17.3233 7.65736 17.6 8.82045 17.6004 10C17.6001 12.0155 16.7993 13.9483 15.374 15.3734C13.9488 16.7985 12.0159 17.599 10.0004 17.599Z" fill="white"/>
            </g>
            <defs>
            <clipPath id="clip0_2061_2">
            <rect width="20" height="20" fill="white"/>
            </clipPath>
            </defs>
            </svg><span>Manage Slots</span></div> -->
            <div class="items" data-page="admin_panel.php"><svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M15.76 4.0375C16.3267 4.2125 16.8683 4.4375 17.385 4.7125L19.6763 3.3375C19.9152 3.1942 20.1951 3.13482 20.4716 3.16879C20.7481 3.20276 21.0054 3.32814 21.2025 3.525L22.475 4.7975C22.6719 4.99463 22.7972 5.25187 22.8312 5.52838C22.8652 5.8049 22.8058 6.08484 22.6625 6.32375L21.2875 8.615C21.5625 9.13167 21.7875 9.67333 21.9625 10.24L24.5537 10.8888C24.8241 10.9565 25.064 11.1126 25.2354 11.3322C25.4069 11.5519 25.5 11.8226 25.5 12.1012V13.8988C25.5 14.1774 25.4069 14.4481 25.2354 14.6678C25.064 14.8874 24.8241 15.0435 24.5537 15.1112L21.9625 15.76C21.7875 16.3267 21.5625 16.8683 21.2875 17.385L22.6625 19.6763C22.8058 19.9152 22.8652 20.1951 22.8312 20.4716C22.7972 20.7481 22.6719 21.0054 22.475 21.2025L21.2025 22.475C21.0054 22.6719 20.7481 22.7972 20.4716 22.8312C20.1951 22.8652 19.9152 22.8058 19.6763 22.6625L17.385 21.2875C16.8683 21.5625 16.3267 21.7875 15.76 21.9625L15.1112 24.5537C15.0435 24.8241 14.8874 25.064 14.6678 25.2354C14.4481 25.4069 14.1774 25.5 13.8988 25.5H12.1012C11.8226 25.5 11.5519 25.4069 11.3322 25.2354C11.1126 25.064 10.9565 24.8241 10.8888 24.5537L10.24 21.9625C9.67837 21.7889 9.13431 21.5629 8.615 21.2875L6.32375 22.6625C6.08484 22.8058 5.8049 22.8652 5.52838 22.8312C5.25187 22.7972 4.99463 22.6719 4.7975 22.475L3.525 21.2025C3.32814 21.0054 3.20276 20.7481 3.16879 20.4716C3.13482 20.1951 3.1942 19.9152 3.3375 19.6763L4.7125 17.385C4.43705 16.8657 4.21106 16.3216 4.0375 15.76L1.44625 15.1112C1.17615 15.0436 0.936373 14.8877 0.764953 14.6683C0.593534 14.4488 0.500286 14.1784 0.5 13.9V12.1025C0.500007 11.8238 0.593128 11.5532 0.764569 11.3335C0.936011 11.1138 1.17594 10.9577 1.44625 10.89L4.0375 10.2413C4.2125 9.67458 4.4375 9.13292 4.7125 8.61625L3.3375 6.325C3.1942 6.08609 3.13482 5.80615 3.16879 5.52963C3.20276 5.25312 3.32814 4.99588 3.525 4.79875L4.7975 3.525C4.99463 3.32814 5.25187 3.20276 5.52838 3.16879C5.8049 3.13482 6.08484 3.1942 6.32375 3.3375L8.615 4.7125C9.13167 4.4375 9.67333 4.2125 10.24 4.0375L10.8888 1.44625C10.9564 1.17615 11.1123 0.936373 11.3317 0.764953C11.5512 0.593534 11.8216 0.500286 12.1 0.5H13.8975C14.1762 0.500007 14.4468 0.593128 14.6665 0.764569C14.8862 0.936011 15.0423 1.17594 15.11 1.44625L15.76 4.0375ZM13 18C14.3261 18 15.5979 17.4732 16.5355 16.5355C17.4732 15.5979 18 14.3261 18 13C18 11.6739 17.4732 10.4021 16.5355 9.46447C15.5979 8.52678 14.3261 8 13 8C11.6739 8 10.4021 8.52678 9.46447 9.46447C8.52678 10.4021 8 11.6739 8 13C8 14.3261 8.52678 15.5979 9.46447 16.5355C10.4021 17.4732 11.6739 18 13 18Z" fill="white"></path>
            </svg><span>Setting</span></div>
            <div class="items" data-page="history.php"><svg width="30" height="30" viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.5 39.375C18.1875 39.375 14.43 37.9456 11.2275 35.0869C8.025 32.2281 6.18875 28.6575 5.71875 24.375H9.5625C10 27.625 11.4456 30.3125 13.8994 32.4375C16.3531 34.5625 19.22 35.625 22.5 35.625C26.1563 35.625 29.2581 34.3519 31.8056 31.8056C34.3531 29.2594 35.6262 26.1575 35.625 22.5C35.6238 18.8425 34.3506 15.7413 31.8056 13.1963C29.2606 10.6513 26.1587 9.3775 22.5 9.375C20.3438 9.375 18.3281 9.875 16.4531 10.875C14.5781 11.875 13 13.25 11.7188 15H16.875V18.75H5.625V7.5H9.375V11.9062C10.9688 9.90625 12.9144 8.35937 15.2119 7.26562C17.5094 6.17188 19.9387 5.625 22.5 5.625C24.8438 5.625 27.0394 6.07063 29.0869 6.96188C31.1344 7.85313 32.9156 9.05563 34.4306 10.5694C35.9456 12.0831 37.1488 13.8644 38.04 15.9131C38.9313 17.9619 39.3762 20.1575 39.375 22.5C39.3738 24.8425 38.9288 27.0381 38.04 29.0869C37.1513 31.1356 35.9481 32.9169 34.4306 34.4306C32.9131 35.9444 31.1319 37.1475 29.0869 38.04C27.0419 38.9325 24.8463 39.3775 22.5 39.375ZM27.75 30.375L20.625 23.25V13.125H24.375V21.75L30.375 27.75L27.75 30.375Z" fill="#ffffff"/>
            </svg><span>History</span></div>
        </div>
    </div>
    <div class="container">
        <div class="nav">
            <div class="nav-part"><h3 class="heading">Dashboard</h3></div>
            <div class="nav-part"><svg width="25" height="25" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 23.75C2.5 25.875 4.125 27.5 6.25 27.5H23.75C25.875 27.5 27.5 25.875 27.5 23.75V13.75H2.5V23.75ZM23.75 5H21.25V3.75C21.25 3 20.75 2.5 20 2.5C19.25 2.5 18.75 3 18.75 3.75V5H11.25V3.75C11.25 3 10.75 2.5 10 2.5C9.25 2.5 8.75 3 8.75 3.75V5H6.25C4.125 5 2.5 6.625 2.5 8.75V11.25H27.5V8.75C27.5 6.625 25.875 5 23.75 5Z" fill="black"/></svg>
                <span>
                    <?php $date = DateTime::createFromFormat('d-M-y', date('d-M-y'));
                        echo strtolower($date->format('l, F j, Y'));
                    ?>
                </span>
            </div>
            <div class="nav-part">
                <button onclick="openFullscreen()" class="fullscreen"><i class="fas fa-solid fa-expand"></i></button>
                <button onclick="closeFullscreen()" class="exit-fullscreen"><i class="fas fa-solid fa-compress"></i></button>
            </div>
        </div>
        <div class="card"></div>
    </div>
</body>
<script>
    let menu_items = document.querySelectorAll('.items');
    let heading = document.querySelector('.heading');
    let card = document.querySelector('.card');
    let elem = document.querySelector('.container');
    const fsBtn = document.querySelector(".fullscreen");
    const exitBtn = document.querySelector(".exit-fullscreen");
    
    //full screen
    function openFullscreen() {
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) { // Safari
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) { // IE11
            elem.msRequestFullscreen();
        }
        fsBtn.style.display = "none";
        exitBtn.style.display = "flex";
    }

    function closeFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
        fsBtn.style.display = "flex";
        exitBtn.style.display = "none";
    }


    fetch('dashboard.php')
    .then(res => res.text())
    .then(html => card.innerHTML = html);
    
    function remove_active_menu(){
        menu_items.forEach((item)=>{
            if(item.classList.contains('active')){
                item.classList.remove('active')
            }
        });
    }

    menu_items.forEach((item)=>{
        item.addEventListener('click',()=>{
            remove_active_menu()
            if(!(item.classList.contains('active'))){
                item.classList.add('active')
                heading.innerText =  item.innerText;
                fetch(item.dataset.page)
                .then(res => res.text())
                .then(html => card.innerHTML = html);
                return;
            }
        });
    });

// DELETE STAFF ======================================
    // Select all checkboxes
    function toggleAll(master) {
        let rows = document.getElementsByClassName('row-check');

        for (let i = 0; i < rows.length; i++) {
            rows[i].checked = master.checked;
        }

        toggleDeleteBtn(); // update button visibility
    }

    function toggleDeleteBtn() {
        let rows  = document.getElementsByClassName('row-check');
        let btn   = document.getElementById('deleteBtn');
        let addbtn   = document.querySelector('.add-staff');
        let count = 0;

        for (let i = 0; i < rows.length; i++) {
            if (rows[i].checked) count++;
        }

        if (count > 0) {
            btn.style.display = 'flex';
            addbtn.style.display = 'none';
            document.getElementById('selCount').innerText = `( ${count} )`;
        } else {
            btn.style.display = 'none';
            addbtn.style.display = 'flex';
        }
    }

    // Delete selected faculties
    function deleteSelectedStaff() {
        let ids = [];
        document.querySelectorAll('.row-check:checked').forEach(cb => {
            ids.push(cb.value);
        });

        if (ids.length === 0) {
            alert('Please select at least one faculty');
            return;
        }

        if (!confirm('Are you sure you want to delete selected faculties?')) return;

        fetch('./Backend/delete_faculty.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 200) {
                fetch('manage_staff.php')
                .then(res => res.text())
                .then(html => card.innerHTML = html);
                return;
            } else {
                alert('Delete failed');
            }
        });
    }

// DELETE BLOCK ======================================
    // Select all checkboxes
    function toggleAllblks(master) {
        let rows = document.getElementsByClassName('row-check-blk');

        for (let i = 0; i < rows.length; i++) {
            rows[i].checked = master.checked;
        }

        toggleDeleteblkBtn(); // update button visibility
    }

    function toggleDeleteblkBtn() {
        let rows  = document.getElementsByClassName('row-check-blk');
        let btn   = document.getElementById('deleteblkBtn');
        let addbtn   = document.querySelector('.add-block');
        let count = 0;

        for (let i = 0; i < rows.length; i++) {
            if (rows[i].checked) count++;
        }

        if (count > 0) {
            btn.style.display = 'flex';
            addbtn.style.display = 'none';
            document.getElementById('selblkCount').innerText = `( ${count} )`;
        } else {
            btn.style.display = 'none';
            addbtn.style.display = 'flex';
        }
    }

    // Delete selected faculties
    function deleteSelectedblock() {
        let ids = [];
        document.querySelectorAll('.row-check-blk:checked').forEach(cb => {
            ids.push(cb.value);
        });

        if (ids.length === 0) {
            alert('Please select at least one Block');
            return;
        }

        if (!confirm('Are you sure you want to delete selected Blocks?')) return;

        fetch('./Backend/delete_block.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ ids })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 200) {
                fetch('manage_block.php')
                .then(res => res.text())
                .then(html => card.innerHTML = html);
                return;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => console.error(err));
    }


    // //Delete Staff
    // let delete_staff = (el) => {
    //     const tr = el.closest('tr');
    //     const facultyId = tr.getAttribute('data-id');

    //     if (!confirm('Are you sure you want to delete this faculty?')) return;

    //     fetch('./Backend/delete_faculty.php', {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/json'
    //         },
    //         body: JSON.stringify({ id: facultyId })
    //     })
    //     .then(res => res.json())
    //     .then(data => {
    //         if (data.status === 200) {
    //             fetch('manage_staff.php')
    //             .then(res => res.text())
    //             .then(html => card.innerHTML = html);
    //             return;
    //         } else {
    //             alert('Error: ' + data.message);
    //         }
    //     })
    //     .catch(err => console.error(err));
    // }

    //Delete Block
    // let delete_block = (el) => {
    //     const tr = el.closest('tr');
    //     const blockId = tr.getAttribute('data-id');

    //     if (!confirm('Are you sure you want to delete this block?')) return;

    //     fetch('./Backend/delete_block.php', {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/json'
    //         },
    //         body: JSON.stringify({ id: blockId })
    //     })
    //     .then(res => res.json())
    //     .then(data => {
    //         if (data.status === 200) {
    //             fetch('manage_block.php')
    //             .then(res => res.text())
    //             .then(html => card.innerHTML = html);
    //             return;
    //         } else {
    //             alert('Error: ' + data.message);
    //         }
    //     })
    //     .catch(err => console.error(err));
    // }

    //Delete slot
    let delete_slots = (el) => {
        const tr = el.closest('tr');
        const slot_id = tr.getAttribute('data-id');

        const data = {
            action       : 'delete',
            slot_id     : slot_id
        };

        fetch('Backend/slot_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            console.log(res);
            fetch('manage_slot.php')
            .then(res => res.text())
            .then(html => card.innerHTML = html);
            return;
        })
        .catch(err => console.error(err));
    }

    //Goto Edit Block
    let edit_block = (el) => {
        const tr = el.closest('tr');
        const blockId = tr.getAttribute('data-id');

        fetch('add-block.php?b='+blockId)
        .then(res => res.text())
        .then(html => {
            card.innerHTML = html;
        });
    }

    //Goto Edit Staff
    let edit_staff = (el) => {
        const tr = el.closest('tr');
        const staffId = tr.getAttribute('data-id');

        location.href = ('staff_profile.php?s='+staffId)
    }

    //Goto Edit Slot
    let edit_slots = (el) => {
        const tr = el.closest('tr');
        const slot_Id = tr.getAttribute('data-id');

        fetch('add-slot.php?s='+slot_Id)
        .then(res => res.text())
        .then(html => {
            card.innerHTML = html;
        });
    }

    //add and update staff
    function Add_staff(s_id) {
        submitStaff('add',s_id);
    }

    function Update_staff(s_id) {
        submitStaff('update',s_id);
    }

    //add schedule
    let add_schedule = () => {
        let formdata = new FormData();
            
        formdata.append('time_table',document.querySelector('#time_table_file').files[0]);
        formdata.append('task_type',document.querySelector('#task_type').value);
        formdata.append('task_name',document.querySelector('#task').value);

        fetch('Backend/add_schedule.php', {
            method: 'POST',
            body: formdata
        })
        .then(res => res.json())
        .then(res => {
            console.log(res)
            if (res.status !== 200) {
                document.querySelector(`#err_${res.field}`).innerText = res.message;
                return;
            }
            alert(res.message);
            window.location.href = "allocate.php?s="+res.id;
        })
        .catch(err => console.error(err));
    }

    function submitStaff(action,s_id) {

        const data = {
            action       : action,
            staff_id     : s_id, // empty for add
            faculty_name : document.getElementById('staff-name').value.trim(),
            courses      : document.getElementById('staff-course').value.trim(),
            dept_code    : document.getElementById('staff-Branch').value,
            role         : document.getElementById('staff-role').value,
            duties       : document.getElementById('staff-duties').value,
            status       : document.getElementById('staff-status').value
        };

        fetch('Backend/save_staff.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if (res.status !== 200) {
                document.querySelector(`#err_${res.field}`).innerText = res.message;
                return;
            }
            fetch('manage_staff.php')
            .then(res => res.text())
            .then(html => card.innerHTML = html);
            return;
        })
        .catch(err => console.error(err));
    }

    //add and update block
    function Add_block(b_id) {
        submitBlock('add',b_id);
    }

    function Update_block(b_id) {
        submitBlock('update',b_id);
    }

    function submitBlock(action,b_id) {

        const data = {
            action       : action,
            block_id     : b_id, // empty for add
            block_no: document.getElementById('block-no').value,
            place: document.getElementById('dept').value,
            capacity: document.getElementById('block-capacity').value,
            double_sit: document.querySelector('#double_sit').value
        };

        fetch('Backend/block_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            console.log(res)
            if (res.status !== 200) {
                document.querySelector(`#err_${res.field}`).innerText = res.message;
                return;
            }
            fetch('manage_block.php')
            .then(res => res.text())
            .then(html => card.innerHTML = html);
            return;
        })
        .catch(err => console.error(err));
    }

    //add and update slots
    function Add_slot(slot_id) {
        submitSlot('add',slot_id);
    }

    function Update_slot(slot_id) {
        submitSlot('update',slot_id);
    }

    function submitSlot(action,slot_id) {

        const data = {
            action       : action,
            slot_id     : slot_id, // empty for add
            exam_name : document.getElementById('exam-name').value,
            slot_mode: document.getElementById('slot-mode').value,
            slot_start_time: document.getElementById('slot-start-time').value,
            slot_end_time: document.querySelector('#slot-end-time').value
        };

        fetch('Backend/slot_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            console.log(res)
            if (res.status !== 200) {
                document.querySelector(`#err_${res.field}`).innerText = res.message;
                return;
            }
            fetch('manage_slot.php')
            .then(res => res.text())
            .then(html => card.innerHTML = html);
            return;
        })
        .catch(err => console.error(err));
    }

    let go_to_schedule = (s) => {
        window.location.href = "./allocate.php?s="+s;
    }

    //Click Events
    document.addEventListener('click', function (e) {
        //assign supervision
        if (e.target.closest('.pls')) {

            fetch('add_schedule.php')
                .then(res => res.text())
                .then(html => {
                    card.innerHTML = html;
                });
        }
        
        //add-staff
        if (e.target.closest('.add-staff')) {

            fetch('add-staff.php')
                .then(res => res.text())
                .then(html => {
                    card.innerHTML = html;
                });
        }

        if (e.target.closest('.add-slot')) {

            fetch('add-slot.php')
                .then(res => res.text())
                .then(html => {
                    card.innerHTML = html;
                });
        }

        //add-block
        if (e.target.closest('.add-block')) {

            fetch('add-block.php')
                .then(res => res.text())
                .then(html => {
                    card.innerHTML = html;
                });
        }

        //add-schedule
        // if (e.target.closest('.add-schedule')) {
        //     window.location.href = "Block_allocation.php";
        // }

        //open import file container
        if (e.target.closest('.import_btn')) {
            document.querySelector('#staff_dialog').showModal();
        }else if(e.target.closest('.import_block_btn')){
            document.querySelector('#block_dialog').showModal();
        }else if(e.target.closest('.import_slot_btn')){
            document.querySelector('#slot_dialog').showModal();
        }

        //import staff file
        if (e.target.closest('.Add_staff_file_btn')) {
            e.preventDefault();
            let fileInput = document.querySelector('#staff_file');

            let formdata = new FormData();
            formdata.append('excel_file',fileInput.files[0]);

            fetch('Backend/upload_staff.php',{
                method : 'post',
                body : formdata
            })
            .then(res => res.json())
            .then(data => {
                console.log(data);
                if(data.status != 200){
                    document.querySelector(`#err_${data.field}`).innerText = data.message;
                    return;
                }else if(data.status == 200){
                    fetch('manage_staff.php')
                    .then(res => res.text())
                    .then(html => card.innerHTML = html);
                    return;
                }
            })
            .catch(err => console.error(err));
        }

        //import block file
        if (e.target.closest('.Add_block_file_btn')) {
            e.preventDefault();
            let fileInput = document.querySelector('#block_file');

            let formdata = new FormData();
            formdata.append('excel_file',fileInput.files[0]);

            fetch('Backend/upload_block.php',{
                method : 'post',
                body : formdata
            })
            .then(res => res.json())
            .then(data => {
                console.log(data);
                if(data.status != 200){
                    document.querySelector(`#err_${data.field}`).innerText = data.message;
                    return;
                }else if(data.status == 200){
                    fetch('manage_block.php')
                    .then(res => res.text())
                    .then(html => card.innerHTML = html);
                    return;
                }
            })
            .catch(err => console.error(err));
        }

        //import slot file
        if (e.target.closest('.Add_slot_file_btn')) {
            e.preventDefault();
            let fileInput = document.querySelector('#slot_file');

            let formdata = new FormData();
            formdata.append('excel_file',fileInput.files[0]);

            fetch('Backend/upload_slot.php',{
                method : 'post',
                body : formdata
            })
            .then(res => res.json())
            .then(data => {
                console.log(data);
                if(data.status != 200){
                    document.querySelector(`#err_${data.field}`).innerText = data.message;
                    return;
                }else if(data.status == 200){
                    fetch('manage_slot.php')
                    .then(res => res.text())
                    .then(html => card.innerHTML = html);
                    return;
                }
            })
            .catch(err => console.error(err));
        }
    });
    
    function deleteSchedule(e, id) {
        e.stopPropagation(); // 🔥 stop go_to_schedule

        if (!confirm("Are you sure you want to delete this schedule?")) {
            return;
        }

        fetch("./Backend/delete_schedule.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ s_id: id })
        })
        .then(res => res.json())
        .then(data => {
            console.log(data);
            if (data.status === 200) {
                // remove row from UI
                const row = document.querySelector(`.block-data[data-id="${id}"]`);
                if (row) row.remove();
            } else {
                alert(data.msg || "Delete failed");
            }
        })
        .catch(() => alert("Server error"));
    }
</script>
<!-- ==================== supervison rules settings ========================= -->
<script>
    function saveSettings() {

        const data = {
            duties_restriction: document.getElementById('maxDutiesCheck').checked ? 1 : 0,
            block_capacity: parseInt(document.querySelectorAll('input[type=number]')[0].value),
            reliever: parseInt(document.querySelectorAll('input[type=number]')[1].value),
            extra_faculty: document.querySelectorAll('input[type=number]')[2].value / 100,

            role_restriction: document.getElementById('roleRestrictionCheck').checked ? 1 : 0,

            teaching_staff: document.querySelectorAll('#roleFields input[type=number]')[0].value / 100,
            non_teaching_staff: document.querySelectorAll('#roleFields input[type=number]')[1].value / 100,

            sub_restriction: document.getElementById('subjectOn').checked ? 1 : 0,
            dept_restriction: document.getElementById('deptOn').checked ? 1 : 0
        };

        // console.log(data);

        fetch('./Backend/save_supervisor_settings.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            console.log(res);
            if(res.status == 200){
                showSuccessAlert('successAlert')
            }
        });
    }
    function toggleRoleFields(checkbox, targetId) {
        const target = document.getElementById(targetId);
        if (!target) return;

        target.classList.toggle('hidden', !checkbox.checked);
    }

   function showSuccessAlert(alertId, duration = 2000) {
        const alertBox = document.getElementById(alertId);
        if (!alertBox) return;

        alertBox.classList.remove('d-none');

        setTimeout(() => {
            alertBox.classList.add('d-none');
        }, duration);
    }
    function syncPercentages(changedInput, otherInput) {
        let val = parseInt(changedInput.value) || 0;

        // Ensure value is between 0 and 100
        if (val < 0) val = 0;
        if (val > 100) val = 100;

        changedInput.value = val;

        // Automatically adjust the other input
        otherInput.value = 100 - val;
    }
</script>
<!-- ==================== Documentation settings ========================= -->
<script>
    function uploadImage(imgEl) {
        let input = '';
        if(imgEl.dataset.key == 'signature'){
            input = document.getElementById('signatureInput');
        }else if(imgEl.dataset.key == 'logo'){
            input = document.getElementById('LogoInput');
        }

        input.onchange = () => {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = e => {
                imgEl.src = e.target.result; // base64 preview
                imgEl.dataset.value = e.target.result; // store for JSON
            };
            reader.readAsDataURL(file);
        };

        input.click();
    }

    function saveLetter() {

        const formData = new FormData();
        let jsonData = {};

        // Loop through all data-key elements
        document.querySelectorAll('[data-key]').forEach(el => {

            // RADIO
            if (el.querySelector('input[type=radio]')) {
                const checked = el.querySelector('input[type=radio]:checked');
                formData.append(el.dataset.key, checked ? checked.value : 'yes');
            }

            // IMAGE
            else if (el.tagName === 'IMG') {
                if (el.dataset.key) {
                    let file = '';
                    if(el.dataset.key == 'signature'){
                        file = document.getElementById('signatureInput');
                    }else if(el.dataset.key == 'logo'){
                        file = document.getElementById('LogoInput');
                    }

                    if (file){
                        formData.append(el.dataset.key, file.files[0] || '');
                    }
                }
            }

            // TEXT
            else {
                formData.append(el.dataset.key, el.innerHTML);
            }
        });

        formData.forEach((value, key) => {
            console.log(`${key}: ${value}`);
        });

        fetch('./Backend/save_letter.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then((res) => {
            console.log(res);
            if(res.status == 200){
                showSuccessAlert('successAlert')
            }
        });
    }

    function toggleScheduleTable(el) {
        const table = document.getElementById('scheduleTable');

        if (el.value === 'no') {
            table.style.display = 'none';
        } else {
            table.style.display = 'table';
        }
    }
</script>
</html>