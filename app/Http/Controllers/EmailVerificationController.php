<?php

namespace App\Http\Controllers;

use App\Notifications\EmailVerificationNotification;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Cache;

class EmailVerificationController extends Controller
{
    //验证邮箱
    public function verify(Request $request){
        //从url中获取`email`和`token`
        $email = $request->input('email');
        $token = $request->input('token');
        //验证参数
        if(!$email || !$token){
            throw new Exception('验证链接不正确');
        }
        if($token != Cache::get('email_verification_'.$email)){
            throw new Exception('验证链接不正确或已过期');
        }

        if(!$user = User::where('email',$email)->first()){
            throw new Exception('用户不存在');
        }

        Cache::forget('email_verification_'.$email);
        $user->update(['email_verified' => true]);

        return view('pages.success',['msg'=>'邮箱验证成功！']);
    }

    //手动发送验证邮件
    public function send(Request $request){
        $user = $request->user();
        if($user->email_verified){
            throw new Exception('您已经验证过邮箱了');
        }
        $user->notify(new EmailVerificationNotification());
        return view('pages.success',['msg' => '邮件发送成功！']);
    }
}
