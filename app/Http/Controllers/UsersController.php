<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function create()
    {
        return view('users.create');
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
//        return view('users.show', ['user' => $user]);
    }

    /**
     * 注册
     * @param Request $request
     * @return null
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $this->validate($request,[
           'name' => 'required|unique:users|max:50',
           'email' => 'required|email|unique:users|max:255',
           'password' => 'required|confirmed|max:12'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        session()->flash('success', '欢迎，你将在这里开启一段新的旅程~');
        return redirect()->route('users.show', [$user]);
    }

}
