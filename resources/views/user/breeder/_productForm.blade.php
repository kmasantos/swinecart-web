{{--
	Forms regarding creation and showcasing of Breeder's products

	General Info and other product details
	Media (Images and Videos)
--}}

{{--  Add Product Modal --}}
<div id="add-product-modal" class="modal modal-fixed-footer" style="max-height: 88vh; height: 88vh !important; width: 60vw !important;">
	{!! Form::open(['route' => 'products.store', 'class' => 's12', 'id' => 'create-product']) !!}
	<div class="modal-content" style="overflow-y: auto !important;">
		<h4>Add Product <i class="material-icons right modal-action modal-close">close</i> </h4>
		<div class="row">
			<div id="tabs-container" class="col s12">
				<ul class="tabs tabs-fixed-width grey lighten-5">
					<li id="swine-info-tab" class="tab col s4"><a href="#swine-information">Swine Information</a></li>
					<li id="breed-info-tab" class="tab col s4"><a href="#breed-information">Breed Information</a></li>
					<li id="other-details-tab" class="tab col s4"><a href="#other-details">Other Details</a></li>
				</ul>
			</div>

			{{-- Swine Information --}}
			<div id="swine-information" class="col s12 m12 l10 offset-l1">
				<div class="row">
					<br>
					{{-- Name --}}
					<div class="input-field col s6">
						{!! Form::text('name', null, ['id' => 'name', 'class' => 'validate input-manage-products'])!!}
						{!! Form::label('name', 'Name*', ['class' => 'grey-text text-darken-3']) !!}
					</div>

					{{-- Type --}}
					<div class="input-field col s6">
						<select id="select-type" data-form="add">
					      <option value="" disabled selected>Choose Type</option>
					      <option value="boar">Boar</option>
					      <option value="sow">Sow</option>
						  <option value="gilt">Gilt</option>
					      <option value="semen">Semen</option>
					    </select>
					    <label class="grey-text text-darken-3">Type*</label>
					</div>
				</div>

				<div class="row">
					{{-- Farm From --}}
					<div class="input-field col s6">
						<select id="select-farm">
					    	<option value="" disabled selected>Choose Farm</option>
							@foreach($farms as $farm)
								<option value="{{$farm->id}}">{{$farm->name}}, {{$farm->province}}</option>
							@endforeach
					    </select>
					    <label class="grey-text text-darken-3">Farm From*</label>
					</div>
				</div>

				<div class="row">
					{{-- Price --}}
					<div class="input-field col s6">
						{!! Form::text('price', null, ['class' => 'validate input-manage-products price-field'])!!}
						{!! Form::label('price', 'Price', ['class' => 'grey-text text-darken-3']) !!}
					</div>
				</div>
			</div>

			{{-- Breed Information --}}
			<div id="breed-information" class="col s12 m12 l10 offset-l1">
				<br>
				<label class="grey-text text-darken-3">Type of Breed</label>
				<div class="row">
					 {{-- Breed --}}
					<div class="input-field col s7">
						<p>
							<input name="radio-breed" type="radio" value="purebreed" id="purebreed" class="with-gap purebreed" checked/>
		      				<label class="grey-text text-darken-3" for="purebreed">Purebreed</label>
						</p>
						<p>
							<input name="radio-breed" type="radio" value="crossbreed" id="crossbreed" class="with-gap crossbreed"/>
		      				<label class="grey-text text-darken-3" for="crossbreed">Crossbreed</label>
						</p>
					</div>
				</div>

				<div class="row">
					<div class="input-purebreed-container">
						{{-- If pure breed --}}
						<div class="input-field col s6">
							{!! Form::text('breed', null, ['id' => 'breed', 'class' => 'validate input-manage-products'])!!}
							{!! Form::label('breed', 'Breed*', ['class' => 'grey-text text-darken-3']) !!}
						</div>
					</div>
					<div class="input-crossbreed-container">
						{{-- If crossbreed --}}
						<div class="input-field col s6">
							{!! Form::text('fbreed', null, ['id' => 'fbreed', 'class' => 'validate input-manage-products'])!!}
							{!! Form::label('fbreed', 'Father\'s Breed*', ['class' => 'grey-text text-darken-3']) !!}
						</div>
						<div class="input-field col s6">
							{!! Form::text('mbreed', null, ['id' => 'mbreed', 'class' => 'validate input-manage-products'])!!}
							{!! Form::label('mbreed', 'Mother\'s Breed*', ['class' => 'grey-text text-darken-3']) !!}
						</div>
					</div>
				</div>

				<div class="row">
					{{-- Birthdate --}}
					<div class="input-field col s6">
						<input style="cursor: pointer;" type="date" id="birthdate" name="birthdate" class="datepicker validate"/>
						<label class="grey-text text-darken-3" for="birthdate">Birth Date</label>
					</div>

					{{-- ADG --}}
					<div class="input-field col s6">
						{!! Form::text('adg', null, ['class' => 'validate input-manage-products'])!!}
						{!! Form::label('adg', 'Average Daily Gain (grams)', ['class' => 'grey-text text-darken-3']) !!}
					</div>
				</div>

				<div class="row">
					{{-- FCR --}}
					<div class="input-field col s6">
						{!! Form::text('fcr', null, ['class' => 'validate input-manage-products'])!!}
						{!! Form::label('fcr', 'Feed Conversion Ratio', ['class' => 'grey-text text-darken-3']) !!}
					</div>

					{{-- Backfat thickness --}}
					<div class="input-field col s6">
						{!! Form::text('backfat_thickness', null, ['class' => 'validate input-manage-products'])!!}
						{!! Form::label('backfat_thickness', 'Backfat thickness (mm)', ['class' => 'grey-text text-darken-3']) !!}
					</div>
				</div>

			</div>

			{{-- Other Details --}}
			<div id="other-details" class="col s12 m12 l10 offset-l1">
				<div class="row">
					<br>
					{{-- Other Details --}}
					<div class="other-details-container">
						<div class="detail-container">
							<div class="input-field col s6">
								{!! Form::text('characteristic[]', null, ['class' => 'validate input-manage-products'])!!}
								{!! Form::label('characteristic[]', 'Characteristic', ['class' => 'grey-text text-darken-3']) !!}
							</div>
							<div class="input-field col s5">
								{!! Form::text('value[]', null, ['class' => 'validate input-manage-products'])!!}
								{!! Form::label('value[]', 'Value', ['class' => 'grey-text text-darken-3']) !!}
							</div>
							<div class="input-field col s1 remove-button-container">
								<a href="#" class="tooltipped remove-detail grey-text text-lighten-1" data-position="top" data-delay="50" data-tooltip="Remove detail">
						            <i class="material-icons">remove_circle</i>
						        </a>
							</div>
						</div>
					</div>

					<div class="col s12">
						<a 
							style="text-transform: none !important; font-weight: 700;"
							class="waves-effect waves-light btn-flat right add-other-details blue-text"
						><i class="material-icons blue-text right">add_circle</i>Add Details</a>
					</div>

				</div>
			</div>
		</div>
	</div>
	<div class="modal-footer" style="background: hsl(0, 0%, 97%); border: none;">
		<button id="submit-button" type="submit" class="btn waves-effect waves-light modal-action teal darken-3" style="display:none;"> Add Product</button>
	</div>
	{!! Form::close() !!}
