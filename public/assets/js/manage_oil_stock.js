document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('oilProductForm');
    const formTitle = document.getElementById('formTitle');
    const productIdInput = document.getElementById('product_id');
    const cancelBtn = document.getElementById('cancelEditBtn');

    document.querySelectorAll('.edit').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const row = e.target.closest('tr');
            const productData = JSON.parse(row.dataset.product);

            formTitle.textContent = 'Editando Produto';
            productIdInput.value = productData.id;
            form.querySelector('#name').value = productData.name;
            form.querySelector('#brand').value = productData.brand || '';
            form.querySelector('#stock_liters').value = productData.stock_liters;
            form.querySelector('#cost_per_liter').value = productData.cost_per_liter;
            form.querySelector('#secretariat_id').value = productData.secretariat_id || '';
            
            cancelBtn.style.display = 'inline-block';
            form.scrollIntoView({ behavior: 'smooth' });
        });
    });

    cancelBtn.addEventListener('click', () => {
        formTitle.textContent = 'Adicionar Novo Produto';
        productIdInput.value = '';
        form.reset();
        cancelBtn.style.display = 'none';
    });
});

// --- LÓGICA DE EXCLUSÃO ---
    const deleteModal = document.getElementById('deleteConfirmationModal');
    const deleteModalCloseBtn = deleteModal.querySelector('.modal-close');
    const deleteForm = document.getElementById('deleteForm');
    const productNameToDeleteSpan = document.getElementById('productNameToDelete');
    const productIdToDeleteInput = document.getElementById('productIdToDelete');

    const openDeleteModal = (product) => {
        productNameToDeleteSpan.textContent = product.name;
        productIdToDeleteInput.value = product.id;
        deleteModal.style.display = 'flex';
    };

    const closeDeleteModal = () => {
        deleteModal.style.display = 'none';
    };

    document.querySelectorAll('.delete').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const row = e.target.closest('tr');
            const productData = JSON.parse(row.dataset.product);
            openDeleteModal(productData);
        });
    });

    deleteModalCloseBtn.addEventListener('click', closeDeleteModal);

    // Adiciona o listener para fechar o modal ao clicar fora dele
    window.addEventListener('click', (e) => {
        if (e.target === deleteModal) {
            closeDeleteModal();
        }
    });