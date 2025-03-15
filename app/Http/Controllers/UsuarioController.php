<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Responses\AppResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::with('rol')->get();
        $usuarios = $usuarios->map(function ($usuario) {
            $usuario->makeHidden(['rol_id','created_at', 'updated_at']);
            $usuario->rol->makeHidden(['id','created_at', 'updated_at']);

            return [
                'id'=>$usuario->id,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'email' => $usuario->email, // Devolver el objeto de categoría completo
                'contacto' => $usuario->phone_number, // Devolver el objeto de categoría completo
                'genero' => $usuario->gender, // Devolver el objeto de categoría completo
                'ruc' => $usuario->ruc, // Devolver el objeto de categoría completo

                'rol' => $usuario->rol, // Devolver el objeto de marca completo
                'image' => $usuario->image, // Devolver el objeto de marca completo

            ];
        });
        return AppResponse::success("lista de usaurios",200,$usuarios);   


    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'phone_number' => 'required|string|max:20',
            'password' => 'required|string|min:8',
        ]);
        $usuario = Usuario::create([
            'nombre' => $validatedData['nombre'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'password' => Hash::make($validatedData['password']),
        ]);
        return response()->json(['message' => 'User created successfully!', 'user' => $usuario], 201);
    }

    public function show($id)
    {
        try{
            $usuario=User::with('rol')->findOrFail($id);
            if (!$usuario) {
                return AppResponse::error('usuario no encontrado',401,$usuario);
            }
            $usuario->makeHidden(['rol_id','created_at', 'updated_at']);
            $usuario->rol->makeHidden(['created_at', 'updated_at']);
            return AppResponse::success("dato usuario",201,$usuario);   

        }catch(QueryException $e){
            return AppResponse::error('error de consulta',500);
        }catch(Exception $e){
            return AppResponse::error('no se encontro resultados',500,[]);
        }
       

       
    }
    public function byRol($rol_id)
    {
        $usuarios = User::with('rol')->where('rol_id', $rol_id)->get();
        return AppResponse::success('usuarios por Rol',201,$usuarios);

    }
    public function byNombre($nombre)
    {
        $usuarios = User::with('rol')->where('nombre', $nombre)->get();
        return AppResponse::success('usuarios por nombre',201,$usuarios);

    }
    public function byRuc($ruc)
    {
        $usuario = User::with('rol')->where('ruc', $ruc)->get();
        if ($usuario->isEmpty()) {
            return AppResponse::error('usuario no encontrado',404,[]);
        }
        return AppResponse::success('usuario por ruc',201,$usuario);

    }
    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:users,nombre,' . $id,
            'apellido' => 'required|string|max:255|unique:users,apellido,' . $id,
            'phone_number' => 'required|string|max:255|unique:users,phone_number,' . $id,
            'ruc' => 'required|string|max:255|unique:users,ruc,' . $id,
            'image' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            return AppResponse::error('error, ya existen estos datos',422,$validator->errors());

        }
        $validated = $validator->validated();
        $usuario = User::findOrFail($id);
        if (!$usuario) {
            return AppResponse::error('usuario no encontrado',404,[]);
        }
        if (isset($validated['image'])) {
            $imageData = $validated['image'];

            // Validar que el string es Base64 válido y contiene datos de imagen
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif, etc.

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    return response()->json(['error' => 'Invalid image type.'], 422);
                }

                $imageData = base64_decode($imageData);

                if ($imageData === false) {
                    return response()->json(['error' => 'Base64 decoding failed.'], 422);
                }

                // Generar un nombre único para la imagen
                $fileName = Str::random(10) . '.' . $type;
                $imagePath = "images/{$fileName}";

                // Guardar la imagen en el sistema de almacenamiento público
                Storage::disk('public')->put($imagePath, $imageData);

                // Eliminar la imagen antigua si existía
                if ($usuario->image) {
                    Storage::disk('public')->delete($usuario->image);
                }

                $usuario->image = $imagePath;
            } else {
                return response()->json(['error' => 'Invalid Base64 string.'], 422);
            }
        }

        // Actualizar los demás campos
        if (isset($validated['nombre'])) $usuario->nombre = $validated['nombre'];
        if (isset($validated['apellido'])) $usuario->apellido = $validated['apellido'];

        if (isset($validated['email'])) $usuario->email = $validated['email'];
        if (isset($validated['phone_number'])) $usuario->phone_number = $validated['phone_number'];
        if (isset($validated['ruc'])) $usuario->ruc = $validated['ruc'];

        // Guardar los cambios
        $usuario->save();

        // Retornar la respuesta con la URL de la imagen pública
        /*
        return response()->json([
            'user' => $usuario,
            'image_url' => $usuario->image ? asset("storage/{$usuario->image}") : null,
        ]);*/
        //$usuario->update($request->all());
       return AppResponse::success('usuario actualizado',202,$usuario);

    }

    public function destroy($id)
    {
        try{
            $usuario = Usuario::find($id);

            if (!$usuario) {
                return AppResponse::error('usuario no encontrado',401,$usuario);
            }
    
            $usuario->delete();
    
            return AppResponse::success('usuario eliminado',202,$usuario);
        }catch(QueryException $e){
            return AppResponse::error('error de consulta',500);
        }catch(Exception $e){
            return AppResponse::error($e,500);
        }
    }
}
