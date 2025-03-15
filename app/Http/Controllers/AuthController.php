<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Http\Responses\AppResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use App\Notifications\PurchaseDetailsNotification;
use App\Notifications\CustomResetPassword;
use Illuminate\Support\Facades\Mail; 
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Cache; 
class AuthController extends Controller
{
    public function register(Request $request)
    {    
        try{
            $validator = Validator::make($request->all(),[
            'nombre' => 'required|min:4',
            'email' => 'required|email|unique:users,email',
            'apellido' => 'required|string|min:5',
            'password' => 'required|string|min:8',
            'phone_number' => 'required|string|min:10',
            'image' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return AppResponse::error("Invalid  data.",422,$validator->errors());

            }
            $validatedData = $validator->validated();
            $imagePath = null;
        if ($validatedData['image']) {
            $imageData = $validatedData['image'];

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
            } else {
                return AppResponse::error("Invalid Base64 string.",422,[]);

            }
        }

        $user = User::create([
            'nombre' => $validatedData['nombre'],
            'apellido' => $validatedData['apellido'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'phone_number'=>$validatedData['phone_number'],
            'gender'=>$request->gender,
            'image' => $imagePath,
            'ruc' => $request->ruc,
            'rol_id'=>$request->rol_id
        ]);
        $formatoRegistro=[
            'token' => $user->createToken('API TOKEN')->plainTextToken,
            'usuario' => $user->nombre,
            'usuario_id'=>$user->id,
            'rol'=>$user->rol->nombre,
            'image_url' => $imagePath ? asset("storage/{$imagePath}") : null,
        ];
        //$user->sendEmailVerificationNotification();
        return AppResponse::success("registro exitoso",200,$validatedData);  
        }catch(\Exception $e){
            return AppResponse::error('error inseperado',404,$e);
        }
    }
    public function updateUser(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|required|min:4',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
                'apellido' => 'sometimes|required|string|min:5',
                'password' => 'sometimes|required|string|min:8',
                'phone_number' => 'sometimes|required|string|min:10',
                'image' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return AppResponse::error("Invalid data.", 422, $validator->errors());
            }

            $validatedData = $validator->validated();
            $user = User::findOrFail($id);

            if (isset($validatedData['image'])) {
                $imageData = $validatedData['image'];
                if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                    $imageData = substr($imageData, strpos($imageData, ',') + 1);
                    $type = strtolower($type[1]);

                    if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                        return response()->json(['error' => 'Invalid image type.'], 422);
                    }

                    $imageData = base64_decode($imageData);

                    if ($imageData === false) {
                        return response()->json(['error' => 'Base64 decoding failed.'], 422);
                    }

                    $fileName = Str::random(10) . '.' . $type;
                    $imagePath = "images/{$fileName}";

                    Storage::disk('public')->put($imagePath, $imageData);
                    $user->image = $imagePath;
                } else {
                    return AppResponse::error("Invalid Base64 string.", 422, []);
                }
            }

            $user->update([
                'nombre' => $validatedData['nombre'] ?? $user->nombre,
                'apellido' => $validatedData['apellido'] ?? $user->apellido,
                'email' => $validatedData['email'] ?? $user->email,
                'password' => isset($validatedData['password']) ? Hash::make($validatedData['password']) : $user->password,
                'phone_number' => $validatedData['phone_number'] ?? $user->phone_number,
                'image' => $user->image,
            ]);

            return AppResponse::success("User updated successfully", 200, $user);
        } catch (\Exception $e) {
            return AppResponse::error('Unexpected error', 500, $e->getMessage());
        }
    }

    public function deleteUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user) {
                $user->delete();
                return AppResponse::success('User deleted successfully', 200, []);
            } else {
                return AppResponse::error('User not found', 404, []);
            }
        } catch (\Exception $e) {
            return AppResponse::error('Unexpected error', 500, $e->getMessage());
        }
    }
    public function login(Request $request)
    {
        $fields = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'remember' => 'boolean',
            ]);

            if ($fields->fails()) {
                return AppResponse::error("Invalid  data.",422,$fields->errors());

            }
            $validatedData = $fields->validated();
            $credentials = [
                'email' => $validatedData['email'],
                'password' => $validatedData['password'],
            ];
        if (!Auth::attempt($credentials, $validatedData['remember'])) {
            return AppResponse::error("error de acceso no existen esas credenciales",404,[]
        ); 
        }
        $user = User::findOrFail(Auth::user()->id);

        $token =  $user->createToken($request->email)->plainTextToken;
        session()->regenerate();


        return AppResponse::successLogin("login exitoso",200,Auth::user(),$token); 
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        return AppResponse::success('Logout successful', 200, []);
    }
    public function emailVerify($user_id, Request $request) 
    {
        if (!$request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 400);
        }

        $user = User::findOrFail($user_id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 400);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            return response()->json([
                'message' => 'Email address successfully verified',
                'user' => $user,
            ]);
        }

        return response()->json([
            'message' => 'Email address already verified.',
        ], 400);
    }

    public function resendEmailVerificationMail(Request $request) 
    {
        $user_id = $request->input('user_id');

        $user = User::findOrFail($user_id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Email verification link sent to your email address',
        ]);
    }

    public function forgotPassword(Request $request)
    {  
        $fields = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($fields->fails()) {
            return AppResponse::error("Invalid  data.",422,$fields->errors());

        }
        $validatedData = $fields->validated();

        $email= User::where('email', $validatedData['email'])->exists();
        if($email==false){
         return AppResponse::error('email no esta registrado',404,[]);
        }
        
    $response = $this->sendCustomResetLink(
        $request->only('email')
    );

    $status = $response['status'];
    $token = $response['token'];
    
    return $status === Password::RESET_LINK_SENT
        ? AppResponse::success(trans($status),200,$token)
        : AppResponse::error(trans($status),400,$status);
    }

    public function resetPassword(Request $request)
    {
        $fields = Validator::make($request->all(),
                    [
                        'token' => 'required',
                        'email' => 'required|email',
                        'password' => 'required|min:8|confirmed',
                    ]
                    );
        
        if ($fields->fails()) {
            return AppResponse::error("Invalid  data.",422,$fields->errors());

        }
        $validatedData = $fields->validated();

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
        ? AppResponse::success(trans($status),200,$status)
        : AppResponse::error(trans($status),400,$status);
    }

    public function sendCustomResetLink(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

    if (!$user) {
        return Password::INVALID_USER;
    }

    $token = Password::createToken($user);

    $user->notify(new CustomResetPassword($token));

    return [
        'status' => Password::RESET_LINK_SENT,
        'token' => $token
    ];
    }
    public function sendVerificationCode(Request $request)
    {
        try{
            $validator = Validator::make($request->all(),[
                'email' => 'required|email',
                ]);
    
                if ($validator->fails()) {
                    return AppResponse::error("Invalid  data.",422,$validator->errors());
    
                }
                $validatedData = $validator->validated();
               $email= User::where('email', $validatedData['email'])->exists();
               if($email==false){
                return AppResponse::error('email no esta registrado',404,[]);
               }

               $code = $this->generateCode(); // Genera un código de 8 caracteres
               // Guardar el código en caché con una expiración de 15 minutos
               Cache::put('verification_code_'.$request->email, $code, now()->addMinutes(15));
       
               //Mail::to($request->email)->send(new VerificationCodeMail($code));
               return AppResponse::success('codigo enviado'.$code,200,'en momentos recivira un codigo en su correo');
          
        }catch(\Exception $e){
            return AppResponse::error('error inseperado',404,$e);
        }
     

    }

    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'code' => 'required|string|size:8'
        ]);
        
        if ($validator->fails()) {
            return AppResponse::error("Invalid  data.",422,$validator->errors());

        }
        $cachedCode = Cache::get('verification_code_'.$request->email);

        if ($cachedCode === $request->code) {
            return AppResponse::success('codigo verificado correctamente',200,$request->code);

        } else {
            return AppResponse::error('codigo incorrecto o expirado',400,[]);

        }
    }

    private function generateCode()
    {
        return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
      
}
