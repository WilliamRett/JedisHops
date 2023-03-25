<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PantientController extends Controller
{
    public function index()
    {
        $products = Pantient::all()->toArray();
        return array_reverse($products);      
    }

    public function store(Request $request)
    {
        $pantient = new Pantient([
            // 'photo'=> $request->input('name'),
            'name' => $request->input('name'),
            'mon' => $request->input('name'),
            'birthday' => $request->input('name'),
            'cpf' => $request->input('name'),
            'cns' => $request->input('name'),
            'address_id' => $request->input('name'),
        ]);
        $pantient->save();
        return response()->json('Product created!');
    }

    public function show($id)
    {
        $pantient = Pantient::find($id);
        return response()->json($pantient);
    }

    public function update($id, Request $request)
    {
        $pantient = Pantient::find($id);
        $pantient->update($request->all());
        return response()->json('Pantient updated!');
    }

    public function destroy($id)
    {
        $pantient = Pantient::find($id);
        $pantient->delete();
        return response()->json('Pantient deleted!');
    }
}
