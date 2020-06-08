{{--
    Displays products of the respective Breeder user
--}}

@extends('user.breeder.home')

@section('title')
| Breeder - Product Inventory & Status
@endsection

@section('pageId')
id="page-breeder-product-status"
@endsection

@section('breadcrumbTitle')
<div class="breadcrumb-container">
  Orders
</div>
@endsection

@section('breadcrumb')
<div class="breadcrumb-container">
  <a href="{{ route('home_path') }}" class="breadcrumb">Home</a>
</div>
@endsection

@section('breeder-content')
<!-- <div class="row">
        <div class="col s12">
            <p class="caption">
                See what's happening with your products. <br>
            </p>
        </div>
    </div> -->

<div id="product-status-container" class="row">
  <div class="row">
    <div id="status-select" class="input-field col left">
      <custom-status-select v-model="statusFilter" @status-select="statusChange"> </custom-status-select>
    </div>
    <form class="col s4 right">
      <div class="input-field col s12">
        <i class="material-icons prefix" style="padding-top: 2vh;">search</i>
        <input type="text" style="padding-left: 10px;" name="search" v-model="searchQuery"
          placeholder="Search for a product" autocomplete="off">
      </div>

    </form>
  </div>
  <status-table :token="'{{ $token }}'" :products="products" :filter-query="searchQuery" :status-filter="statusFilter"
    @update-product="updateProduct">
  </status-table>
</div>

