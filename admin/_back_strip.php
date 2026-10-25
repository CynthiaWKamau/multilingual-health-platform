<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') return;
?>
<a href="../admin/index.php" class="admin-fab" title="Back to Admin Dashboard" aria-label="Back to Admin Dashboard">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
        <path d="M2 17l10 5 10-5"/>
        <path d="M2 12l10 5 10-5"/>
    </svg>
    <span class="admin-fab-label">Admin</span>
</a>
<style>
.admin-fab {
    position: fixed; bottom: clamp(1.2rem, 4vw, 2rem); right: clamp(1.2rem, 4vw, 2rem);
    z-index: 9999; display: flex; align-items: center; gap: 7px;
    background: #1a237e; color: #ffffff; text-decoration: none;
    font-family: 'Poppins', sans-serif; font-size: 0.78rem; font-weight: 700;
    padding: 10px 16px 10px 13px; border-radius: 50px;
    box-shadow: 0 4px 18px rgba(26,35,126,0.45), 0 1px 4px rgba(0,0,0,0.18);
    max-width: 46px; overflow: hidden; white-space: nowrap;
    transition: max-width 0.28s ease, padding 0.28s ease, box-shadow 0.2s;
}
.admin-fab:hover, .admin-fab:focus-visible {
    max-width: 160px; padding: 10px 18px 10px 14px;
    box-shadow: 0 6px 24px rgba(26,35,126,0.55), 0 2px 6px rgba(0,0,0,0.2);
    outline: none;
}
.admin-fab svg { flex-shrink: 0; stroke: #9fa8da; }
.admin-fab-label { flex-shrink: 0; opacity: 0; transition: opacity 0.18s ease 0.06s; }
.admin-fab:hover .admin-fab-label, .admin-fab:focus-visible .admin-fab-label { opacity: 1; }
@media (max-width: 480px) {
    .admin-fab { bottom: 1rem; right: 1rem; padding: 9px 13px 9px 11px; max-width: 40px; }
    .admin-fab:hover, .admin-fab:focus-visible { max-width: 140px; padding: 9px 15px 9px 12px; }
}
</style>