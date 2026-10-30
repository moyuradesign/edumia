<?php
namespace App\Http\Controllers\Auth;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
class RegisteredUserController extends Controller {
 public function create() {return view('auth.register');}
 public function store(Request $request) {
    $data=$request->validate(['name'=>['required','string','max:150'],'email'=>['required','email','max:190','unique:users,email'],
        'password'=>['required','confirmed',Password::min(12)->mixedCase()->numbers()->symbols()]]);
    $user=User::create(['name'=>$data['name'],'email'=>$data['email'],'password'=>$data['password'],'locale'=>'en']);
    // Roles are intentionally not mass-assignable. New registrations are always students.
    event(new Registered($user)); Auth::login($user); $request->session()->regenerate();
    return redirect()->route('dashboard');
 }
}