{{-- Template for the <status-table> component --}}
<template id="status-table-template">
  <div class="">
    <table id="product-status-table" class="striped bordered">
      <thead>
        <tr>
          <td style="cursor: pointer; font-size: 3vh;" @click="sortBy('name')"
            :class="sortKey == 'name' ? 'table-header-bold' : '' ">
            <span class="">
              PRODUCT INFORMATION
              <i style="margin-left: 0 !important;" class="material-icons prefix"
                :class="isProductInformationUpActive === true ? 'blue-text' : 'black-text'">keyboard_arrow_up</i>
              <i class="material-icons prefix"
                :class="isProductInformationUpActive === false ? 'blue-text' : 'black-text'">keyboard_arrow_down</i>
            </span>
          </td>
          <td style="cursor: pointer; font-size: 3vh;" @click="sortBy('status')"
            :class="sortKey == 'status' ? 'table-header-bold' : '' ">
            <span class="">
              STATUS
              <!-- <i class="material-icons prefix" v-if="sortOrders['status'] > 0">keyboard_arrow_up</i>
                                <i class="material-icons prefix" v-else>keyboard_arrow_down</i> -->

              <i style="margin-left: 0 !important;" class="material-icons prefix"
                :class="isStatusUpActive === true ? 'blue-text' : 'black-text'">keyboard_arrow_up</i>
              <i class="material-icons prefix"
                :class="isStatusUpActive === false ? 'blue-text' : 'black-text'">keyboard_arrow_down</i>
            </span>
          </td>
          <td style="font-size: 3vh;"> ACTIONS </td>
        </tr>
      </thead>
      <tbody v-if="filteredProducts.length">
        <tr v-for="product in filteredProducts">
          <td>
            <div class="row">
              <div class="col s2 center-align">
                <a>
                  <img v-bind:src="product.img_path" width="75" height="75" class="circle" />
                </a>
              </div>
              <div class="col s4">
                <span style="font-weight: 700; color:hsl(0, 0%, 13%);">@{{ product.name }}</span><br>
                <span style="color:hsl(0, 0%, 29%);">@{{ product.type | capitalize }} - @{{ product.breed }}</span>
                <template v-if="product.reservation_id && product.type === 'semen'">
                  <p style="color:hsl(0, 0%, 29%);">Quantity: @{{ product.quantity }}</p>
                </template>
              </div>
              <div class="col s6">
                <template v-if="product.customer_name">
                  <span style="cursor:pointer; font-weight: 700; color:hsl(0, 0%, 13%);"
                    @click.prevent="showCustomerInfo(product.customer_id, product.customer_name)">
                    @{{ product.customer_name }}
                  </span>
                  <br>
                  <a href="#" class="anchor-title blue-text" style="font-weight: 700;"
                    @click.prevent="showReservationDetails(product.uuid)">
                    RESERVATION DETAILS
                  </a> <br>
                </template>

                <template v-if="product.customer_name">
                  <a class="btn teal darken-3 tooltipped" :href="'{{ route('breeder.messages') }}/' + product.userid"
                    :data-breeder-id="product.breeder_id" :data-customer-id="product.customer_id" data-position="top"
                    data-delay="50" :data-tooltip="'Message ' + product.customer_name">
                    Message
                  </a>
                </template>

              </div>
            </div>
          </td>
          <td>
            @{{ product.status | transformToReadableStatus }}
            <br>
            <span class="grey-text" v-if="product.status_time">
              @{{ product.status_time | transformDate }}
            </span>
            <template v-if="product.status === 'on_delivery'">
              <br>
              Expected to arrive on @{{ product.delivery_date }}
            </template>
          </td>
          <td>
            {{--  If product's status is requested --}}
            <a class="btn teal darken-3" style="width: 13vw;" href="#"
              @click.prevent="getProductRequests(product.uuid, $event)" v-if="product.status == 'requested'">
              See Requests
            </a>

            {{-- If product's status is reserved --}}
            <template v-if="product.status == 'reserved'">
              <a class="waves-effect waves-light btn primary tooltipped" data-position="right"
                data-tooltip="Send for delivery" style="margin-bottom:1rem; width: 13vw;" href="#"
                @click.prevent="setUpConfirmation(product.uuid,'delivery')">
                Send
              </a> <br>
              <a class="btn red darken-4 tooltipped red-button" data-position="right" data-tooltip="Cancel transaction"
                style="margin-bottom:1rem; width: 13vw;" href="#"
                @click.prevent="setUpConfirmation(product.uuid,'cancel_transaction')">
                Cancel
              </a>
            </template>

            {{-- If product's status is on_delivery --}}
            <template v-if="product.status == 'on_delivery'">
              <a class="btn teal darken-3 tooltipped" style="margin-bottom:1rem; width: 13vw;" data-position="right"
                data-tooltip="Confirm sold" href="#" @click.prevent="setUpConfirmation(product.uuid,'sold')">
                Confirm
              </a> <br>
              <a class="btn red accent-2 tooltipped" style="margin-bottom:1rem; width: 13vw;" data-position="right"
                data-tooltip="Cancel transaction" href="#"
                @click.prevent="setUpConfirmation(product.uuid,'cancel_transaction')">
                Cancel
              </a>
            </template>

            {{-- If product's status is sold --}}
            <template v-if="product.status == 'sold'">
              <span class="teal-text">
                (SOLD)
              </span>
            </template>
          </td>
        </tr>
      </tbody>
      <tbody v-else>
        <tr>
          <td colspan="3" class="center">No products found.</td>
        </tr>
      </tbody>
    </table>

    {{-- Product Requests Modal --}}
    <div id="product-requests-modal" class="modal modal-fixed-footer"
      style="width: 75% !important; height: 60%; overflow-x: auto;">
      <div class="modal-content">
        <span style="font-size: 2.5rem;">Product Request for:&ensp;</span>
        <span style=" font-weight: 700;
                                font-size: 2.5rem;
                                color: hsl(0, 0%, 29%);">
          @{{ productRequest.productName }}
          (@{{ productRequest.type | capitalize }} - @{{ productRequest.breed }})
        </span>
        <br><br>
        <table class="responsive-table bordered highlight">
          <thead>
            <tr>
              <td style="color: hsl(0, 0%, 60%);"> Name </td>
              <td style="color: hsl(0, 0%, 60%);"> Province </td>
              <td style="color: hsl(0, 0%, 60%);"> Other Request </td>
              <td style="color: hsl(0, 0%, 60%);" class="right-align"> Quantity </td>
              <td style="color: hsl(0, 0%, 60%);" class="right-align" v-show="productRequest.type === 'semen'"> Date
                Needed </td>
              <td style="color: hsl(0, 0%, 60%);" class="center-align"> Actions </td>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(customer, index) in productRequest.customers">
              <td>
                <span class="blue-text" style="cursor:pointer; font-weight: 600;"
                  @click.prevent="showCustomerInfo(customer.customerId, customer.customerName)">
                  @{{ customer.customerName }}
                </span>
              </td>
              <td> @{{ customer.customerProvince }} </td>
              <td style="width: 15vw; word-break: break-word;">
                <p>
                  @{{ customer.specialRequest }}
                </p>
              </td>
              <td style="width: 2vw;" class="center-align"> @{{ customer.requestQuantity }} </td>
              <td class="right-align" v-show="productRequest.type === 'semen'"> @{{ customer.dateNeeded }} </td>
              <td class="center-align">
                <a href="#!" class="btn tooltipped teal darken-3" style="margin-bottom:1rem; width: 10vw;"
                  data-position="top" data-delay="50" :data-tooltip="'Reserve product to ' + customer.customerName"
                  @click.prevent="confirmReservation(index)">
                  <b>Reserve</b>
                </a> <br>
                <a v-bind:href="'{{ route('breeder.messages') }}/' + customer.userId"
                  class="btn tooltipped primary white-text" style="width: 10vw;" data-position="top" data-delay="50"
                  :data-tooltip="'Send message to ' + customer.customerName">
                  <b>Message Customer</b>
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer" style="background: hsl(0, 0%, 97%);
                            border: none !important;">
        <a style="text-transform: none;" class="modal-action modal-close waves-effect waves-green btn-flat">Close</a>
      </div>
    </div>

    {{-- Reserve Product Confirmation Modal --}}
    <div id="reserve-product-confirmation-modal" class="modal">
      <div class="modal-content">
        <h5>Reserve <b>@{{ productRequest.productName }}</b> to @{{ productReserve.customerName }}?</h5>
      </div>
      <div class="modal-footer" style="background: hsl(0, 0%, 97%);">
        <a style="text-transform: none;"
          class="modal-action modal-close waves-effect waves-green btn-flat reserve-product-buttons">Close</a>
        <a class="modal-action waves-effect waves-green blue btn reserve-product-buttons"
          @click.prevent="reserveToCustomer($event)"><b>Yes, reserve it</b></a>
      </div>
    </div>

    {{-- Cancel Transaction Confirmation Modal --}}
    <div id="cancel-transaction-confirmation-modal" class="modal" style="width: 60% !important;">
      <div class="modal-content">
        <h5>Are you sure you want to cancel transaction on product: <b>@{{ productInfoModal.productName }}</b> to
          @{{ productInfoModal.customerName }}?</h5>
        <div>
          <blockquote class="warning">
            Once this action is done, it cannot be reverted.
          </blockquote>
        </div>
      </div>
      <div class="modal-footer">
        <a style="text-transform: none;"
          class="modal-action modal-close waves-effect waves-green btn-flat cancel-transaction">Close</a>
        <a class="modal-action waves-effect waves-green btn red darken-4 cancel-transaction"
          @click.prevent="productCancelTransaction($event)"><b>Yes, cancel transaction</b></a>
      </div>
    </div>

    {{-- Product Delivery Confirmation Modal --}}
    <div id="product-delivery-confirmation-modal" class="modal" style="overflow-y: hidden; max-height: 90%;">
      <div class="modal-content">
        <h4>Deliver <b>@{{ productInfoModal.productName }}</b> to @{{ productInfoModal.customerName }}?</h4>
        <div>
          <div class="row">
            <div class="col s6" style="color: hsl(0, 0%, 29%);">
              <br>
              Product will be delivered on or before
            </div>

            <div class="col s3" style="color: #00705E;">
              <custom-date-select v-model="productInfoModal.deliveryDate" @date-select="dateChange">
              </custom-date-select>
            </div>
          </div>
        </div>
      </div>
      <br><br><br><br><br><br><br><br><br><br>
      <div class="modal-footer" style="background: hsl(0, 0%, 97%);">
        <a style="text-transform: none;"
          class="modal-action modal-close waves-effect waves-green btn-flat delivery-product-buttons">Close</a>
        <a class="modal-action waves-effect waves-green btn primary delivery-product-buttons"
          @click.prevent="productOnDelivery($event)">Yes, deliver the product</a>
      </div>
    </div>

    {{-- Sold Product Confirmation Modal --}}
    <div id="sold-product-confirmation-modal" class="modal">
      <div class="modal-content">
        <h5>Confirm that the product: <b>@{{ productInfoModal.productName }}</b> was sold to
          <b>@{{ productInfoModal.customerName }}</b>?</h5>
      </div>
      <div class="modal-footer" style="background: hsl(0, 0%, 97%);">
        <a style="text-transform: none;"
          class="modal-action modal-close waves-effect waves-green btn-flat sold-product-buttons">Close</a>
        <a class="modal-action waves-effect waves-green btn blue sold-product-buttons"
          @click.prevent="productOnSold($event)"><b>Yes, it is sold</b></a>
      </div>
    </div>

    {{--  Product Reservation Details modal --}}
    <div id="product-reservation-details-modal" class="modal">
      <div class="modal-content">
        <h4>@{{ reservationDetails.productName }} Reservation Details</h4>
        <p style="color:hsl(0, 0%, 40%);">
          @{{ reservationDetails.type | capitalize }} - @{{ reservationDetails.breed }}
        </p>
        <table>
          <thead>
            <tr> </tr>
          </thead>
          <tbody style="color:hsl(0, 0%, 29%);">
            <tr>
              <th> Customer Name </th>
              <td> @{{ reservationDetails.customerName }} </td>
            </tr>
            <tr v-show="reservationDetails.type === 'semen'">
              <th> Date Needed </th>
              <td> @{{ reservationDetails.dateNeeded }} </td>
            </tr>
            <tr>
              <th> Special Request </th>
              <td> @{{ reservationDetails.specialRequest }} </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <a style="text-transform: none;" class="modal-action modal-close waves-effect waves-green btn-flat ">Close</a>
      </div>
    </div>

    {{-- Customer info modal --}}
    <div id="customer-info-modal" class="modal">
      <div class="modal-content">
        <h4>Customer Details</h4>
        <table>
          <thead>
            <tr> </tr>
          </thead>
          <tbody style="color: hsl(0, 0%, 29%);">
            <tr>
              <th> Customer Name </th>
              <td> @{{ customerInfo.name }} </td>
            </tr>
            <tr>
              <th> Address </th>
              <td>
                @{{ customerInfo.addressLine1 }} <br>
                @{{ customerInfo.addressLine2 }} <br>
                @{{ customerInfo.province }}
              </td>
            </tr>
            <tr>
              <th> Mobile </th>
              <td> @{{ customerInfo.mobile }} </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <a style="text-transform: none;" class="modal-action modal-close waves-effect waves-green btn-flat ">Close</a>
      </div>
    </div>

  </div>
</template>
@endsection

@section('customScript')
<script type="text/javascript">
  // Variables
  var rawProducts = {!! $products !!};
  $(document).ready(function () {
    $('#status-select').on('click', function (event) {
      event.stopPropagation();
    });
  });
</script>
<script src="{{ elixir('/js/breeder/dashboardProductStatus.js') }}"></script>
@endsection