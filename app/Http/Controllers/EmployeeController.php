<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        //
        $employees = Employee::where('deleted', 0)->paginate(10);
        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        //
        return view('employees.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        //
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'document' => 'required|string|max:11',
            'birth_date' => 'required|date',
            'phone' => 'required|string|max:15',
            'address' => 'required|string|max:255',
        ]);

        Employee::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'document' => $request->document,
            'birth_date' => $request->birth_date,
            'phone' => $request->phone,
            'address' => $request->address,
            'deleted' => 0, // Por defecto, el colaborador está activo
        ]);

        return redirect()->route('employees.index')
            ->with('success', 'Empleado creado correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $employee = Employee::where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empleado no encontrado'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Empleado encontrado',
                'data' => $employee
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener el empleado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id): JsonResponse
    {
        try {
            $employee = Employee::where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empleado no encontrado'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Datos del empleado para edición',
                'data' => $employee
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener los datos para edición: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Buscar el empleado
            $employee = Employee::where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empleado no encontrado'
                ], 404);
            }

            // Validar los campos
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'document' => 'required|string|max:11|unique:employees,document,' . $id . ',id,deleted,0',
                'birth_date' => 'required|date',
                'phone' => 'required|string|max:15',
                'address' => 'required|string|max:255',
            ]);

            // Actualizar el empleado
            $employee->update([
                'name' => $validated['name'],
                'last_name' => $validated['last_name'],
                'document' => $validated['document'],
                'birth_date' => $validated['birth_date'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Empleado actualizado correctamente',
                'data' => $employee->fresh()
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al actualizar el empleado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $employee = Employee::where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Empleado no encontrado'
                ], 404);
            }

            // Verificar si el empleado está siendo usado en otras tablas
            $isUsedInSales = DB::table('sales')
                ->where('employee_id', $id)
                ->exists();

            if ($isUsedInSales) {
                return response()->json([
                    'status' => false,
                    'message' => 'No se puede eliminar el empleado porque está siendo utilizado en ventas'
                ], 400);
            }

            // Soft delete - marcar como eliminado
            $employee->update(['deleted' => 1]);

            return response()->json([
                'status' => true,
                'message' => 'Empleado eliminado correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al eliminar el empleado: ' . $e->getMessage()
            ], 500);
        }
    }
}
