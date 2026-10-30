@extends('layouts.panel')
@section('title','Course certificate')
@section('content')
<div class="panel-heading"><h1>Certificate of completion</h1><button onclick="window.print()" type="button" class="btn btn-secondary">Print / Save as PDF</button></div>
<div class="certificate-sheet"><p class="section-kicker">EDUMIA · LEARNING MANAGEMENT SYSTEM</p><h1>Certificate of Completion</h1>
<p>This certifies that</p><h2>{{ $certificate->user->name }}</h2><p>has successfully completed the course</p>
<h2>{{ $certificate->course->title }}</h2><p>Issued: {{ $certificate->issued_at?->format('F j, Y') }} · ID: {{ $certificate->certificate_number }}</p>
@if($certificate->revoked_at)<p class="flash error">This credential has been revoked.</p>@endif
<p>Verification: <a href="{{ route('certificate.verify',$certificate->verification_token) }}">{{ route('certificate.verify',$certificate->verification_token) }}</a></p>
<p>Edumia · Ton Samith</p></div>
@if(auth()->user()->isAdmin() && !$certificate->revoked_at)<form method="post" action="{{ route('admin.certificates.revoke',$certificate) }}">@csrf<button class="btn btn-secondary">Revoke certificate</button></form>@endif
@endsection
