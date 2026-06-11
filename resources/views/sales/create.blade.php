@extends('layouts.app')

@section('header')
<h1>Ventas</h1>
<p>Registrar una nueva venta</p>
@endsection

@section('content')
@php
$colors = ['btn-outline-primary', 'btn-outline-success', 'btn-outline-info', 'btn-outline-warning', 'btn-outline-danger', 'btn-outline-dark'];
$invoices_enabled = auth()->user()->location->invoices_enabled == 1;
@endphp
<div class="container-fluid content-inner mt-n5 py-0">
    <!-- Card que contiene el formulario y la tabla -->
    <div class="card shadow">
        <!-- Cuerpo del Card -->
        <div class="card-body">
            <form action="{{ route('sales.store') }}" id="saveSale" method="POST">
                @csrf
                <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
                <input type="hidden" name="location_id" id="location_id_hidden" value="{{ auth()->user()->location_id }}">

                <div class="row">
                    <div class="col-xl-4 col-lg-12 order-2 order-lg-1 mt-4 mt-lg-0">
                        <div class="btn-group d-flex justify-content-start mb-4">
                            <button type="button" class="btn btn-outline-primary me-1 {{ $invoices_enabled ? '' : 'd-none' }}" id="btn-boleta">Boleta</button>
                            <button type="button" class="btn btn-outline-success me-1  {{ $invoices_enabled ? '' : 'd-none' }}" id="btn-factura">Factura</button>
                            <button type="button" class="btn btn-outline-info me-1" id="btn-ticket">Ticket</button>
                        </div>
                        <div class="mb-2 row">
                            <label class="col-sm-4 col-form-label text-start"><strong>Documento</strong></label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-xs" id="document"
                                        name="document" maxlength="11" autocomplete="off" placeholder="Documento"
                                        onkeypress="isNumber(event)">
                                    <button type="button" class="btn btn-primary btn-xs d-none" id="document-search-btn"
                                        onclick="searchAPI()"><i
                                            class="bi bi-search"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2 row">
                            <label class="col-form-label text-start"><strong>Cliente</strong></label>
                            <div class="col-sm-12">
                                <input type="text" class="form-control form-control-sm" id="client" name="client">
                            </div>
                        </div>
                        
                        <div class="d-none">
                            <div class="d-flex mb-3">
                                <div class="form-check me-4">
                                    <input class="form-check-input" type="checkbox" value="1" id="anticipada" name="anticipada">
                                    <label class="form-check-label" for="anticipada">
                                        Por Entregar
                                    </label>
                                </div>
                            </div>
                            <input type="hidden" name="type_sale" id="type_sale" value="0">
                            <input type="hidden" name="status" id="status" value="1">
                            <input type="hidden" name="type_status" id="type_status" value="0">
                            <div id="grupo-fecha-entrega">
                                <label for="fecha_entrega" class="mb-2"><strong>Fecha de entrega</strong></label>
                                <input type="date" id="fecha_entrega" name="fecha_entrega"
                                    class="form-control form-control-sm mb-4"
                                    onkeydown="return false;"
                                    onpaste="return false;">
                                <label for="hora_entrega" class="form-label"><strong>Hora:</strong></label>
                                <input type="text" class="form-control form-control-sm mb-4" id="hora_entrega" name="hora_entrega">
    
                                <label class="mb-2"><strong>Teléfono</strong></label>
                                <input type="text" id="telefono" name="telefono"
                                    class="form-control form-control-sm mb-4">
    
                                <label class="mb-2"><strong>Dirección</strong></label>
                                <input type="text" id="direccion" name="direccion"
                                    class="form-control form-control-sm mb-4">
                            </div>
                        </div>
                        
                        <div id="grupo-delivery">
                            <label class="mb-2"><strong>Referencia</strong></label>
                            <input type="text" id="referencia" name="referencia"
                                class="form-control form-control-sm mb-4">
                        </div>
                        <label class="mb-2"><strong>Observación</strong></label>
                        <input type="text" id="observacion" name="observacion"
                            class="form-control form-control-sm ">
                        <div class="d-flex flex-column mb-5 mt-3">
                            <label class="mb-2"><strong>Método de Pago</strong></label>
                            <div class="d-flex flex-wrap">
                                @foreach ($pms as $index => $method)
                                @php
                                $colorClass = $colors[$index % count($colors)];
                                @endphp
                                <button type="button"
                                    id="btn-{{ $method->id }}"
                                    class="btn {{ $colorClass }} me-2 mb-2"
                                    data-campos="campos-{{ $method->name }}"
                                    data-id="{{ $method->id }}"
                                    onclick="seleccionarMedioPago('{{ $method->id }}', event)">
                                    {{ strtoupper($method->name) }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        <!-- HTML actualizado - Solo mostrar vuelto para Efectivo -->
                        @foreach ($pms as $method)
                        <div class="d-flex align-items-center mb-4 d-none" id="campos-{{ $method->name }}">
                            <label class="mb-2 me-3"><strong>{{ strlen($method->name) > 4 ? strtoupper(substr($method->name, 0, 4) . '.') : strtoupper($method->name) }}</strong></label>
                            <div class="input-group me-2">
                                <span class="input-group-text">S/</span>
                                <input type="text" class="form-control" placeholder="Ingrese Monto"
                                    name="monto[{{ $method->id }}]"
                                    onkeypress="isDecimal(event)"
                                    oninput="calcularVueltoEfectivo('{{ $method->name }}', '{{ $method->id }}', this)">
                            </div>
                            <!-- Campo de vuelto - SOLO para efectivo -->
                            @if(($method->name) === 'EFECTIVO')
                            <div class="input-group me-2">
                                <input type="text" class="form-control" placeholder="0.00" style="width: 150px;"
                                    id="vuelto-efectivo" readonly>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    <div class="col-xl-8 col-lg-12 order-1 order-lg-2">
                        <!-- Seleccionar Productos -->
                        <div class="form-group">
                            <label class="col-sm-3 col-form-label text-start">Producto:</label>
                            <div class="col-md-12">
                                <input type="text" id="search-product" class="form-control" placeholder="Buscar Producto...">
                                <input type="hidden" id="product_id" name="product_id">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="producto_id"
                                class="col-sm-3 col-form-label text-start"><strong>Categorías</strong></label>
                            <div class="mb-3">
                                @foreach ($pc as $category)
                                <button class="btn btn-outline-primary btn-sm m-1" type="button"
                                    onclick="handleCategoryClick('{{ $category->id }}')">
                                    {{ $category->name }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                        <div id="product-container"></div>
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered table-striped text-xs">
                                <thead>
                                    <tr class="text-center">
                                        <th>N°</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio</th>
                                        <th>Subtotal</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Botón guardar: SIEMPRE al final -->
                    <div class="col-12 order-3 mt-4 text-end">
                        <h5><strong>TOTAL: S/ <span id="totalAmount" name="total">0.00</span></strong></h5>
                        <h6><strong>SALDO: S/ <span id="saldoAmount">0.00</span></strong></h6>
                        <input type="hidden" step="0.01" name="total" id="totalAmountInput" value="0">
                        <button class="btn btn-success mt-3" type="submit">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    var serial = "{{ config('printer.serial') }}";


    $(document).ready(function() {
        // Buscar el elemento toggle del sidebar
        const sidebarToggle = $('.sidebar-toggle[data-toggle="sidebar"]');

        if (sidebarToggle.length > 0) {
            // Simular clic en el toggle para ocultar el sidebar
            sidebarToggle.trigger('click');
        }

        // Método alternativo si el anterior no funciona
        setTimeout(function() {
            // Agregar clase para ocultar sidebar
            $('aside.sidebar').addClass('sidebar-mini');
            $('body').addClass('sidebar-main');
        }, 100);


        $('#anticipada').change(function() {
            const isChecked = $(this).is(':checked');
            if (isChecked) {
                // Si está marcado: type_sale = 1, status = 0, type_status = 1
                $('#type_sale').val(0);
                $('#status').val(0);
                $('#type_status').val(1);
                console.log('Venta anticipada activada: type_sale=1, status=0, type_status=1');
            } else {
                // Si no está marcado: type_sale = 0, status = 1, type_status = 0
                $('#type_sale').val(0);
                $('#status').val(1);
                $('#type_status').val(0);
                console.log('Venta normal: type_sale=0, status=1, type_status=0');
            }
        });

        // Inicializar valores por defecto (checkbox desmarcado)
        $('#type_sale').val(0);
        $('#status').val(1);
        $('#type_status').val(0);

        // Configurar botones de tipo de comprobante
        $('#btn-boleta').click(function() { selectVoucherType('Boleta', this); });
        $('#btn-factura').click(function() { selectVoucherType('Factura', this); });
        $('#btn-ticket').click(function() { selectVoucherType('Ticket', this); });

        // Seleccionar Ticket por defecto
        selectVoucherType('Ticket', document.getElementById('btn-ticket'));
    });

    let clientSearchTimeout = null;
    $('#search-product').autocomplete({
        source: function(request, response) {
            clearTimeout(clientSearchTimeout);
            clientSearchTimeout = setTimeout(function() {
                let currentTerm = $('#search-product').val();
                // Solo buscar si hay al menos una letra
                if (currentTerm && currentTerm.length > 0) {
                    $.ajax({
                        url: "{{ route('products.searchpv') }}",
                        method: 'GET',
                        data: {
                            query: currentTerm
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    label: item.name + ' - Stock: ' + (item.stock || 0) + ' - S/ ' + parseFloat(item.unit_price || 0).toFixed(2),
                                    value: item.name,
                                    id: item.id,
                                    name: item.name,
                                    unit_price: item.unit_price,
                                    stock: item.stock || 0,
                                    category: item.category
                                };
                            }));
                        }
                    });
                } else {
                    // Si no hay letras, limpia el autocomplete
                    response([]);
                }
            }, 500);
        },
        appendTo: '.container-fluid',
        select: function(event, ui) {
            // Agregar producto directamente a la tabla cuando se selecciona
            if (ui.item.stock > 0) {
                handleProductClick(ui.item.id, ui.item.name, ui.item.unit_price, ui.item.stock, ui.item.category);
                // Limpiar el campo de búsqueda
                $('#search-product').val('');
                $('#product_id').val('');
            } else {
                alert('Este producto no tiene stock disponible.');
                $('#search-product').val('');
            }
            return false; // Previene que se llene el input con el valor
        },
    }).autocomplete("instance")._renderItem = function(ul, item) {
    const stockClass = item.stock > 0 ? 'text-success' : 'text-danger';
    const stockText = item.stock > 0 ? 'Disponible' : 'Sin Stock';
        return $("<li>")
            .append(`<div class="d-flex justify-content-between">
                        <span>${item.name}</span>
                        <small class="${stockClass}">${stockText}</small>
                     </div>`)
            .appendTo(ul);
    };

    // Variables globales para manejo de productos
    let productTableCounter = 0;
    const productTableBody = document.querySelector('tbody');

    function handleCategoryClick(categoryId) {
        const productContainer = document.getElementById('product-container');

        // Mostrar loader mientras carga
        productContainer.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';

        // Hacer petición AJAX para obtener productos de la categoría
        $.ajax({
            url: "{{ route('sales.getProductsByCategory', '') }}/" + categoryId,
            method: 'GET',
            success: function(products) {
                // Limpiar contenedor
                productContainer.innerHTML = '';

                if (products && products.length > 0) {
                    // Obtener nombre de la categoría del botón
                    const categoryButton = document.querySelector(`button[onclick="handleCategoryClick(${categoryId})"]`);
                    const categoryName = categoryButton ? categoryButton.textContent.trim() : 'Categoría';

                    // Crear título de la categoría
                    const categoryTitle = document.createElement('h6');
                    categoryTitle.className = 'mt-3 mb-2 text-primary';
                    categoryTitle.innerHTML = `<strong>Productos de ${categoryName}:</strong>`;
                    productContainer.appendChild(categoryTitle);

                    // Crear contenedor para los productos
                    const productsDiv = document.createElement('div');
                    productsDiv.className = 'd-flex flex-wrap gap-2'; // Cambia aquí

                    products.forEach(producto => {
                        const productCol = document.createElement('div');

                        const productElement = document.createElement('button');
                        productElement.className = "btn btn-outline-success btn-sm";
                        productElement.type = "button";

                        // Mostrar nombre del producto con stock y precio
                        const stock = producto.stock || 0;
                        const precio = parseFloat(producto.unit_price || 0).toFixed(2);

                        productElement.innerHTML = `
                            <div class="text-start">
                                <div class="fw-bold">${producto.name.toUpperCase()} (${stock})</div>
                            </div>
                        `;

                        // Deshabilitar si no hay stock
                        if (stock <= 0) {
                            productElement.disabled = true;
                            productElement.className = "btn btn-outline-secondary btn-sm";
                            productElement.innerHTML = `
                                <div class="text-start">
                                    <div class="fw-bold text-muted">${producto.name.toUpperCase()} (Sin Stock)</div>
                                </div>
                            `;
                        }

                        productElement.onclick = function() {
                            if (stock > 0) {
                                handleProductClick(producto.id, producto.name, producto.unit_price, stock, producto.category);
                            }
                        };

                        productCol.appendChild(productElement);
                        productsDiv.appendChild(productCol);
                    });

                    productContainer.appendChild(productsDiv);
                } else {
                    // Mostrar mensaje si no hay productos
                    const noProductsMsg = document.createElement('div');
                    noProductsMsg.className = 'alert alert-info mt-3';
                    noProductsMsg.textContent = 'No hay productos disponibles en esta categoría.';
                    productContainer.appendChild(noProductsMsg);
                }

                // Resaltar categoría seleccionada
                document.querySelectorAll('button[onclick*="handleCategoryClick"]').forEach(btn => {
                    btn.className = 'btn btn-outline-primary btn-sm m-1';
                });

                const selectedButton = document.querySelector(`button[onclick="handleCategoryClick(${categoryId})"]`);
                if (selectedButton) {
                    selectedButton.className = 'btn btn-primary btn-sm m-1';
                }
            },
            error: function() {
                productContainer.innerHTML = '<div class="alert alert-danger mt-3">Error al cargar los productos. Por favor, intente nuevamente.</div>';
            }
        });
    }

    function handleProductClick(productId, productName, unitPrice, stock, category) {
        // Verificar si el producto ya está en la tabla
        const existingRow = document.querySelector(`tr[data-product-id="${productId}"]`);

        if (existingRow) {
            // Si ya existe, incrementar cantidad
            const quantityInput = existingRow.querySelector('.quantity-input');
            const currentQuantity = parseInt(quantityInput.value);

            if (currentQuantity < stock) {
                const newQuantity = currentQuantity + 1;
                quantityInput.value = newQuantity;
                updateRowSubtotal(existingRow, unitPrice, newQuantity);
                updateTotal();
            } else {
                // Mostrar alerta de stock insuficiente
                alert(`Stock insuficiente. Solo hay ${stock} unidades disponibles.`);
            }
        } else {
            // Agregar nueva fila
            addProductToTable(productId, productName, unitPrice, stock, category);
        }
    }

    function addProductToTable(productId, productName, unitPrice, stock, category) {
        productTableCounter++;

        const row = document.createElement('tr');
        row.setAttribute('data-product-id', productId);
        
        const priceCellHtml = (category === 'Panes') ? `
            <td class="text-center">
                <input type="number" step="0.01" class="form-control form-control-sm text-center price-input"
                    value="${parseFloat(unitPrice).toFixed(2)}"
                    name="products[${productId}][precio]">
            </td>
        ` : `
            <td class="text-center">
                S/ ${parseFloat(unitPrice).toFixed(2)}
                <input type="hidden" name="products[${productId}][precio]" value="${unitPrice}">
            </td>
        `;

        row.innerHTML = `
            <td class="text-center">${productTableCounter}</td>
            <td>${productName}</td>
            <td class="text-center">
                <div class="input-group" style="width: 120px; margin: 0 auto;">
              <input type="number" class="form-control form-control-sm text-center quantity-input" 
                  value="1" min="1" max="${stock}" 
                  onchange="validateQuantity(this, ${stock}, ${unitPrice})"
                  name="products[${productId}][cantidad]">
                </div>
          <input type="hidden" name="products[${productId}][id]" value="${productId}">
          <input type="hidden" name="products[${productId}][precio]" value="${unitPrice}">
            </td>
            ${priceCellHtml}
            <td class="text-center subtotal">S/ ${parseFloat(unitPrice).toFixed(2)}</td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeProduct(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        productTableBody.appendChild(row);
        updateTotal();
    }

    function validateQuantity(input, maxStock, unitPrice) {
        let value = parseInt(input.value);

        if (isNaN(value) || value < 1) {
            value = 1;
        } else if (value > maxStock) {
            value = maxStock;
            alert(`Stock máximo: ${maxStock} unidades`);
        }

        input.value = value;
        const row = input.closest('tr');
        updateRowSubtotal(row, unitPrice, value);
        updateTotal();
    }

    function updateRowSubtotal(row, unitPrice, quantity) {
        const subtotal = unitPrice * quantity;
        const subtotalCell = row.querySelector('.subtotal');
        subtotalCell.textContent = `S/ ${subtotal.toFixed(2)}`;
    }

    function removeProduct(button) {
        if (confirm('¿Está seguro de eliminar este producto?')) {
            const row = button.closest('tr');
            row.remove();
            updateTotal();
            renumberRows();
        }
    }

    function renumberRows() {
        const rows = productTableBody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.querySelector('td:first-child').textContent = index + 1;
        });
        productTableCounter = rows.length;
    }

    function updateTotal() {
        const subtotalCells = document.querySelectorAll('.subtotal');
        let total = 0;

        subtotalCells.forEach(cell => {
            const value = parseFloat(cell.textContent.replace('S/ ', '')) || 0;
            total += value;
        });

        document.getElementById('totalAmount').textContent = total.toFixed(2);
        document.getElementById('totalAmountInput').value = total.toFixed(2);

        // Si el total es 0, resetear métodos de pago
        if (total === 0) {
            resetPaymentMethods();
        } else {
            // Si hay un método de pago seleccionado, actualizar el monto automáticamente
            const metodoPagoActivo = document.querySelector('[id^="campos-"]:not(.d-none)');
            if (metodoPagoActivo) {
                const montoInput = metodoPagoActivo.querySelector('input[name^="monto["]');
                if (montoInput) {
                    const montoActual = parseFloat(montoInput.value) || 0;
                    // Solo actualizar si el campo está vacío o si era igual al total anterior
                    if (montoActual === 0 || Math.abs(montoActual - parseFloat(document.getElementById('totalAmountInput').getAttribute('data-previous-total') || 0)) < 0.01) {
                        montoInput.value = total.toFixed(2);
                    }

                    // Trigger del evento para recalcular vuelto si es efectivo
                    const event = new Event('input', {
                        bubbles: true
                    });
                    montoInput.dispatchEvent(event);
                }
            }
        }

        // Guardar el total actual para la próxima comparación
        document.getElementById('totalAmountInput').setAttribute('data-previous-total', total.toFixed(2));

        // Actualizar saldo
        calcularSaldo();
    }

    function calcularSaldo() {
        const total = parseFloat(document.getElementById('totalAmountInput').value) || 0;
        let totalPagado = 0;

        // Sumar todos los montos de pago visibles
        document.querySelectorAll('input[name^="monto["]').forEach(input => {
            const container = input.closest('.d-flex, .mb-3, .mb-4');
            if (container && !container.classList.contains('d-none') && container.style.display !== 'none') {
                totalPagado += parseFloat(input.value) || 0;
            }
        });

        const saldo = total - totalPagado;
        const saldoElement = document.getElementById('saldoAmount');

        if (saldoElement) {
            if (total === 0) {
                saldoElement.textContent = '0.00';
                saldoElement.className = '';
            } else {
                saldoElement.textContent = Math.abs(saldo).toFixed(2);

                // Aplicar validación y colores según las reglas de negocio
                const validacion = validarVenta();
                if (validacion.valido) {
                    if (saldo > 0.01) {
                        saldoElement.className = 'text-warning'; // Falta dinero pero es válido (anticipada)
                    } else if (saldo < -0.01) {
                        saldoElement.className = 'text-success'; // Vuelto válido
                    } else {
                        saldoElement.className = 'text-success'; // Exacto
                    }
                } else {
                    saldoElement.className = 'text-danger'; // Error en validación
                }
            }
        }

        return saldo;
    }

    /**
     * Función principal de validación de ventas según las reglas de negocio
     * @returns {object} {valido: boolean, mensaje: string, tipo: string}
     */
    function validarVenta() {
        // 1. Obtener variables necesarias
        const totalVenta = parseFloat(document.getElementById('totalAmountInput').value) || 0;
        
        // Si no hay total, no validar
        if (totalVenta === 0) {
            return { valido: true, mensaje: '', tipo: 'sin-venta' };
        }

        let totalPagado = 0;
        let hayEfectivo = false;
        let montoEfectivo = 0;

        // Calcular total pagado y verificar si hay efectivo
        document.querySelectorAll('input[name^="monto["]').forEach(input => {
            const container = input.closest('.d-flex, .mb-3, .mb-4');
            if (container && !container.classList.contains('d-none') && container.style.display !== 'none') {
                const monto = parseFloat(input.value) || 0;
                totalPagado += monto;
                
                // Verificar si es efectivo (case insensitive)
                const camposId = container.id;
                if (camposId && camposId.toLowerCase().includes('efectivo')) {
                    hayEfectivo = true;
                    montoEfectivo = monto;
                }
            }
        });

        const saldo = totalVenta - totalPagado;
        const esAnticipada = document.getElementById('anticipada').checked;
        const voucherType = document.querySelector('input[name="voucher_type"]')?.value?.toLowerCase() || 'ticket';
        const tipoComprobante = voucherType;

        // Debug info (se puede remover en producción)
        console.log('Validación:', {
            totalVenta,
            totalPagado,
            saldo,
            hayEfectivo,
            montoEfectivo,
            esAnticipada,
            tipoComprobante
        });

        // 2. Aplicar reglas de validación
        
        // VENTA DIRECTA (no anticipada)
        if (!esAnticipada) {
            // Sin efectivo: Pago debe ser exacto
            if (!hayEfectivo) {
                if (Math.abs(saldo) > 0.01) {
                    return {
                        valido: false,
                        mensaje: 'En ventas directas sin efectivo, el pago debe ser exacto',
                        tipo: 'pago-inexacto'
                    };
                }
                return { valido: true, mensaje: 'Pago exacto válido', tipo: 'pago-exacto' };
            }
            
            // Con efectivo: Debe pagar completo, puede haber vuelto
            if (saldo > 0.01) {
                return {
                    valido: false,
                    mensaje: 'En ventas directas debe pagarse el monto completo',
                    tipo: 'pago-incompleto'
                };
            }
            
            // Verificar que el vuelto no exceda el efectivo
            if (saldo < 0) {
                const vuelto = Math.abs(saldo);
                if (vuelto > montoEfectivo) {
                    return {
                        valido: false,
                        mensaje: `El vuelto (S/${vuelto.toFixed(2)}) no puede ser mayor al efectivo recibido (S/${montoEfectivo.toFixed(2)})`,
                        tipo: 'vuelto-excesivo'
                    };
                }
            }
            
            return { valido: true, mensaje: 'Venta directa válida', tipo: 'venta-directa-valida' };
        }
        
        // VENTA ANTICIPADA
        else {
            // Para Boleta/Factura: debe pagarse completo
            if (tipoComprobante === 'boleta' || tipoComprobante === 'factura') {
                // Sin efectivo: debe ser exacto
                if (!hayEfectivo) {
                    if (Math.abs(saldo) > 0.01) {
                        return {
                            valido: false,
                            mensaje: 'Para boletas/facturas el pago debe ser exacto',
                            tipo: 'boleta-factura-inexacta'
                        };
                    }
                    return { valido: true, mensaje: 'Boleta/Factura con pago exacto', tipo: 'boleta-factura-exacta' };
                }
                
                // Con efectivo: no puede quedar saldo pendiente
                if (saldo > 0.01) {
                    return {
                        valido: false,
                        mensaje: 'Para boletas/facturas no puede quedar saldo pendiente',
                        tipo: 'boleta-factura-saldo-pendiente'
                    };
                }
                
                // Verificar vuelto válido
                if (saldo < 0) {
                    const vuelto = Math.abs(saldo);
                    if (vuelto > montoEfectivo) {
                        return {
                            valido: false,
                            mensaje: `El vuelto (S/${vuelto.toFixed(2)}) no puede ser mayor al efectivo recibido (S/${montoEfectivo.toFixed(2)})`,
                            tipo: 'vuelto-excesivo'
                        };
                    }
                }
                
                return { valido: true, mensaje: 'Boleta/Factura anticipada válida', tipo: 'boleta-factura-anticipada-valida' };
            }
            
            // Para Ticket anticipado: más flexible
            else {
                // Sin efectivo: puede quedar saldo pero no debe haber vuelto
                if (!hayEfectivo) {
                    if (saldo < -0.01) {
                        return {
                            valido: false,
                            mensaje: 'Sin efectivo no puede haber vuelto en ticket anticipado',
                            tipo: 'ticket-vuelto-sin-efectivo'
                        };
                    }
                    return { valido: true, mensaje: 'Ticket anticipado válido', tipo: 'ticket-anticipado-valido' };
                }
                
                // Con efectivo: verificar que el vuelto no exceda el efectivo
                if (saldo < 0) {
                    const vuelto = Math.abs(saldo);
                    if (vuelto > montoEfectivo) {
                        return {
                            valido: false,
                            mensaje: `El vuelto (S/${vuelto.toFixed(2)}) no puede ser mayor al efectivo recibido (S/${montoEfectivo.toFixed(2)})`,
                            tipo: 'vuelto-excesivo'
                        };
                    }
                }
                
                return { valido: true, mensaje: 'Ticket anticipado con efectivo válido', tipo: 'ticket-anticipado-efectivo-valido' };
            }
        }
    }

    function resetPaymentMethods() {
        // Limpiar todos los campos de monto
        document.querySelectorAll('input[name^="monto["]').forEach(input => {
            input.value = '';
        });

        // Limpiar campo de vuelto
        const vueltoInput = document.getElementById('vuelto-efectivo');
        if (vueltoInput) {
            vueltoInput.value = '';
            vueltoInput.className = 'form-control';
        }

        // Ocultar todos los campos de métodos de pago
        document.querySelectorAll('[id^="campos-"]').forEach(campo => {
            campo.classList.add('d-none');
            campo.classList.remove('d-flex');
        });

        // Solo quitar la clase de selección activa (btn-success), mantener todos los demás estilos
        document.querySelectorAll('[data-id]').forEach(btn => {
            btn.classList.remove('btn-success');
        });
    }

    // Función mejorada para calcular vuelto en efectivo
    function calcularVueltoEfectivo(methodName, methodId, input) {
        if (methodName.toLowerCase() === 'efectivo') {
            const total = parseFloat(document.getElementById('totalAmountInput').value) || 0;
            const montoPagado = parseFloat(input.value) || 0;
            const vuelto = montoPagado - total;

            const vueltoInput = document.getElementById('vuelto-efectivo');
            if (vueltoInput) {
                if (total === 0 || montoPagado === 0) {
                    vueltoInput.value = '';
                    vueltoInput.className = 'form-control';
                } else if (vuelto > 0.01) {
                    vueltoInput.value = `Vuelto: S/ ${vuelto.toFixed(2)}`;
                    vueltoInput.className = 'form-control text-success';
                } else if (Math.abs(vuelto) <= 0.01) {
                    vueltoInput.value = 'Pago Exacto';
                    vueltoInput.className = 'form-control text-success';
                } else {
                    vueltoInput.value = `Falta: S/ ${Math.abs(vuelto).toFixed(2)}`;
                    vueltoInput.className = 'form-control text-danger';
                }
            }
        }
        calcularSaldo();
    }

    // Funciones auxiliares para validación
    function isNumber(event) {
        const charCode = (event.which) ? event.which : event.keyCode;
        // Permitir números (48-57), backspace (8), delete (46), tab (9)
        if (charCode < 48 || charCode > 57) {
            if (charCode !== 8 && charCode !== 46 && charCode !== 9) {
                event.preventDefault();
                return false;
            }
        }
        return true;
    }

    function isDecimal(event) {
        const charCode = (event.which) ? event.which : event.keyCode;
        const input = event.target;
        const value = input.value;

        // Permitir números (48-57), punto decimal (46), backspace (8), delete (46), tab (9)
        if ((charCode < 48 || charCode > 57) && charCode !== 46) {
            if (charCode !== 8 && charCode !== 9) {
                event.preventDefault();
                return false;
            }
        }

        // Permitir solo un punto decimal
        if (charCode === 46 && value.indexOf('.') !== -1) {
            event.preventDefault();
            return false;
        }

        return true;
    }

    function seleccionarMedioPago(methodId, event) {
        // Ocultar todos los campos de métodos de pago
        document.querySelectorAll('[id^="campos-"]').forEach(campo => {
            campo.classList.add('d-none');
            campo.classList.remove('d-flex');
        });

        // Quitar solo la clase de selección activa de todos los botones
        document.querySelectorAll('[data-id]').forEach(btn => {
            btn.classList.remove('btn-success');
        });

        // Mostrar el campo del método seleccionado
        const targetButton = event.target;
        const camposId = targetButton.getAttribute('data-campos');
        const camposElement = document.getElementById(camposId);

        if (camposElement) {
            camposElement.classList.remove('d-none');
            camposElement.classList.add('d-flex');

            // Resaltar botón seleccionado
            targetButton.classList.add('btn-success');

            // Auto-llenar con el total actual
            const totalActual = parseFloat(document.getElementById('totalAmountInput').value) || 0;
            const montoInput = camposElement.querySelector('input[name^="monto["]');

            if (montoInput && totalActual > 0) {
                montoInput.value = totalActual.toFixed(2);

                // Trigger del evento oninput para calcular vuelto si es efectivo
                const event = new Event('input', {
                    bubbles: true
                });
                montoInput.dispatchEvent(event);

                // Enfocar en el input después de un delay
                setTimeout(() => {
                    montoInput.focus();
                    montoInput.select(); // Seleccionar todo el texto para fácil edición
                }, 100);
            } else if (montoInput) {
                setTimeout(() => montoInput.focus(), 100);
            }
        }
    }

    function getSelectedVoucherType() {
        return ($('input[name="voucher_type"]').val() || 'Ticket').toString().toLowerCase();
    }

    function updateDocumentSearchUI(type) {
        const normalizedType = (type || 'Ticket').toString().toLowerCase();
        const $documentInput = $('#document');
        const $searchButton = $('#document-search-btn');

        $('#client').val('');
        $('#direccion').val('');
        $documentInput.val('');

        if (normalizedType === 'boleta') {
            $documentInput.attr('maxlength', 8).attr('placeholder', 'Ingrese DNI');
            $searchButton.removeClass('d-none');
        } else if (normalizedType === 'factura') {
            $documentInput.attr('maxlength', 11).attr('placeholder', 'Ingrese RUC');
            $searchButton.removeClass('d-none');
        } else {
            $documentInput.attr('maxlength', 11).attr('placeholder', 'Documento');
            $searchButton.addClass('d-none');
        }
    }

    // Función para buscar por API
    function searchAPI() {
        const voucherType = getSelectedVoucherType();
        const doc = ($('#document').val() || '').replace(/\D+/g, '');

        if (voucherType !== 'boleta' && voucherType !== 'factura') {
            ToastError.fire({ text: 'Seleccione boleta o factura para consultar documento.' });
            return;
        }

        const expectedLength = voucherType === 'factura' ? 11 : 8;

        if (doc.length !== expectedLength) {
            ToastError.fire({
                text: voucherType === 'factura'
                    ? 'Ingrese un RUC válido de 11 dígitos.'
                    : 'Ingrese un DNI válido de 8 dígitos.'
            });
            return;
        }

        const endpoint = voucherType === 'factura'
            ? "{{ route('api.ruc') }}"
            : "{{ route('api.reniec') }}";

        Swal.showLoading();

        $.ajax({
            url: endpoint,
            method: 'GET',
            data: voucherType === 'factura' ? { ruc: doc } : { dni: doc },
            success: function(response) {
                const success = typeof response.status !== 'undefined' ? response.status : response.success;
                const data = response.data || response;

                if (success) {
                    if (voucherType === 'factura') {
                        $('#document').val(data.ruc || doc);
                        $('#client').val((data.legal_name || data.name || '').trim());
                        $('#direccion').val((data.address || '').trim());
                    } else {
                        const nombreCompleto = (data.nombre_completo
                            || [data.nombres, data.apellido_paterno, data.apellido_materno].filter(Boolean).join(' ')
                            || data.name
                            || '').trim();

                        $('#document').val(data.dni || doc);
                        $('#client').val(nombreCompleto);
                        $('#direccion').val((data.direccion || '').trim());
                    }
                } else {
                    ToastError.fire({ text: response.message || 'No se encontró información' });
                }

                Swal.close();
            },
            error: function(xhr) {
                const message = xhr?.responseJSON?.message || 'Error al consultar DNI/RUC';
                ToastError.fire({ text: message });
                Swal.close();
            }
        });
    }

    // Función para seleccionar tipo de comprobante
    function selectVoucherType(type, button) {
        // Remover clases activas de todos los botones
        document.querySelectorAll('#btn-boleta, #btn-factura, #btn-ticket').forEach(btn => {
            btn.className = btn.className.replace('btn-primary', 'btn-outline-primary')
                .replace('btn-success', 'btn-outline-success')
                .replace('btn-info', 'btn-outline-info');
        });

        // Activar el botón seleccionado
        if (type === 'Boleta') {
            button.className = 'btn btn-primary me-1';
        } else if (type === 'Factura') {
            button.className = 'btn btn-success me-1';
        } else if (type === 'Ticket') {
            button.className = 'btn btn-info me-1';
        }

        // Crear o actualizar campo voucher_type
        let voucherTypeInput = $('input[name="voucher_type"]');
        if (voucherTypeInput.length === 0) {
            $('#saveSale').append(`<input type="hidden" name="voucher_type" value="${type}">`);
        } else {
            voucherTypeInput.val(type);
        }

        updateDocumentSearchUI(type);

        console.log('Tipo de comprobante seleccionado:', type);
    }

    $('#selectSede').on('change', function() {
        $('#location_id_hidden').val($(this).val());
    });

    // --- ENVÍO AJAX DEL FORMULARIO DE VENTA ---
    $('#saveSale').on('submit', function(e) {
        e.preventDefault();

        // Validar productos
        const productRows = document.querySelectorAll('tbody tr');
        if (productRows.length === 0) {
            // Ocultar spinner si falló validación
            $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
            ToastMessage.fire({
                icon: 'error',
                text: 'Debe seleccionar al menos un producto.'
            });
            return;
        }

        // Validar método de pago
        const metodoPagoActivo = document.querySelector('[id^="campos-"]:not(.d-none)');
        if (!metodoPagoActivo) {
            // Ocultar spinner si falló validación
            $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
            ToastMessage.fire({
                icon: 'error',
                text: 'Debe seleccionar al menos un método de pago.'
            });
            return;
        }

        // Validar monto de pago
        const montoInput = metodoPagoActivo.querySelector('input[name^="monto["]');
        const montoValue = parseFloat(montoInput.value) || 0;
        if (montoValue <= 0) {
            // Ocultar spinner si falló validación
            $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
            ToastMessage.fire({
                icon: 'error',
                text: 'Debe ingresar un monto válido para el método de pago seleccionado.'
            });
            return;
        }

        // NUEVA VALIDACIÓN: Verificar reglas de saldo según tipo de venta y comprobante
        const validacionSaldo = validarVenta();
        if (!validacionSaldo.valido) {
            // Ocultar spinner si falló validación
            $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
            ToastError.fire({
                text: validacionSaldo.mensaje
            });
            return;
        }

        // Validar documento según tipo de comprobante
        const voucherType = document.querySelector('input[name="voucher_type"]').value.toLowerCase();
        const documentValue = (document.getElementById('document').value || '').replace(/\D+/g, '');
        if (voucherType === 'factura' && documentValue.length !== 11) {
            // Ocultar spinner si falló validación
            $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
            ToastError.fire({
                text: 'Debe ingresar un RUC válido de 11 dígitos.'
            });
            return;
        }
        if (voucherType === 'boleta' && documentValue.length !== 8) {
            // Ocultar spinner si falló validación
            $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
            ToastError.fire({
                text: 'Debe ingresar un DNI válido de 8 dígitos.'
            });
            return;
        }

        // Preparar datos de productos
        const productsData = [];
        productRows.forEach(row => {
            const productId = row.getAttribute('data-product-id');
            const quantityInput = row.querySelector('.quantity-input');
            const priceInput = row.querySelector('input[name*="[precio]"]');
            if (productId && quantityInput && priceInput) {
                productsData.push({
                    id: productId,
                    cantidad: quantityInput.value,
                    precio: priceInput.value
                });
            }
        });

        // Preparar FormData solo con campos necesarios
        const form = this;
        const formData = new FormData();
        
        // Agregar campos básicos del formulario
        formData.append('_token', form.querySelector('input[name="_token"]').value);
        formData.append('user_id', form.querySelector('input[name="user_id"]').value);
        formData.append('location_id', document.getElementById('location_id_hidden').value);
        formData.append('type_sale', form.querySelector('input[name="type_sale"]').value);
        formData.append('status', form.querySelector('input[name="status"]').value);
        formData.append('type_status', form.querySelector('input[name="type_status"]').value);
        
        // Agregar campos de cliente y documento
        formData.append('document', document.getElementById('document').value);
        formData.append('client', document.getElementById('client').value);
        formData.append('telefono', document.getElementById('telefono').value);
        formData.append('direccion', document.getElementById('direccion').value);
        formData.append('referencia', document.getElementById('referencia').value);
        formData.append('observacion', document.getElementById('observacion').value);
        formData.append('fecha_entrega', document.getElementById('fecha_entrega').value);
        formData.append('hora_entrega', document.getElementById('hora_entrega').value);
        
        for (const pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        // Agregar solo el método de pago seleccionado
        const metodoPagoActivoInput = metodoPagoActivo.querySelector('input[name^="monto["]');
        if (metodoPagoActivoInput) {
            let montoAPagar = parseFloat(metodoPagoActivoInput.value) || 0;
            const totalVenta = parseFloat(document.getElementById('totalAmountInput').value) || 0;
            
            // Si es efectivo y hay vuelto, enviar solo el total de la venta
            const esEfectivo = metodoPagoActivo.id.toLowerCase().includes('efectivo');
            if (esEfectivo && montoAPagar > totalVenta) {
                montoAPagar = totalVenta;
            }
            
            formData.append(metodoPagoActivoInput.name, montoAPagar.toFixed(2));
        }
        
        // Agregar productos y otros datos
        formData.append('products', JSON.stringify(productsData));
        formData.append('voucher_type', voucherType.charAt(0).toUpperCase() + voucherType.slice(1));
        formData.append('total', document.getElementById('totalAmountInput').value);

        // Si anticipada está chequeado
        if (document.getElementById('anticipada').checked) {
            formData.append('anticipada', 'on');
        }

    // Enviar AJAX
        $.ajax({
            url: $(form).attr('action'),
            method: $(form).attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            success: async function(response) {
                if (response.status) {
                    ToastMessage.fire({
                        icon: 'success',
                        text: 'Venta registrada correctamente.'
                    });
                    
                    // Resetear formulario y UI
                    resetFormulario();
                    
                    // Imprimir venta si hay ID
                    if (response.sale_id) {
                        await imprimirVenta(response.sale_id);
                    }
                } else {
                    ToastError.fire({
                        text: 'No se pudo registrar la venta'
                    });
                }
            },
            error: function(xhr) {
                let msg = 'Error al registrar venta';
                try {
                    const json = JSON.parse(xhr.responseText);
                    if (json?.error) msg = json.error;
                } catch (e) {}
                ToastError.fire({ text: msg });
            }
        });
    });

    // Resetear formulario después de venta exitosa
    function resetFormulario() {
        // Limpiar tabla de productos
        document.querySelectorAll('tbody tr').forEach(tr => tr.remove());
        productTableCounter = 0;
        renumberRows();

        // Limpiar contenedor de productos
        const productContainer = document.getElementById('product-container');
        if (productContainer) productContainer.innerHTML = '';

        // Limpiar campos del formulario
        ['document','client','telefono','direccion','referencia','observacion','hora_entrega','search-product','fecha_entrega'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        // Resetear inputs ocultos y totales
        $('#type_sale').val(0);
        $('#status').val(1);
        $('#type_status').val(0);
        $('#totalAmount').text('0.00');
        $('#totalAmountInput').val('0.00').removeAttr('data-previous-total');

        // Limpiar y ocultar métodos de pago
        resetPaymentMethods();

        // Resetear checkboxes y otros elementos
        $('#anticipada').prop('checked', false);
        $('#product_id').val('');
        $('#saldoAmount').text('0.00').removeClass();
        $('#vuelto-efectivo').val('').attr('class', 'form-control');
        
        // Limpiar y ocultar métodos de pago
        $('input[name^="monto["]').val('');
        
        // Resetear botones
        $('button[onclick*="handleCategoryClick"]').attr('class', 'btn btn-outline-primary btn-sm m-1');
        $('[data-id]').removeClass('btn-success');
        
        @if($invoices_enabled)
        $('#btn-boleta').attr('class', 'btn btn-outline-primary me-1');
        $('#btn-factura').attr('class', 'btn btn-outline-success me-1');
        @endif

        $('#btn-ticket').attr('class', 'btn btn-info me-1');

        // Resetear tipo de comprobante a Ticket
        let voucher = $('input[name="voucher_type"]');
        if (voucher.length === 0) {
            $('#saveSale').append('<input type="hidden" name="voucher_type" value="Ticket">');
        } else {
            voucher.val('Ticket');
        }

        updateDocumentSearchUI('Ticket');
    }

    // Función para convertir números a letras
    function numeroALetras(numero) {
        const unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        const decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        const especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        const centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if (numero === 0) return 'CERO SOLES';
        
        const entero = Math.floor(numero);
        const decimales = Math.round((numero - entero) * 100);
        
        let resultado = '';
        
        if (entero === 0) {
            resultado = 'CERO';
        } else if (entero < 10) {
            resultado = unidades[entero];
        } else if (entero < 20) {
            resultado = especiales[entero - 10];
        } else if (entero < 100) {
            const dec = Math.floor(entero / 10);
            const uni = entero % 10;
            resultado = decenas[dec] + (uni > 0 ? ' Y ' + unidades[uni] : '');
        } else if (entero < 1000) {
            const cen = Math.floor(entero / 100);
            const resto = entero % 100;
            resultado = (entero === 100 ? 'CIEN' : centenas[cen]);
            if (resto > 0) {
                resultado += ' ' + numeroALetras(resto).replace(' SOLES', '');
            }
        } else {
            resultado = 'MAS DE MIL';
        }
        
        resultado += ' SOLES';
        if (decimales > 0) {
            resultado += ' CON ' + decimales.toString().padStart(2, '0') + '/100';
        }
        
        return resultado;
    }

    function imprimirVenta(saleId) {
        $.ajax({
            url: "{{ route('sale_print') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                sale_id: saleId
            },
            success: async function(response) {
                if (!response.status) {
                    ToastError.fire({
                        text: response.error || 'Error al obtener datos de la venta'
                    });
                    return;
                }

                const data = response;
                const venta = data.venta;
                const productos = data.productos;
                const pagos = data.pagos;
                const voucherType = (venta.voucher_type || '').toLowerCase();

                // Formato especial para boleta/factura
                if (voucherType === 'boleta' || voucherType === 'factura') {
                    // Calcular OP. GRAVADA e IGV
                    let opGravada = 0;
                    let igv = 0;
                    let total = 0;
                    let productosLineas = [];

                    productos.forEach(function(producto) {
                        const cantidad = parseFloat(producto.cantidad) || 0;
                        const precio = parseFloat(producto.precio) || 0;
                        const subtotal = parseFloat(producto.subtotal) || (cantidad * precio);
                        opGravada += subtotal;
                        productosLineas.push({
                            nombre: producto.nombre,
                            cantidad: cantidad,
                            precio: precio,
                            subtotal: subtotal
                        });
                    });

                    let opGravadaSinIGV = opGravada / 1.18;
                    igv = opGravada - opGravadaSinIGV;
                    total = opGravada;

                    let operaciones = [{
                            nombre: "Iniciar",
                            argumentos: []
                        },
                        {
                            nombre: "EstablecerAlineacion",
                            argumentos: [1]
                        },
                        {
                            nombre: "EstablecerEnfatizado",
                            argumentos: [true]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: ["MY LADY\n"]
                        },
                        {
                            nombre: "EstablecerEnfatizado",
                            argumentos: [false]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: ["RUC 10166794493\n"]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: ["MZ. C LOTE. 19 URB. EL AMAUTA\n"]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: ["CHICLAYO - LAMBAYEQUE\n"]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: [`SEDE: ${venta.location_name || 'Sin sede'}\n`]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: ["=================================================\n"]
                        },
                        {
                            nombre: "EstablecerEnfatizado",
                            argumentos: [true]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: [voucherType === 'boleta' ? "BOLETA DE VENTA ELECTRÓNICA\n" : "FACTURA ELECTRÓNICA\n"]
                        },
                        {
                            nombre: "EstablecerEnfatizado",
                            argumentos: [false]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: [`${venta.number || ''}\n`]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: [
                                voucherType === 'factura' ?
                                `RAZON SOCIAL: ${venta.cliente || 'CLIENTE VARIOS'}\n` :
                                `NOMBRE: ${venta.cliente || 'CLIENTE VARIOS'}\n`
                            ]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: [
                                voucherType === 'factura' ?
                                `RUC: ${venta.document || '00000000000'}\n` :
                                `DNI: ${venta.document || '00000000'}\n`
                            ]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: [`EMISION: ${data.now || ''}\n`]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: ["MONEDA:  SOL (PEN)\n"]
                        },
                        {
                            nombre: "EscribirTexto",
                            argumentos: ["METODOS DE PAGO\n"]
                        }
                    ];

                    // Agregar métodos de pago
                    if (pagos && pagos.length > 0) {
                        pagos.forEach(function(pago) {
                            operaciones.push({
                                nombre: 'EscribirTexto',
                                argumentos: [`${pago.metodo_pago}: S/${parseFloat(pago.monto).toFixed(2)}\n`]
                            });
                        });
                    }

                    // Agregar productos
                    operaciones.push({
                        nombre: "EscribirTexto",
                        argumentos: ["------------------------------------------------\n"]
                    }, {
                        nombre: 'EscribirTexto',
                        argumentos: ['CODIGO DESCRIPCION   CANT   P.UNIT   P.TOTAL\n']
                    }, {
                        nombre: "EscribirTexto",
                        argumentos: ["-------------------------------------------------\n"]
                    });

                    productosLineas.forEach(function(prod) {
                        // Divide el nombre en líneas de máximo 20 caracteres
                        let nombre = prod.nombre;
                        let lineas = [];
                        while (nombre.length > 20) {
                            lineas.push(nombre.substring(0, 20));
                            nombre = nombre.substring(20);
                        }
                        if (nombre.length > 0) lineas.push(nombre);

                        // Imprime la primera línea con las columnas
                        let cantidad = prod.cantidad.toFixed(2).padStart(5);
                        let precio = prod.precio.toFixed(2).padStart(8);
                        let subtotal = prod.subtotal.toFixed(2).padStart(8);
                        operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [lineas[0].padEnd(20) + cantidad + precio + subtotal + '\n']
                        });

                        // Imprime las siguientes líneas solo con el nombre
                        for (let i = 1; i < lineas.length; i++) {
                            operaciones.push({
                                nombre: 'EscribirTexto',
                                argumentos: [lineas[i] + '\n']
                            });
                        }
                    });

                    // Totales
                    operaciones.push({
                        nombre: "EscribirTexto",
                        argumentos: ["------------------------------------------------\n"]
                    }, {
                        nombre: "EscribirTexto",
                        argumentos: ["OP. GRAVADA   : S/ " + opGravadaSinIGV.toFixed(2) + "\n"]
                    }, {
                        nombre: "EscribirTexto",
                        argumentos: ["IGV           : S/ " + igv.toFixed(2) + "\n"]
                    }, {
                        nombre: "EscribirTexto",
                        argumentos: ["IMPORTE TOTAL : S/ " + total.toFixed(2) + "\n"]
                    }, {
                        nombre: "EscribirTexto",
                        argumentos: ["SON: " + numeroALetras(total) + "\n"]
                    });

                    // Información adicional
                    operaciones.push({
                        nombre: "EscribirTexto",
                        argumentos: ["\nINFORMACION ADICIONAL:\n"]
                    });

                    // Agrega dirección si existe
                    if (venta.direccion) {
                        // Divide la dirección en líneas de máximo 40 caracteres
                        let direccion = `DIRECCION: ${venta.direccion}`;
                        while (direccion.length > 40) {
                            operaciones.push({
                                nombre: "EscribirTexto",
                                argumentos: [direccion.substring(0, 40) + '\n']
                            });
                            direccion = direccion.substring(40);
                        }
                        if (direccion.length > 0) {
                            operaciones.push({
                                nombre: "EscribirTexto",
                                argumentos: [direccion + '\n']
                            });
                        }
                    }

                    // Agrega referencia si existe
                    if (venta.referencia) {
                        operaciones.push({
                            nombre: "EscribirTexto",
                            argumentos: [`REFERENCIA: ${venta.referencia}\n`]
                        });
                    }

                    // Agrega observación si existe
                    if (venta.observacion) {
                        operaciones.push({
                            nombre: "EscribirTexto",
                            argumentos: [`OBSERVACION: ${venta.observacion}\n`]
                        });
                    }
                    // Footer
                    operaciones.push({
                        nombre: "Feed",
                        argumentos: [2]
                    }, {
                        nombre: "EstablecerAlineacion",
                        argumentos: [1]
                    }, {
                        nombre: "EscribirTexto",
                        argumentos: ["Gracias por su preferencia\n"]
                    }, {
                        nombre: "EscribirTexto",
                        argumentos: ["Implementado por xinergia.net\n"]
                    }, {
                        nombre: "EscribirTexto",
                        argumentos: [`IMPRESION: ${data.now}\n`]
                    }, {
                        nombre: "Feed",
                        argumentos: [1]
                    }, {
                        nombre: "Corte",
                        argumentos: [1]
                    });

                    // IMPRESIÓN DE BOLETA/FACTURA
                    try {
                        // Verificar si el servicio de impresión está disponible
                        const testResponse = await fetch('http://localhost:8000/imprimir', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                serial: serial,
                                nombreImpresora: 'Ticketera',
                                operaciones: operaciones
                            })
                        });

                        if (!testResponse.ok) {
                            throw new Error(`HTTP error! status: ${testResponse.status}`);
                        }

                        const res = await testResponse.json();
                        if (!res.ok) {
                            throw new Error(res.message || 'Error al imprimir localmente');
                        } else {
                            ToastMessage.fire({
                                text: 'Comprobante impreso correctamente'
                            });
                        }
                    } catch (error) {
                        console.log('Error en impresión local, intentando remota:', error.message);

                        // Si falla local, intentar impresión remota
                        try {
                            const payload = {
                                operaciones: operaciones,
                                nombreImpresora: 'Ticketera',
                                serial: serial,
                            };

                            const remoteResponse = await fetch('http://localhost:8000/reenviar', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json; charset=utf-8'
                                },
                                body: JSON.stringify(payload)
                            });

                            if (!remoteResponse.ok) {
                                throw new Error(`HTTP error en remoto! status: ${remoteResponse.status}`);
                            }

                            const remoteRes = await remoteResponse.json();
                            if (remoteRes.ok) {
                                ToastMessage.fire({
                                    text: 'Comprobante impreso correctamente (Remoto)'
                                });
                            } else {
                                throw new Error('Impresión remota falló: ' + (remoteRes.message || 'Error desconocido'));
                            }
                        } catch (errorRemoto) {
                            ToastError.fire({
                                text: 'Error al imprimir: ' + (errorRemoto.message || 'Servicio no disponible')
                            });
                            return;
                        }
                    }

                    // Si llegó aquí, la impresión fue exitosa, terminar función
                    return;
                }

                // FORMATO ORIGINAL PARA TICKET (solo si NO es boleta/factura)
                const opts = {
                    serial: serial,
                    nombreImpresora: 'Ticketera',
                    operaciones: [{
                            nombre: 'Iniciar',
                            argumentos: []
                        },
                        {
                            nombre: "EstablecerAlineacion",
                            argumentos: [1]
                        },
                        {
                            nombre: 'EscribirTexto',
                            argumentos: ['MY LADY\n']
                        },
                        {
                            nombre: 'EscribirTexto',
                            argumentos: ['----------------------------------------\n']
                        },
                        {
                            nombre: 'EscribirTexto',
                            argumentos: [`TICKET DE VENTA\n`]
                        },
                        {
                            nombre: "EstablecerAlineacion",
                            argumentos: [0]
                        },
                        {
                            nombre: 'EscribirTexto',
                            argumentos: ['----------------------------------------\n']
                        },
                        {
                            nombre: 'EscribirTexto',
                            argumentos: [`NUMERO: ${venta.number || ''}\n`]
                        },
                        {
                            nombre: 'EscribirTexto',
                            argumentos: [`FECHA: ${venta.fecha}\n`]
                        },
                        {
                            nombre: 'EscribirTexto',
                            argumentos: [`SEDE: ${venta.location_name || 'Sin sede'}\n`]
                        }
                    ]
                };

                opts.operaciones.push({
                    nombre: 'EscribirTexto',
                    argumentos: ['----------------------------------------\n']
                });

                /*if (venta.type_sale == 1) {
                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: ['INFORMACION DE PAGOS:\n']
                    }, {
                        nombre: 'EscribirTexto',
                        argumentos: [`TOTAL VENTA: S/${venta.total}\n`]
                    }, {
                        nombre: 'EscribirTexto',
                        argumentos: [`TOTAL PAGADO: S/${(venta.total - venta.saldo).toFixed(2)}\n`]
                    }, {
                        nombre: 'EscribirTexto',
                        argumentos: [`SALDO PENDIENTE: S/${venta.saldo}\n`]
                    }, {
                        nombre: 'EscribirTexto',
                        argumentos: ['----------------------------------------\n']
                    });
                }*/

                // Métodos de pago
                /*if (pagos && pagos.length > 0) {
                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: ['METODOS DE PAGO:\n']
                    });
                    pagos.forEach(function(pago) {
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`${pago.metodo_pago}: S/${pago.monto}\n`]
                        });
                    });
                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: ['----------------------------------------\n']
                    });
                }*/

                // Productos
                opts.operaciones.push({
                    nombre: 'EscribirTexto',
                    argumentos: ['PRODUCTOS:\n']
                });
                opts.operaciones.push({
                    nombre: 'EscribirTexto',
                    argumentos: ['CANT PRODUCTO        P.U     TOTAL\n']
                });
                opts.operaciones.push({
                    nombre: 'EscribirTexto',
                    argumentos: ['----------------------------------------\n']
                });

                productos.forEach(function(producto) {
                    const cant = producto.cantidad.toString().padEnd(4);
                    const precio = `S/${parseFloat(producto.precio).toFixed(2)}`.padStart(8);
                    const total = `S/${parseFloat(producto.subtotal).toFixed(2)}`.padStart(8);

                    if (producto.nombre.length > 15) {
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`${cant} ${producto.nombre}\n`]
                        });
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`${' '.repeat(19)} ${precio} ${total}\n`]
                        });
                    } else {
                        const nombre = producto.nombre.padEnd(15);
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`${cant} ${nombre} ${precio} ${total}\n`]
                        });
                    }
                });

                // Footer del ticket
                opts.operaciones.push({
                    nombre: 'EscribirTexto',
                    argumentos: ['----------------------------------------\n']
                });
                opts.operaciones.push({
                    nombre: "EstablecerAlineacion",
                    argumentos: [2]
                });
                opts.operaciones.push({
                    nombre: 'EscribirTexto',
                    argumentos: [`TOTAL: S/${parseFloat(venta.total).toFixed(2)}\n`]
                });
                opts.operaciones.push({
                    nombre: "EstablecerAlineacion",
                    argumentos: [0]
                });
                opts.operaciones.push({
                    nombre: 'EscribirTexto',
                    argumentos: ['----------------------------------------\n']
                });
                opts.operaciones.push({
                    nombre: "EstablecerAlineacion",
                    argumentos: [1]
                });

                // Información adicional para ventas anticipadas
                /*if (venta.type_sale == 1) {
                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: ['INFORMACION ADICIONAL:\n']
                    });
                    
                    if (venta.referencia) {
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`REFERENCIA: ${venta.referencia}\n`]
                        });
                    }
                    if (venta.observacion) {
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`OBSERVACION: ${venta.observacion}\n`]
                        });
                    }
                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: ['----------------------------------------\n']
                    });
                }*/

                opts.operaciones.push({
                    nombre: "EstablecerAlineacion",
                    argumentos: [1]
                }, {
                    nombre: "EstablecerAlineacion",
                    argumentos: [0]
                }, {
                    nombre: 'Feed',
                    argumentos: [2]
                }, {
                    nombre: "EstablecerAlineacion",
                    argumentos: [1]
                }, {
                    nombre: 'EscribirTexto',
                    argumentos: ['Gracias por su preferencia\n']
                }, {
                    nombre: 'EscribirTexto',
                    argumentos: ['Implementado por xinergia.net\n']
                }, {
                    nombre: 'EscribirTexto',
                    argumentos: [`IMPRESION: ${data.now}\n`]
                }, {
                    nombre: 'Feed',
                    argumentos: [1]
                }, {
                    nombre: 'Corte',
                    argumentos: [1]
                });

                // IMPRESIÓN DEL TICKET
                try {
                    // Intentar impresión local primero
                    const http = await fetch('http://localhost:8000/imprimir', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            serial: serial,
                            nombreImpresora: 'Ticketera',
                            operaciones: opts.operaciones
                        })
                    });

                    if (!http.ok) {
                        throw new Error(`HTTP error! status: ${http.status}`);
                    }

                    const res = await http.json();
                    if (!res.ok) {
                        throw new Error(res.message || 'Error al imprimir localmente');
                    } else {
                        ToastMessage.fire({
                            text: 'Ticket impreso correctamente'
                        });
                    }
                } catch (error) {
                    console.log('Error en impresión local, intentando remota:', error.message);

                    // Si falla local, intentar impresión remota
                    try {
                        const payload = {
                            operaciones: opts.operaciones,
                            nombreImpresora: 'Ticketera',
                            serial: serial,
                        };

                        const remoteResponse = await fetch('http://localhost:8000/reenviar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json; charset=utf-8'
                            },
                            body: JSON.stringify(payload)
                        });

                        if (!remoteResponse.ok) {
                            throw new Error(`HTTP error en remoto! status: ${remoteResponse.status}`);
                        }

                        const remoteRes = await remoteResponse.json();
                        if (remoteRes.ok) {
                            ToastMessage.fire({
                                text: 'Ticket impreso correctamente (Remoto)'
                            });
                        } else {
                            throw new Error('Impresión remota falló: ' + (remoteRes.message || 'Error desconocido'));
                        }
                    } catch (errorRemoto) {
                        ToastError.fire({
                            text: 'Error al imprimir: ' + (errorRemoto.message || 'Servicio no disponible')
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('Error en la solicitud:', error);
                ToastError.fire({
                    text: 'Error al obtener datos para impresión'
                });
            }
        });
    }
</script>
@endsection
