{{--
	View for Registration Page which includes form for Customer registration
--}}

@extends('layouts.default')

@section('pageId')
    id="page-register"
@endsection

@section('content')
	<div class="row container">
        <div class="col s12 m6 offset-m3">
    		<div class="card-panel">
    			<div class="row s12">
    				<h4 class="center-align"> Register as Customer </h4>
    				{{-- Display Validation Errors --}}
    				@include('common._errors')

    				{{-- Registration Form --}}
    				<form id="registration-form" action="{{ url('register') }}" method="POST" class="col s12">
    					{{ csrf_field() }}

    					{{-- Name --}}
    					<div class="row">
    						<div class="input-field col s12">
    							<input class="validate" id="name" name="name" value="{{ old('name') }}" autofocus>
    							<label for="email">Name</label>
    						</div>
    					</div>

    					{{-- E-Mail Address --}}
    					<div class="row">
    						<div class="input-field col s12">
    							<input class="validate" type="email" id="email" name="email" value="{{ old('email') }}">
    							<label for="email">E-mail</label>
    						</div>
    					</div>

    					{{-- Password --}}
    					<div class="row">
    						<div class="input-field col s12">
    							<input class="validate" type="password" id="password" name="password">
    							<label for="password">Password</label>
    						</div>
    					</div>

    					{{-- Confirm Password --}}
    					<div class="row">
    						<div class="input-field col s12">
    							<input class="validate" type="password" id="password_confirmation" name="password_confirmation">
    							<label for="password_confirmation">Re-Type Password</label>
    						</div>
    					</div>

              {{-- For Terms and Policy --}}
              <div class="terms-and-policy-container">
                <p class="terms-and-policy">
                  By clicking Register, you agree to our
                <a href="{{ route('termsOfAgreement') }}" target="_blank">Terms</a>
                  and
                  <a href="{{ route('customerPrivacyPolicy') }}" target="_blank">Data Policy</a>.
                </p>
              </div>

    					{{-- Register Button --}}
    					<div class="row">
    						<div class="">
                  <button 
                    type="submit"
                    class="btn primary primary-hover col s5 push-s7"
                  >
                    Register as Customer
    								<i class="material-icons right">send</i>
    							</button>
    						</div>
    					</div>

    				</form>

    				<div class="row">
    					<h5 class="center-align"> OR </h5>
    					{{-- Facebook Button --}}
    					<!-- <div class="col s12">
                            <a href="/login/facebook" class="btn-large waves-effect waves-light indigo darken-2 col s12 social-button"> Register with Facebook </a>
                        </div> -->
    				</div>

    				<div class="row">
    					{{-- Google Button --}}
    					<div class="col s12">
    						<a href="/login/google" class="btn-large waves-effect waves-light red col s12 social-button"> Register with Google </a>
    					</div>
    				</div>
    			</div>
    		</div>
        </div>
	</div>
@endsection

@section('customScript')
    <script src="{{ elixir('/js/register.js') }}"></script>
@endsection