</div>

{{-- Add Media Modal --}}
<div id="add-media-modal" class="modal modal-fixed-footer">
	<div class="modal-content">
		<h4>Add Media</h4>
		<div class="row">
			{!! Form::open(['route' => 'products.mediaUpload', 'class' => 's12 dropzone', 'id' => 'media-dropzone', 'enctype' => 'multipart/form-data']) !!}
				<div class="fallback">
					<input type="file" name="media[]" accept="image/png, image/jpeg, image/jpg, video/avi, video/mp4, video/flv, video/mov" multiple>
				</div>
			{!! Form::close() !!}
		</div>
	</div>
	<div class="modal-footer">
		<button id="next-button" type="submit" class="btn waves-effect waves-light modal-action teal darken-3"> Product Summary </button>
		<a href="#!" class="modal-action waves-effect waves-green btn-flat back-button">Back</a>
	</div>
</div>

{{-- Product Summary Modal --}}
<div id="product-summary-modal" class="modal modal-fixed-footer" style="max-height: 90%; height: 80vh !important; width: 60vw !important;">
	<div class="modal-content">
		<h4>Product Summary</h4>
		<div class="row">
			<ul id="product-summary-collection" class="collection with-header">
				<li class="collection-header">
					<h5 style="font-weight: 700;">Product Name</h5>
					<h6>Province</h6>
				</li>
				<div></div>
			</ul>
		</div>
		<div class="row">
	        <div class="col s12">
	            <div id="other-details-summary" class="card" style="box-shadow: 0px 0px !important; border: 1px solid #DFDFDF;">
	                <div class="card-content black-text">
	                    <span class="card-title">Other Details</span>
						<div></div>
	                </div>
	            </div>
	        </div>
	    </div>
		<div class="row">
	        <div class="col s12">
	            <div id="images-summary" class="card grey lighten-5" style="box-shadow: 0px 0px !important; border: none;">
	                <div class="card-content black-text">
	                    <span class="card-title">List of Images</span>
						{!! Form::open(['route' => 'products.setPrimaryPicture', 'class' => 's12']) !!}
						<div class="row"></div>
						{!! Form::close() !!}
	                </div>
	            </div>
	        </div>
	    </div>
	    
		<div class="row">
	        <div class="col s12">
	            <div id="videos-summary" class="card grey lighten-5" style="box-shadow: 0px 0px !important; border: none;">
	                <div class="card-content black-text">
	                    <span class="card-title">List of Videos</span>
						<div class="row"></div>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>

	<div class="modal-footer" style="background: hsl(0, 0%, 97%); border: none;">
		<div class="from-add-process">
			{!! Form::open(['route' => 'products.display', 'class' => 's12', 'id' => 'display-product-form']) !!}
				<button id="display-button" class="btn waves-effect waves-light modal-action teal darken-3"> Display Product</button>
				<button id="save-draft-button" class="btn waves-effect waves-light modal-action teal darken-3"> Save as Draft </button>
			{!! Form::close() !!}
		</div>
		<div class="from-edit-process">
			<button id="save-button" class="btn waves-effect waves-light modal-action teal darken-3"> Save </button>
			<a href="#!" class="modal-action waves-effect waves-green btn-flat back-button">Back</a>
		</div>
	</div>
