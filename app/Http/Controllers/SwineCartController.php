<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;

// Should this job be used, just uncomment
// use App\Jobs\AddToTransactionLog;
use App\Jobs\NotifyUser;
use App\Jobs\SendSMS;
use App\Jobs\SendToPubSubServer;
use App\Http\Requests;
use App\Models\Customer;
use App\Models\Breeder;
use App\Models\FarmAddress;
use App\Models\Breed;
use App\Models\Image;
use App\Models\SwineCartItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\TransactionLog;
use App\Models\ProductReservation;
use App\Repositories\CustomHelpers;

use Illuminate\Support\Facades\Log;

use Validator;
use Auth;

class SwineCartController extends Controller
{
    use CustomHelpers {
        transformBreedSyntax as private;
        transformDateSyntax as private;
        computeAge as private;
    }

    protected $user;

    /**
     * Create new CustomerController instance
     */
    public function __construct()
    {
        $this->middleware('role:customer');
        $this->middleware('updateProfile:customer');
        $this->middleware(function($request, $next){
            $this->user = Auth::user();

            return $next($request);
        });
    }

    /**
     * Add to Swine Cart the product picked by the user
     * AJAX
     *
     * @param  Request $request
     * @return Array
     */
    public function addToSwineCart(Request $request)
    {
        if($request->ajax()){
            $customer = $this->user->userable;
            $swineCartItems = $customer->swineCartItems();
            $checkProduct = $swineCartItems->where('product_id',$request->productId)->where('reservation_id', 0)->get();

            // Check first if product is already in Swine Cart
            if(!$checkProduct->isEmpty()){
                // Then check if it is already requested
                if($checkProduct->first()->ifRequested) $returnArray = ['requested', Product::find($request->productId)->name];
                else $returnArray = ['fail', Product::find($request->productId)->name];
                return $returnArray;
            }
            else{
                $item = new SwineCartItem;
                $item->product_id = $request->productId;
                if(Product::find($request->productId)->type == 'semen') $item->quantity = 2;
                else $item->quantity = Product::find($request->productId)->quantity;

                $swineCartItems->save($item);

                $returnArray = ['success', Product::find($request->productId)->name, $customer->swineCartItems()->where('if_requested',0)->count()];
                return $returnArray;
            }
        }
    }

    /**
     * Requests item from Swine Cart
     * AJAX
     *
     * @param  Request $request
     * @return Array
     */
    public function requestSwineCartItem(Request $request)
    {

        if ($request->ajax()) {
            Log::info($request->all());

            if ($request->productType == "semen") {
              $this->validate($request, [
                'dateNeeded' => 'required'
              ]);
            }

            $alreadyRequestedFlag = 0;
            $customer = $this->user->userable;
            $swineCartItems = $customer->swineCartItems();

            // Update Swine Cart
            $requested = $swineCartItems->find($request->itemId);
            $requested->if_requested = 1;
            $requested->quantity = $request->requestQuantity;
            $requested->date_needed = ($request->dateNeeded) ? date_format(date_create($request->dateNeeded), 'Y-n-j') : '';
            $requested->special_request = $request->specialRequest;
            $requested->save();

            // Update Product if status is not yet updated
            $product = Product::find($request->productId);
            if($product->status == "requested"){
                $alreadyRequestedFlag = 1;
            }
            else{
                $product->status = "requested";
                $product->save();
            }

            $breeder = Breeder::find($product->breeder_id);

            $transactionDetails = [
                'swineCart_id' => $requested->id,
                'customer_id' => $requested->customer_id,
                'breeder_id' => $product->breeder_id,
                'product_id' => $product->id,
                'status' => 'requested',
                'created_at' => Carbon::now()
            ];

            $notificationDetails = [
                'description' => '<b>' . $this->user->name . '</b> requested for Product <b>' . $product->name . '</b>.',
                'time' => $transactionDetails['created_at'],
                'url' => route('dashboard.productStatus')
            ];

            $smsDetails = [
                'message' => 'SwineCart ['. $this->transformDateSyntax($transactionDetails['created_at'], 1) .']: ' . $this->user->name . ' requested for Product ' . $product->name . '.',
                'recipient' => $breeder->office_mobile
            ];

            $pubsubData = [
                'body' => [
                    'uuid' => (string) Uuid::uuid4(),
                    'id' => $product->id,
                    'reservation_id' => 0,
                    'img_path' => route('serveImage', ['size' => 'small', 'filename' => Image::find($product->primary_img_id)->name]),
                    'breeder_id' => $product->breeder_id,
                    'farm_province' => FarmAddress::find($product->farm_from_id)->province,
                    'name' => $product->name,
                    'type' => $product->type,
                    'age' => $this->computeAge($product->birthdate),
                    'breed' => $this->transformBreedSyntax(Breed::find($product->breed_id)->name),
                    'quantity' => $product->quantity,
                    'adg' => $product->adg,
                    'fcr' => $product->fcr,
                    'bft' => $product->backfat_thickness,
                    'status' => $product->status,
                    'status_time' => '',
                    'customer_id' => 0,
                    'customer_name' => $this->user->name,
                    'date_needed' => '',
                    'special_request' => '',
                    'delivery_date' => ''
                ]
            ];

            $breederUser = $breeder->users()->first();

            // Add new Transaction Log
            $this->addToTransactionLog($transactionDetails);

            // Queue notifications (SMS, database, notification, pubsub server)
            dispatch(new SendSMS($smsDetails['message'], $smsDetails['recipient']));
            dispatch(new NotifyUser('product-requested', $breederUser->id, $notificationDetails));
            dispatch(new SendToPubSubServer('notification', $breederUser->email));
            dispatch(new SendToPubSubServer('db-productRequest', $breederUser->email, $pubsubData));
            if(!$alreadyRequestedFlag) dispatch(new SendToPubSubServer('db-requested', $breederUser->email, ['product_type' => $product->type]));

            return [$customer->swineCartItems()->where('if_requested',0)->count(), $transactionDetails['created_at']];

        }
    }

    /**
     * Delete item from Swine Cart
     * AJAX
     *
     * @param  Request $request
     * @return Array
     */
    public function deleteFromSwineCart(Request $request)
    {
        if($request->ajax()){
            $customer = $this->user->userable;
            $item = $customer->swineCartItems()->where('id',$request->itemId)->get()->first();
            $productName = Product::find($item->product_id)->name;
            if($item) {
                $item->delete();
                return ["success", $productName, $customer->swineCartItems()->where('if_requested',0)->count()];
            }
            else return ["not found", $item->product_id];

        }
        else {
            $customer = $this->user->userable;
            $item = $customer->swineCartItems()->where('id',$request->itemId)->get()->first();
            $productName = Product::find($item->product_id)->name;
            if($item) {
                $item->delete();
                return ["success", $productName, $customer->swineCartItems()->where('if_requested',0)->count()];
            }
            else return ["not found", $item->product_id];
        }

    }

    /**
     * Get items in the Swine Cart
     * [!]AJAX
     *
     * @param  Request $request
     * @return JSON/Array
     */
    public function getSwineCartItems(Request $request)
    {
        if($request->ajax()){
            $customer = $this->user->userable;
            $swineCartItems = $customer->swineCartItems()->where('if_requested',0)->get();
            $items = [];

            foreach ($swineCartItems as $item) {
                $itemDetail = [];
                $product = Product::find($item->product_id);
                $itemDetail['item_id'] = $item->id;
                $itemDetail['product_id'] = $item->product_id;
                $itemDetail['product_name'] = $product->name;
                $itemDetail['product_type'] = $product->type;
                $itemDetail['product_breed'] = Breed::find($product->breed_id)->name;
                $itemDetail['img_path'] = route('serveImage', ['size' => 'small', 'filename' => Image::find($product->primary_img_id)->name]);
                $itemDetail['breeder'] = Breeder::find($product->breeder_id)->users()->first()->name;
                $itemDetail['user_id'] = Breeder::find($product->breeder_id)->users()->first()->id;
                $itemDetail['token'] = csrf_token();
                array_push($items,$itemDetail);
            }

            $itemsCollection = collect($items);
            return $itemsCollection->toJson();
        }
        else {
            $customer = $this->user->userable;
            $swineCartItems = $customer->swineCartItems()->where('if_rated',0)->get();
            $products = [];

            foreach ($swineCartItems as $item) {
                $itemDetail = [];
                Log::debug('ITEM: ' . json_encode($item));
                $product = Product::find($item->product_id);
                Log::debug('PRODUCT: ' . json_encode($product));
                $reviews = Breeder::find($product->breeder_id)->reviews()->get();
                $reservation = ProductReservation::find($item->reservation_id);

                $itemDetail['request_status'] = $item->if_requested;
                $itemDetail['request_quantity'] = $item->quantity;
                $itemDetail['status'] = ($item->reservation_id) ? ProductReservation::find($item->reservation_id)->order_status : $product->status;
                $itemDetail['item_id'] = $item->id;
                $itemDetail['customer_id'] = $customer->id;
                $itemDetail['breeder_id'] = $product->breeder_id;
                $itemDetail['breeder'] = Breeder::find($product->breeder_id)->users()->first()->name;
                $itemDetail['user_id'] = Breeder::find($product->breeder_id)->users()->first()->id;
                $itemDetail['product_id'] = $item->product_id;
                $itemDetail['product_province'] = FarmAddress::find($product->farm_from_id)->province;
                $itemDetail['product_name'] = $product->name;
                $itemDetail['product_type'] = $product->type;
                $itemDetail['product_quantity'] = $product->quantity;
                $itemDetail['product_breed'] = $this->transformBreedSyntax(Breed::find($product->breed_id)->name);
                $itemDetail['product_birthdate'] = $this->transformDateSyntax($product->birthdate);
                $itemDetail['product_age'] = $this->computeAge($product->birthdate);
                $itemDetail['product_adg'] = $product->adg;
                $itemDetail['product_fcr'] = $product->fcr;
                $itemDetail['product_backfat_thickness'] = $product->backfat_thickness;
                $itemDetail['other_details'] = $product->other_details;
                $itemDetail['avg_delivery'] = $reviews->avg('rating_delivery');
                $itemDetail['avg_transaction'] = $reviews->avg('rating_transaction');
                $itemDetail['avg_productQuality'] = $reviews->avg('rating_productQuality');
                $itemDetail['date_needed'] = ($item->date_needed == '0000-00-00') ? '' : $this->transformDateSyntax($item->date_needed);
                $itemDetail['special_request'] = $item->special_request;
                $itemDetail['img_path'] = route('serveImage', ['size' => 'medium', 'filename' => Image::find($product->primary_img_id)->name]);
                $itemDetail['delivery_date'] = ($reservation) ? $this->transformDateSyntax($reservation->delivery_date) : '';
                $itemDetail['status_transactions'] = [
                    "requested" => ($item->transactionLogs()->where('status', 'requested')->latest()->first()->created_at) ?? '',
                    "reserved" => ($item->transactionLogs()->where('status', 'reserved')->latest()->first()->created_at) ?? '',
                    "on_delivery" => ($item->transactionLogs()->where('status', 'on_delivery')->first()->created_at) ?? '',
                    "sold" => ($item->transactionLogs()->where('status', 'sold')->first()->created_at) ?? ''
                ];

                array_push($products,(object) $itemDetail);
            }

            $products = collect($products);
            $token = csrf_token();
            $customerId = $customer->id;
            return view('user.customer.swineCart', compact('products', 'token', 'customerId'));
        }
    }

    /**
     * Get Transaction History of
     * AJAX
     *
     * @param  Request $request
     */
    public function getTransactionHistory(Request $request){
        if($request->ajax()) {

          $customer = $this->user->userable;

          $history = $customer
            ->swineCartItems()
            ->whereHas('transactionLogs', function ($query) {
                $query->where('status', 'rated');
            })
            ->with(['transactionLogs' => function ($query) {
                $query->orderBy('created_at', 'DESC');
            }])
            ->with(
                'product.breed',
                'product.farmFrom',
                'product.breeder.user',
                'product.primaryImage',
                'productReservation'
            )
            ->paginate($request->limit)
            ->map(function ($element) {

                $transaction = [];

                $product = $element->product;
                $productReservation = $element->productReservation;
                $province = $product->farmFrom->province;
                $breed = $product->breed;
                $breeder = $product->breeder->users()->first()->name;

                $transaction['id'] = $element->id;
                $transaction['showFullLogs'] = false;
                $transaction['product_details'] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'type' => $product->type,
                    's_img_path' => route('serveImage', ['size' => 'small', 'filename' => $product->primaryImage->name]),
                    'l_img_path' => route('serveImage', ['size' => 'large', 'filename' => Image::find($product->primary_img_id)->name]),
                    'breed' => $this->transformBreedSyntax($breed->name),
                    'breeder_name' => $breeder,
                    'farm_from' => $province,
                    'birthdate' => $product->birthdate,
                    'age' => $product->age,
                    'fcr' => $product->fcr,
                    'bft' => $product->bft,
                    'other_details' => $product->other_details
                ];

                $transaction['logs'] = $element->transactionLogs->map(function ($item) {
                    $log = [];
                    $log['status'] = $item->status === 'on_delivery'
                        ? 'On Delivery'
                        : ucwords($item->status);
                    $log['createdAt'] = $item->created_at;
                    return $log;
                });

                $trimmed_special_request = trim($productReservation->special_request);

                $transaction['reservationDetails'] = [
                    'quantity' => $productReservation->quantity,
                    'specialRequest' =>
                        $trimmed_special_request === ''
                            ? null
                            : $trimmed_special_request,
                    'dateNeeded' =>
                        ($productReservation->date_needed == '0000-00-00')
                            ? null
                            : $productReservation->date_needed,
                    'deliveryDate' =>
                        ($productReservation->delivery_date == '0000-00-00')
                            ? null
                            : $productReservation->delivery_date,
                ];

                $transaction['statusTime'] = $transaction['logs'][0]['createdAt'];

                return $transaction;
              })
              ->sortByDesc('statusTime')
              ->values()
              ->all();

            return response()->json([
                'data' => [
                    'history' => $history,
                ]
            ]);

        }
    }

    /**
     * Get number of items in the Swine Cart
     * AJAX
     *
     * @param  Request $request
     * @return Integer
     */
    public function getSwineCartQuantity(Request $request)
    {
        if($request->ajax()){
            $customer = $this->user->userable;
            return $customer->swineCartItems()->where('if_requested',0)->count();
        }
    }

    /**
     * Rates breeder from Swine Cart
     * AJAX
     *
     * @param  Request $request
     */
    public function rateBreeder(Request $request){

        if($request->ajax()){

            $this->validate($request, [
              'delivery' => 'required | regex: /[1-5]/',
              'transaction' => 'required | regex: /[1-5]/',
              'productQuality' => 'required | regex: /[1-5]/'
            ]);

            $customer = $this->user->userable;
            $reviews = Breeder::find($request->breederId)->reviews();

            $review = new Review;
            $review->customer_id = $request->customerId;
            $review->comment = $request->comment;
            $review->rating_delivery = $request->delivery;
            $review->rating_transaction = $request->transaction;
            $review->rating_productQuality = $request->productQuality;

            $swineCartItems = $customer->swineCartItems();
            $reviewed = $swineCartItems->where('product_id',$request->productId)->first();
            $reviewed->if_rated = 1;
            $reviewed->save();
            $reviews->save($review);

            $breeder = Breeder::find($request->breederId);

            $transactionDetails = [
                'swineCart_id' => $reviewed->id,
                'customer_id' => $request->customerId,
                'breeder_id' => $request->breederId,
                'product_id' => $request->productId,
                'status' => 'rated',
                'created_at' => Carbon::now()
            ];

            $notificationDetails = [
                'description' => 'Customer <b>' . $this->user->name . ' rated</b> you with ' . round(($review->rating_delivery + $review->rating_transaction + $review->rating_productQuality)/3, 2) . ' (overall average).',
                'time' => $transactionDetails['created_at'],
                'url' => route('dashboard')
            ];

            $smsDetails = [
                'message' => 'SwineCart ['. $this->transformDateSyntax($transactionDetails['created_at'], 1) .']: Customer ' . $this->user->name . ' rated you with ' . round(($review->rating_delivery + $review->rating_transaction + $review->rating_productQuality)/3, 2) . ' (overall average).',
                'recipient' => $breeder->office_mobile
            ];

            $pubsubData = [
                'rating_delivery' => $review->rating_delivery,
                'rating_transaction' => $review->rating_transaction,
                'rating_productQuality' => $review->productQuality,
                'review_comment' => $review->comment,
                'review_customerName' => $this->user->name
            ];

            $breederUser = $breeder->users()->first();

            // Add new Transaction Log
            $this->addToTransactionLog($transactionDetails);

            // Queue notifications (SMS, database, notification, pubsub server)
            dispatch(new SendSMS($smsDetails['message'], $smsDetails['recipient']));
            dispatch(new NotifyUser('breeder-rated', $breederUser->id, $notificationDetails));
            dispatch(new SendToPubSubServer('notification', $breederUser->email));
            dispatch(new SendToPubSubServer('db-rated', $breederUser->email, $pubsubData));

            return "OK";
        }
    }

}
