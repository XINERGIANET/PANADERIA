<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\CashClose;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;

class CashCloseController extends Controller
{

    
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');
        
        // Filtros del formulario
        $date = $request->input('date', now()->toDateString());
        $shift = $request->input('shift');
        $location_id = $request->input('location_id');
        $user_id = $request->input('user_id');

        // Si no es admin, forzar el filtro a su propia sede
        if (!$isAdmin) {
            $location_id = $user->location_id;
        }

        // Obtener todas las sedes para el filtro (solo si es admin)
        $locations = $isAdmin 
            ? Location::where('deleted', 0)->orderBy('name')->get()
            : Location::where('id', $user->location_id)->where('deleted', 0)->get();

        // Obtener usuarios según sede seleccionada
        $users = User::orderBy('name')
            ->when($location_id, function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            })
            ->get();

        // Consultar cierres de caja
        $cashCloses = CashClose::with(['user', 'location'])
            ->where('deleted', 0)
            ->whereDate('date', $date)
            ->when($shift !== null && $shift !== '', function ($q) use ($shift) {
                return $q->where('shift', (int)$shift);
            })
            ->when($location_id, function ($q) use ($location_id) {
                return $q->where('location_id', $location_id);
            })
            ->when($user_id, function ($q) use ($user_id) {
                return $q->where('user_id', $user_id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Calcular totales de ventas por método de pago para cada cierre
        $ventas_payment_methods = PaymentMethod::select('id', 'name')
            ->where('deleted', 0)
            ->get()
            ->map(function ($method) use ($date, $shift, $location_id, $user_id) {
                $total = Payment::where('deleted', 0)
                    ->where('payment_method_id', $method->id)
                    ->where('date', $date)
                    ->when($shift !== null && $shift !== '', function ($q) use ($shift) {
                        return $q->where('shift', (int)$shift);
                    })
                    ->when($location_id, function ($q) use ($location_id) {
                        return $q->where('location_id', $location_id);
                    })
                    ->when($user_id, function ($q) use ($user_id) {
                        return $q->where('user_id', $user_id);
                    })
                    ->whereHas('sale', function ($q) {
                        $q->where('deleted', 0);
                    })
                    ->sum('subtotal');

                $method->total = $total;
                return $method;
            });

        $total_ventas = $ventas_payment_methods->sum('total');
        
        // Efectivo
        $efectivo = Payment::where('deleted', 0)
            ->where('date', $date)
            ->when($shift !== null && $shift !== '', function ($q) use ($shift) {
                return $q->where('shift', (int)$shift);
            })
            ->when($location_id, function ($q) use ($location_id) {
                return $q->where('location_id', $location_id);
            })
            ->when($user_id, function ($q) use ($user_id) {
                return $q->where('user_id', $user_id);
            })
            ->whereHas('paymentMethod', function ($q) {
                $q->whereRaw('UPPER(name) = "EFECTIVO"');
            })
            ->whereHas('sale', function ($q) {
                $q->where('deleted', 0);
            })
            ->sum('subtotal');

        return view('cashClose.index', compact(
            'date',
            'shift',
            'location_id',
            'user_id',
            'locations',
            'users',
            'cashCloses',
            'ventas_payment_methods',
            'total_ventas',
            'efectivo',
            'isAdmin'
        ));
    }


    public function create(Request $request)
    {
        $date = $request->date ? $request->date : now()->toDateString();
        $shift = auth()->user()->shift;
        $location_id = auth()->user()->location_id;

        $ventas_payment_methods = PaymentMethod::select('id', 'name')
            ->where('deleted', 0)
            ->get()
            ->map(function ($method) use ($date, $shift, $location_id) {
                $total = Payment::where('deleted', 0)
                    ->where('payment_method_id', $method->id)
                    ->where('location_id', $location_id)
                    ->where('date', $date)
                    ->where('user_id', auth()->id())
                    ->where('shift', $shift)
                    ->whereHas('sale', function ($q) {
                        $q->where('deleted', 0);
                    })
                    ->sum('subtotal');

                $method->total = $total;
                return $method;
            });

        $total_ventas = $ventas_payment_methods->sum('total');
       
        $efectivo = Payment::where('deleted', 0)
            ->where('date', $date)
            ->where('user_id', auth()->id())
            ->where('shift', $shift)
            ->whereHas('paymentMethod', function ($q) {
                $q->whereRaw('UPPER(name) = "EFECTIVO"');
            })
            ->whereHas('sale', function ($q) {
                $q->where('deleted', 0);
            })
            ->sum('subtotal');

        return view('cashClose.create', compact(
            'efectivo',
            'ventas_payment_methods',
            'total_ventas',
            'date',
            'shift',
        ));
    }

    
    public function store(Request $request)
    {
        try {

            $date = $request->date;
            $amount = $request->amount;
            $shift = auth()->user()->shift;
            $user_id = auth()->user()->id;
            $location_id = auth()->user()->location_id;

            $cierre = CashClose::updateOrCreate(
                [
                    'date' => $date,
                    'shift' => $shift,
                    'location_id' => $location_id,
                    'user_id' => $user_id,
                    'deleted' => 0,
                ],
                [
                    'amount' => $amount,
                ]
            );

            return response()->json([
                'status' => true,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al guardar cierre: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function show($id)
    {

    }

    public function edit($id)
    {}

    public function update(Request $request, $id)
    {}

    public function destroy($id)
    {}



    // public function pdf(Request $request)
    // {
    //     try {
    //         Log::info('cashClosePDF recibe:', $request->all());
    //         $request->validate([
    //             'user_id' => 'nullable|exists:users,id',
    //             'turno' => 'nullable|numeric|in:0,1',
    //             'headquarter_id' => 'nullable|exists:headquarters,id',
    //             'tabla' => 'required',
    //             'date' => 'required|date',
    //             'monto' => 'nullable|numeric'
    //         ]);

    //         $user_id = $request->user_id ?? auth()->user()->id;
    //         $user = Usuario::find($user_id)->nombre;
    //         $turn = $request->turno ?? auth()->user()->turno;
    //         if ($turn === 0) {
    //             $turno = 'mañana';
    //         } else {
    //             $turno = 'tarde';
    //         }
    //         $headquarter_id = $request->headquarter_id ?? auth()->user()->sede_id;
    //         $headquarter = Headquarters::find($headquarter_id)->nombre;
    //         $tabla = $request->tabla;
    //         $fecha = $request->date;
    //         $monto = $request->monto ?? "No registrado";

    //         $pdf = Pdf::loadView('cashClose.pdf', compact('user', 'turno', 'headquarter', 'tabla', 'fecha', 'monto'));

    //         // Descargar el archivo PDF
    //         return $pdf->download('Cierre.pdf');
    //     } catch (\Throwable $e) {
    //         Log::error('Error generando PDF: ' . $e->getMessage());
    //         return response('Error generando PDF: ' . $e->getMessage(), 500);
    //     }
    // }

 

   

}
