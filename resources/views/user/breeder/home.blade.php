{{--
    Displays Home page of Breeder User
--}}

@extends('layouts.default')

@section('title')
    | Breeder
@endsection

@section('globalVariables')
    <script type="text/javascript">
        window.hostUrl = '{{ env('APP_URL') }}';
        window.pubsubTopic = '{{ crypt(Auth::user()->email, md5(Auth::user()->email)) }}';
    </script>
@endsection

@section('pageId')
    id="page-breeder-home"
@endsection

@section('breadcrumbTitle')
    Home
@endsection

@section('navbarHead')
    @if(!Auth::user()->update_profile)
        {{-- <li 
            id="message-main-container"
            class="tooltipped"
            data-position="bottom"
            data-tooltip="Messages"
        >
            <a v-cloak href="{{ route('breeder.messages') }}" id="message-icon"
                data-alignment="right"
            >
                <i class="material-icons left">message</i>
                <span class="badge"
                    v-if="unreadCount > 0  && unreadCount <= 99"
                >
                    @{{ unreadCount + " NEW"}}
                </span>
                <span class="badge"
                    v-if="unreadCount > 99"
                >
                    99+ NEW
                </span>
            </a>
        </li> --}}
        
        {{-- Notifications --}}
        <li id="notification-main-container">
            <a v-cloak href="#!" id="notification-icon"
                class="dropdown-button tooltipped"
                data-beloworigin="true"
                data-hover="false"
                data-alignment="right"
                data-position="bottom"
                data-tooltip="Notifications"
                data-activates="notification-dropdown"
                @click.prevent="getNotificationInstances"
            >
                <i class="material-icons"
                    :class="notificationCount > 0 ? 'left' : '' "
                >
                    notifications
                </i>
                <span class="badge"
                    v-if="notificationCount > 0 && notificationCount <= 99"
                >
                    @{{ notificationCount }}
                </span>
                <span class="badge"
                    v-if="notificationCount > 99"
                >
                    99+
                </span>
            </a>
            {{-- Notification --}}
            <ul id="notification-dropdown" class="dropdown-content collection">
                <div id="notification-preloader-circular" class="row">
                    <div class="center-align">
                        <div class="preloader-wrapper small active">
                            <div class="spinner-layer spinner-blue-only">
                                <div class="circle-clipper left">
                                    <div class="circle"></div>
                                </div>
                                <div class="gap-patch">
                                    <div class="circle"></div>
                                </div>
                                <div class="circle-clipper right">
                                    <div class="circle"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <li>
                    <ul id="notification-container" class="collection">
                        <li v-for="(notification,index) in notifications"
                            style="overflow:auto;"
                            class="collection-item"
                        >
                            <a class="black-text"
                                :href="notification.url"
                                @click.prevent="goToNotification(index)"
                            >
                                <span class="left" v-if="!notification.read_at">
                                    <i class="material-icons indigo-text text-darken-2" style="font-size:1rem;">radio_button_checked</i>
                                </span>
                                <span class="left" v-else >
                                    <i class="material-icons indigo-text text-darken-2" style="font-size:1rem;">radio_button_unchecked</i>
                                </span>
                                <p style="margin-left:1.5rem;" :class=" (notification.read_at) ? 'grey-text' : '' ">
                                    <span v-html="notification.data.description"></span>
                                </p>
                                <p class="right-align grey-text text-darken-1" style="margin-left:1.5rem; font-size:0.8rem;"> @{{ notification.data.time.date | transformToReadableDate }} </p>
                            </a>
                        </li>

                    </ul>
                </li>
                <li>
                    <a href="{{ route('bNotifs') }}" class="center-align">See all Notifications</a>
                </li>
            </ul>
        </li>
    @endif
@endsection

@section('navbarDropdown')
  @if(!Auth::user()->update_profile)
  @endif
@endsection

