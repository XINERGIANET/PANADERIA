@extends('layouts.app')

@section('nav')
<style>
    .card-mesa.borde-naranja {
        border: 6px solid #ffa500 !important;
    }

    .card-mesa.borde-rojo {
        border: 6px solid red !important;
    }

    .card-mesa.borde-verde {
        border: 6px solid green !important;
    }
</style>
@endsection

@section('header')
<h2>Punto de Venta Restaurante</h2>
<p>Lista de mesas</p>
@endsection

@section('content')
@php
$colors = ['btn-outline-primary', 'btn-outline-success', 'btn-outline-info', 'btn-outline-warning', 'btn-outline-danger', 'btn-outline-dark'];
@endphp
<div class="container-fluid content-inner mt-n5 py-0">
    <!-- Card que contiene el formulario y la tabla -->
    <div class="card shadow">
        <!-- Cuerpo del Card -->
        <div class="card-body">
            <div class="row g-4">
                @foreach($mesas as $mesa)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="card shadow border-0 rounded-4 text-center h-100 card-mesa" id="mesa-card-{{ $mesa->id }}" data-mesa-id="{{ $mesa->id }}" data-opened-at="{{ $mesa->opened_at }}">
                        <div class="card-body d-flex flex-column justify-content-between ">
                            <h5 class="card-title mb-3 fw-bold">{{ $mesa->name }}</h5>

                            <span id="estado-mesa-{{ $mesa->id }}" class="badge mb-2 {{ $mesa->status == 'Libre' ? 'bg-success' : 'bg-danger' }} fs-5">
                                {{ ucfirst($mesa->status) }}
                            </span>

                            <div id="acciones-mesa-{{ $mesa->id }}">
                                @if($mesa->status === 'Libre')
                                <button class="btn btn-primary rounded-pill" onclick="abrirMesa('{{ $mesa->id }}', event)">
                                    Abrir Mesa
                                </button>
                                @else
                                <div class="d-grid gap-2">
                                    <button class="btn btn-warning rounded-pill" onclick="verPedido('{{ $mesa->id }}', event)">
                                        Ver Pedido
                                    </button>
                                    <button class="btn btn-danger rounded-pill" onclick="cerrarMesa('{{ $mesa->id }}', event)">
                                        Cancelar Venta <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                @if($mesa->opened_at)
                                <div class="mt-2 text-muted small">
                                    Tiempo: <span id="contador-{{ $mesa->id }}">--:--</span>
                                </div>
                                @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Modal para Abrir Mesa -->
<div class="modal fade" id="abrirMesaModal" tabindex="-1" aria-labelledby="abrirMesaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="abrirMesaModalLabel">Abrir Mesa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <!-- Seleccionar Productos -->
                    <div class="form-group">
                        <label for="producto_id" class="col-sm-3 col-form-label text-start"><strong>Producto</strong></label>
                        <div class="col-md-12">
                            <input hidden type="number" class="form-control" name="producto_id" id="producto_id">
                            <input type="text" class="form-control" name="name" id="search-product" placeholder="Buscar Producto">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 col-form-label text-start"><strong>Categorías</strong></label>
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
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="row justify-content-end mb-3">
                        <div class="col-md-5 text-end">
                            <h5><strong>TOTAL: S/ <span id="totalAmount" name="total">0.00</span></strong></h5>
                            <input hidden type="number" step="0.01" name="total" id="totalAmountInput" value="0">
                            <button class="btn me-2 mt-3 btn-warning" type="button" onclick="confirmOrder()">Confirmar</button>
                            <button class="btn me-2 mt-3 btn-success" type="button" onclick="abrirModalCobro()">Cobrar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cobro -->
