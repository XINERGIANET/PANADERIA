<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Obtener solo los productos activos (estado = 0)
        $clients = Client::where('deleted', 0)->paginate(15);
        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('clients.create');
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
            'business_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'commercial_name' => 'nullable|string|max:255',
            'document' => 'required|string|max:20',
            'phone' => 'required|string|max:15',
            'address' => 'required|string|max:255',
            'department' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'district' => 'required|string|max:100',
        ]);

        Client::create([
            'business_name' => $request->business_name,
            'contact_name' => $request->contact_name,
            'commercial_name' => $request->commercial_name,
            'document' => $request->document,
            'phone' => $request->phone,
            'address' => $request->address,
            'department' => $request->department,
            'province' => $request->province,
            'district' => $request->district,
            'deleted' => 0,
        ]);

        return redirect()->route('clients.index')->with('success', 'Cliente registrado correctamente.');
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

        $request->validate([
            'business_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'commercial_name' => 'nullable|string|max:255',
            'document' => 'required|string|max:20',
            'phone' => 'required|string|max:15',
            'address' => 'required|string|max:255',
            'department' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'district' => 'required|string|max:100',
        ]);

        $client = Client::findOrFail($id);
        $client->update($request->all());

        return redirect()->route('clients.index')
            ->with('success', 'Registro actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->update(['deleted' => 1]); // Cambiar estado a 1 (eliminado)
        return redirect()->route('clients.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }

    public function search(Request $request)
    {
        $query = $request->input('query'); // Obtener el término de búsqueda

        // Buscar clientes que coincidan con el término
        $clients = Client::where('business_name', 'LIKE', "%{$query}%")
            ->orWhere('commercial_name', 'LIKE', "%{$query}%")
            ->orWhere('contact_name', 'LIKE', "%{$query}%")
            ->select('id', 'business_name', 'contact_name')
            ->limit(10)
            ->get();

        return response()->json($clients); // Devolver resultados en JSON
    }
}
