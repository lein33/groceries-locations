<?php

namespace App\Http\Controllers;
use App\Models\Rol;
use Illuminate\Http\Request;
use App\Http\Responses\AppResponse;

class RolController extends Controller
{
    public function index()
    {
        return AppResponse::success("lista de roles",200,Rol::all());    

    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:255']);
        $rol = Rol::create($request->all());
        return AppResponse::success("rol creado",201,$rol);    

    }

    public function show(Rol $rol)
    {
        return AppResponse::success("rol existe",201,$rol);    
    }

    public function update(Request $request, Rol $rol)
    {
        $request->validate(['nombre' => 'sometimes|string|max:255']);
        $rol->update($request->all());
        return AppResponse::success("rol actualizado",201,$rol);    

    }

    public function destroy(Rol $rol)
    {
        $rol->delete();
        return AppResponse::success("rol eliminado",204,null);    

    }
}