<div class="modal fade" id="modalCobro" tabindex="-1" aria-labelledby="modalCobroLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Cobro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <form id="formCobro">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Selección de Comprobante -->
                            <div class="mb-3">
                                <label class="mb-2"><strong>Tipo de Comprobante</strong></label>
                                <div class="btn-group d-flex justify-content-start mb-4">
                                    <button type="button" class="btn btn-outline-primary me-1"
                                        onclick="selectVoucherType('boleta', this)">Boleta</button>
                                    <button type="button" class="btn btn-outline-success me-1"
                                        onclick="selectVoucherType('factura', this)">Factura</button>
                                    <button type="button" class="btn btn-outline-info me-1"
                                        onclick="selectVoucherType('ticket', this)">Ticket</button>
                                </div>
                                <input type="hidden" name="voucher_type" id="voucher_type" value="">
                            </div>

                            <!-- Documento y Cliente -->
                            <div class="mb-3">
                                <label class="col-sm-4 col-form-label text-start"><strong>Documento</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-xs" id="document"
                                        name="document" maxlength="11" onkeypress="isNumber(event)">
                                    <button type="button" class="btn btn-primary btn-xs"
                                        onclick="searchAPI('#document','#name','#address')"><i
                                            class="bi bi-search"></i></button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Cliente</strong></label>
                                <input type="text" class="form-control" id="client" name="client">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Observación</strong></label>
                                <textarea class="form-control" id="observacion" name="observacion" rows="2" placeholder="Observaciones adicionales (opcional)"></textarea>
                            </div>
                            <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
                            <input hidden type="number" name="type_sale" id="type_sale" value="1">
                            <input hidden type="number" name="status" id="status" value="1">
                            <input hidden type="number" name="type_status" id="type_status" value="0">
                        </div>

                        <div class="col-md-6">
                            <!-- Métodos de pago -->
                            <div class="mb-3">
                                <label class="mb-2"><strong>Método de Pago</strong></label>
                                <div class="d-flex flex-wrap">
                                    @foreach ($pms as $index => $method)
                                    @php
                                    $colorClass = $colors[$index % count($colors)];
                                    @endphp
                                    <button
                                        type="button"
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

                            <!-- Campos por método de pago -->
                            @foreach ($pms as $method)
                            <div class="mb-3 d-none align-items-center gap-3" id="campos-{{ $method->name }}">
                                <label class="form-label mb-0">
                                    <strong>{{ strtoupper(Str::limit($method->name, 4, '.')) }}</strong>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="text" class="form-control" placeholder="Ingrese Monto"
                                        name="monto[{{ $method->id }}]" onkeypress="isDecimal(event)" oninput="calcularSaldo()">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="modal-footer mt-4">
                        <!-- Mostrar el total y saldo -->
                        <div class="mb-2 text-end w-100">
                            <h5><strong>TOTAL: S/ <span id="totalAmountModal">0.00</span></strong></h5>
                            <h6><strong>SALDO: S/ <span id="saldoAmount" class="text-danger">0.00</span></strong></h6>
                        </div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Finalizar Venta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function abrirModalCobro() {
        // Sincronizar total en el modal
        const total = $('#totalAmount').text();
        $('#totalAmountModal').text(total);

        // Inicializar el observador del total
        inicializarObservadorTotal();

        // Calcular saldo inicial
        setTimeout(() => {
            calcularSaldo();
        }, 100);

        const modal = new bootstrap.Modal(document.getElementById('modalCobro'));
        modal.show();
    }

    // Restaura la UI de la mesa cuando se libera (para evitar duplicación)
    function restoreMesaUI(mesaId) {
        const estadoSpan = document.getElementById(`estado-mesa-${mesaId}`);
        if (estadoSpan) {
            estadoSpan.textContent = 'Libre';
            estadoSpan.classList.remove('bg-danger');
            estadoSpan.classList.add('bg-success');
        }

        const accionesDiv = document.getElementById(`acciones-mesa-${mesaId}`);
        if (accionesDiv) {
            accionesDiv.innerHTML = `
                <button class="btn btn-primary rounded-pill" onclick="abrirMesa('${mesaId}', event)">
                    Abrir Mesa
                </button>
            `;
        }

        const contador = document.getElementById(`contador-${mesaId}`);
        if (contador) contador.remove();

        // Limpiar timer específico de la mesa
        if (mesaTimers[mesaId]) {
            clearInterval(mesaTimers[mesaId]);
            delete mesaTimers[mesaId];
        }

        const card = document.getElementById(`mesa-card-${mesaId}`);
        if (card) {
            // Remover todas las clases de borde específicamente
            card.classList.remove('borde-verde', 'borde-naranja', 'borde-rojo');
            
            // Remover cualquier estilo inline de border que pueda existir
            card.style.removeProperty('border');
            card.style.removeProperty('border-color');
            card.style.removeProperty('border-width');
            card.style.removeProperty('border-style');
            
            // Restaurar completamente las clases originales
            card.className = 'card shadow border-0 rounded-4 text-center h-100 card-mesa';
            
            // Verificación adicional después de un pequeño delay
            setTimeout(() => {
                if (card.classList.contains('borde-verde') || 
                    card.classList.contains('borde-naranja') || 
                    card.classList.contains('borde-rojo')) {
                    console.warn('Borde persistente detectado en mesa', mesaId, '- forzando limpieza');
                    card.classList.remove('borde-verde', 'borde-naranja', 'borde-rojo');
                    card.style.border = 'none !important';
                }
            }, 100);
            
            console.log('Mesa', mesaId, 'restaurada correctamente - clases:', card.className);
        }
    }

    var serial = "{{ config('printer.serial') }}"
    let openedMesaId = null;
    let timerInterval;
    let selectedProducts = [];
    const mesaTimers = {}; // Asegúrate de declarar esto en el scope global

    function seleccionarComprobante(comprobante, event) {
        const parent = event.target.closest('.btn-group');
        Array.from(parent.children).forEach(child => {
            child.classList.remove('active');
        });

        event.target.classList.add('active');
        document.getElementById('voucher_type').value = comprobante;
    }

    const totalAmountSpan = document.getElementById('totalAmount');

    // Asegurar que el observador se configure después de que el modal esté abierto
    function inicializarObservadorTotal() {
        if (totalAmountSpan && !totalAmountSpan.hasObserver) {
            const observer = new MutationObserver(() => {
                // Sincronizar total en el modal cuando cambie
                const total = $('#totalAmount').text();
                $('#totalAmountModal').text(total);
                calcularSaldo();
            });

            observer.observe(totalAmountSpan, {
                childList: true, // Cambios en los nodos hijos (texto)
                characterData: true, // Cambios en texto directo
                subtree: true // Observar todo el subtree
            });

            totalAmountSpan.hasObserver = true;
            console.log('Observador del total inicializado');
        }
    }

    function seleccionarMedioPago(medio_id, event) {
        // Ocultar todos los campos de métodos de pago
        document.querySelectorAll('[id^="campos-"]').forEach(campo => {
            campo.classList.add('d-none');
            campo.classList.remove('d-flex', 'align-items-center');
        });

        // Quitar solo la clase de selección activa de todos los botones
        document.querySelectorAll('[data-id]').forEach(btn => {
            btn.classList.remove('btn-success', 'active');
        });

        // Limpiar todos los inputs de monto
        document.querySelectorAll('input[name^="monto["]').forEach(input => {
            input.value = '';
        });

        // Mostrar el campo del método seleccionado
        const targetButton = event.target;
        const camposId = targetButton.getAttribute('data-campos');
        const camposElement = document.getElementById(camposId);

        if (camposElement) {
            camposElement.classList.remove('d-none');
            camposElement.classList.add('d-flex', 'align-items-center');

            // Resaltar botón seleccionado
            targetButton.classList.add('btn-success', 'active');

            // Auto-llenar con el total actual
            const totalActual = parseFloat($('#totalAmount').text()) || 0;
            const montoInput = camposElement.querySelector('input[name^="monto["]');

            if (montoInput && totalActual > 0) {
                montoInput.value = totalActual.toFixed(2);
                console.log('Auto-llenando monto:', totalActual.toFixed(2), 'para método:', medio_id);

                // Enfocar en el input después de un delay
                setTimeout(() => {
                    montoInput.focus();
                    montoInput.select(); // Seleccionar todo el texto para fácil edición
                }, 100);
            } else if (montoInput) {
                setTimeout(() => montoInput.focus(), 100);
            }

            // Calcular saldo después de llenar el monto
            setTimeout(() => {
                calcularSaldo();
            }, 50);
        }
    }

    function selectVoucherType(type, button) {
        // Remover clases activas de todos los botones
        const buttons = button.closest('.btn-group').querySelectorAll('button');
        buttons.forEach(btn => {
            btn.classList.remove('btn-primary', 'btn-success', 'btn-info');
            if (btn.textContent.trim() === 'Boleta') {
                btn.className = 'btn btn-outline-primary me-1';
            } else if (btn.textContent.trim() === 'Factura') {
                btn.className = 'btn btn-outline-success me-1';
            } else if (btn.textContent.trim() === 'Ticket') {
                btn.className = 'btn btn-outline-info me-1';
            }
        });

        // Activar el botón seleccionado
        if (type === 'boleta') {
            button.className = 'btn btn-primary me-1';
        } else if (type === 'factura') {
            button.className = 'btn btn-success me-1';
        } else if (type === 'ticket') {
            button.className = 'btn btn-info me-1';
        }

        // Establecer el valor en el campo oculto con la primera letra en mayúscula (como espera el backend)
        const voucherValue = type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById('voucher_type').value = voucherValue;

        console.log('Tipo de comprobante seleccionado:', voucherValue);
    }

    function isDecimal(evt) {
        evt = evt || window.event;
        var charCode = evt.which || evt.keyCode;

        // Solo permite números y un solo punto decimal
        if ((charCode >= 48 && charCode <= 57) || charCode === 46) {
            const input = evt.target || evt.srcElement;
            if (charCode === 46 && input.value.includes('.')) {
                evt.preventDefault();
                return false;
            }
            return true;
        } else {
            evt.preventDefault();
            return false;
        }
    }

    function isNumber(evt) {
        evt = evt || window.event;
        var charCode = evt.which || evt.keyCode;

        // Solo permite números (0–9)
        if (charCode < 48 || charCode > 57) {
            evt.preventDefault();
            return false;
        }

        return true;
    }

    function iniciarContadorMesa(id, openedAtStr) {
        const openedAt = new Date(openedAtStr);
        const span = document.getElementById(`contador-${id}`);
        const card = span.closest('.card-mesa');
        if (!span) return;

        setInterval(() => {
            const now = new Date();
            const diff = Math.floor((now - openedAt) / 1000);
            const min = String(Math.floor(diff / 60)).padStart(2, '0');
            const sec = String(diff % 60).padStart(2, '0');
            span.textContent = `${min}:${sec}`;
            // Si pasan más de 20 minutos, pinta de naranja
            if (diff >= 3600) {
                card.classList.add('borde-rojo');
                card.classList.remove('borde-naranja');
                card.classList.remove('borde-verde');
            } else if (diff >= 1200) {
                card.classList.remove('borde-rojo');
                card.classList.add('borde-naranja');
                card.classList.remove('borde-verde');
            } else {
                card.classList.remove('borde-rojo');
                card.classList.remove('borde-naranja');
                card.classList.add('borde-verde');
            }
        }, 1000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        @foreach($mesas as $mesa)
        @if($mesa->status === 'Ocupado' && $mesa->opened_at)
        try {
            iniciarContadorMesa("{{ $mesa->id }}", "{{ $mesa->opened_at }}");
        } catch (error) {
            console.error('Error al iniciar contador para mesa {{ $mesa->id }}:', error);
        }
        @endif
        @endforeach

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
    });

    // ADAPTACIÓN COMPLETA DEL ENVÍO DE COBRO PARA TU MODAL

    function calcularSaldo() {
        const total = parseFloat($('#totalAmount').text()) || 0;
        let totalPagado = 0;

        // Sumar todos los montos de pago visibles
        document.querySelectorAll('input[name^="monto["]').forEach(input => {
            const container = input.closest('.d-flex, .mb-3, .mb-4');
            if (container && !container.classList.contains('d-none') && container.style.display !== 'none') {
                totalPagado += parseFloat(input.value) || 0;
            }
        });

        const saldo = total - totalPagado;
        const saldoElement = $('#saldoAmount');

        if (saldoElement.length) {
            if (total === 0) {
                saldoElement.text('0.00');
                saldoElement.removeClass('text-danger text-success');
            } else {
                saldoElement.text(Math.abs(saldo).toFixed(2));

                // Cambiar color según el saldo
                if (saldo > 0.01) {
                    saldoElement.removeClass('text-success').addClass('text-danger'); // Debe dinero
                } else if (saldo < -0.01) {
                    saldoElement.removeClass('text-danger').addClass('text-success'); // Sobra dinero (vuelto)
                } else {
                    saldoElement.removeClass('text-danger').addClass('text-success'); // Exacto
                }
            }
        }

        console.log('Cálculo saldo - Total:', total, 'Pagado:', totalPagado, 'Saldo:', saldo);
        return saldo;
    }

    document.getElementById('formCobro').addEventListener('submit', function(e) {
        e.preventDefault();

        const botonesMedioPago = document.querySelectorAll('.d-flex.flex-wrap button');
        const metodoPagoSeleccionado = Array.from(botonesMedioPago).some(btn => btn.classList.contains('active'));
        const comprobante = document.getElementById('voucher_type').value;

        if (!comprobante) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un tipo de comprobante.'
            });
            return;
        }

        if (!metodoPagoSeleccionado) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar al menos un método de pago.'
            });
            return;
        }

        const saldoActual = calcularSaldo();
        if (saldoActual > 0.01) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: `Debe cancelar el monto completo antes de registrar la venta. El saldo actual es: S/ ${saldoActual.toFixed(2)}`
            });
            return;
        }

        const form = this;
        const formData = new FormData(form);

        // Datos básicos
        formData.append('products', JSON.stringify(selectedProducts));
        formData.append('total', document.getElementById('totalAmountInput').value);
        formData.append('voucher_type', comprobante);
        formData.append('restaurant', 1);

        if (openedMesaId) formData.append('mesa_id', openedMesaId);

        const resetFormulario = () => {
            selectedProducts = [];
            addProductToTable();
            document.getElementById('formCobro').reset();
            document.getElementById('totalAmount').textContent = '0.00';
            document.getElementById('totalAmountInput').value = 0;
            document.getElementById('voucher_type').value = '';

            // Limpiar variables globales
            currentOrderId = null;
            const mesaIdToReset = openedMesaId;
            openedMesaId = null;

            // Resetear botones de comprobante
            document.querySelectorAll('.btn-group button').forEach(btn => {
                if (btn.textContent.trim() === 'Boleta') {
                    btn.className = 'btn btn-outline-primary me-1';
                } else if (btn.textContent.trim() === 'Factura') {
                    btn.className = 'btn btn-outline-success me-1';
                } else if (btn.textContent.trim() === 'Ticket') {
                    btn.className = 'btn btn-outline-info me-1';
                }
            });

            // Resetear métodos de pago
            document.querySelectorAll('[id^="btn-"].active').forEach(btn => {
                btn.classList.remove('active', 'btn-success');
                const campos = btn.dataset.campos;
                $(`#${campos}`).addClass('d-none').removeClass('d-flex');
                $(`#${campos} input[type="text"]`).val('');
            });

            console.log('Formulario reseteado completamente para mesa:', mesaIdToReset);
        };

        fetch(`{{ route('sales.store') }}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            })
            .then(res => res.json())
            .then(response => {
                console.log('Respuesta venta:', response);
                if (response.status) {
                    // Limpiar timers antes de cerrar modales
                    if (openedMesaId && mesaTimers[openedMesaId]) {
                        clearInterval(mesaTimers[openedMesaId]);
                        delete mesaTimers[openedMesaId];
                        console.log('Timer limpiado para mesa:', openedMesaId);
                    }
                    
                    $('#modalCobro').modal('hide');
                    $('#abrirMesaModal').modal('hide');
                    clearInterval(timerInterval);

                    if (typeof imprimirVenta === 'function') {
                        imprimirVenta(response.venta.id);
                    }

                    if (typeof ToastMessage !== 'undefined') {
                        ToastMessage.fire({
                            text: 'Venta registrada correctamente'
                        });
                    }

                    // Cerrar mesa y restaurar UI
                    cerrarMesaFrom(openedMesaId);
                    resetFormulario();
                } else {
                    if (typeof ToastError !== 'undefined') {
                        ToastError.fire({
                            text: response.message || 'Error al registrar venta'
                        });
                    } else {
                        alert(response.message || 'Error al registrar venta');
                    }
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                if (typeof ToastError !== 'undefined') {
                    ToastError.fire({
                        text: 'Error de red al enviar la venta'
                    });
                } else {
                    alert('Error de red al enviar la venta');
                }
            });
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
                        url: "{{ route('products.searchrs') }}",
                        method: 'GET',
                        data: {
                            query: currentTerm
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    label: item.name + ' - Stock: ' + (item.quantity || 0) + ' - S/ ' + parseFloat(item.unit_price || 0).toFixed(2),
                                    value: item.name,
                                    id: item.id,
                                    name: item.name,
                                    unit_price: item.unit_price,
                                    quantity: item.quantity || 0
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
        appendTo: '#abrirMesaModal',
        select: function(event, ui) {
            // Agregar producto directamente a la tabla cuando se selecciona
            handleProductClick(ui.item.id, ui.item.name, ui.item.unit_price, ui.item.quantity);
            // Limpiar el campo de búsqueda
            $('#search-product').val('');
            $('#product_id').val('');
            return false; // Previene que se llene el input con el valor
        },
    }).autocomplete("instance")._renderItem = function(ul, item) {
        const stockClass = item.quantity > 0 ? 'text-success' : 'text-danger';
        const stockText = item.quantity > 0 ? 'Disponible' : 'Sin Stock';
        return $("<li>")
            .append(`<div class="d-flex justify-content-between">
                        <span>${item.name}</span>
                        <small class="${stockClass}">${stockText}</small>
                     </div>`)
            .appendTo(ul);
    };

    function abrirMesa(mesaId) {
        fetch(`{{ url('/mesas/abrir') }}/${mesaId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
            })
            .then(res => {
                if (!res.ok) throw new Error("Error al abrir mesa");
                return res.json();
            })
            .then(data => {
                console.log('Respuesta del servidor abrirMesa:', data);

                // ✅ ACTUALIZAR ESTADO
                const estadoSpan = document.getElementById(`estado-mesa-${mesaId}`);
                if (estadoSpan) {
                    estadoSpan.textContent = 'Ocupada';
                    estadoSpan.classList.remove('bg-success');
                    estadoSpan.classList.add('bg-danger');
                }

                // ✅ REEMPLAZAR ACCIONES
                const accionesDiv = document.getElementById(`acciones-mesa-${mesaId}`);
                if (accionesDiv) {
                    accionesDiv.innerHTML = `
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning rounded-pill" onclick="verPedido(${mesaId})">
                            Ver Pedido
                        </button>
                        <button class="btn btn-danger rounded-pill" onclick="cerrarMesa(${mesaId})">
                            Cancelar Venta <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="mt-2 text-muted small">
                        Tiempo: <span id="contador-${mesaId}">--:--</span>
                    </div>
                `;
                }

                // ✅ CONTADOR Y COLOR DINÁMICO
                const openedAt = new Date(data.opened_at);
                const contadorEl = document.getElementById(`contador-${mesaId}`);
                const card = document.getElementById(`mesa-card-${mesaId}`);

                if (contadorEl && card) {
                    const intervalId = setInterval(() => {
                        const now = new Date();
                        const diff = Math.floor((now - openedAt) / 1000);
                        const min = String(Math.floor(diff / 60)).padStart(2, '0');
                        const sec = String(diff % 60).padStart(2, '0');
                        contadorEl.textContent = `${min}:${sec}`;

                        // Cambiar borde por tiempo
                        if (diff >= 3600) {
                            card.classList.add('borde-rojo');
                            card.classList.remove('borde-naranja', 'borde-verde');
                        } else if (diff >= 1200) {
                            card.classList.add('borde-naranja');
                            card.classList.remove('borde-rojo', 'borde-verde');
                        } else {
                            card.classList.add('borde-verde');
                            card.classList.remove('borde-naranja', 'borde-rojo');
                        }
                    }, 1000);

                    mesaTimers[mesaId] = intervalId; // Guardar para limpiar luego
                }

                // ✅ LIMPIAR INFO DE PEDIDO PREVIO
                selectedProducts = [];
                addProductToTable();
                currentOrderId = null;
                openedMesaId = null;
                $('#document').val('');
                $('#client').val('');
                $('#observacion').val('');
                $('#totalAmount').text('0.00');
                $('#totalAmountInput').val('0');
                document.querySelectorAll("input[name^='monto']").forEach(el => el.value = '');

                // ✅ GUARDAR INFO ACTUAL
                openedMesaId = mesaId;
                currentOrderId = data.order_id;

                // ✅ CARGAR PRODUCTOS EXISTENTES SI HAY UN PEDIDO
                if (data.order_id && data.productos && data.productos.length > 0) {
                    console.log('Cargando productos existentes:', data.productos);
                    selectedProducts = data.productos.map(p => ({
                        id: p.id || p.product_id,
                        nombre: p.nombre || p.name,
                        precio: toNum(p.precio || p.product_price || p.unit_price, 2),
                        cantidad: toNum(p.cantidad || p.quantity, 3),
                        stock: p.stock || p.quantity_available || 9999
                    }));
                    addProductToTable();
                    console.log('selectedProducts después de cargar:', selectedProducts);
                }

                $('#abrirMesaModal').modal('show');
            })
            .catch(error => {
                console.error(error);
                alert("No se pudo abrir la mesa.");
            });
    }

    function searchAPI(docEl, nameEl, addressEl) {
        var doc = $(docEl).val();

        $(nameEl).val('');
        $(addressEl).val('');
        $('#client').val('');

        if (doc.length != 8 && doc.length != 11) {
            return;
        }

        Swal.showLoading();

        $.ajax({
            url: "{{ url('sunat/consultar') }}?doc=" + doc,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    if (doc.length === 8) {
                        var fullName = `${data.nombre} ${data.apellido_paterno} ${data.apellido_materno}`;
                        $(nameEl).val(fullName);
                        $(addressEl).val(data.domicilio?.direccion || '');
                        $('#client').val(fullName);
                    } else {
                        $(nameEl).val(data.nombre);
                        $(addressEl).val(data.domicilio?.direccion || '');
                        $('#client').val(data.nombre);
                    }
                } else {
                    ToastError.fire({
                        text: response.message || 'No se encontró información'
                    });
                }
                Swal.close();
            },
            error: function(xhr) {
                ToastError.fire({
                    text: 'Error al consultar SUNAT/RENIEC'
                });
                Swal.close();
            }
        });
    }

    // Variables globales para manejo de productos
    let productTableCounter = 0;
    let productTableBody = null; // será asignado cuando el DOM esté listo dentro del modal

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

                        // Mostrar solo el nombre; el stock se conserva para validaciones internas
                        const stock = producto.quantity || 0;
                        const precio = parseFloat(producto.unit_price || 0).toFixed(2);

                        productElement.innerHTML = `
                            <div class="text-start">
                                <div class="fw-bold">${producto.name.toUpperCase()}</div>
                            </div>
                        `;

                        productElement.onclick = function() {
                            handleProductClick(producto.id, producto.name, producto.unit_price, stock);
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

    function handleProductClick(productId, productName, unitPrice, stock) {
        agregarProductoClick({
            id: productId,
            precio: unitPrice,
            nombre: productName,
            stock: stock
        });

        $('#search-product').val('');
        $('#product_id').val('');
    }

    function addProductToTable(productId, productName, unitPrice, stock) {
        // Asignar productTableBody si no está asignado
        if (!productTableBody) {
            productTableBody = document.querySelector('#abrirMesaModal table tbody') || document.querySelector('tbody');
        }

        // Si no se pasan argumentos, re-renderizar la tabla desde selectedProducts
        if (typeof productId === 'undefined') {
            productTableBody.innerHTML = '';
            productTableCounter = 0;

            if (!Array.isArray(selectedProducts) || selectedProducts.length === 0) {
                updateTotal();
                return;
            }

            selectedProducts.forEach(p => {
                productTableCounter++;
                const id = p.id;
                const name = p.nombre || p.name || '';
                const precio = parseFloat(p.precio || p.unit_price || 0).toFixed(2);
                const cantidad = (typeof p.cantidad !== 'undefined') ? p.cantidad : 1;
                const maxStock = p.stock || p.quantity || 9999;

                const row = document.createElement('tr');
                row.setAttribute('data-product-id', id);
                row.innerHTML = `
                    <td class="text-center">${productTableCounter}</td>
                    <td>${name}</td>
                    <td class="text-center">
                        <div class="input-group" style="width: 120px; margin: 0 auto;">
                          <input id="quantity-${productTableCounter}" type="number" class="form-control form-control-sm text-center quantity-input" 
                              value="${cantidad}" min="1"  
                              onchange="validateQuantity(this, ${maxStock}, ${precio}); updateSubtotal(${productTableCounter - 1});"
                              name="products[${id}][cantidad]">
                        </div>
                        <input type="hidden" name="products[${id}][id]" value="${id}">
                        <input type="hidden" name="products[${id}][precio]" value="${precio}">
                    </td>
                    <td class="text-center">S/ ${parseFloat(precio).toFixed(2)}</td>
                    <td class="text-center subtotal">S/ ${(parseFloat(precio) * parseFloat(cantidad)).toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeProduct(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;

                productTableBody.appendChild(row);
            });

            updateTotal();
            return;
        }

        // Comportamiento anterior: agregar una fila individual
        productTableCounter++;

        const row = document.createElement('tr');
        row.setAttribute('data-product-id', productId);

        row.innerHTML = `
                        <td class="text-center">${productTableCounter}</td>
                        <td>${productName}</td>
                        <td class="text-center">
                                <div class="input-group" style="width: 120px; margin: 0 auto;">
                            <input id="quantity-${productTableCounter}" type="number" class="form-control form-control-sm text-center quantity-input" 
                                    value="1" min="1"  
                                    onchange="validateQuantity(this, ${stock}, ${unitPrice}); updateSubtotal(${productTableCounter - 1});"
                                    name="products[${productId}][cantidad]">
                                </div>
                    <input type="hidden" name="products[${productId}][id]" value="${productId}">
                    <input type="hidden" name="products[${productId}][precio]" value="${unitPrice}">
                        </td>
            <td class="text-center">S/ ${parseFloat(unitPrice).toFixed(2)}</td>
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
        if (!confirm('¿Está seguro de eliminar este producto?')) return;

        const row = button.closest('tr');
        const productId = row.getAttribute('data-product-id');

        // Actualizar el array selectedProducts
        const productIndex = selectedProducts.findIndex(p => String(p.id) === String(productId));
        if (productIndex > -1) {
            selectedProducts.splice(productIndex, 1);
        }

        // Si hay orden activa, primero eliminar en backend
        if (currentOrderId) {
            eliminarProductoDelPedido(productId)
                .then(() => {
                    row.remove();
                    updateTotal();
                    renumberRows();
                    // Programar envío de la tabla completa actualizada
                    scheduleEnviarTabla();
                })
                .catch(err => {
                    console.error('No se pudo eliminar producto en backend:', err);
                    alert('No se pudo eliminar el producto en el servidor. Intente de nuevo.');
                });
        } else {
            // Si no hay orden en backend, solo actualizar UI
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

    const ToastSuccess = Swal.mixin({
        toast: true,
        position: 'top-end',
        icon: 'success',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });

    let debounceTimers = {};

    function updateSubtotal(index) {
        const input = document.getElementById(`quantity-${index + 1}`); // +1 porque los IDs empiezan en 1
        const raw = parseFloat(input.value);
        const newQuantity = Number.isFinite(raw) ? toNum(raw, 3) : 0;

        // Verificar que el índice sea válido
        if (index >= 0 && index < selectedProducts.length) {
            const item = selectedProducts[index];
            item.cantidad = newQuantity;

            // Programar envío de la tabla completa después de editar cantidad
            if (debounceTimers[index]) clearTimeout(debounceTimers[index]);
            debounceTimers[index] = setTimeout(() => {
                scheduleEnviarTabla();
            }, 800);

            updateSubtotalDisplay(index);
            updateTotal();
        }
    }


    // Nueva función para actualizar solo el display del subtotal
    function updateSubtotalDisplay(index) {
        const row = document.querySelector(`#quantity-${index + 1}`).closest('tr'); // +1 porque los IDs empiezan en 1
        const subtotalCell = row.cells[4]; // La celda del subtotal (índice 4)

        // Verificar que el índice sea válido
        if (index >= 0 && index < selectedProducts.length) {
            const precio = parseFloat(selectedProducts[index].precio) || 0;
            const cantidad = parseFloat(selectedProducts[index].cantidad);
            // Si cantidad es NaN (input vacío o inválido), mostrar 0
            const subtotal = (!isNaN(cantidad) ? precio * cantidad : 0).toFixed(2);
            subtotalCell.textContent = `S/ ${subtotal}`;
        }
    }

    // Debounce global para enviar la tabla completa de productos al backend
    let sendTableTimer = null;
    const SEND_TABLE_DELAY = 1500; // ms

    function scheduleEnviarTabla() {
        if (sendTableTimer) clearTimeout(sendTableTimer);
        sendTableTimer = setTimeout(() => {
            enviarTablaProductosAlPedido();
        }, SEND_TABLE_DELAY);
    }

    function agregarProductoClick(producto) {
        const idStr = String(producto.id);
        const idx = selectedProducts.findIndex(p => String(p.id) === idStr);
        const stock = producto.stock;

        if (idx > -1) {
            const current = Number(selectedProducts[idx].cantidad) || 0;
            selectedProducts[idx].cantidad = toNum(current + 1, 3);
        } else {
            selectedProducts.push({
                id: producto.id,
                nombre: producto.nombre ?? null,
                precio: toNum(producto.precio, 2),
                cantidad: 1,
                stock: stock
            });
        }

        addProductToTable();
        scheduleEnviarTabla();
    }

    async function enviarTablaProductosAlPedido() {
        if (!currentOrderId) {
            console.warn('No hay order id al intentar enviar la tabla de productos');
            return;
        }
        // En este endpoint el backend espera un solo producto por request con campos: product_id, quantity, product_price
        // Hacemos un POST por cada producto para sincronizar el pedido en el servidor (modo overwrite)
        if (!Array.isArray(selectedProducts) || selectedProducts.length === 0) {
            console.log('selectedProducts vacío, no se envía nada.');
            sendTableTimer = null;
            return;
        }

        console.log('Sincronizando', selectedProducts.length, 'productos al pedido', currentOrderId);

        try {
            for (const p of selectedProducts) {
                const body = {
                    product_id: p.id,
                    quantity: p.cantidad,
                    product_price: p.precio,
                    nombre: p.nombre ?? null,
                    sumar: false // overwrite: el backend hará updateOrCreate
                };

                console.log('Enviando producto al servidor:', body);

                const res = await fetch(`{{ url('/orders') }}/${currentOrderId}/addproducts`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                });

                if (!res.ok) {
                    const text = await res.text();
                    console.error('Error al sincronizar producto', p.id, 'status:', res.status, text);
                    // No abortamos: seguimos intentando con los demás productos
                } else {
                    const data = await res.json().catch(() => null);
                    console.log('Respuesta servidor para producto', p.id, data);
                }
            }
        } catch (err) {
            console.error('Error de red al sincronizar productos:', err);
        } finally {
            sendTableTimer = null;
        }
    }

    function eliminarProductoDelPedido(productId) {
        if (!currentOrderId) {
            console.error("No hay orden activa");
            return Promise.reject(new Error("No hay orden activa"));
        }

        console.log("Eliminando producto:", productId, "de la orden:", currentOrderId);

        return fetch(`{{ url('/orders') }}/${currentOrderId}/removeproduct`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    product_id: productId
                })
            })
            .then(response => {
                console.log('Status:', response.status);
                console.log('Status Text:', response.statusText);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.json();
            })
            .then(data => {
                console.log('Respuesta al eliminar producto:', data);
                if (data.success) {
                    console.log('Producto eliminado exitosamente del backend');
                } else {
                    console.error('Error del servidor:', data.message);
                    alert("Error al eliminar el producto: " + (data.message || "Error desconocido"));
                }
            })
            .catch(error => {
                console.error('Error al eliminar producto:', error);
                alert("Error de conexión al eliminar el producto. Verifique su conexión.");
            });
    }

    const toNum = (v, dec = null) => {
        const n = parseFloat(v);
        if (!Number.isFinite(n)) return 0;
        return dec === null ? n : +n.toFixed(dec);
    };

    function verPedido(mesaId) {
        fetch(`{{ url('/mesas/pedido') }}/${mesaId}`)
            .then(res => {
                if (!res.ok) throw new Error("Error al obtener el pedido.");
                return res.json();
            })
            .then(data => {
                if (!data.success) {
                    alert(data.message);
                    return;
                }

                selectedProducts = (data.productos || []).map(p => ({
                    ...p,
                    cantidad: toNum(p.cantidad, 3),
                    precio: toNum(p.precio, 2),
                }));
                currentOrderId = data.order_id;
                openedMesaId = mesaId;
                addProductToTable();
                $('#abrirMesaModal').modal('show');
            })
            .catch(err => {
                console.error('Error al cargar pedido:', err);
                alert("Error al cargar el pedido.");
            });
    }

    // Vuelve a tener la función updateTotal simple
    function updateTotal() {
        let total = 0;
        selectedProducts.forEach(p => {
            total += (parseFloat(p.precio) || 0) * (parseFloat(p.cantidad) || 0);
        });
        document.getElementById('totalAmount').textContent = total.toFixed(2);
        document.getElementById('totalAmountInput').value = total.toFixed(2);
    }

    function cerrarMesaFrom(mesaId) {
        fetch(`{{ url('/mesas') }}/${mesaId}/cerrar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    console.log('Mesa cerrada exitosamente desde backend:', mesaId);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Mesa liberada',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });

                    // Restaurar UI usando helper (esto debería quitar el borde)
                    restoreMesaUI(mesaId);

                } else {
                    console.error('Error al cerrar mesa desde backend:', data.message);
                    Swal.fire('Error', data.message || 'No se pudo cerrar la mesa.', 'error');
                }
            })
            .catch(err => {
                console.error('Error al cerrar la mesa:', err);
                Swal.fire('Error', 'Error inesperado al cerrar la mesa.', 'error');
            });
    }

    function cerrarMesa(mesaId) {
        Swal.fire({
            title: '¿Liberar mesa?',
            text: 'Esto eliminará el pedido y liberará la mesa.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, liberar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`{{ url('/mesas') }}/${mesaId}/cerrar`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Mesa liberada',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });


                            // Restaurar UI usando helper
                            restoreMesaUI(mesaId);


                        } else {
                            Swal.fire('Error', data.message || 'No se pudo cerrar la mesa.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error('Error al cerrar la mesa:', err);
                        Swal.fire('Error', 'Error inesperado al cerrar la mesa.', 'error');
                    });
            }
        });

    }

    let currentProductIndex = -1;

    function convertirMontoALetras(monto) {
        const [entero, decimal] = monto.toFixed(2).split('.');
        const parteEntera = parseInt(entero);
        const centavos = parseInt(decimal);

        let resultado = '';

        if (parteEntera === 0) {
            resultado = 'cero soles';
        } else if (parteEntera === 1) {
            resultado = 'un sol';
        } else if (parteEntera < 1000) {
            resultado = numeroALetras(parteEntera) + ' soles';
        } else {
            // Para miles
            const miles = Math.floor(parteEntera / 1000);
            const resto = parteEntera % 1000;

            if (miles === 1) {
                resultado = 'mil';
            } else {
                resultado = numeroALetras(miles) + ' mil';
            }

            if (resto > 0) {
                resultado += ' ' + numeroALetras(resto);
            }

            resultado += ' soles';
        }

        // Agregar centavos
        if (centavos > 0) {
            resultado += ' con ' + numeroALetras(centavos) + ' céntimos';
        }

        return resultado.toUpperCase();
    }


    function confirmOrder(showModal = true) {
        var order_id = currentOrderId;
        $.ajax({
            url: '{{ route("orders.confirm") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                order_id
            },
            success: async function(response) {
                if (response.status) {
                    var table = response.table;
                    var details = response.details;
                    mostrarIconoConfirmado();

                    // Construir y enviar impresión usando funciones reutilizables
                    try {
                        const opts = buildPrintOpts('PREPARACION', table, details);
                        const res = await sendToPrinter(opts);
                        console.log('Impresión result:', res);
                        if (res && res.ok && typeof ToastMessage !== 'undefined') {
                            ToastMessage.fire({
                                text: 'Documento enviado a impresión correctamente'
                            });
                        }
                    } catch (error) {
                        console.error('Error en el proceso de impresión:', error);
                        let errorMessage = 'Error desconocido';
                        if (error.name === 'TypeError' && error.message.includes('fetch')) {
                            errorMessage = 'No se pudo conectar con el servicio de impresión. Verifica que esté funcionando.';
                        } else if (error.message && error.message.includes('timeout')) {
                            errorMessage = 'Timeout: El servicio de impresión no responde.';
                        } else {
                            errorMessage = error.message || errorMessage;
                        }

                        if (typeof ToastError !== 'undefined') {
                            ToastError.fire({
                                text: `Error al imprimir: ${errorMessage}`
                            });
                        }
                    }


                } else {
                    //ToastError.fire({ text: response.error });
                }
            },
            error: function(err) {
                console.log('Ocurrió un error');
            }
        });
    }

    function preaccount(showModal = true) {
        var order_id = currentOrderId;
        $.ajax({
            url: '{{ route("orders.preaccount") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                order_id
            },
            success: async function(response) {
                if (response.status) {
                    var table = response.table;
                    var details = response.details;

                    // Construir y enviar impresión usando funciones reutilizables
                    try {
                        const opts = buildPrintOpts('PRECUENTA', table, details);
                        const res = await sendToPrinter(opts);
                        console.log('Impresión result:', res);
                        if (res && res.ok && typeof ToastMessage !== 'undefined') {
                            ToastMessage.fire({
                                text: 'Documento enviado a impresión correctamente'
                            });
                        }
                    } catch (error) {
                        console.error('Error en el proceso de impresión:', error);
                        let errorMessage = 'Error desconocido';
                        if (error.name === 'TypeError' && error.message.includes('fetch')) {
                            errorMessage = 'No se pudo conectar con el servicio de impresión. Verifica que esté funcionando.';
                        } else if (error.message && error.message.includes('timeout')) {
                            errorMessage = 'Timeout: El servicio de impresión no responde.';
                        } else {
                            errorMessage = error.message || errorMessage;
                        }

                        if (typeof ToastError !== 'undefined') {
                            ToastError.fire({
                                text: `Error al imprimir: ${errorMessage}`
                            });
                        }
                    }


                } else {
                    //ToastError.fire({ text: response.error });
                }
            },
            error: function(err) {
                console.log('Ocurrió un error');
            }
        });
    }

    function mostrarIconoConfirmado() {
        // Selecciona todos los elementos con la clase 'subtotal-container'
        const elementos = document.querySelectorAll('.subtotal-container');
        elementos.forEach(el => {
            // Solo agrega el icono si no existe ya en el elemento
            if (!el.querySelector('.bi-check2-square')) {
                el.innerHTML += '\n<i class="bi bi-check2-square" title="Confirmado"></i>';
            }
        });
    }
</script>
@endsection