@section('content')
    <d class="row">
      <div class="col l2 primary hide-on-med-and-down"
        style="height: 100vh;
          position: fixed;
          top: 1px;
          padding: 0;
          border-bottom: solid 1px red;
        "
      >
      
        <div style="margin-top: 4rem;" class="teal accent-3 divider"></div>
        
        <div style="border-style: none;" class="collection">
          <a href="{{ route('dashboard', ['farm_address' => 'all-farms']) }}" style="border-style: none; font-weight: 700;"
            class="primary primary-hover collection-item white-text"
          >
            <i style="padding: 2px;" class="material-icons left">assessment</i>
            <span style="font-size: 1.375rem;">Dashboard</span>
          </a>

          <a href="{{ route('dashboard.productStatus') }}" style="border-style: none; padding-left: 4rem; font-size: 1.125rem; font-weight: 600;"
            class="primary primary-hover collection-item white-text"
          >
            Orders
          </a>
          <a href="{{route('products',['type' => 'all-type', 'status' => 'all-status', 'sort' => 'none'])}}" style="border-style: none; padding-left: 4rem; font-size: 1.125rem; font-weight: 600;"
            class="primary primary-hover collection-item white-text"
          >
            Product Management
          </a>
          <a href="{{ route('dashboard.reviews') }}" style="border-style: none; padding-left: 4rem; font-size: 1.125rem; font-weight: 600;"
            class="primary primary-hover collection-item white-text"
          >
            Your Reviews
          </a>
          <a href="{{ route('products.create') }}" style="border-style: none; padding-left: 4rem; font-size: 1.125rem; font-weight: 600;"
            class="primary primary-hover collection-item white-text"
          >
            Add Product
          </a>
          
          <div class="teal accent-3 divider"></div>
          
          <a href="{{ route('breeder.messages') }}" style="border-style: none; font-weight: 700;"
            class="primary primary-hover collection-item white-text"
          >
            <i style="padding: 2px; " class="material-icons left">message</i>
            <span style="font-size: 1.375rem;">Messages</span>
          </a>

          <div class="teal accent-3 divider"></div>

          <a href="{{ route('breeder.edit') }}" style="border-style: none; font-weight: 700;"
            class="primary primary-hover collection-item white-text"
          >
            <i style="padding: 2px; " class="material-icons left">settings</i>
            <span style="font-size: 1.375rem;">Account Settings</span>
          </a>

          <div class="teal accent-3 divider"></div>
          
          {{-- Terms and Policy --}}
          <div class="row terms-and-policy-breeder-container">
            <span>
              <a 
                href="{{ route('breeder.getTermsOfAgreement') }}"
                class="terms-and-policy-breeder"
                target="_blank"
              >
                Terms
              </a>
            </span>
            <span>
              <a 
                href="{{ route('breeder.privacyPolicy') }}"
                class="terms-and-policy-breeder"
                target="_blank" 
              >
                Privacy Policy
              </a>
            </span>
          </div>
        </div>
        
        {{-- <div style="border-style: none !important;" class="collection">
          <a href="#"
            style="padding: 0 !important;"
            class="primary primary-hover
              sidenav-main-element
              collection-item"
          >
            <i style="padding: 5px; padding-right: 0;" class="material-icons left">assessment</i>
            <p style="font-weight: 700; padding: 20px; padding-left: 0; margin: 0; font-size: 1.2rem;">
              Dashboard
            </p>
          </a>
          <br>
          <a href="#"
            style="padding: 0 !important;"
            class="primary primary-hover
              sidenav-main-element
              collection-item"
          >
            <i style="padding: 5px; padding-right: 0;" class="material-icons left">assessment</i>
            <p style="font-weight: 700; padding: 20px; padding-left: 0; margin: 0; font-size: 1.2rem;">
              Dashboard
            </p>
          </a>


        </div> --}}
        {{-- 
        <a class="sidenav-main-element" href="{{ route('home_path') }}">
          <div style="box-shadow: none; text-align: left; width: 15.9vw; padding-left: 0;" class="btn-large primary primary-hover side-nav-btn-large">  
            <i class="material-icons left">assessment</i>
            <span style="font-weight: 500; font-size: 1.4rem;">Dashboard</span>
          </div>
        </a>

        <div class="sidenav-sub-element-container">
          
          <li class="sidenav-sub-element">
            <a href="{{ route('dashboard.productStatus') }}"">
              <div class="btn primary primary-hover side-nav-btn">
                Orders
              </div>
            </a>
          </li>

          <li class="sidenav-sub-element">
              <a href="{{route('products',['type' => 'all-type', 'status' => 'all-status', 'sort' => 'none'])}}">
                <div class="btn primary primary-hover side-nav-btn">
                  Product Management
                </div>
              </a>
            </li>

          <li class="sidenav-sub-element">
            <a href="{{ route('dashboard.reviews') }}"">
              <div class="btn primary primary-hover side-nav-btn">
                Your Reviews
              </div>
            </a>
          </li>

          <li class="sidenav-sub-element">
            <a href="{{ route('products.create') }}">
              <div class="btn primary primary-hover side-nav-btn">
                Add Product
              </div>
            </a>
          </li>
        
        </div>
      
        <div style="width: 15.9vw; margin-left: 0;" class="teal accent-3 divider"></div>
        <a class="sidenav-main-element" href="{{ route('breeder.messages') }}">
          <div style="box-shadow: none; text-align: left; width: 15.9vw; padding-left: 0" class="btn-large primary primary-hover side-nav-btn-large">
            <div v-cloak id="message-main-container">
              <i class="material-icons left">message</i>
              <span style="font-weight: 500; font-size: 1.4rem;">
                Messages
              </span>
    
              <span class="message-badge"
                  v-if="unreadCount > 0  && unreadCount <= 99"
              >
                  @{{ unreadCount + " NEW"}}
              </span>
              <span class="message-badge"
                  v-if="unreadCount > 99"
              >
                  99+ NEW
              </span>
            </div>
          </div>
        </a>
        <div style="width: 15.9vw; margin-left: 0;" class="teal accent-3 divider"></div>
      
      
        <a class="sidenav-main-element" href="{{ route('breeder.edit') }}">
          <div style="box-shadow: none; text-align: left; width: 15.9vw; padding-left: 0" class="btn-large primary primary-hover side-nav-btn-large">
            <i class="material-icons left">settings</i>
            <span style="font-weight: 500; font-size: 1.4rem;">
              Account Settings
            </span>
          </div>
        </a>

        <div style="width: 15.9vw; margin-left: 0;" class="teal accent-3 divider"></div> --}}
      </div>
      {{-- <div class="col s2"></div> --}}
      <div class="col s12 l10 offset-l2">
        @yield('breeder-content')
      </div>
    </div>
