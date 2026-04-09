<!-- Back + Refresh Buttons -->
<style>
.btn-group {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    top: 10px;
    left: 18px;
}

/* Common Button Style */
.nav-btn {
    padding: 10px 14px;
    font-size: 16px;
    font-weight: 500;
    color: black;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    z-index: 9999;
    background: white;
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.25);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Hover */
.nav-btn:hover {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 12px 25px rgba(0,0,0,0.35);
}

/* Click */
.nav-btn:active {
    transform: scale(0.95);
}

/* Icon */
.icon {
    font-size: 20px;
}
</style>

<div class="btn-group">
    <!-- Back Button -->
    <button class="nav-btn" onclick="goBack()">
        <span class="icon">⮜</span>
    </button>

    <!-- Refresh Button -->
    <button class="nav-btn" onclick="refreshPage()">
        <span class="icon">⟳</span>
    </button>
</div>

<script>
function goBack() {
    if (document.referrer !== "") {
        window.history.back();
    } else {
        window.location.href = "/";
    }
}

function refreshPage() {
    location.reload();
}

</script>