</div>

{{-- Edit Product Modal --}}
<div id="edit-product-modal" class="modal modal-fixed-footer" style="max-height: 88vh; height: 88vh !important; width: 60vw !important;">
	{!! Form::open(['route' => 'products.update', 'class' => 's12', 'id' => 'edit-product']) !!}
	<div class="modal-content" style="overflow-y: auto !important;">
		<h4>Edit Product <i class="material-icons right modal-action modal-close">close</i> </h4>
		<div class="row">
			<div id="tabs-container" class="col s12">
				<ul class="tabs tabs-fixed-width grey lighten-5">
					<li class="tab col s4"><a href="#edit-swine-information">Swine Information</a></li>
					<li class="tab col s4"><a href="#edit-breed-information">Breed Information</a></li>
					<li class="tab col s4"><a href="#edit-other-details">Other Details</a></li>
				</ul>
			</div>

			{{-- Swine Information --}}
			<div id="edit-swine-information" class="col s12 m12 l10 offset-l1">
				<div class="row">
					<br>
					{{-- Name --}}
					<div class="input-field col s6">
						{!! Form::text('edit-name', null, ['id' => 'edit-name', 'class' => 'validate input-manage-products'])!!}
						{!! Form::label('edit-name', 'Name*', ['class' => 'grey-text text-darken-3']) !!}
					</div>

					{{-- Type --}}
					<div class="input-field col s6">
						<select id="edit-select-type"data-form="add">
					      <option value="" disabled selected>Choose Type</option>
					      <option value="boar">Boar</option>
					      <option value="sow">Sow</option>
						  <option value="gilt">Gilt</option>
					      <option value="semen">Semen</option>
					    </select>
					    <label class="grey-text text-darken-3">Type*</label>
					</div>
				</div>

				<div class="row">
					{{-- Farm From --}}
					<div class="input-field col s6">
						<select id="edit-select-farm">
					    	<option value="" disabled selected>Choose Farm</option>
							@foreach($farms as $farm)
								<option value="{{$farm->id}}">{{$farm->name}}, {{$farm->province}}</option>
							@endforeach
					    </select>
					    <label class="grey-text text-darken-3">Farm From*</label>
					</div>
				</div>

				<div class="row">
					{{-- Price --}}
					<div class="input-field col s6">
						{!! Form::text('edit-price', null, ['class' => 'validate input-manage-products', 'onchange' => 'addComma(this)'])!!}
						{!! Form::label('edit-price', 'Price', ['class' => 'grey-text text-darken-3']) !!}
					</div>
				</div>
			</div>

			{{-- Breed Information --}}
			<div id="edit-breed-information" class="col s12 m12 l10 offset-l1">
				<br>
				<label class="grey-text text-darken-3">Type of Breed</label>
				<div class="row">
					 {{-- Breed --}}
					<div class="input-field col s7">
						<p>
							<input name="radio-breed" type="radio" value="purebreed" id="edit-purebreed" class="with-gap purebreed" checked/>
		      				<label class="grey-text text-darken-3" for="edit-purebreed">Purebreed</label>
						</p>
						<p>
							<input name="radio-breed" type="radio" value="crossbreed" id="edit-crossbreed" class="with-gap crossbreed"/>
		      				<label class="grey-text text-darken-3" for="edit-crossbreed">Crossbreed</label>
						</p>
					</div>
				</div>

				<div class="row">
					<div class="input-purebreed-container">
						{{-- If pure breed --}}
						<div class="input-field col s6">
							{!! Form::text('edit-breed', null, ['id' => 'edit-breed', 'class' => 'validate validate input-manage-products'])!!}
							{!! Form::label('edit-breed', 'Breed*', ['class' => 'grey-text text-darken-3']) !!}
						</div>
					</div>
					<div class="input-crossbreed-container">
						{{-- If crossbreed --}}
						<div class="input-field col s6">
							{!! Form::text('edit-fbreed', null, ['id' => 'edit-fbreed', 'class' => 'validate validate input-manage-products'])!!}
							{!! Form::label('edit-fbreed', 'Father\'s Breed*', ['class' => 'grey-text text-darken-3']) !!}
						</div>
						<div class="input-field col s6">
							{!! Form::text('edit-mbreed', null, ['id' => 'edit-mbreed', 'class' => 'validate validate input-manage-products'])!!}
							{!! Form::label('edit-mbreed', 'Mother\'s Breed*', ['class' => 'grey-text text-darken-3']) !!}
						</div>
					</div>
				</div>

				<div class="row">
					{{-- Birhtdate --}}
					<div class="input-field col s6">
						<input style="cursor: pointer;" type="date" id="edit-birthdate" name="edit-birthdate" class="datepicker"/>
						<label class="grey-text text-darken-3" for="edit-birthdate">Birth Date</label>
					</div>

					{{-- ADG --}}
					<div class="input-field col s6">
						{!! Form::text('edit-adg', null, ['class' => 'validate input-manage-products'])!!}
						{!! Form::label('edit-adg', 'Average Daily Gain (grams)', ['class' => 'grey-text text-darken-3']) !!}
					</div>
				</div>

				<div class="row">
					{{-- FCR --}}
					<div class="input-field col s6">
						{!! Form::text('edit-fcr', null, ['class' => 'validate input-manage-products'])!!}
						{!! Form::label('edit-fcr', 'Feed Conversion Ratio', ['class' => 'grey-text text-darken-3']) !!}
					</div>

					{{-- Backfat thickness --}}
					<div class="input-field col s6">
						{!! Form::text('edit-backfat_thickness', null, ['class' => 'validate input-manage-products'])!!}
						{!! Form::label('edit-backfat_thickness', 'Backfat thickness (mm)', ['class' => 'grey-text text-darken-3']) !!}
					</div>
				</div>

			</div>

			{{-- Other Details --}}
			<div id="edit-other-details" class="col s12 m12 l10 offset-l1">
				<div class="row">
					<br>
					{{-- Other Details --}}
				
					<div class="other-details-container">
						<div class="detail-container">
							<div class="input-field col s6">
								{!! Form::text('characteristic[]', null, ['class' => 'validate input-manage-products'])!!}
								{!! Form::label('characteristic[]', 'Characteristic', ['class' => 'grey-text text-darken-3']) !!}
							</div>
							<div class="input-field col s5">
								{!! Form::text('value[]', null, ['class' => 'validate input-manage-products'])!!}
								{!! Form::label('value[]', 'Value', ['class' => 'grey-text text-darken-3']) !!}
							</div>
							<div class="input-field col s1 remove-button-container">
								<a href="#" class="tooltipped remove-detail grey-text text-lighten-1" data-position="top" data-delay="50" data-tooltip="Remove detail">
						            <i class="material-icons">remove_circle</i>
						        </a>
							</div>
						</div>
					</div>

					<div class="col s12">
						<a 
							style="text-transform: none !important; font-weight: 700;"
							class="waves-effect waves-light btn-flat right add-other-details blue-text"
						><i class="material-icons blue-text right">add_circle</i>Add Details</a>
					</div>

				</div>
			</div>
		</div>
	</div>
	<div class="modal-footer" style="background: hsl(0, 0%, 97%); border: none;">
		<div class="from-add-process" style="display:none;">
			<button id="add-media-button" class="btn waves-effect waves-light modal-action"> Add Media </button>
		</div>
		<div class="from-edit-process">
			<button class="btn waves-effect waves-light modal-action update-button teal darken-3"> Update Product </button>
			<button id="edit-media-button" class="btn waves-effect waves-light modal-action teal darken-3"> Edit Media </button>
		</div>
	</div>
	{!! Form::close() !!}
