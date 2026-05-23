@extends('layouts.app')

@section('nav')
<ul class="nav justify-content-center">
    <li class="nav-item" style="margin: 0 10px 5px 10px;"> <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
        <a class="nav-link btn btn-primary active" href="{{ route('roles.create') }}">Registro</a>
    </li>
    <li class="nav-item" style="margin: 0 10px 5px 10px;"> <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
        <a class="nav-link btn btn-secondary" href="{{ route('roles.index') }}">Historico</a>
    </li>
</ul>
@endsection

@section('header')
<h1>Lista Roles</h1>
<p>Listado de roles</p>
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
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $rol)
                        <tr>
                            <td>{{ $rol->name }}</td>
                            <td>{{ $rol->description }}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-id="{{ $rol->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                    data-id="{{ $rol->id }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center">No hay roles registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $roles->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>
@endsection