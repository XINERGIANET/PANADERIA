@extends('layouts.app')

@section('nav')
<ul class="nav justify-content-center">
    <li class="nav-item" style="margin: 0 10px 5px 10px;"> <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
        <a class="nav-link btn btn-primary active" href="{{ route('clients.create') }}">Registro</a>
    </li>
    <li class="nav-item" style="margin: 0 10px 5px 10px;"> <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
        <a class="nav-link btn btn-secondary" href="{{ route('clients.index') }}">Historico</a>
    </li>
</ul>
@endsection

@section('header')
<h1>Lista Clientes</h1>
<p>Listado de clientes</p>
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
                            <th>Nombres/Razón Social</th>
                            <th>DNI/RUC</th>
                            <th>Teléfono</th>
                            <th>Dirección</th>
                            <th>Región</th>
                            <th>Provincia</th>
                            <th>Distrito</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                        <tr>
                            <td>{{ ($clients->currentPage() - 1) * $clients->perPage() + $loop->iteration }}</td>
                            <td>{{ $client->business_name }}</td>
                            <td>{{ $client->document }}</td>
                            <td>{{ $client->phone }}</td>
                            <td>{{ $client->address }}</td>
                            <td>{{ $client->department }}</td>
                            <td>{{ $client->province }}</td>
                            <td>{{ $client->district }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-id="{{ $client->id }}"
                                    data-nombres_razon_social="{{ $client->business_name }}"
                                    data-dni_ruc="{{ $client->document }}"
                                    data-telefono="{{ $client->phone }}"
                                    data-direccion="{{ $client->address }}"
                                    data-region="{{ $client->department }}"
                                    data-provincia="{{ $client->province }}"
                                    data-distrito="{{ $client->district }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                    data-id="{{ $client->id }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No hay clientes registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $clients->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editClientForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Editar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row">
                    <div class="col-md-6 mb-3">
                        <label for="edit_nombres_razon_social" class="form-label">Nombres/Razón Social</label>
                        <input type="text" class="form-control" id="edit_nombres_razon_social" name="business_name"
                            required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="edit_dni_ruc" class="form-label">DNI/RUC</label>
                        <input type="text" class="form-control" id="edit_dni_ruc" name="document" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="edit_telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="edit_telefono" name="phone" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="edit_direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="edit_direccion" name="address" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="department" class="form-label">Departamento</label>
                        <select name="department" id="edit_region" onchange="" class="form-select border-dark">
                            <option value="">Seleccione</option>
                            <option value="Amazonas">Amazonas</option>
                            <option value="Ancash">Ancash</option>
                            <option value="Apurímac">Apurímac</option>
                            <option value="Arequipa">Arequipa</option>
                            <option value="Ayacucho">Ayacucho</option>
                            <option value="Cajamarca">Cajamarca</option>
                            <option value="Callao">Callao</option>
                            <option value="Cuzco">Cuzco</option>
                            <option value="Huancavelica">Huancavelica</option>
                            <option value="Huánuco">Huánuco</option>
                            <option value="Ica">Ica</option>
                            <option value="Junín">Junín</option>
                            <option value="La_Libertad">La Libertad</option>
                            <option value="Lambayeque">Lambayeque</option>
                            <option value="Lima">Lima</option>
                            <option value="Loreto">Loreto</option>
                            <option value="Madre_de_Dios">Madre de Dios</option>
                            <option value="Moquegua">Moquegua</option>
                            <option value="Pasco">Pasco</option>
                            <option value="Piura">Piura</option>
                            <option value="Puno">Puno</option>
                            <option value="San_Martín">San Martín</option>
                            <option value="Tacna">Tacna</option>
                            <option value="Tumbes">Tumbes</option>
                            <option value="Ucayali">Ucayali</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="edit_provincia" class="form-label">Provincia</label>
                        <select class="form-select border-dark" name="province" id="edit_provincia"
                            onchange="">
                            <option>Seleccione la Provincia</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="edit_distrito" class="form-label">Distrito</label>
                        <select class="form-select border-dark" name="district" id="edit_distrito">
                            <option>Seleccione el Distrito</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteClientForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar este cliente?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection