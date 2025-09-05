document.addEventListener('DOMContentLoaded', () => {
    const openModalBtn = document.getElementById('openAddProductModalBtn');
    const modal = document.getElementById('productModal');
    const closeModalBtn = modal.querySelector('.modal-close');
    const productForm = document.getElementById('productForm');
    const modalTitle = document.getElementById('modalTitle');
    const productIdInput = document.getElementById('productId');
    const tableBody = document.getElementById('oilProductTableBody');
    const searchInput = document.getElementById('productSearch');

    const openModal = () => modal.style.display = 'flex';

    const closeModal = () => {
        modal.style.display = 'none';
        productForm.reset();
        productIdInput.value = '';
        modalTitle.textContent = 'Adicionar Novo Produto';
        document.getElementById('productStock').removeAttribute('readonly');
    };

    openModalBtn.addEventListener('click', openModal);
    closeModalBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    searchInput.addEventListener('input', () => {
        const term = searchInput.value.toLowerCase();
        tableBody.querySelectorAll('tr').forEach(row => {
            const name = row.cells[1].textContent.toLowerCase();
            const brand = row.cells[2].textContent.toLowerCase();
            row.style.display = (name.includes(term) || brand.includes(term)) ? '' : 'none';
        });
    });

    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(productForm);
        const data = Object.fromEntries(formData.entries());

        const url = `${BASE_URL}/sector-manager/oil-stock/store`;
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.message);

            alert(result.message);
            location.reload(); // Recarrega a página para ver a tabela atualizada

        } catch (error) {
            alert(`Erro: ${error.message}`);
        }
    });

    tableBody.addEventListener('click', async (e) => {
        const target = e.target.closest('.edit-product-btn, .delete-product-btn');
        if (!target) return;

        const row = target.closest('tr');
        const id = row.dataset.id;

        if (target.classList.contains('edit-product-btn')) {
            try {
                const response = await fetch(`${BASE_URL}/sector-manager/oil-stock/get?id=${id}`);
                const result = await response.json();

                if (!response.ok) throw new Error(result.message);
                
                const product = result.data;
                modalTitle.textContent = `Editando: ${product.name}`;
                productIdInput.value = product.id;
                document.getElementById('productName').value = product.name;
                document.getElementById('productBrand').value = product.brand || '';
                document.getElementById('productStock').value = product.stock_liters;
                document.getElementById('productCost').value = product.cost_per_liter;
                
                const stockInput = document.getElementById('productStock');
                stockInput.setAttribute('readonly', true);
                stockInput.title = 'O estoque só pode ser ajustado através de uma troca de óleo ou entrada manual (futura funcionalidade).';


                const secretariatSelect = document.getElementById('productSecretariat');
                if (secretariatSelect) {
                    secretariatSelect.value = product.secretariat_id || '';
                }

                openModal();

            } catch (error) {
                alert(`Erro ao buscar produto: ${error.message}`);
            }
        }

        if (target.classList.contains('delete-product-btn')) {
            const productName = row.cells[1].textContent;
            if (!confirm(`Tem certeza que deseja excluir o produto "${productName}"? Esta ação não pode ser desfeita.`)) {
                return;
            }

            try {
                const response = await fetch(`${BASE_URL}/sector-manager/oil-stock/delete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);

                alert(result.message);
                row.remove();

            } catch (error) {
                alert(`Erro ao excluir: ${error.message}`);
            }
        }
    });
});