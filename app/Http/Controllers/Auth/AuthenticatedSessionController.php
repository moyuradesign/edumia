<?php
namespace App\Http\Controllers\Auth;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
class AuthenticatedSessionController extends Controller {
 public function create() {return view('auth.login');}
 public function store(Request $request) {
    $data=$request->validate(['email'=>['required','email','max:190'],'password'=>['required','string'],'remember'=>['sometimes','boolean']]);
    if(!Auth::attempt(['email'=>$data['email'],'password'=>$data['password'],'status'=>'active'],$request->boolean('remember'))) {
       return back()->withErrors(['email'=>'These credentials are incorrect or the account is inactive.'])->onlyInput('email');
    }
    $request->session()->regenerate();
    return redirect()->intended(route('dashboard'));
 }
 public function destroy(Request $request) {
    Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();
    return redirect()->route('home');
 }
}
