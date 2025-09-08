<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias de Veículos</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/create_user.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/manage_users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../layouts/sector_manager_sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <h1>Gerenciar Categorias e Intervalos de Troca de Óleo</h1>
            <a href="<?php echo BASE_URL; ?>/sector-manager/oil-change" class="btn-back"><i class="fas fa-oil-can"></i>Voltar</a>
        </header>
        <div class="content-body">
            <div class="form-container">
                <h2 class="section-title" id="formTitle">Adicionar Nova Categoria</h2>
                <form id="categoryForm" action="<?php echo BASE_URL; ?>/sector-manager/categories/store" method="POST">
                    <input type="hidden" name="category_id" id="category_id">
                    <div class="form-row">
                        <div class="form-group"><label for="name">Nome da Categoria*</label><input type="text" id="name" name="name" placeholder="Ex: Motocicleta, Veículo Leve, Van" required></div>
                        <div class="form-group"><label for="oil_change_km">Intervalo de Troca (KM)*</label><input type="number" id="oil_change_km" name="oil_change_km" placeholder="Ex: 5000" required></div>
                        <div class="form-group"><label for="oil_change_days">Intervalo de Troca (Dias)*</label><input type="number" id="oil_change_days" name="oil_change_days" placeholder="Ex: 180" required></div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Salvar Categoria</button>
                        <button type="button" id="cancelEditBtn" class="btn-submit" style="display: none; background-color: #6c757d;">Cancelar Edição</button>
                    </div>
                </form>
            </div>
            <div class="table-container">
                <h2 class="section-title">Categorias Cadastradas</h2>
                <table class="user-table">
                    <thead>
                        <tr><th>Nome</th><th>Intervalo (KM)</th><th>Intervalo (Dias)</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr data-category='<?php echo json_encode($cat, JSON_HEX_QUOT | JSON_HEX_TAG); ?>'>
                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                            <td><?php echo number_format($cat['oil_change_km'], 0, ',', '.'); ?> km</td>
                            <td><?php echo htmlspecialchars($cat['oil_change_days']); ?> dias</td>
                            <td class="actions">
                                <a href="#" class="edit"><i class="fas fa-edit"></i></a>
                                <a href="#" class="delete"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('categoryForm');
            const formTitle = document.getElementById('formTitle');
            const categoryIdInput = document.getElementById('category_id');
            const cancelBtn = document.getElementById('cancelEditBtn');

            document.querySelectorAll('.edit').forEach(button => {
                button.addEventListener('click', e => {
                    e.preventDefault();
                    const row = e.target.closest('tr');
                    const data = JSON.parse(row.dataset.category);
                    
                    formTitle.textContent = 'Editando Categoria';
                    categoryIdInput.value = data.id;
                    form.querySelector('#name').value = data.name;
                    form.querySelector('#oil_change_km').value = data.oil_change_km;
                    form.querySelector('#oil_change_days').value = data.oil_change_days;
                    
                    cancelBtn.style.display = 'inline-block';
                    form.scrollIntoView({ behavior: 'smooth' });
                });
            });

            document.querySelectorAll('.delete').forEach(button => {
                 button.addEventListener('click', e => {
                    e.preventDefault();
                    if (confirm('Tem certeza que deseja excluir esta categoria? Ela não pode estar em uso por nenhum veículo.')) {
                        const row = e.target.closest('tr');
                        const data = JSON.parse(row.dataset.category);
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '<?php echo BASE_URL; ?>/sector-manager/categories/delete';
                        form.innerHTML = `<input type="hidden" name="category_id" value="${data.id}">`;
                        document.body.appendChild(form);
                        form.submit();
                    }
                 });
            });

            cancelBtn.addEventListener('click', () => {
                formTitle.textContent = 'Adicionar Nova Categoria';
                categoryIdInput.value = '';
                form.reset();
                cancelBtn.style.display = 'none';
            });
        });
    </script>
</body>
</html>