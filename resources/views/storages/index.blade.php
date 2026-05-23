@extends('layouts.app')

@section('header')
<h1>Almacén</h1>
<p>Listado de productos en Almacén</p>
@endsection

@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
    <div class="card shadow">
        <div class="card-body">
            <div class="mb-3">
                <label for="search" class="form-label">Buscar Producto</label>
                <input type="text" class="form-control" id="search" placeholder="Buscar por nombre o categoría...">
            </div>

            <div class="table-responsive mt-4">
                <table class="table table-bordered table-striped" id="productsTable">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td>
                                {{ $product->location_prices->first()->price ?? '-' }}
                            </td>
                            <td>{{ $product->location_prices->first()->quantity }}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#stockModal"
                                    data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-quantity="{{ $product->quantity }}">
                                    <i class="bi bi-plus-circle"></i> Modificar Stock
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No hay productos registrados para esta categoría.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para modificar stock -->
<div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formRegistro" action="{{ route('storages.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="stockModalLabel">Modificar Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modal_product_id" name="product_id">
                    <div class="mb-3">
                        <label for="modal_product_name" class="form-label">Producto</label>
                        <input type="text" class="form-control" id="modal_product_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="modal_quantity" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="modal_quantity" name="quantity" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection



@section('scripts')
<script>
    var stockModal = document.getElementById('stockModal');
    stockModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var productId = button.getAttribute('data-id');
        var productName = button.getAttribute('data-name');
        var quantity = button.getAttribute('data-quantity');

        var modalProductId = stockModal.querySelector('#modal_product_id');
        var modalProductName = stockModal.querySelector('#modal_product_name');
        var modalQuantity = stockModal.querySelector('#modal_quantity');

        modalProductId.value = productId;
        if (modalProductName) modalProductName.value = productName;
        modalQuantity.value = quantity;
    });

    document.getElementById('formRegistro').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Cerrar modal
                var modal = bootstrap.Modal.getInstance(stockModal);
                modal.hide();
                // Recargar la página para ver el nuevo stock (o puedes actualizar solo la fila)
                location.reload();
            } else {
                alert('Error al actualizar el stock');
            }
        })
        .catch(() => alert('Error al actualizar el stock'));
    });

    document.getElementById('search').addEventListener('input', function() {
        var searchTerm = this.value.toLowerCase();
        var rows = document.querySelectorAll('#productsTable tbody tr');
        
        rows.forEach(function(row) {
            var productName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            var categoryName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            
            if (productName.includes(searchTerm) || categoryName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>
@endsection