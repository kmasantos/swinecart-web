@extends('layouts.default')

@section('title')
    | Customer
@endsection

@section('navbar_head')
    <li><a href="{{ route('home_path') }}"> Products </a></li>
    <li><a href="{{ route('home_path') }}"> Shopping Cart </a></li>
@endsection

@section('navbar_dropdown')
    <li><a href="#">Update Profile</a></li>
@endsection

@section('content')
    <div class="valign-wrapper">
        <div class="valign">
          <div class="card blue-grey lighten-2">
            <div class="card-content white-text">
                <span class="card-title">Home - Customer</span>
                <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa.
                    Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.
                    Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim.
                    Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu.
                    In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.
                    Integer tincidunt. Cras dapibus.     
                </p>
            </div>
          </div>
        </div>
    </div>
    
@endsection
