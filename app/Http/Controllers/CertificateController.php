<?php
namespace App\Http\Controllers;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class CertificateController extends Controller {
 public function show(Request $request,Certificate $certificate) {
    abort_unless($request->user()->id===$certificate->user_id || $request->user()->isAdmin(),403);
    return view('certificate.show',['certificate'=>$certificate->load(['course','user'])]);
 }
 public function verify(string $token) {
    $certificate=Certificate::with(['course','user'])->where('verification_token',$token)->firstOrFail();
    return view('certificate.verify',['certificate'=>$certificate]);
 }
 public function revoke(Request $request,Certificate $certificate) {
    abort_unless($request->user()->isAdmin(),403);
    $certificate->update(['revoked_at'=>now()]);return back()->with('success','Certificate revoked.');
 }
}
