@extends('layouts.app')

@section('nav')
<ul class="nav justify-content-center">
    <li class="nav-item" style="margin: 0 10px 5px 10px;"> <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
        <a class="nav-link btn btn-primary active" href="{{ route('employees.create') }}">Registro</a>
    </li>
    <li class="nav-item" style="margin: 0 10px 5px 10px;"> <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
        <a class="nav-link btn btn-secondary" href="{{ route('employees.index') }}">Historico</a>
    </li>
</ul>
@endsection

@section('header')
<h1>Lista Empleados</h1>
<p>Listado de empleados</p>
@endsection

@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
    <!-- Card que contiene el formulario y la tabla -->
    <div class="card shadow">
        <!-- Cuerpo del Card -->
        <div class="card-body">
            <!-- Tabla de Registros -->
            <div class="table-responsive mt-4">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>F. nacimiento</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                        <tr>
                            <td>{{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}</td>
                            <td>{{ $employee->name }} {{ $employee->last_name }}</td>
                            <td>{{ $employee->document }}</td>
                            <td>{{ $employee->birth_date->format('d/m/Y') }}</td>
                            <td>{{ $employee->phone }}</td>
                            <td>{{ $employee->address }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-employee-btn" 
                                    data-id="{{ $employee->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-employee-btn" 
                                    data-id="{{ $employee->id }}" 
                                    data-name="{{ $employee->name }} {{ $employee->last_name }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No hay empleados registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $employees->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editEmployeeForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Editar Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row">
                    <div class="col-md-6 mb-3">
                        <label for="edit_name" class="form-label">Nombres</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                        <div class="invalid-feedback" id="edit_name_error"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="edit_last_name" class="form-label">Apellidos</label>
                        <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        <div class="invalid-feedback" id="edit_last_name_error"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="edit_document" class="form-label">Documento</label>
                        <input type="text" class="form-control" id="edit_document" name="document" required>
                        <div class="invalid-feedback" id="edit_document_error"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="edit_birth_date" class="form-label">F. nacimiento</label>
                        <input type="date" class="form-control" id="edit_birth_date" name="birth_date" required>
                        <div class="invalid-feedback" id="edit_birth_date_error"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="edit_phone" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone" required>
                        <div class="invalid-feedback" id="edit_phone_error"></div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="edit_address" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="edit_address" name="address" required>
                        <div class="invalid-feedback" id="edit_address_error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="editSaveBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar Empleado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar el empleado <strong id="delete_employee_name"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentEmployeeId = null;

    // Editar empleado
    $('.edit-employee-btn').on('click', function() {
        currentEmployeeId = $(this).data('id');
        
        // Mostrar modal y limpiar campos
        $('#editEmployeeForm')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#editModal').modal('show');
        
        // Obtener datos del empleado
        $.ajax({
            url: "{{ route('employees.edit', ':id') }}".replace(':id', currentEmployeeId),
            type: 'GET',
            success: function(response) {
                if (response.status) {
                    const employee = response.data;
                    $('#edit_name').val(employee.name);
                    $('#edit_last_name').val(employee.last_name);
                    $('#edit_document').val(employee.document);
                    $('#edit_birth_date').val(employee.birth_date);
                    $('#edit_phone').val(employee.phone);
                    $('#edit_address').val(employee.address);
                } else {
                    ToastMessage.fire({
                        icon: 'error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                ToastMessage.fire({
                    icon: 'error',
                    text: 'Error al cargar los datos del empleado'
                });
            }
        });
    });

    // Guardar cambios del empleado
    $('#editEmployeeForm').on('submit', function(e) {
        e.preventDefault();
        
        const saveBtn = $('#editSaveBtn');
        const spinner = saveBtn.find('.spinner-border');
        
        // Mostrar loading
        saveBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        // Limpiar errores previos
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        $.ajax({
            url: "{{ route('employees.update', ':id') }}".replace(':id', currentEmployeeId),
            type: 'PUT',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                name: $('#edit_name').val(),
                last_name: $('#edit_last_name').val(),
                document: $('#edit_document').val(),
                birth_date: $('#edit_birth_date').val(),
                phone: $('#edit_phone').val(),
                address: $('#edit_address').val()
            },
            success: function(response) {
                if (response.status) {
                    $('#editModal').modal('hide');
                    ToastMessage.fire({
                        icon: 'success',
                        text: response.message
                    });
                    
                    // Recargar la página después de 1 segundo
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    ToastMessage.fire({
                        icon: 'error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Errores de validación
                    const errors = xhr.responseJSON.errors;
                    
                    Object.keys(errors).forEach(function(field) {
                        $(`#edit_${field}`).addClass('is-invalid');
                        $(`#edit_${field}_error`).text(errors[field][0]);
                    });
                } else {
                    console.error('Error:', xhr);
                    ToastMessage.fire({
                        icon: 'error',
                        text: 'Error al actualizar el empleado'
                    });
                }
            },
            complete: function() {
                // Ocultar loading
                saveBtn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });

    // Eliminar empleado
    $('.delete-employee-btn').on('click', function() {
        currentEmployeeId = $(this).data('id');
        const employeeName = $(this).data('name');
        
        $('#delete_employee_name').text(employeeName);
        $('#deleteModal').modal('show');
    });

    // Confirmar eliminación
    $('#confirmDeleteBtn').on('click', function() {
        const deleteBtn = $(this);
        const spinner = deleteBtn.find('.spinner-border');
        
        // Mostrar loading
        deleteBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        
        $.ajax({
            url: "{{ route('employees.destroy', ':id') }}".replace(':id', currentEmployeeId),
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#deleteModal').modal('hide');
                
                if (response.status) {
                    ToastMessage.fire({
                        icon: 'success',
                        text: response.message
                    });
                    
                    // Recargar la página después de 1 segundo
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    ToastMessage.fire({
                        icon: 'error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                const message = xhr.responseJSON?.message || 'Error al eliminar el empleado';
                ToastMessage.fire({
                    icon: 'error',
                    text: message
                });
            },
            complete: function() {
                // Ocultar loading
                deleteBtn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });

    // Limpiar modales al cerrar
    $('#editModal').on('hidden.bs.modal', function() {
        $('#editEmployeeForm')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        currentEmployeeId = null;
    });

    $('#deleteModal').on('hidden.bs.modal', function() {
        currentEmployeeId = null;
    });
});
</script>