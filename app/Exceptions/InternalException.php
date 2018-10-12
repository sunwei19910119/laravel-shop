<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Illuminate\Http\Request;

class InternalException extends Exception
{
    protected $msgForUser;

    public function __construct(string $message,string $msgForUser = '系统内部错误', int $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render(Request $request){
        if($request->expectsJson()){
            return response()->json(['msg' => $this->msgForUser],$this->code);
        }

        return view('pages.error',['msg' => $this->msgForUser]);
    }
}
