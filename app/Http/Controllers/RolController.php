<?php

namespace App\Http\Controllers;
use App\Models\Rol;
use Illuminate\Http\Request;
use App\Http\Responses\AppResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Support\Facades\Storage;

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

    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:rols,nombre,' . $id,
            
        ]);
        if ($validator->fails()) {
            return AppResponse::error('error, ya existen estos datos',422,$validator->errors());

        }
         $validated = $validator->validated();
        $rol = Rol::findOrFail($id);
        
        if (!$rol) {
            return AppResponse::error('rol no encontrado',404,[]);
        }
        //if (isset($validated['nombre'])) $rol->nombre = $validated['nombre'];
        $rol->nombre = $validated['nombre'];

       $rol->save();
       return AppResponse::success("rol editado",201,$rol);    


    }

    public function destroy(Rol $rol)
    {
        $rol->delete();
        return AppResponse::success("rol eliminado",204,null);    

    }
}
