
{{--
    Displays Customer profile form upon profile edit
--}}

@extends('user.customer.home')

@section('title')
    | Customer - Messages
@endsection

@section('pageId')
    id="page-customer-messages"
@endsection

@section('breadcrumbTitle')
    Messages
@endsection

@section('content')
<style>
 	#chatMessages{ width: 100%; min-height: 100px; height: 50vh; overflow-y: auto;}
 	#chatMessages li { width: 100%; padding: 10px;}
 	#thread-collection{ height: 60vh; overflow-y: auto; }

 	.chat-bubble { border-radius: 10px; padding: 7px; max-width: 30vw;}
 	.chat-bubble.in { float:left; background-color: #e0e0e0; color: #424242;}
  .chat-bubble.out { float:right; background-color: #0071FF; color: white;}
  .chat-media-bubble { float:right; width: 100%; height: 100%; }
  .chat-bubble-media { float:right; border-radius: 5px; padding: 0px; max-width: 30vw; }
  .chat-media-bubble.in { float:left; width: 30vw; height: 100%; }
  .chat-media-bubble.out { float:right; width: 30vw; height: 100%; }

</style>

<div class="row container" style="padding-left: 0.5vw;">
	<div id="threadname">
		@if($threadId != '' && sizeof($threads) == 0)
			{{ $otherName }}
		@elseif(sizeof($threads) == 0)
			You have no messages.
		@else
			{{ $threads[0]->otherparty() }}
		@endif
	</div>
</div>

<div class="row container">

	<div class="col m3 row">
	  <ul class="collection" id="thread-collection" style="border: 1px solid #ddd !important; margin: 0 !important;">
	  	@foreach($threads as $thread)
	  		@if($userType == 'Customer')
	  			<a id="thread-{{ $thread->breeder_id }}" href="/customer/messages/{{ $thread->breeder_id }}">
	  		@else
	  			<a id="thread-{{ $thread->customer_id }}" href="/breeder/messages/{{ $thread->customer_id }}">
	  		@endif

	  		@if(($threadId == $thread->breeder_id && $userType == 'Customer') || ($threadId == $thread->customer_id && $userType == 'Breeder'))
		    	<li class="collection-item avatar green lighten-4">
	  		@else
		    	<li class="collection-item avatar">
	  		@endif

		      <i class="material-icons circle small">chat</i>
		      <span class="title">
		         @if($thread->read_at == NULL)
		           *
		         @endif
		         {{ $thread->otherparty() }}
		      </span>

		    </li>
		    </a>
	    @endforeach
	  </ul>
	</div>

	<div class="col m9 row">

		<div>

			<div class="panel panel-default">
				<div class="panel-body" id="chat" v-cloak>
					<ul id="chatMessages" style="border: 1px solid #ddd;">

            {{-- Page Reloaded Messages --}}
            <div v-for="item in items">
              {{-- Sender --}}
              <div v-if="
                  (item.direction == 0 && usertype == 'Customer') ||
                  (item.direction == 1 && usertype == 'Breeder')
                "
              >
                {{-- if message has a media_url --}}
                <li
                  v-if="item.media_url"
                  class="message"
                  :class="mine" 
                  style="clear:both;"
                >
                  <div class="chat-bubble-media">
                    
                    <img
                      v-if="item.media_type === 'image'"
                      class="chat-media-bubble"
                      :src="item.media_url"
                    >
                    
                    <video
                      v-if="item.media_type === 'video'"
                      class="chat-media-bubble"
                      :src="item.media_url"
                      controls
                    >
                      Sorry, your browser doesn't support embedded videos.
                    </video>
                  </div>
                </li>

                {{-- if message is a text --}}
                <li
                  v-else
                  class="message"
                  :class="mine"
                  style="clear:both;"
                >
                  <div class="chat-bubble out">
                    @{{ item.message }}
                  </div>
                </li>
              </div>

              {{-- Receiver --}}
              <div v-else>
                {{-- if message has a media url --}}
                <li
                  v-if="item.media_type"
                  class="message"
                  :class="user"
                  style="clear:both"
                >
                  <div class="chat-media-bubble in">
                    <img
                      v-if="item.media_type === 'image'"
                      class="chat-media-bubble"
                      :src="item.media_url"
                    >
                    
                    <video
                      v-if="item.media_type === 'video'"
                      class="chat-media-bubble"
                      :src="item.media_url"
                      controls
                    >
                      Sorry, your browser doesn't support embedded videos.
                    </video>
                  </div>
                </li>

                {{-- if message is a text --}}
                <li
                  v-else
                  class="message"
                  :class="user"
                  style="clear:both;"
                >
                  <div class="chat-bubble in">
                    @{{ item.message }}
                  </div>
                </li>
              </div>
            </div>

            {{-- Real Time Messages --}}
            <li
              v-for="message in messages"
              class="message"
              :class="mine"
              style="display:none;clear:both;"
            >
              <div 
                v-if="message.media_url"
                class="chat-media-bubble"
                :class="message.dir"
              >
                <img 
                  v-if="message.media_type === 'image'"
                  class="chat-media-bubble"
                  :src="message.media_url"
                >

                <video
                  v-if="message.media_type === 'video'"
                  class="chat-media-bubble"
                  :src="message.media_url"
                  controls
                >
                  Sorry, your browser doesn't support embedded videos.
                </video>
              </div>

              <div
                v-else
                class="chat-bubble"
                :class="message.dir"
              >
                @{{ message.msg }}
              </div>
                
            </li>

					</ul>

					<div id="belowChatBox" class="row">
            <a
              href="#upload-media-modal"
              @click="uploadMedia"
              id="modal-trigger"
              class="col s1 center-align"
              style="margin-top: 1vh; cursor: pointer;"
            >
              <i class="small material-icons primary-text">photo</i>
            </a>

						<div class="col s10 center-align">
							<input placeholder="Enter your message here."
						 		style="display:table-cell; width: 100%;"
							   type="text"
							   v-model="newMessage"
							   @keyup.enter="sendMessage">
            </div>
            
						<div 
					    @click="sendMessage" 
							class="col s1 center-align"
							style="margin-top: 1vh; cursor: pointer;">
							<i class="small material-icons primary-text">send</i>
						</div>
          </div>
          

          {{-- Upload Media Modal --}}
          <div id="upload-media-modal" class="modal modal-fixed-footer">
            <div class="modal-content">
              <h4>Upload Media</h4>
              <div class="row">
                {!! Form::open([
                    'route' => 'messages.uploadMedia',
                    'class' => 's12 dropzone',
                    'id' => 'media-dropzone',
                    'enctype' => 'multipart/form-data'
                  ]) 
                !!}
                  <div class="fallback">
                    <input type="file"
                      name="medium"
                      accept="image/png, image/jpeg, image/jpg, video/avi, video/mp4, video/flv, video/mov"
                    >
                  </div>
                {!! Form::close() !!}
              </div>
            </div>
            
            <div class="modal-footer">
              <button 
                @click="sendMessage"
                class="btn waves-effect
                  waves-light
                  modal-action
                  primary
                  primary-hover"
              >
                Send
              </button>
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
          
				</div>
			</div>
		</div>
	</div>

</div>


@endsection

@section('customScript')
<script>
  $(document).ready(function(){
    $('.message').show(0);
  });
  
	var username = "{{ $userName }}";
	var userid = "{{ $userId }}";
	var usertype = "{{ $userType }}";

	var chatport = "{{ $chatPort }}";
	var url = "{{ explode(':', str_replace('http://', '', str_replace('https://', '', App::make('url')->to('/'))))[0] }}";
    var threadid = "{{ $threadId }}";
  var otherparty;
  var allMessages = {!! $messages !!};

</script>
<script src="{{ elixir('/js/chat.js') }}"></script>
@endsection