@endsection

@section('static')
    {{-- Floating Action Button --}}
    {{-- <div class="fixed-action-btn" style="bottom: 30px; right: 24px;">
      <a id="action-button" class="btn-floating btn-large waves-effect waves-light red" style="display:none;" data-position="left" data-delay="50" data-tooltip="More Actions">
        <i class="material-icons">more_vert</i>
        <ul>
            <li><a class="btn-floating waves-effect waves-light red darken-4 tooltipped delete-selected-button" data-position="left" data-delay="50" data-tooltip="Delete all chosen"><i class="material-icons">delete</i></a></li>
            @if(!empty($filters['hidden']))
                Only show when products are unshowcased
                <li><a class="btn-floating waves-effect waves-light teal ligthen-2 tooltipped display-selected-button" data-position="left" data-delay="50" data-tooltip="Display all chosen"><i class="material-icons">visibility</i></a></li>
            @elseif(!empty($filters['displayed']))
                Only show when products are showcased
                <li><a class="btn-floating waves-effect waves-light teal ligthen-2 tooltipped hide-selected-button" data-position="left" data-delay="50" data-tooltip="Hide all chosen"><i class="material-icons">visibility_off</i></a></li>
            @endif
            <li><a href="#" class="btn-floating modal-trigger waves-effect waves-light green tooltipped select-all-button" data-tooltip="Select all Products" data-position="left" data-delay="50"><i class="material-icons">event_available</i></a></li>
            <li><a href="#" class="btn-floating modal-trigger waves-effect waves-light blue tooltipped add-product-button" data-position="left" data-delay="50" data-tooltip="Add product"><i class="material-icons">add</i></a></li>
        </ul>
      </a>
    </div> --}}
@endsection

@section('initScript')
    <script src="{{ elixir('/js/breeder/custom.js') }}"></script>
@endsection
