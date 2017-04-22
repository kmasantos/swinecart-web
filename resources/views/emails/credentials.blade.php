
@extends('layouts.adminNotificationsLayout')

@section('title')
    @if ($type == 0)
        - SwineCart Breeder Credentials
    @else
        - SwineCart Spectator Credentials
    @endif

@endsection

@section('header')
    <div class="row">
        <div class="col s12 m12 l12 xl12">
            @if ($type == 0)
                <h4>Breeder Credentials<h4>
            @else
                <h4>Spectator Credentials<h4>
            @endif

        </div>
    </div>
    <div class="divider"></div>
@endsection

@section('content')
  <div class="row">
      <div class="col s12 m12 l12 xl12">
          <p>The following are your credentials to access SwineCart.</p>
          <p>Email: <em><strong>{{$email}}</em></strong></p>
          <p>Password: <em><strong>{{$password}}</strong></em></p>
          <p><em>Note: Your password is randomly generated. Please be advised to change your password as soon as possible</em><p>
      </div>
  </div>
@endsection
