<?php


namespace App\Http\Controllers;


use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class PasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:2,1', [ // 一分钟内只能访问两次
            'only' => ['showLinkRequestForm', 'showLinkRequestForm']
        ]);
        $this->middleware('throttle:3,10', [ // 十分钟内访问三次
            'only' => ['sendResetLinkEmail']
        ]);
    }

    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        // 验证邮箱
        $request->validate(['email' => 'required|email']);
        $email = $request->email;

        // 获取用户
        $user = User::where('email', $email)->first();
//        若用户不存在
        if (is_null($user)) {
            session()->flash('danger', '邮箱未注册');
            return redirect()->back()->withInput();
        }
//        生成 Token，会在视图 emails.reset_link 里拼接链接
        $token = hash_hmac('sha256', Str::random(40), config('app.key'));
//        入库 使用 updateOrInsert 来保持email的唯一性
        DB::table('password_resets')->updateOrInsert(['email' => $email], [
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => new Carbon,
        ]);
//        将 Token 链接发送给用户
        Mail::send('emails.reset_link', compact('token'), function ($message) use ($email) {
            $message->to($email)->subject('忘记密码');
        });

        session()->flash('success', '重置邮件发送成功，请查收');

        return redirect()->back();
    }

    public function showResetForm(Request $request)
    {
        $token = $request->route()->parameter('token');
        return view('auth.passwords.reset', compact('token'));
    }

    public function reset(Request $request)
    {
//        验证数据是否合规
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);
        $email = $request->email;
        $token = $request->token;
//        找回密码链接有效时间
        $expires = 60 * 10;
//        获取对应用户
        $user = User::where('email', $email)->first();
//        若不存在
        if (is_null($user)) {
            session()->flash('danger', '邮箱未注册');
            return redirect()->back()->withInput();
        }
//        读取重置的记录
        $record = (array)DB::table('password_resets')->where('email', $email)->first();
//        记录存在
        if ($record) {
//            检查是否过期
            if (Carbon::parse($record['created_at'])->addSeconds($expires)->isPast()) {
                session()->flash('danger', '链接已过期，请重新尝试');
                return redirect()->back();
            }
//            检查是否正确
            if (!Hash::check($token, $record['token'])) {
                session()->flash('danger', '令牌错误');
                return redirect()->back();
            }
//            一切正常，更新用户密码
            $user->update(['password' => bcrypt($request->password)]);
//            提示用户更新成功
            session()->flash('success', '密码重置成功，请使用新密码登录');
            return redirect()->route('login');
        }

//        记录不存在
        session()->flash('danger', '未找到重置记录');
        return  redirect()->back();
    }

}
