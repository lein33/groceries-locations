<?php

namespace App\Http\Responses;

class AppResponse
{
    public static function success($message='listar',$codeStatus,$data=[])
    {
        return response()->json([
            'message'=>$message,
            "data"=>$data,
            "codigoStatus"=>$codeStatus
            #'fecha_modificada'=>$this->created_at->format('d-m-Y')

        ]);
    }
    public static function successLogin($message='listar',$codeStatus,$data=[],$token)
    {
        return response()->json([
            'message'=>$message,
            "data"=>$data,
            "codigoStatus"=>$codeStatus,
            "token"=>$token
            #'fecha_modificada'=>$this->created_at->format('d-m-Y')

        ]);
    }
    public static function error($message='error',$statuscode,$data=[])
{
    return response()->json([
        "messsage"=>$message,
        "statusCode"=>$statuscode,
        "error"=>true,
        "data"=>$data
    ],$statuscode);
}
}