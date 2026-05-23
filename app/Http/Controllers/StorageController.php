<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\LocationPrice;
use App\Models\Storage;
use Illuminate\Http\Request;

class StorageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $locationId = $request->input('location_id', auth()->user()->location_id);
    
        // Traer productos con precio en la sede del usuario
        $products = Product::where('deleted', 0)
            ->whereHas('location_prices', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            })
            ->with(['location_prices' => function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            }])
            ->get();
    
        // En la vista puedes acceder a $product->quantity y $product->location_prices[0]->price
        return view('storages.index', compact('products'));
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id',
        ]);

        auth()->user()->update([
            'location_id' => $request->location_id,
        ]);

        return response()->json(['success' => true]);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('payment_methods.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location_id' => 'sometimes|exists:locations,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        $locationId = $request->input('location_id', auth()->user()->location_id);

        $locationPrice = LocationPrice::where('product_id', $request->product_id)
            ->where('location_id', $locationId)
            ->first();

        if (! $locationPrice) {
            // Crear registro si no existe (ajusta campos según tu migration)
            $locationPrice = LocationPrice::create([
                'product_id'  => $request->product_id,
                'location_id' => $locationId,
                'price'       => $locationPrice->price ?? 0, // o establece un valor por defecto adecuado
                'quantity'    => $request->quantity,
            ]);

            return response()->json(['success' => true, 'message' => 'Stock por sede creado correctamente.']);
        }

        $locationPrice->quantity = $request->quantity;
        $locationPrice->save();

        return response()->json(['success' => true, 'message' => 'Stock por sede actualizado correctamente.']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
