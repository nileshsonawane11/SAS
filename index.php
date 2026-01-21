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
    <title>Dashboard - GPNashik Admin</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            height: 100vh;
            width: 100%;
            display: flex;
            background: #f8fafc;
            overflow: hidden;
        }

        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            padding: 10px 15px;
            cursor: pointer;
            font-size: 20px;
            box-shadow: var(--shadow);
        }

        /* Sidebar Styles */
        .menu {
            height: 100vh;
            width: 280px;
            background: var(--sidebar-bg);
            color: white;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            position: relative;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .admin-details {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-details h4 {
            font-size: 18px;
            color: #cbd5e1;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .logo-name {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-name img {
            height: 50px;
            width: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-light);
        }

        .admin-name {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .menu-items {
            padding: 20px 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .items {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            color: #cbd5e1;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border-left: 4px solid transparent;
            font-size: 16px;
            font-weight: 500;
        }

        .items:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border-left-color: var(--primary-light);
        }

        .items.active {
            background: var(--sidebar-active);
            color: white;
            border-left-color: var(--primary);
            position: relative;
        }

        .items.active::before {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 50%;
            background: var(--primary);
            border-radius: 2px 0 0 2px;
        }

        .items svg {
            min-width: 24px;
            min-height: 24px;
        }

        /* Main Container */
        .container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Navigation */
        .nav {
            height: 70px;
            background: white;
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow);
            z-index: 10;
            position: relative;
        }

        .nav-part {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-part h3 {
            font-size: 24px;
            color: var(--text-dark);
            font-weight: 600;
        }

        .nav-part span {
            color: var(--text-light);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .fullscreen, .exit-fullscreen {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            border: none;
            background: var(--primary);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .fullscreen:hover, .exit-fullscreen:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .exit-fullscreen {
            display: none;
        }

        /* Card Container */
        .card {
            flex: 1;
            background: #f8fafc;
            padding: 25px;
            overflow-y: auto;
            position: relative;
            height: 100%;
        }

        /* Stats Cards */
        .count-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .count-container {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .count-container:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .identity {
            width: 60px;
            height: 60px;
            border-radius: var(--radius);
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .count-txt {
            flex: 1;
        }

        .count {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1;
            margin-bottom: 5px;
        }

        .c-text {
            font-size: 16px;
            color: var(--text-light);
            font-weight: 500;
        }

        /* History/Schedule Cards */
        .history-list {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-top: 25px;
        }

        .block-data {
            display: grid;
            justify-items: center;
            grid-template-columns: 80px 1fr 120px 180px 80px;
            gap: 15px;
            padding: 18px 25px;
            align-items: center;
            border-bottom: 1px solid var(--border);
            transition: var(--transition);
        }

        .block-data:first-child {
            background: var(--primary);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .block-data:not(:first-child):hover {
            background: #f8fafc;
        }

        .blk-no {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        .courses {
            display: flex;
            align-items: center;
        }

        .c-list {
            background: #f1f5f9;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .date, .time {
            color: var(--text-light);
            font-size: 14px;
        }

        .delete {
            cursor: pointer;
            color: #ef4444;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
        }

        .delete:hover {
            background: #fee2e2;
        }

        /* Floating Action Button */
        .pls {
            position: fixed;
            right: 30px;
            bottom: 30px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            z-index: 50;
            overflow: hidden;
            max-width: 55px;
        }

        .pls:hover {
            max-width: 230px;
            padding-right: 30px;
        }

        .icon {
            min-width: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .text {
            white-space: nowrap;
            font-weight: 600;
            font-size: 15px;
            opacity: 0;
            transform: translateX(-10px);
            transition: var(--transition);
        }

        .pls:hover .text {
            opacity: 1;
            transform: translateX(0);
        }

        /* Table Styles */
        .add-container {
            padding: 25px;
            background: white;
            border-radius: var(--radius);
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }

        .add-staff, .add-block, .delete-selected, .delete-selected_blocks {
            width: 100%;
            padding: 15px 25px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: var(--transition);
            color: white;
        }

        .add-staff, .add-block {
            background: var(--primary-gradient);
        }

        .add-staff:hover, .add-block:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .delete-selected, .delete-selected_blocks {
            background: #ef4444;
        }

        .delete-selected:hover, .delete-selected_blocks:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .table-wrapper {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            max-height: calc(100vh - 250px);
            overflow-y: auto;
        }

        .staff-table {
            width: 100%;
            border-collapse: collapse;
        }

        .staff-table thead {
            background: var(--primary);
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .staff-table th {
            padding: 18px 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 15px;
            border: none;
        }

        .staff-table td {
            padding: 16px 15px;
            border-bottom: 1px solid var(--border);
            color: var(--text-dark);
            font-size: 15px;
        }

        .staff-table tbody tr {
            transition: var(--transition);
        }

        .staff-table tbody tr:hover {
            background: #f8fafc;
        }

        .icon-cell {
            cursor: pointer;
            color: var(--primary);
            transition: var(--transition);
        }

        .icon-cell:hover {
            color: var(--primary-dark);
            transform: scale(1.1);
        }

        /* Form Styles */
        .form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100%;
            padding: 25px;
        }

        .task-form, .staff-form {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 800px;
        }

        .staff-form-heading {
            font-size: 28px;
            color: var(--text-dark);
            margin-bottom: 30px;
            font-weight: 600;
            text-align: center;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .inputfield {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .inputfield label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .inputfield input,
        .inputfield select,
        .inputfield textarea {
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 15px;
            transition: var(--transition);
            background: white;
        }

        .inputfield input:focus,
        .inputfield select:focus,
        .inputfield textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .required {
            color: #ef4444;
        }

        /* Dialog Styles */
        #block_dialog,
        #staff_dialog,
        #slot_dialog {
            border: none;
            border-radius: var(--radius);
            padding: 30px;
            width: 400px;
            max-width: 90vw;
            box-shadow: var(--shadow-lg);
        }

        #block_dialog::backdrop,
        #staff_dialog::backdrop,
        #slot_dialog::backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .menu {
                position: fixed;
                left: -280px;
                top: 0;
                height: 100vh;
                z-index: 1000;
                box-shadow: 5px 0 20px rgba(0, 0, 0, 0.2);
            }

            .menu.active {
                left: 0;
            }

            .mobile-toggle {
                display: flex;
            }

            .container {
                width: 100%;
            }

            .nav {
                padding: 0 20px;
            }

            .nav-part h3 {
                font-size: 20px;
            }

            .count-bar {
                grid-template-columns: 1fr;
            }

            .block-data {
                grid-template-columns: 60px 1fr 100px 140px 60px;
                gap: 10px;
                padding: 15px;
                font-size: 14px;
            }

            .staff-table {
                display: block;
                overflow-x: auto;
            }

            .staff-table th,
            .staff-table td {
                padding: 12px 10px;
                min-width: 120px;
            }
        }

        @media (max-width: 768px) {
            .card {
                padding: 15px;
            }

            .add-container {
                padding: 20px;
            }
            .mobile-toggle {
                display: flex;
            }

            .block-data {
                grid-template-columns: 1fr;
                gap: 10px;
                text-align: center;
                width: 100%;
            }

            .history-list {
                display: flex;
            }

            /* .block-data > div {
                width: 100%;
            } */

            .blk-no {
                margin: 0 auto;
            }

            .pls {
                right: 20px;
                bottom: 20px;
            }

            .pls:hover {
                max-width: 200px;
            }
        }

        @media (max-width: 480px) {
            .count-container {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .mobile-toggle {
                display: flex;
            }

            .identity {
                margin-bottom: 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .task-form, .staff-form {
                padding: 20px;
            }
        }

        /* Fullscreen Overlay */
        .fullscreen-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }

        .fullscreen-overlay.active {
            display: flex;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
            padding-bottom: 50px;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Custom Checkbox */
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Loading State */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 300px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f1f5f9;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Task Form Container */
    .form-container {
        /* height: 100%; */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Task Form Styling */
    .task-form {
        background: var(--card-bg);
        border-radius: var(--radius);
        padding: 35px;
        box-shadow: var(--shadow-lg);
        width: 100%;
        max-width: 480px;
        border: 1px solid var(--border);
    }

    .inputfield {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 20px;
    }

    .inputfield:last-child {
        margin-bottom: 0;
    }

    .inputfield label {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .required {
        color: #ef4444;
        font-size: 16px;
    }

    .inputfield input[type="text"],
    .inputfield select {
        padding: 14px 16px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 15px;
        transition: var(--transition);
        background: white;
        width: 100%;
        box-sizing: border-box;
    }

    .inputfield input[type="text"]:focus,
    .inputfield select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }

    .inputfield input[type="text"]::placeholder,
    .inputfield select::placeholder {
        color: #94a3b8;
    }

    /* File Input Styling */
    .inputfield input[type="file"] {
        padding: 12px;
        border-radius: var(--radius-sm);
        background: white;
        cursor: pointer;
        transition: var(--transition);
    }

    .inputfield input[type="file"]::-webkit-file-upload-button {
        background: var(--primary-gradient);
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        margin-right: 12px;
    }

    .inputfield input[type="file"]::-webkit-file-upload-button:hover {
        background: var(--primary-dark);
    }

    .inputfield input[type="file"]:hover {
        border-color: var(--primary-light);
    }

    /* Button Styling */
    .add-schedule {
        width: 100%;
        padding: 16px;
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: var(--radius-sm);
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .add-schedule:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .add-schedule:active {
        transform: translateY(0);
    }

    /* Error Message Styling */
    .error {
        color: #ef4444;
        font-size: 14px;
        margin-top: -10px;
        margin-bottom: 15px;
        padding: 8px 12px;
    }

    .error.show {
        display: block;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Select Dropdown Styling */
    select {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 16px center;
        background-size: 16px;
        padding-right: 40px !important;
        cursor: pointer;
    }

    select option {
        padding: 12px;
        font-size: 14px;
    }

    select option[value=""][hidden] {
        display: none;
    }

    /* Form Heading (if added later) */
    .task-form h3 {
        text-align: center;
        font-size: 22px;
        color: var(--text-dark);
        margin-bottom: 30px;
        font-weight: 600;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .form-container {
            padding: 15px;
        }

        .task-form {
            padding: 25px;
            max-width: 100%;
        }

        .inputfield input[type="text"],
        .inputfield select,
        .inputfield input[type="file"] {
            padding: 12px 14px;
            font-size: 14px;
        }

        .add-schedule {
            padding: 14px;
            font-size: 15px;
        }

        .task-form h3 {
            font-size: 20px;
            margin-bottom: 25px;
        }
    }

    @media (max-width: 480px) {
        .form-container {
            padding: 10px;
        }

        .task-form {
            padding: 20px;
        }

        .inputfield {
            margin-bottom: 15px;
        }

        .inputfield label {
            font-size: 13px;
        }

        .inputfield input[type="text"],
        .inputfield select,
        .inputfield input[type="file"] {
            padding: 10px 12px;
            font-size: 13px;
        }

        .add-schedule {
            padding: 12px;
            font-size: 14px;
        }

        .task-form h3 {
            font-size: 18px;
            margin-bottom: 20px;
        }
    }

    /* Loading State for Button */
    .add-schedule.loading {
        position: relative;
        color: transparent;
    }

    .add-schedule.loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        border: 2px solid white;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Focus States for Accessibility */
    .inputfield input:focus-visible,
    .inputfield select:focus-visible,
    .add-schedule:focus-visible {
        outline: 2px solid var(--primary);
        outline-offset: 2px;
    }

    /* Form Group Animation */
    .inputfield {
        animation: fadeInUp 0.4s ease-out;
        animation-fill-mode: both;
    }

    .inputfield:nth-child(1) { animation-delay: 0.1s; }
    .inputfield:nth-child(2) { animation-delay: 0.2s; }
    .inputfield:nth-child(3) { animation-delay: 0.3s; }
    .inputfield:nth-child(4) { animation-delay: 0.4s; }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* File Upload Preview */
    .file-preview {
        margin-top: 8px;
        font-size: 13px;
        color: var(--text-light);
        background: rgba(248, 250, 252, 0.8);
        padding: 8px 12px;
        border-radius: 6px;
        border-left: 3px solid var(--primary);
        display: none;
    }

    .file-preview.show {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    /* Validation Styles */
    .inputfield input:invalid,
    .inputfield select:invalid {
        border-color: #fca5a5;
    }

    .inputfield input:valid,
    .inputfield select:valid {
        border-color: #86efac;
    }

    /* Help Text */
    .help-text {
        font-size: 12px;
        color: var(--text-light);
        margin-top: 4px;
        font-style: italic;
    }


    /* Form Container */
    .form-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 100px);
        padding: 2rem;
        background: #f8fafc;
    }

    /* Form Card */
    .staff-form {
        background: var(--card-bg);
        border-radius: var(--radius);
        box-shadow: var(--shadow-lg);
        width: 100%;
        max-width: 800px;
        padding: 2rem;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Form Header */
    .staff-form-heading {
        color: var(--text-dark);
        font-size: 1.75rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--border);
        text-align: center;
    }

    /* Form Body */
    .staff-form-body {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    /* Form Rows */
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    /* Input Fields */
    .inputfield {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .inputfield label {
        color: var(--text-dark);
        font-size: 0.95rem;
        font-weight: 500;
    }

    .inputfield label span {
        color: var(--text-light);
        font-size: 0.85rem;
        font-weight: normal;
    }

    .required {
        color: #ef4444;
        font-size: 1rem;
    }

    /* Input Styles */
    .inputfield input,
    .inputfield select,
    .inputfield textarea {
        padding: 0.75rem 1rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 0.95rem;
        color: var(--text-dark);
        background: #ffffff;
        transition: var(--transition);
    }

    .inputfield textarea {
        min-height: 100px;
        resize: vertical;
        font-family: inherit;
    }

    .inputfield input:focus,
    .inputfield select:focus,
    .inputfield textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }

    /* Error Messages */
    .error {
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.5rem;
        min-height: 1.25rem;
    }

    /* Form Footer */
    .staff-from-footer {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
        gap: 1rem;
    }

    /* Button Styles */
    .Add_staff,
    .Add_block {
        padding: 0.75rem 2rem;
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: var(--radius-sm);
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        width: 100%;
    }

    .Add_staff:hover,
    .Add_block:hover {
        background: var(--secondary-gradient);
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .Add_staff:active,
    .Add_block:active {
        transform: translateY(0);
    }

    .import_btn,
    .import_block_btn {
        background: var(--primary-light);
    }

    .import_btn:hover,
    .import_block_btn:hover {
        background: var(--primary);
    }

    /* Dialog Styles */
    #staff_dialog,
    #block_dialog {
        border: none;
        border-radius: var(--radius);
        padding: 2rem;
        width: 90%;
        max-width: 500px;
        box-shadow: var(--shadow-lg);
        background: var(--card-bg);
        transform: translate(50%, 50%);
    }

    #staff_dialog::backdrop,
    #block_dialog::backdrop {
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    .staff_dialog_form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .task {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 1rem;
    }

    .Add_staff_file_btn,
    .Add_block_file_btn {
        padding: 0.75rem 1.5rem;
    }

    .cancel_btn {
        background: #64748b;
    }

    .cancel_btn:hover {
        background: #475569;
    }

    /* File Input */
    .inputfield input[type="file"] {
        padding: 0.75rem;
        border: 2px dashed var(--border);
        background: #f8fafc;
        cursor: pointer;
    }

    .inputfield input[type="file"]:hover {
        border-color: var(--primary-light);
        background: #f1f5f9;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .staff-form {
            padding: 1.5rem;
        }
        
        .staff-from-footer {
            flex-direction: column;
        }
        
        .Add_staff,
        .Add_block {
            width: 100%;
        }
    }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="menu" id="sidebar">
        <div class="admin-details">
            <h4>Admin Panel</h4>
            <div class="logo-name">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB8AAAAcCAMAAACu5JSlAAABCFBMVEVHcEzh2pXr55Dm3pHg24bY0JvQx5GkmY/l3ojw7pG4roDs627r5Ifq5Y/u7pTq4LD09I/p53Du7Wjp5Jf18Wjr5I/Pzp/u7Zb19ZL7+4D8+nnd7LLk7637/Ij594f084vj7qXw7YrXpFfs3Hz+8fb19IDt9Jn3+ZDv6qL36O7m753fw2b87vLkzG3j13bv5nvq86Pd7Kns86j0+Zvo4Ir8/HT491Lt3dXw4uTiqln591zg7rzr2Wvs5Jz09GTetGPUyMPWkkjjy3fm2r797uvb4p7m2N7a5cbPlkfZfkHT4tC1paja0I10vcz8xsNIw8uHd3nKgTqM2ar/VUv5fmn0+QD8q6Dv8QCaD+/iAAAAGXRSTlMAj2+hSTclCl2QGcXAr/767qjggO7qiM/XQWcBFgAAAhJJREFUKJGVkoeO4jAQhgkQ6lIX2LWd2I7TewKh9w7br7//mxwHAu6ETrobyZI1n0fze/6Jxf4r6lL9b4jn+ETZxX49wSdStziBgczYfLfv9yOA72/4g+zK3otG19GCuerdTXmfLShqEaHZCiVpLnN/0Ay32K/1YSCsPUopQmPW55JXnCb+zmoK1LM92rdfAgtFjBSvPCsyD6G1LBOjaxDZRYHuzcX4hafkXSvUXH9pmo7ZW2IbhcZUSl/bqz4SotHYXHX63xVTciNBUMFVQAG2tOGoZCrqj69f3pxeFFmhCLJnzKnAampg7DjFb59f3xxnLNIWhOpZwB2AJKTQMFfm+/vryulJUKMYwuxvnNJ2SVmZSs9xHGUMEAJXHrtTWwECxa4zmz0/95yupAcWAJcZ8+lYATVJWzKV2cdG6ZE20qwEHz+blOYecg16ULzsbjbdbglqAVqWcOEygGQZZxpaCGBbKpF2W6N0ZgDCn8uLQGJiYUstS4cQ6EQIt5LICMCnF8n7sr9gvvEkBJYVClRDgiS6e1/PZi4G6dO5rBpPQzQMh1tDFadMfLhMv1ZN8rrMMMRFw2gWMVA/TUk8l69VjnzSGQzyuUeMRduWRyN7BIBaTsUng87g2KB6wPlJMg6BZ9vMdj2As7lqrTLoTE57nDk4markucKjjkUglrONSv5XKnPdgOM/DyeXSqVPt3+Mn/JjTapygFkhAAAAAElFTkSuQmCC" alt="">
                <label for="" class="admin-name">GPNashik</label>
            </div>
        </div>
        <div class="menu-items">
            <div class="items active" data-page="dashboard.php">
                <svg width="24" height="24" viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M24.375 16.875V5.625H39.375V16.875H24.375ZM5.625 24.375V5.625H20.625V24.375H5.625ZM24.375 39.375V20.625H39.375V39.375H24.375ZM5.625 39.375V28.125H20.625V39.375H5.625Z" fill="currentColor"/>
                </svg>
                <span>Dashboard</span>
            </div>
            <div class="items" data-page="manage_staff.php">
                <svg width="24" height="24" viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.5 10.3125C24.2405 10.3125 25.9097 11.0039 27.1404 12.2346C28.3711 13.4653 29.0625 15.1345 29.0625 16.875C29.0625 18.6155 28.3711 20.2847 27.1404 21.5154C25.9097 22.7461 24.2405 23.4375 22.5 23.4375C20.7595 23.4375 19.0903 22.7461 17.8596 21.5154C16.6289 20.2847 15.9375 18.6155 15.9375 16.875C15.9375 15.1345 16.6289 13.4653 17.8596 12.2346C19.0903 11.0039 20.7595 10.3125 22.5 10.3125ZM9.375 15C10.425 15 11.4 15.2812 12.2438 15.7875C11.9625 18.4687 12.75 21.1312 14.3625 23.2125C13.425 25.0125 11.55 26.25 9.375 26.25C7.88316 26.25 6.45242 25.6574 5.39752 24.6025C4.34263 23.5476 3.75 22.1168 3.75 20.625C3.75 19.1332 4.34263 17.7024 5.39752 16.6475C6.45242 15.5926 7.88316 15 9.375 15ZM35.625 15C37.1168 15 38.5476 15.5926 39.6025 16.6475C40.6574 17.7024 41.25 19.1332 41.25 20.625C41.25 22.1168 40.6574 23.5476 39.6025 24.6025C38.5476 25.6574 37.1168 26.25 35.625 26.25C33.45 26.25 31.575 25.0125 30.6375 23.2125C32.2716 21.1017 33.0303 18.4428 32.7562 15.7875C33.6 15.2812 34.575 15 35.625 15ZM10.3125 34.2187C10.3125 30.3375 15.7688 27.1875 22.5 27.1875C29.2312 27.1875 34.6875 30.3375 34.6875 34.2187V37.5H10.3125V34.2187ZM0 37.5V34.6875C0 32.0812 3.54375 29.8875 8.34375 29.25C7.2375 30.525 6.5625 32.2875 6.5625 34.2187V37.5H0ZM45 37.5H38.4375V34.2187C38.4375 32.2875 37.7625 30.525 36.6562 29.25C41.4562 29.8875 45 32.0812 45 34.6875V37.5Z" fill="currentColor"/>
                </svg>
                <span>Manage Staff</span>
            </div>
            <div class="items" data-page="manage_block.php">
                <svg width="24" height="24" viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19.8712 3.76873L36.7462 7.51873C36.9587 7.5612 37.1499 7.67593 37.2873 7.84342C37.4247 8.01092 37.4999 8.22083 37.5 8.43748V36.5625C37.4999 36.7791 37.4247 36.989 37.2873 37.1565C37.1499 37.324 36.9587 37.4388 36.7462 37.4812L19.8712 41.2312C19.7353 41.2584 19.595 41.2551 19.4605 41.2215C19.326 41.1879 19.2006 41.1249 19.0933 41.0371C18.9861 40.9492 18.8996 40.8386 18.8403 40.7133C18.7809 40.588 18.7501 40.4511 18.75 40.3125V4.68748C18.7501 4.54884 18.7809 4.41193 18.8403 4.28664C18.8996 4.16134 18.9861 4.05077 19.0933 3.9629C19.2006 3.87502 19.326 3.81203 19.4605 3.77845C19.595 3.74488 19.7353 3.74156 19.8712 3.76873ZM16.875 7.49998V37.5H8.90625C8.56643 37.5 8.23811 37.3769 7.98201 37.1535C7.7259 36.9302 7.55934 36.6216 7.51312 36.285L7.5 36.0937V8.90623C7.50001 8.56641 7.62308 8.23809 7.84644 7.98199C8.0698 7.72589 8.37834 7.55932 8.715 7.51311L8.90625 7.49998H16.875ZM24.375 20.625C23.8777 20.625 23.4008 20.8225 23.0492 21.1742C22.6975 21.5258 22.5 22.0027 22.5 22.5C22.5 22.9973 22.6975 23.4742 23.0492 23.8258C23.4008 24.1774 23.8777 24.375 24.375 24.375C24.8723 24.375 25.3492 24.1774 25.7008 23.8258C26.0525 23.4742 26.25 22.9973 26.25 22.5C26.25 22.0027 26.0525 21.5258 25.7008 21.1742C25.3492 20.8225 24.8723 20.625 24.375 20.625Z" fill="currentColor"/>
                </svg>
                <span>Manage Blocks</span>
            </div>
            <div class="items" data-page="admin_panel.php">
                <svg width="24" height="24" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M15.76 4.0375C16.3267 4.2125 16.8683 4.4375 17.385 4.7125L19.6763 3.3375C19.9152 3.1942 20.1951 3.13482 20.4716 3.16879C20.7481 3.20276 21.0054 3.32814 21.2025 3.525L22.475 4.7975C22.6719 4.99463 22.7972 5.25187 22.8312 5.52838C22.8652 5.8049 22.8058 6.08484 22.6625 6.32375L21.2875 8.615C21.5625 9.13167 21.7875 9.67333 21.9625 10.24L24.5537 10.8888C24.8241 10.9565 25.064 11.1126 25.2354 11.3322C25.4069 11.5519 25.5 11.8226 25.5 12.1012V13.8988C25.5 14.1774 25.4069 14.4481 25.2354 14.6678C25.064 14.8874 24.8241 15.0435 24.5537 15.1112L21.9625 15.76C21.7875 16.3267 21.5625 16.8683 21.2875 17.385L22.6625 19.6763C22.8058 19.9152 22.8652 20.1951 22.8312 20.4716C22.7972 20.7481 22.6719 21.0054 22.475 21.2025L21.2025 22.475C21.0054 22.6719 20.7481 22.7972 20.4716 22.8312C20.1951 22.8652 19.9152 22.8058 19.6763 22.6625L17.385 21.2875C16.8683 21.5625 16.3267 21.7875 15.76 21.9625L15.1112 24.5537C15.0435 24.8241 14.8874 25.064 14.6678 25.2354C14.4481 25.4069 14.1774 25.5 13.8988 25.5H12.1012C11.8226 25.5 11.5519 25.4069 11.3322 25.2354C11.1126 25.064 10.9565 24.8241 10.8888 24.5537L10.24 21.9625C9.67837 21.7889 9.13431 21.5629 8.615 21.2875L6.32375 22.6625C6.08484 22.8058 5.8049 22.8652 5.52838 22.8312C5.25187 22.7972 4.99463 22.6719 4.7975 22.475L3.525 21.2025C3.32814 21.0054 3.20276 20.7481 3.16879 20.4716C3.13482 20.1951 3.1942 19.9152 3.3375 19.6763L4.7125 17.385C4.43705 16.8657 4.21106 16.3216 4.0375 15.76L1.44625 15.1112C1.17615 15.0436 0.936373 14.8877 0.764953 14.6683C0.593534 14.4488 0.500286 14.1784 0.5 13.9V12.1025C0.500007 11.8238 0.593128 11.5532 0.764569 11.3335C0.936011 11.1138 1.17594 10.9577 1.44625 10.89L4.0375 10.2413C4.2125 9.67458 4.4375 9.13292 4.7125 8.61625L3.3375 6.325C3.1942 6.08609 3.13482 5.80615 3.16879 5.52963C3.20276 5.25312 3.32814 4.99588 3.525 4.79875L4.7975 3.525C4.99463 3.32814 5.25187 3.20276 5.52838 3.16879C5.8049 3.13482 6.08484 3.1942 6.32375 3.3375L8.615 4.7125C9.13167 4.4375 9.67333 4.2125 10.24 4.0375L10.8888 1.44625C10.9564 1.17615 11.1123 0.936373 11.3317 0.764953C11.5512 0.593534 11.8216 0.500286 12.1 0.5H13.8975C14.1762 0.500007 14.4468 0.593128 14.6665 0.764569C14.8862 0.936011 15.0423 1.17594 15.11 1.44625L15.76 4.0375ZM13 18C14.3261 18 15.5979 17.4732 16.5355 16.5355C17.4732 15.5979 18 14.3261 18 13C18 11.6739 17.4732 10.4021 16.5355 9.46447C15.5979 8.52678 14.3261 8 13 8C11.6739 8 10.4021 8.52678 9.46447 9.46447C8.52678 10.4021 8 11.6739 8 13C8 14.3261 8.52678 15.5979 9.46447 16.5355C10.4021 17.4732 11.6739 18 13 18Z" fill="currentColor"/>
                </svg>
                <span>Settings</span>
            </div>
            <div class="items" data-page="history.php">
                <svg width="24" height="24" viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.5 39.375C18.1875 39.375 14.43 37.9456 11.2275 35.0869C8.025 32.2281 6.18875 28.6575 5.71875 24.375H9.5625C10 27.625 11.4456 30.3125 13.8994 32.4375C16.3531 34.5625 19.22 35.625 22.5 35.625C26.1563 35.625 29.2581 34.3519 31.8056 31.8056C34.3531 29.2594 35.6262 26.1575 35.625 22.5C35.6238 18.8425 34.3506 15.7413 31.8056 13.1963C29.2606 10.6513 26.1587 9.3775 22.5 9.375C20.3438 9.375 18.3281 9.875 16.4531 10.875C14.5781 11.875 13 13.25 11.7188 15H16.875V18.75H5.625V7.5H9.375V11.9062C10.9688 9.90625 12.9144 8.35937 15.2119 7.26562C17.5094 6.17188 19.9387 5.625 22.5 5.625C24.8438 5.625 27.0394 6.07063 29.0869 6.96188C31.1344 7.85313 32.9156 9.05563 34.4306 10.5694C35.9456 12.0831 37.1488 13.8644 38.04 15.9131C38.9313 17.9619 39.3762 20.1575 39.375 22.5C39.3738 24.8425 38.9288 27.0381 38.04 29.0869C37.1513 31.1356 35.9481 32.9169 34.4306 34.4306C32.9131 35.9444 31.1319 37.1475 29.0869 38.04C27.0419 38.9325 24.8463 39.3775 22.5 39.375ZM27.75 30.375L20.625 23.25V13.125H24.375V21.75L30.375 27.75L27.75 30.375Z" fill="currentColor"/>
                </svg>
                <span>History</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="nav">
            <div class="nav-part">
                <!-- Mobile Toggle Button -->
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="nav-part">
                <h3 class="heading">Dashboard</h3>
            </div>
            <!-- <div class="nav-part">
                <svg width="20" height="20" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.5 23.75C2.5 25.875 4.125 27.5 6.25 27.5H23.75C25.875 27.5 27.5 25.875 27.5 23.75V13.75H2.5V23.75ZM23.75 5H21.25V3.75C21.25 3 20.75 2.5 20 2.5C19.25 2.5 18.75 3 18.75 3.75V5H11.25V3.75C11.25 3 10.75 2.5 10 2.5C9.25 2.5 8.75 3 8.75 3.75V5H6.25C4.125 5 2.5 6.625 2.5 8.75V11.25H27.5V8.75C27.5 6.625 25.875 5 23.75 5Z" fill="#64748b"/>
                </svg>
                <span>
                    <?php 
                    $date = DateTime::createFromFormat('d-M-y', date('d-M-y'));
                    echo strtolower($date->format('l, F j, Y'));
                    ?>
                </span>
            </div> -->
            <div class="nav-part">
                <button onclick="openFullscreen()" class="fullscreen">
                    <i class="fas fa-expand"></i>
                </button>
                <button onclick="closeFullscreen()" class="exit-fullscreen">
                    <i class="fas fa-compress"></i>
                </button>
            </div>
        </div>
        <div class="card animate-fadeIn"></div>
    </div>

    <!-- Floating Action Button -->
    <div class="pls" title="Assign Supervision">
        <span class="icon">
            <svg width="20" height="20" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M39.5834 22.9166H27.0834V10.4166C27.0834 9.86411 26.8639 9.33421 26.4732 8.94351C26.0825 8.55281 25.5526 8.33331 25 8.33331C24.4475 8.33331 23.9176 8.55281 23.5269 8.94351C23.1362 9.33421 22.9167 9.86411 22.9167 10.4166V22.9166H10.4167C9.86417 22.9166 9.33427 23.1361 8.94357 23.5268C8.55287 23.9175 8.33337 24.4474 8.33337 25C8.33337 25.5525 8.55287 26.0824 8.94357 26.4731C9.33427 26.8638 9.86417 27.0833 10.4167 27.0833H22.9167V39.5833C22.9167 40.1358 23.1362 40.6658 23.5269 41.0565C23.9176 41.4472 24.4475 41.6666 25 41.6666C25.5526 41.6666 26.0825 41.4472 26.4732 41.0565C26.8639 40.6658 27.0834 40.1358 27.0834 39.5833V27.0833H39.5834C40.1359 27.0833 40.6658 26.8638 41.0565 26.4731C41.4472 26.0824 41.6667 25.5525 41.6667 25C41.6667 24.4474 41.4472 23.9175 41.0565 23.5268C40.6658 23.1361 40.1359 22.9166 39.5834 22.9166Z" fill="white"/>
            </svg>
        </span>
        <span class="text">Assign Supervision</span>
    </div>
</body>
<script>
    // DOM Elements
    let menu_items = document.querySelectorAll('.items');
    let heading = document.querySelector('.heading');
    let card = document.querySelector('.card');
    let elem = document.querySelector('.container');
    const fsBtn = document.querySelector(".fullscreen");
    const exitBtn = document.querySelector(".exit-fullscreen");
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.createElement('div');
    overlay.className = 'fullscreen-overlay';

    // Initialize
    fetch('dashboard.php')
        .then(res => res.text())
        .then(html => {
            card.innerHTML = html;
            card.classList.add('animate-fadeIn');
        });

    // Mobile Sidebar Toggle
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        if (sidebar.classList.contains('active')) {
            document.body.appendChild(overlay);
            overlay.classList.add('active');
            overlay.addEventListener('click', closeSidebar);
        } else {
            overlay.remove();
        }
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.remove();
    }

    mobileToggle.addEventListener('click', toggleSidebar);

    // Fullscreen Functions
    function openFullscreen() {
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
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

    // Menu Navigation
    function remove_active_menu() {
        menu_items.forEach((item) => {
            item.classList.remove('active');
        });
    }

    function loadPage(page) {
        fetch(page)
            .then(res => res.text())
            .then(html => {
                card.innerHTML = html;
                card.classList.add('animate-fadeIn');
                setTimeout(() => card.classList.remove('animate-fadeIn'), 300);
                
                // Close sidebar on mobile after selection
                if (window.innerWidth <= 1024) {
                    closeSidebar();
                }
            });
    }

    menu_items.forEach((item) => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            remove_active_menu();
            item.classList.add('active');
            heading.innerText = item.innerText;
            loadPage(item.dataset.page);
        });
    });

    // Resize listener for mobile
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            closeSidebar();
        }
    });

    // DELETE STAFF functions
    function toggleAll(master) {
        let rows = document.getElementsByClassName('row-check');
        for (let i = 0; i < rows.length; i++) {
            rows[i].checked = master.checked;
        }
        toggleDeleteBtn();
    }

    function toggleDeleteBtn() {
        let rows = document.getElementsByClassName('row-check');
        let btn = document.getElementById('deleteBtn');
        let addbtn = document.querySelector('.add-staff');
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
                loadPage('manage_staff.php');
            } else {
                alert('Delete failed');
            }
        });
    }

    // DELETE BLOCK functions
    function toggleAllblks(master) {
        let rows = document.getElementsByClassName('row-check-blk');
        for (let i = 0; i < rows.length; i++) {
            rows[i].checked = master.checked;
        }
        toggleDeleteblkBtn();
    }

    function toggleDeleteblkBtn() {
        let rows = document.getElementsByClassName('row-check-blk');
        let btn = document.getElementById('deleteblkBtn');
        let addbtn = document.querySelector('.add-block');
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
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 200) {
                loadPage('manage_block.php');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => console.error(err));
    }

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
            loadPage('manage_slot.php');
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
            loadPage('manage_staff.php');
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
            loadPage('manage_block.php');
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
            loadPage('manage_slot.php');
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
            loadPage('add_schedule.php');
        }
        
        //add-staff
        if (e.target.closest('.add-staff')) {
            loadPage('add-staff.php');
        }

        //add-slot
        if (e.target.closest('.add-slot')) {
            loadPage('add-slot.php');
        }

        //add-block
        if (e.target.closest('.add-block')) {
            loadPage('add-block.php');
        }

        //open import file container
        if (e.target.closest('.import_btn')) {
            document.querySelector('#staff_dialog').showModal();
        } else if(e.target.closest('.import_block_btn')) {
            document.querySelector('#block_dialog').showModal();
        } else if(e.target.closest('.import_slot_btn')) {
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
                if(data.status != 200) {
                     
                    document.querySelector(`#err_${data.field}`).innerText = data.message;
                    return;
                } else if(data.status == 200) {
                    loadPage('manage_staff.php');
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
                if(data.status != 200) {
                     
                    document.querySelector(`#err_${data.field}`).innerText = data.message;
                    return;
                } else if(data.status == 200) {
                    loadPage('manage_block.php');
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
                if(data.status != 200) {
                     
                    document.querySelector(`#err_${data.field}`).innerText = data.message;
                    return;
                } else if(data.status == 200) {
                    loadPage('manage_slot.php');
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

    // Add loading indicator for page transitions
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        if (args[0].endsWith('.php') && !args[0].includes('Backend/')) {
            card.innerHTML = '<div class="loading"><div class="spinner"></div></div>';
        }
        return originalFetch.apply(this, args);
    };
</script>

<!-- ==================== supervision rules settings ========================= -->
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
            dept_restriction: document.getElementById('deptOn').checked ? 1 : 0,
            common_duties : document.getElementById('commonOn').checked ? 1 : 0
        };

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