</div>

{{-- Edit Media Modal --}}
<div id="edit-media-modal" class="modal modal-fixed-footer" style="max-height: 90%; height: 80vh !important; width: 60vw !important;">
	<div class="modal-content">
		<h4>Edit Media </h4>
		<div class="row">
			{!! Form::open(['route' => 'products.mediaUpload', 'class' => 's12 dropzone', 'id' => 'edit-media-dropzone', 'enctype' => 'multipart/form-data']) !!}
				<div class="fallback">
					<input type="file" name="media[]" accept="image/png, image/jpeg, image/jpg, video/avi, video/mp4, video/flv, video/mov" multiple>
				</div>
			{!! Form::close() !!}
		</div>
		<div class="row">
	        <div class="col s12">
	            <div id="edit-images-summary" class="card grey lighten-5" style="box-shadow: 0px 0px !important; border: none;">
	                <div class="card-content black-text">
	                    <span class="card-title">List of Images</span>
						{!! Form::open(['route' => 'products.setPrimaryPicture', 'class' => 's12']) !!}
						<div class="row"></div>
						{!! Form::close() !!}
	                </div>
	            </div>
	        </div>
	    </div>
	  <hr style="border-top: #ccc;">
		<div class="row">
	        <div class="col s12">
	            <div id="edit-videos-summary" class="card grey lighten-5" style="box-shadow: 0px 0px !important; border: none;">
	                <div class="card-content black-text">
	                    <span class="card-title">List of Videos</span>
						<div class="row"></div>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>

	<div class="modal-footer" style="background: hsl(0, 0%, 97%); border: none;">
		<button class="btn waves-effect waves-light modal-action update-button teal darken-3"> Update Product </button>
		<a href="#!" class="modal-action waves-effect waves-green btn-flat back-button">Back</a>
	</div>
