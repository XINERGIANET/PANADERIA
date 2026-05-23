<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\LocationPrice;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $searchTerm = request()->input('search'); // Capturamos el término de búsqueda

        $products = Product::with('category')
            ->when($searchTerm, function ($query, $searchTerm) {
                return $query->where('name', 'like', "%{$searchTerm}%");
            })
            ->where('deleted', 0)
            ->paginate(10)
            ->appends(['search' => $searchTerm]); // Agregar el parámetro 'search' a la URL de la paginación

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        //
        $categories = Category::where('deleted', 0)->get();
        $locations = Location::where('deleted', 0)->get();
        return view('products.create', compact(['categories', 'locations']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            // Validar los campos
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'required|integer|exists:categories,id',
                'price' => 'required|array',
                'price.*' => 'nullable|numeric|min:0.01',
            ]);

            // Crear el producto
            $product = Product::create([
                'name' => $validated['name'],
                'category_id' => $validated['category_id'],
            ]);

            // Guardar los precios por sede en LocationPrice
            foreach ($validated['price'] as $location_id => $price) {
                $location_id = (int)$location_id;
                if ($price !== null && $price !== '') {
                    LocationPrice::create([
                        'product_id' => $product->id,
                        'location_id' => $location_id,
                        'price' => $price,
                        'quantity' => 0,
                        'deleted' => 0
                    ]);
                }
            }

            // Redirigir con mensaje de éxito
            return redirect()->route('products.index')
                ->with('success', 'Producto creado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('products.index')
                ->with('error', 'Error al crear el producto: ' . $e->getMessage());
        }
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
            $product = Product::with('category')
                ->where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Producto encontrado',
                'data' => $product
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener el producto: ' . $e->getMessage()
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
            Log::info('=== EDIT PRODUCTO INICIADO ===', ['product_id' => $id]);

            $product = Product::with('category')
                ->where('id', $id)
                ->where('deleted', 0)
                ->first();

            Log::info('Producto encontrado:', ['found' => $product ? true : false]);

            if (!$product) {
                Log::error('Producto no encontrado', ['id' => $id]);
                return response()->json([
                    'status' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            $categories = Category::where('deleted', 0)->get();
            $locations = Location::where('deleted', 0)->get();
            
            Log::info('Datos básicos obtenidos:', [
                'categories_count' => $categories->count(),
                'locations_count' => $locations->count()
            ]);
            
            // Obtener precios directamente con consulta SQL simple
            $locationPrices = LocationPrice::where('product_id', $id)
                ->where('deleted', 0)
                ->with('location')
                ->get();

            Log::info('Location prices obtenidos:', [
                'count' => $locationPrices->count(),
                'data' => $locationPrices->toArray()
            ]);

            $responseData = [
                'product' => $product,
                'categories' => $categories,
                'locations' => $locations,
                'location_prices' => $locationPrices,
            ];

            Log::info('=== EDIT PRODUCTO COMPLETADO ===');

            return response()->json([
                'status' => true,
                'message' => 'Datos del producto para edición',
                'data' => $responseData
            ], 200);
        } catch (\Exception $e) {
            Log::error('ERROR EN EDIT:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
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
            Log::info('=== UPDATE PRODUCTO INICIADO ===', [
                'product_id' => $id,
                'request_data' => $request->all(),
                'method' => $request->method()
            ]);

            // Buscar el producto
            $product = Product::find($id);
            Log::info('Producto buscado:', ['found' => $product ? true : false, 'product' => $product]);
            
            if (!$product || $product->deleted == 1) {
                Log::error('Producto no encontrado o eliminado', ['id' => $id, 'deleted' => $product ? $product->deleted : 'not found']);
                return response()->json([
                    'status' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            // Validar datos básicos
            $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'required|integer|exists:categories,id',
            ]);
            Log::info('Validación pasada correctamente');

            // Actualizar producto
            $product->name = $request->input('name');
            $product->category_id = $request->input('category_id');
            $saved = $product->save();
            Log::info('Producto actualizado:', ['saved' => $saved, 'name' => $product->name, 'category_id' => $product->category_id]);

            // Procesar precios - manejar el array price directamente
            $priceCount = 0;
            $priceData = $request->input('price', []);
            Log::info('Datos de precio recibidos:', ['price_data' => $priceData, 'is_array' => is_array($priceData)]);
            
            if (is_array($priceData)) {
                foreach ($priceData as $locationId => $price) {
                    Log::info('Procesando precio:', ['locationId' => $locationId, 'price' => $price]);
                    
                    if (is_numeric($locationId) && !empty($price) && is_numeric($price)) {
                        $priceCount++;
                        Log::info('Precio válido encontrado:', ['locationId' => $locationId, 'price' => $price]);
                        
                        $existing = LocationPrice::where('product_id', $product->id)
                            ->where('location_id', $locationId)
                            ->first();
                        
                        Log::info('Verificando precio existente:', ['exists' => $existing ? true : false]);
                        
                        if ($existing) {
                            $existing->price = $price;
                            $priceSaved = $existing->save();
                            Log::info('Precio actualizado:', ['saved' => $priceSaved, 'old_price' => $existing->getOriginal('price'), 'new_price' => $price]);
                        } else {
                            $newPrice = LocationPrice::create([
                                'product_id' => $product->id,
                                'location_id' => $locationId,
                                'price' => $price,
                                'quantity' => 0,
                                'deleted' => 0
                            ]);
                            Log::info('Precio creado:', ['created' => $newPrice ? true : false, 'id' => $newPrice->id ?? null]);
                        }
                    } else {
                        Log::warning('Precio inválido ignorado:', [
                            'locationId' => $locationId, 
                            'price' => $price,
                            'locationId_numeric' => is_numeric($locationId),
                            'price_numeric' => is_numeric($price),
                            'price_empty' => empty($price)
                        ]);
                    }
                }
            }
            
            Log::info('Precios procesados:', ['total_prices' => $priceCount]);

            Log::info('=== UPDATE PRODUCTO COMPLETADO ===');
            return response()->json([
                'status' => true,
                'message' => 'Producto actualizado correctamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('ERROR EN UPDATE:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Error al actualizar el producto: ' . $e->getMessage()
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
            $product = Product::where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            // Soft delete - marcar como eliminado
            $product->update(['deleted' => 1]);

            return response()->json([
                'status' => true,
                'message' => 'Producto eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al eliminar el producto: ' . $e->getMessage()
            ], 500);
        }
    }


    public function searchpv(Request $request)
    {
        $query = $request->input('query');
        $user = auth()->user();

        $products = Product::with('category')
            ->where('products.name', 'LIKE', "%{$query}%")
            ->where('products.deleted', 0)
            ->whereHas('location_prices', function ($q) use ($user) {
                $q->where('location_id', $user->location->id);
            })
            ->join('location_prices', function ($join) use ($user) {
                $join->on('products.id', '=', 'location_prices.product_id')
                    ->where('location_prices.location_id', $user->location->id);
            })
            ->join('categories', 'products.category_id', '=', 'categories.id') // <- agregado
            ->select('products.id', 'products.name', 'location_prices.price as unit_price', 'location_prices.quantity as stock', 'products.category_id', 'categories.name as category') 
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    public function searchrs(Request $request)
    {
        $query = $request->input('query'); // Obtener el término de búsqueda

        // Buscar productos que coincidan con el término
        $products = Product::with('category')
            ->where('name', 'LIKE', "%{$query}%")
            ->where('deleted', 0) // Solo productos no eliminados
            ->select('id', 'name', 'unit_price', 'quantity')
            ->limit(10)
            ->get();

        return response()->json($products); // Devolver resultados en JSON
    }
}
