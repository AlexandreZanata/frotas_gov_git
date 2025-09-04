<script>
    // Passa variáveis do PHP para o JavaScript de forma segura
    const BASE_URL = "<?php echo BASE_URL; ?>";
    const CSRF_TOKEN = "<?php echo !empty($csrf_token) ? htmlspecialchars($csrf_token) : ''; ?>";
    const IS_ADMIN = <?php echo (isset($_SESSION['user_role_id']) && $_SESSION['user_role_id'] == 1) ? 'true' : 'false'; ?>;
</script>

<script src="<?php echo BASE_URL; ?>/assets/js/admin_dashboard.js"></script>

<?php if (isset($page_scripts) && is_array($page_scripts)): ?>
    <?php foreach ($page_scripts as $script): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo $script; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>