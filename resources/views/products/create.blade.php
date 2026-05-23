@extends('layouts.app')

@section('nav')
<ul class="nav justify-content-center">
    <li class="nav-item" style="margin: 0 10px 5px 10px;"> <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
        <a class="nav-link btn btn-primary active" href="{{ route('products.create') }}">Registro</a>
    </li>
    <li class="nav-item" style="margin: 0 10px 5px 10px;"> <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
        <a class="nav-link btn btn-secondary" href="{{ route('products.index') }}">Historico</a>
    </li>
</ul>
@endsection

@section('header')
<h1>Registro Producto</h1>
<p>Registrar un nuevo producto</p>
@endsection

@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
    <div class="card shadow">
        <div class="card-body">
            <form id="formRegistro" action="{{ route('products.store') }}" method="POST">
                @csrf
                <!-- Nombre del producto y categoría en la misma fila -->
                <div class="row mb-3 align-items-end">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Producto</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Categoría</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Seleccione una categoría</option>
                            @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Tabla de precio por producto y sede -->
                <div class="mb-3 row">
                    <div class="col-12">
                        <label class="form-label mb-2">Precio por Sede</label>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle text-center" id="productionTable">
                                <thead class="table">
                                    <tr>
                                        <th>Sede</th>
                                        <th>Precio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($locations as $location)
                                    <tr>
                                        <td class="align-middle">{{ $location->name }}</td>
                                        <td>
                                            <input type="number"
                                                id="price{{ $location->id }}"
                                                name="price[{{ $location->id }}]"
                                                class="form-control text-end"
                                                min="0.01"
                                                step="0.01"
                                                placeholder="0.00">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Botones -->
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection