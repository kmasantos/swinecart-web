<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;

// Should this job be used, just uncomment
// use App\Jobs\AddToTransactionLog;
use App\Jobs\NotifyUser;
use App\Jobs\SendSMS;
use App\Jobs\SendToPubSubServer;
use App\Models\Breeder;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Breed;
use App\Models\SwineCartItem;
use App\Models\FarmAddress;
use App\Models\Image;
use App\Models\TransactionLog;

use Auth;

class DashboardRepository
{
    use CustomHelpers {
        transformBreedSyntax as private;
        transformDateSyntax as private;
        computeAge as private;
    }

    /**
     * Get all of the products for the Customer
     *
     * @return Collection
     */
    public function forCustomer()
    {
        return Product::all();
    }

    /**
     * Get all of the products of a given Breeder
     *
     * @param  Breeder      $breeder
     * @return Collection
     */
    public function forBreeder(Breeder $breeder)
    {
        $products = $breeder->products()->where('status','requested')->get();
        $reservations = $breeder->reservations()->with('product')->get();
        $items = [];

        // Include all "requested" products
        foreach ($products as $product) {
            if($product->quantity == 0) continue;
            $itemDetail = [];
            $itemDetail['uuid'] = (string) Uuid::uuid4();
            $itemDetail['id'] = $product->id;
            $itemDetail['reservation_id'] = 0;
            $itemDetail['img_path'] = route('serveImage', ['size' => 'small', 'filename' => Image::find($product->primary_img_id)->name]);
            $itemDetail['breeder_id'] = $product->breeder_id;
            $itemDetail['farm_province'] = FarmAddress::find($product->farm_from_id)->province;
            $itemDetail['name'] = $product->name;
            $itemDetail['type'] = $product->type;
            $itemDetail['age'] = $this->computeAge($product->birthdate);
            $itemDetail['breed'] = $this->transformBreedSyntax(Breed::find($product->breed_id)->name);
            $itemDetail['quantity'] = $product->quantity;
            $itemDetail['is_unique'] = $product->is_unique;
            $itemDetail['adg'] = $product->adg;
            $itemDetail['fcr'] = $product->fcr;
            $itemDetail['bft'] = $product->backfat_thickness;
            $itemDetail['status'] = $product->status;
            $itemDetail['status_time'] = '';
            $itemDetail['customer_id'] = 0;
            $itemDetail['customer_name'] = '';
            $itemDetail['date_needed'] = '';
            $itemDetail['special_request'] = '';
            $itemDetail['delivery_date'] = '';
            array_push($items, (object)$itemDetail);
        }

        // Include "reserved" / "on_delivery" products
        foreach ($reservations as $reservation) {
            $product = $reservation->product;

            $itemDetail = [];
            $itemDetail['uuid'] = (string) Uuid::uuid4();
            $itemDetail['id'] = $product->id;
            $itemDetail['reservation_id'] = $reservation->id;
            $itemDetail['img_path'] = route('serveImage', ['size' => 'small', 'filename' => Image::find($product->primary_img_id)->name]);
            $itemDetail['breeder_id'] = $product->breeder_id;
            $itemDetail['farm_province'] = FarmAddress::find($product->farm_from_id)->province;
            $itemDetail['name'] = $product->name;
            $itemDetail['type'] = $product->type;
            $itemDetail['age'] = $this->computeAge($product->birthdate);
            $itemDetail['breed'] = $this->transformBreedSyntax(Breed::find($product->breed_id)->name);
            $itemDetail['quantity'] = $reservation->quantity;
            $itemDetail['is_unique'] = $product->is_unique;
            $itemDetail['adg'] = $product->adg;
            $itemDetail['fcr'] = $product->fcr;
            $itemDetail['bft'] = $product->backfat_thickness;
            $itemDetail['status'] = $reservation->order_status;
            $itemDetail['status_time'] = $reservation->transactionLogs->
                where('status', $reservation->order_status)->
                sortByDesc('created_at')->first()->created_at;
            $itemDetail['customer_id'] = $reservation->customer_id;
            $itemDetail['customer_name'] = Customer::find($reservation->customer_id)->users()->first();
            $itemDetail['customer_name'] = $itemDetail['customer_name']['name'];
            $itemDetail['userid'] = Customer::find($reservation->customer_id)->users()->first()->id;
            $itemDetail['date_needed'] = $this->transformDateSyntax($reservation->date_needed);
            $itemDetail['special_request'] = $reservation->special_request;
            $itemDetail['delivery_date'] = $this->transformDateSyntax($reservation->delivery_date);
            array_push($items, (object)$itemDetail);
        }

        return collect($items)->toJson();
    }

    /**
     * Get the number statuses of the products of a Breeder
     * Include hidden, displayed, requested,
     * reserved, on_delivery,
     * and sold quantity
     *
     * @param  Breeder  $breeder
     * @return Array
     */
    public function getProductNumberStatus(Breeder $breeder, $status)
    {

        if($status == 'hidden' || $status == 'displayed' || $status == 'requested'){
            $products = $breeder->products;

            $boarQuery = $products->where('status',$status)->where('type','boar')->where('quantity', '<>', 0);
            $sowQuery = $products->where('status',$status)->where('type','sow')->where('quantity', '<>', 0);
            $giltQuery = $products->where('status',$status)->where('type','gilt')->where('quantity', '<>', 0);
            $semenQuery = $products->where('status',$status)->where('type','semen')->where('quantity', '<>', 0);

            return [
                'boar' => $boarQuery->count(),
                'sow' => $sowQuery->count(),
                'gilt' => $giltQuery->count(),
                'semen' => $semenQuery->count()
            ];
        }
        else{
            $reservations = $breeder->reservations()->with('product')->get();

            foreach ($reservations as $reservation) {
                $reservation->type = $reservation->product->type;
            }

            $boarQuery = $reservations->where('order_status',$status)->where('type','boar');
            $sowQuery = $reservations->where('order_status',$status)->where('type','sow');
            $giltQuery = $reservations->where('order_status',$status)->where('type','gilt');
            $semenQuery = $reservations->where('order_status',$status)->where('type','semen');

            return [
                'boar' => $boarQuery->count(),
                'sow' => $sowQuery->count(),
                'gilt' => $giltQuery->count(),
                'semen' => $semenQuery->count()
            ];

        }

    }

    /**
     * Get sold products of Breeder on a specified time frequency
     *
     * @param  Request  $request
     * @param  Breeder  $breeder
     * @return Array
     */
    public function getSoldProducts($request, Breeder $breeder)
    {

        $dateFrom = Carbon::createFromFormat('Y-m-d', $request->dateFrom);
        $dateTo = $request->dateTo ? Carbon::createFromFormat('Y-m-d', $request->dateTo) : '';
        $soldData = [
            'title' => '',
            'labels' => [],
            'dataSets' => [
                [], [], [], []
            ],
        ];

        // Fetch sold products data depending on the chosen frequency
        switch ($request->frequency) {
            case 'monthly':
                // Get boar, sow, gilt, semen count for the months specified
                $monthFromInt = $dateFrom->month;
                $monthToInt = $dateTo->month;

                // Make sure to get the correct month difference
                $diff = ($monthFromInt <= $monthToInt) ? $monthToInt - $monthFromInt : ($monthToInt + 12) - $monthFromInt;
                $currentDate = $dateFrom;

                $soldData['title'] = 'Sales Performance for the month of ' . $dateFrom->format('F Y') . ' - ' . $dateTo->format('F Y');

                for ($i = 0; $i < $diff + 1; $i++) {

                    $boarQuery = $breeder->transactionLogs()
                        // ->with('product.farmFrom')
                        ->where('status','sold')
                        ->whereYear('created_at', $currentDate->year)
                        ->whereMonth('created_at', $currentDate->month)
                        ->whereHas('product', function($query){
                            $query->where('type','boar');
                        });

                    $sowQuery = $breeder->transactionLogs()
                        ->where('status','sold')
                        ->whereYear('created_at', $currentDate->year)
                        ->whereMonth('created_at', $currentDate->month)
                        ->whereHas('product', function($query){
                            $query->where('type','sow');
                        });

                    $giltQuery = $breeder->transactionLogs()
                        ->where('status','sold')
                        ->whereYear('created_at', $currentDate->year)
                        ->whereMonth('created_at', $currentDate->month)
                        ->whereHas('product', function($query){
                            $query->where('type','gilt');
                        });

                    $semenQuery = $breeder->transactionLogs()
                        ->where('status','sold')
                        ->whereYear('created_at', $currentDate->year)
                        ->whereMonth('created_at', $currentDate->month)
                        ->whereHas('product', function($query){
                            $query->where('type','semen');
                        });

                    array_push($soldData['labels'], $currentDate->format('M \'y'));
                    // dataSets refer to boar, sow, gilt, and semen respectively
                    array_push($soldData['dataSets'][0], $boarQuery->count());
                    array_push($soldData['dataSets'][1], $sowQuery->count());
                    array_push($soldData['dataSets'][2], $giltQuery->count());
                    array_push($soldData['dataSets'][3], $semenQuery->count());

                    $currentDate->addMonth();
                }

                break;

            case 'weekly':
                // Get boar, sow, gilt, semen count for the weeks specified

                $startDay = $dateFrom;
                $startDay->day = 1;
                $endDay = $startDay->copy()->addWeek();
                $endDayOfMonth = Carbon::create($startDay->year, $startDay->month, $startDay->daysInMonth);

                $soldData['title'] = 'No. of Products Sold Weekly on ' . $startDay->format('F Y');

                for ($i = 0; $i < ($startDay->daysInMonth/7) ; $i++) {

                    if($endDay->gte($endDayOfMonth)) $endDay = $endDayOfMonth;

                    $boarQuery = $breeder->transactionLogs()
                        ->where('status','sold')
                        ->whereDate('created_at', '>=', $startDay->format('Y-m-d'))
                        ->whereDate('created_at', '<', $endDay->format('Y-m-d'))
                        ->whereHas('product', function($query){
                            $query->where('type','boar');
                        });

                    $sowQuery = $breeder->transactionLogs()
                        ->where('status','sold')
                        ->whereDate('created_at', '>=', $startDay->format('Y-m-d'))
                        ->whereDate('created_at', '<', $endDay->format('Y-m-d'))
                        ->whereHas('product', function($query){
                            $query->where('type','sow');
                        });

                    $giltQuery = $breeder->transactionLogs()
                        ->where('status','sold')
                        ->whereDate('created_at', '>=', $startDay->format('Y-m-d'))
                        ->whereDate('created_at', '<', $endDay->format('Y-m-d'))
                        ->whereHas('product', function($query){
                            $query->where('type','gilt');
                        });

                    $semenQuery = $breeder->transactionLogs()
                        ->where('status','sold')
                        ->whereDate('created_at', '>=', $startDay->format('Y-m-d'))
                        ->whereDate('created_at', '<', $endDay->format('Y-m-d'))
                        ->whereHas('product', function($query){
                            $query->where('type','semen');
                        });

                    array_push($soldData['labels'], $startDay->format('M j'). ' - ' . $endDay->format('M j'));
                    // dataSets refer to boar, sow, gilt, and semen respectively
                    array_push($soldData['dataSets'][0], $boarQuery->count());
                    array_push($soldData['dataSets'][1], $sowQuery->count());
                    array_push($soldData['dataSets'][2], $giltQuery->count());
                    array_push($soldData['dataSets'][3], $semenQuery->count());

                    $startDay->addWeek();
                    $endDay->addWeek();
                }

                break;

            case 'daily':
                // Get boar, sow, gilt, semen count for the days specified
                $dayFromInt = $dateFrom->dayOfWeek;
                $dayToInt = $dateTo->dayOfWeek;

                // Make sure to get the correct month difference
                $diff = ($dayFromInt <= $dayToInt) ? $dayToInt - $dayFromInt : ($dayToInt + 7) - $dayFromInt;
                $currentDate = $dateFrom;

                $soldData['title'] = 'No. of Products Sold Daily from ' . $dateFrom->format('M j, Y') . ' - ' . $dateTo->format('M j, Y');

                for ($i = 0; $i < $diff + 1; $i++) {

                    $boarQuery = $breeder->transactionLogs()
                        ->where('status','sold')
                        ->whereDate('created_at', $currentDate->format('Y-m-d'))
                        ->whereHas('product', function($query){
                            $query->where('type','boar');
                        });

                    $sowQuery = $breeder->transactionLogs()
                        ->where('status','sold')
                        ->whereDate('created_at', $currentDate->format('Y-m-d'))
                        ->whereHas('product', function($query){
                            $query->where('type','sow');
                        });

                    $giltQuery = $breeder->transactionLogs()
                        ->where('status','sold')
                        ->whereDate('created_at', $currentDate->format('Y-m-d'))
                        ->whereHas('product', function($query){
                            $query->where('type','gilt');
                        });

                    $semenQuery = $breeder->transactionLogs()
                        ->where('status','sold')
                        ->whereDate('created_at', $currentDate->format('Y-m-d'))
                        ->whereHas('product', function($query){
                            $query->where('type','semen');
                        });

                    array_push($soldData['labels'], $currentDate->format('M j (D)'));
                    // dataSets refer to boar, sow, gilt, and semen respectively
                    array_push($soldData['dataSets'][0], $boarQuery->count());
                    array_push($soldData['dataSets'][1], $sowQuery->count());
                    array_push($soldData['dataSets'][2], $giltQuery->count());
                    array_push($soldData['dataSets'][3], $semenQuery->count());

                    $currentDate->addDay();
                }

                break;

            default: break;
        }

        return $soldData;
    }

    /**
     * Get the summary of reviews and ratings of the Breeder.
     * Include overall, delivery,
     * transaction, and product
     * quality rating
     *
     * @param  Breeder  $breeder
     * @return Array
     */
    public function getSummaryReviewsAndRatings(Breeder $breeder)
    {
        $reviewDetails = [];
        $query = $breeder->reviews()->orderBy('created_at','desc')->get();
        $reviewsSize = $query->count();
        $reviews = $query->take(3);
        $deliveryRating = $query->avg('rating_delivery');
        $transactionRating = $query->avg('rating_transaction');
        $productQualityRating = $query->avg('rating_productQuality');
        $overallRating = ($deliveryRating + $transactionRating + $productQualityRating)/3;

        foreach ($reviews as $review) {
            $reviewDetail = [];
            $reviewDetail['customerName'] = Customer::find($review->customer_id)->users()->first()->name;
            $reviewDetail['comment'] = $review->comment;
            array_push($reviewDetails, $reviewDetail);
        }

        return [
            'overall' => round($overallRating,2),
            'delivery' => round($deliveryRating,1),
            'transaction' => round($transactionRating,1),
            'productQuality' => round($productQualityRating,1),
            'reviewsSize' => $reviewsSize,
            'reviews' => $reviewDetails
        ];
    }

    /**
     * Get Customers who requested for a respective Product
     *
     * @param  Integer   $productId
     * @return Array
     */
    public function getProductRequests($productId)
    {
        $productRequests = SwineCartItem::where('product_id', $productId)->where('if_requested', 1)->where('reservation_id', 0)->get();
        $productRequestDetails = [];

        foreach ($productRequests as $productRequest) {
            $customer = Customer::find($productRequest->customer_id);
            $province = $customer->address_province;
            $name = $customer->users()->first()->name;
            array_push($productRequestDetails,
                [
                    'swineCartId' => $productRequest->id,
                    'customerId' => $productRequest->customer_id,
                    'customerName' => $name,
                    'userId' => $customer->users()->first()->id,
                    'customerProvince' => $province,
                    'requestQuantity' => $productRequest->quantity,
                    'dateNeeded' => ($productRequest->date_needed == '0000-00-00') ? '' : $this->transformDateSyntax($productRequest->date_needed),
                    'specialRequest' => $productRequest->special_request
                ]
            );
        }

        return $productRequestDetails;
    }

    /**
     * Update product status
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return Array/String
     */
    public function updateStatus(Request $request, Product $product)
    {
        switch ($request->status) {
            case 'reserved':
                // Check if product is available for reservations
                if($product->quantity){
                    $customerName = Customer::find($request->customer_id)->users()->first()->name;
                    $breederUser = $product->breeder->users()->first();

                    // Update quantity of product
                    if($product->type != 'semen'){
                        if ($product->is_unique == 1) {
                          $product->quantity = 0;
                          $product->status = 'hidden';
                        }
                        else {
                          // TODO: ask if multiplier is reserved to someone, is it still displayed? or reserved?
                          //$product->status = 'displayed';
                          $product->quantity = $product->quantity - $request->request_quantity;
                        }
                        $product->save();
                    }

                    // Make a product reservation
                    $reservation = new ProductReservation;
                    $reservation->customer_id = $request->customer_id;
                    $reservation->quantity = $request->request_quantity;
                    $reservation->date_needed = date_format(date_create($request->date_needed), 'Y-n-j');
                    $reservation->special_request = $request->special_request;
                    $reservation->order_status = 'reserved';
                    $product->reservations()->save($reservation);

                    // Update the Swine Cart item
                    $swineCartItem = SwineCartItem::find($request->swinecart_id);
                    $swineCartItem->reservation_id = $reservation->id;
                    $swineCartItem->save();

                    $transactionDetails = [
                        'swineCart_id' => $swineCartItem->id,
                        'customer_id' => $request->customer_id,
                        'breeder_id' => $product->breeder_id,
                        'product_id' => $product->id,
                        'status' => 'reserved',
                        'created_at' => Carbon::now()
                    ];

                    $notificationDetailsReserved = [
                        'description' => 'Product <b>' . $product->name . '</b> by <b>' . $breederUser->name . '</b> was <b>reserved</b> to you.',
                        'time' => $transactionDetails['created_at'],
                        'url' => route('cart.items')
                    ];

                    // $smsDetails = [
                    //     'message' => 'SwineCart ['. $this->transformDateSyntax($transactionDetails['created_at'], 1) .']: Product ' . $product->name . ' by ' . $breederUser->name . ' was reserved to you.',
                    //     'recipient' => $swineCartItem->customer->mobile
                    // ];

                    $pubsubData = [
                        'item_id' => $transactionDetails['swineCart_id'],
                        'reserved' => $transactionDetails['created_at']->toDateTimeString()
                    ];

                    $reservedCustomerUser = Customer::find($reservation->customer_id)->users()->first();

                    // Add new Transaction Log
                    $this->addToTransactionLog($transactionDetails);

                    // Queue notifications (SMS, database, notification, pubsub server)
                    // dispatch(new SendSMS($smsDetails['message'], $smsDetails['recipient']));
                    dispatch(new NotifyUser('product-reserved', $reservedCustomerUser->id, $notificationDetailsReserved));
                    dispatch(new SendToPubSubServer('notification', $reservedCustomerUser->email));
                    dispatch(new SendToPubSubServer('sc-reserved', $reservedCustomerUser->email, $pubsubData));
                    dispatch(new SendToPubSubServer('db-reserved', Auth::user()->email, ['product_type' => $product->type]));

                    // If product type is not semen remove other requests to this product
                    $productRequests = SwineCartItem::where('product_id', $product->id)->where('customer_id', '<>', $request->customer_id)->where('reservation_id',0);

                    if($product->type != 'semen' && $product->is_unique == 1){

                        // Notify Customer users that the product has been reserved to another customer
                        foreach ($productRequests->get() as $productRequest) {
                            $customerUser = $productRequest->customer->users()->first();

                            $transactionDetailsOther = [
                                'swineCart_id' => $productRequest->id,
                                'customer_id' => $productRequest->customer_id,
                                'breeder_id' => $product->breeder_id,
                                'product_id' => $product->id,
                                'status' => 'reserved_to_another',
                                'created_at' => Carbon::now()
                            ];

                            $notificationDetailsOther = [
                                'description' => 'Sorry, product <b>' . $product->name . '</b> is <b>already reserved</b>.',
                                'time' => $transactionDetailsOther['created_at'],
                                'url' => route('cart.items')
                            ];

                            $smsDetails = [
                                'message' => 'SwineCart ['. $this->transformDateSyntax($transactionDetailsOther['created_at'], 1) .']: Sorry, product ' . $product->name . ' is already reserved.',
                                'recipient' => $productRequest->customer->mobile
                            ];

                            // Add new Transaction Log.
                            $this->addToTransactionLog($transactionDetailsOther);

                            // Queue notifications (SMS, database, notification, pubsub server)
                            // dispatch(new SendSMS($smsDetails['message'], $smsDetails['recipient']));
                            dispatch(new NotifyUser('product-reserved-to-other-customer', $customerUser->id, $notificationDetailsOther));
                            dispatch(new SendToPubSubServer('notification', $customerUser->email));
                            dispatch(new SendToPubSubServer('sc-reservedToOthers', $customerUser->email, ['item_id' => $transactionDetailsOther['swineCart_id']]));
                        }

                        // Delete requests to this product after notifying Customer users
                        $productRequests->delete();
                    }
                    else{
                        if($productRequests->count() == 0){
                            $product->status = 'displayed';
                            $product->save();

                            return [
                                'success',
                                'Product ' . $product->name . ' reserved to ' . $customerName,
                                $reservation->id,
                                (string) Uuid::uuid4(),
                                true,
                                $transactionDetails['created_at']
                            ];
                        }
                    }

                    // [0] - success/fail operation flag
                    // [1] - toast message
                    // [2] - reservation_id
                    // [3] - generated UUID
                    // [4] - flag for removing the parent product display in the UI component
                    // [5] - timestamp of reservation
                    return [
                        'success',
                        'Product ' . $product->name.' reserved to '.$customerName,
                        $reservation->id,
                        (string) Uuid::uuid4(),
                        false,
                        $transactionDetails['created_at']
                    ];

                } // End of if product is available for reservation
                else {
                    return ['fail', 'Product ' . $product->name.' is already reserved to another customer'];
                }

            case 'on_delivery':

                // Update Reservation
                $reservation = ProductReservation::find($request->reservation_id);
                $reservation->order_status = 'on_delivery';
                $reservation->delivery_date = date_format(date_create($request->delivery_date), 'Y-n-j');
                $reservation->save();

                $customer = Customer::find($reservation->customer_id);
                $breederUser = $product->breeder->users()->first();

                $transactionDetails = [
                    'swineCart_id' => $reservation->swineCartItem->id,
                    'customer_id' => $reservation->customer_id,
                    'breeder_id' => $product->breeder_id,
                    'product_id' => $reservation->product_id,
                    'status' => 'on_delivery',
                    'created_at' => Carbon::now()
                ];

                $notificationDetails = [
                    'description' => 'Product <b>' . $product->name . '</b> by <b>' . $breederUser->name . '</b> is <b>on delivery</b>. It is expected to arrive on <b>' . $request->delivery_date . '</b>.',
                    'time' => $transactionDetails['created_at'],
                    'url' => route('cart.items')
                ];

                // $smsDetails = [
                //     'message' => 'SwineCart ['. $this->transformDateSyntax($transactionDetails['created_at'], 1) .']: Product ' . $product->name . ' by ' . $breederUser->name . ' is on delivery. It is expected to arrive on ' . $request->delivery_date . '.',
                //     'recipient' => $customer->mobile
                // ];

                $pubsubData = [
                    'item_id' => $transactionDetails['swineCart_id'],
                    'on_delivery' => $transactionDetails['created_at']->toDateTimeString(),
                    'delivery_date' => $request->delivery_date
                ];

                $reservedCustomerUser = $customer->users()->first();

                // Add new Transaction Log.
                $this->addToTransactionLog($transactionDetails);

                // Queue notifications (SMS, database, notification, pubsub server)
                // dispatch(new SendSMS($smsDetails['message'], $smsDetails['recipient']));
                dispatch(new NotifyUser('product-reservation-update', $reservedCustomerUser->id, $notificationDetails));
                dispatch(new SendToPubSubServer('notification', $reservedCustomerUser->email));
                dispatch(new SendToPubSubServer('sc-onDelivery', $reservedCustomerUser->email, $pubsubData));
                dispatch(new SendToPubSubServer('db-onDelivery', Auth::user()->email, ['product_type' => $product->type]));

                return [
                    "OK",
                    $transactionDetails['created_at']
                ];

            case 'sold':

                // Update Reservation
                $reservation = ProductReservation::find($request->reservation_id);
                $reservation->order_status = 'sold';
                $reservation->save();

                $customer = Customer::find($reservation->customer_id);
                $breederUser = $product->breeder->users()->first();

                $transactionDetails = [
                    'swineCart_id' => $reservation->swineCartItem->id,
                    'customer_id' => $reservation->customer_id,
                    'breeder_id' => $product->breeder_id,
                    'product_id' => $reservation->product_id,
                    'status' => 'sold',
                    'created_at' => Carbon::now()
                ];

                $notificationDetails = [
                    'description' => 'Product <b>' . $product->name . '</b> by <b>' . $breederUser->name . '</b> is <b>sold</b>.',
                    'time' => $transactionDetails['created_at'],
                    'url' => route('cart.items')
                ];

                // $smsDetails = [
                //     'message' => 'SwineCart ['. $this->transformDateSyntax($transactionDetails['created_at'], 1) .']: Product ' . $product->name . ' by ' . $breederUser->name . ' is sold.',
                //     'recipient' => $customer->mobile
                // ];

                $pubsubData = [
                    'item_id' => $transactionDetails['swineCart_id'],
                    'sold' => $transactionDetails['created_at']->toDateTimeString()
                ];

                $reservedCustomerUser = $customer->users()->first();

                // Add new Transaction Log.
                $this->addToTransactionLog($transactionDetails);

                // Queue notifications (SMS, database, notification, pubsub server)
                // dispatch(new SendSMS($smsDetails['message'], $smsDetails['recipient']));
                dispatch(new NotifyUser(
                    'product-reservation-update',
                    $reservedCustomerUser->id,
                    $notificationDetails
                ));

                dispatch(new SendToPubSubServer(
                    'notification',
                    $reservedCustomerUser->email
                ));

                dispatch(new SendToPubSubServer(
                    'sc-sold',
                    $reservedCustomerUser->email,
                    $pubsubData
                ));

                dispatch(new SendToPubSubServer(
                    'db-sold',
                    Auth::user()->email,
                    [
                        'product_type' => $product->type
                    ]
                ));

                return [
                    "OK",
                    $transactionDetails['created_at']
                ];

            case 'cancel_transaction':

                $reservation = ProductReservation::with('swineCartItem')
                    ->find($request->reservation_id);

                $swineCartItem = $reservation->swineCartItem;
                $customer = $swineCartItem->customer;
                $breederUser = $product->breeder->users()->first();

                // Store previous reservation status
                $oldStatus = $reservation->order_status;

                // Update product
                $product->status = "displayed";
                $product->quantity = ($product->type == 'semen') ? -1 : 1;
                $product->save();

                $transactionDetails = [
                    'swineCart_id' => $swineCartItem->id,
                    'customer_id' => $swineCartItem->customer_id,
                    'breeder_id' => $product->breeder_id,
                    'product_id' => $product->id,
                    'status' => 'cancel_transaction',
                    'created_at' => Carbon::now()
                ];

                $notificationDetails = [
                    'description' => '<b>' . $breederUser->name . ' cancelled</b> your transaction for <b>' . $product->name . '</b> .',
                    'time' => $transactionDetails['created_at'],
                    'url' => route('cart.items')
                ];

                // $smsDetails = [
                //     'message' => 'SwineCart ['. $this->transformDateSyntax($transactionDetails['created_at'], 1) .']: ' . $breederUser->name . ' cancelled your transaction for ' . $product->name . '.',
                //     'recipient' => $swineCartItem->customer->mobile
                // ];

                $customerUser = $swineCartItem->customer->users()->first();

                // Add new Transaction Log
                $this->addToTransactionLog($transactionDetails);

                // Queue notifications (SMS, database, notification, pubsub server)
                // dispatch(new SendSMS($smsDetails['message'], $smsDetails['recipient']));
                dispatch(new NotifyUser(
                    'product-cancel-transaction',
                    $customerUser->id,
                    $notificationDetails
                ));
                
                dispatch(new SendToPubSubServer(
                    'notification', 
                    $customerUser->email
                ));
                
                dispatch(new SendToPubSubServer(
                    'sc-cancelTransaction',
                    $customerUser->email,
                    [
                        'item_id' => $transactionDetails['swineCart_id'],
                        'previous_status' => $oldStatus
                    ]
                ));
                
                dispatch(new SendToPubSubServer(
                    'db-cancelTransaction',
                    Auth::user()->email,
                    [
                        'product_type' => $product->type,
                        'previous_status' => $oldStatus
                    ]
                ));

                // Delete reservation
                $reservation->delete();

                // Remove item from cart

                $swineCartItem->delete();

                // Add same product to Cart
                $new_item = new SwineCartItem;
                $new_item->product_id = $product->id;
                $new_item->quantity = $product->type == 'semen' ? 2 : 1;

                $customer->swineCartItems()->save($new_item);

                return [
                    "OK",
                    $transactionDetails['created_at']
                ];

            default:
                return "Invalid operation";
        }
    }

}