</div>

{{-- Confirmation Modal --}}
<div id="confirmation-modal" class="modal">
	<div class="modal-content">
    <h5>Are you sure you want to delete product/s?</h5>
    <p style="font-size: 1.2rem; color: hsl(0, 0%, 45%)">
      Once you delete product/s, it will no longer be available in your inventory.
    </p>
	</div>
	<div class="modal-footer">
    
    <a
    href="#!"
    class="modal-action modal-close waves-effect waves-green btn-flat grey-text"
    style="text-transform: none; font-weight: 700;"
	  >
    No
  </a>

  <a
    href="#!"
    id="confirm-remove"
    class=" modal-action modal-close waves-effect waves-green btn-flat red darken-4 white-text"
    style="text-transform: none;  font-weight: 700;"
  >
    Yes, Delete product/s
  </a>
	</div>
</div>

{{--  Custom preview for dropzone --}}
<div id="custom-preview" style="display:none;">
	<div class="dz-preview dz-file-preview">
		<div class="dz-image">
			<img data-dz-thumbnail alt="" src=""/>
		</div>
		<div class="dz-details">
			<div class="dz-filename"><span data-dz-name></span></div>
			<div class="dz-size" data-dz-size></div>
		</div>
		<div class="dz-progress progress red lighten-4"><div class="determinate green" style="width:0%" data-dz-uploadprogress></div></div>
		<div class="dz-success-mark"><span><i class='medium material-icons green-text'>check_circle</i></span></div>
		<div class="dz-error-mark"><span><i class='medium material-icons orange-text text-lighten-1'>error</i></span></div>
		<div class="dz-error-message"><span data-dz-errormessage></span></div>
		<a><i class="dz-remove material-icons red-text text-lighten-1 tooltipped" data-position="bottom" data-delay="50" data-tooltip="Remove this media" data-dz-remove>cancel</i></a>
	</div>
</div>
