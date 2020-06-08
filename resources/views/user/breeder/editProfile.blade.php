{{--
    Displays Breeder profile form upon profile edit
--}}

@extends('user.breeder.home')

@section('title')
| Breeder - Update Profile
@endsection

@section('pageId')
id="page-breeder-edit-profile"
@endsection

@section('breadcrumbTitle')
<div class="breadcrumb-container">
  Update Profile
</div>
@endsection

@section('breadcrumb')
<div class="breadcrumb-container">
  <a href="{{ route('home_path') }}" class="breadcrumb">Home</a>
  <a href="#!" class="breadcrumb">Update Profile</a>
</div>
@endsection

@section('breeder-content')
<div class="row">
  <div class="col s12 m10 offset-m1">

    <div class="row">
      <div class="col s2 center-align">
        <p class="caption">Update your profile.</p>
        <div id="logo-card" class="card">
          <div class="card-image">
            <img src="{{ $breeder->logoImage }}" alt="" />
          </div>
        </div>
        <a id="change-logo" href="#">Change Logo</a>
      </div>

      <div class="col s8 offset-s1">
        <p class="caption">SwineCart Profile URL:</p>
        <p class="grey-text text-darken-2">
          Note: Share this link to your customers for them to view your products
        </p>

        <br>

        <div class="row">
          <div class="col s9 grey lighten-3">
            <input id="breeder-link" style="border: none; font-size: 1.5rem;" type="text" readonly
              value="http://swinecart.test/customer/view-breeder/{{ $breeder->breeder_handle }}" />
            {{-- value="http://swinecart.test/customer/view-breeder/{{ $breeder->identifier }}" /> --}}
          </div>

          <div class="col s2" style="padding-top: 10px;">
            <button onclick="copyToClipBoard()" class="waves-effect waves-light btn primary tooltipped"
              data-position="right" data-tooltip="Copy your SwineCart link to your clipboard">
              COPY
            </button>
          </div>
        </div>

      </div>
    </div>

    @include('common._errors')
    @include('user.breeder._editProfileForm')
  </div>
</div>

{{-- Remove Farm confirmation modal --}}
<div id="confirmation-modal" class="modal">
  <div class="modal-content">
    <p>Are you sure you want to remove this farm?</p>
  </div>
  <div class="modal-footer">
    <a href="#!" id="confirm-remove" class=" modal-action modal-close waves-effect waves-green btn-flat"><i
        class="material-icons">done</i></a>
    <a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat"><i
        class="material-icons">clear</i></a>
  </div>
</div>

{{-- Change Logo Modal --}}
<div id="change-logo-modal" class="modal">
  <div class="modal-content">
    <h5>Set new logo</h5>
    <div class="row">
      <div class="col s12">
        {!! Form::open(['route' => 'breeder.logoUpload', 'class' => 's12 dropzone', 'id' => 'logo-dropzone', 'enctype'
        => 'multipart/form-data']) !!}
        <div class="fallback">
          <input type="file" name="logo" accept="image/png, image/jpeg, image/jpg">
        </div>
        {!! Form::close() !!}
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <a style="text-transform: none;" href="#!"
      class="modal-action modal-close waves-effect waves-green btn-flat">Cancel</a>
    <a style="text-transform: none;" href="#!" id="confirm-change-logo"
      class="waves-effect waves-green btn blue white-text">Set Logo</a>
  </div>
</div>

{{--  Custom preview for dropzone --}}
<div id="custom-preview" style="display:none;">
  <div class="dz-preview dz-file-preview">
    <div class="dz-image">
      <img data-dz-thumbnail alt="" src="" />
    </div>
    <div class="dz-details">
      <div class="dz-filename"><span data-dz-name></span></div>
      <div class="dz-size" data-dz-size></div>
    </div>
    <div class="dz-progress progress red lighten-4">
      <div class="determinate green" style="width:0%" data-dz-uploadprogress></div>
    </div>
    <div class="dz-success-mark"><span><i class='medium material-icons green-text'>check_circle</i></span></div>
    <div class="dz-error-mark"><span><i class='medium material-icons orange-text text-lighten-1'>error</i></span></div>
    <div class="dz-error-message"><span data-dz-errormessage></span></div>
    <a>
      <i class="dz-remove material-icons red-text text-lighten-1 tooltipped" data-position="bottom" data-delay="50"
        data-tooltip="Remove this image" data-dz-remove>cancel</i>
    </a>
  </div>
</div>
@endsection

@section('customScript')
@if(Session::has('message'))
<script type="text/javascript">
  $(document).ready(function() {
    $('.tooltipped').tooltip();
    Materialize.toast('{{ Session::get('message') }}', 4000, 'green lighten-1');
  });
</script>
@endif
<script type="text/javascript">
  var provinces = {!! $provinces !!};

  $('#office-province, #farm-province').on('click', function (event) {
    event.stopPropagation();
  });

  function copyToClipBoard () {
    const breederLink = document.getElementById('breeder-link');
    breederLink.select();
    breederLink.setSelectionRange(0, 99999);
    document.execCommand("copy");

    Materialize.toast('Copied to clipboard!', 4000, 'green lighten-1');
  }
</script>
<script src="{{ elixir('/js/breeder/editProfile.js') }}"></script>
@endsection