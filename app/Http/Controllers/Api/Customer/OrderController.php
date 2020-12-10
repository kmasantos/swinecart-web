<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

use App\Jobs\AddToTransactionLog;
use App\Jobs\NotifyUser;
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

use App\Repositories\ProductRepository;
use App\Repositories\CustomHelpers;

use Auth;
use Response;
use Validator;
use JWTAuth;
use Mail;
use Storage;
use Config;
use DB;

class OrderController extends Controller
{

    use CustomHelpers {
        transformBreedSyntax as private;
        transformDateSyntax as private;
        computeAge as private;
    }

    private function formatOrderDetails($reservation)
    {
        $trimmed_special_request = trim($reservation->special_request);

        return [
            'quantity' => $reservation->quantity,
            'specialRequest' =>
                $trimmed_special_request === ''
                    ? null
                    : $trimmed_special_request,
            'deliveryDate' =>
                ($reservation->delivery_date == '0000-00-00')
                    ? null
                    : $reservation->delivery_date,
            'dateNeeded' =>
                ($reservation->date_needed == '0000-00-00')
                    ? null
                    : $reservation->date_needed
        ];
    }

    private function dispatchRatedNotif($item, $product, $review, $customer, $breeder)
    {
        $transactionDetails = [
            'swineCart_id' => $item->id,
            'customer_id' => $customer->id,
            'breeder_id' => $breeder->id,
            'product_id' => $product->id,
            'status' => 'rated',
            'created_at' => Carbon::now()
        ];

        $notificationDetails = [
            'description' => 'Customer <b>' . $this->user->name . ' rated</b> you with ' . round(($review->rating_delivery + $review->rating_transaction + $review->rating_productQuality)/3, 2) . ' (overall average).',
            'time' => $transactionDetails['created_at'],
            'url' => route('dashboard')
        ];

        $pubsubData = [
            'rating_delivery' => $review->rating_delivery,
            'rating_transaction' => $review->rating_transaction,
            'rating_productQuality' => $review->productQuality,
            'review_comment' => $review->comment,
            'review_customerName' => $this->user->name
        ];

        $breederUser = $breeder->user;

        // Add new Transaction Log
        $this->addToTransactionLog($transactionDetails);

        // Queue notifications (SMS, database, notification, pubsub server)
        dispatch(new NotifyUser('breeder-rated', $breederUser->id, $notificationDetails));
        dispatch(new SendToPubSubServer('notification', $breederUser->email));
        dispatch(new SendToPubSubServer('db-rated', $breederUser->email, $pubsubData));
    }

    public function __construct() 
    {
        $this->middleware('jwt:auth');
        $this->middleware('jwt.role:customer');
        $this->middleware(function($request, $next) {
            $this->user = JWTAuth::user();
            return $next($request);
        });
    }

    public function getHistory(Request $request)
    {
        $customer = $this->user->userable;

        $history = $customer
            ->swineCartItems()
            ->whereHas('transactionLogs', function ($query) {
                $query->where('status', 'rated');
            })
            ->with(
                'product.breed',
                'product.farmFrom',
                'product.breeder.user',
                'product.primaryImage'
            )
            ->orderBy('id', 'DESC')
            ->paginate($request->limit)
            ->map(function ($element) {

                $order = [];

                $product = $element->product;
                $province = $product->farmFrom->province;
                $breed = $product->breed;
                $breederName = $product->breeder->users()->first()->name;

                $order['id'] = $element->id;

                $order['product'] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'breed' => $this->transformBreedSyntax($breed->name),
                    'type' => $product->type,
                    'farmLocation' => $province,
                    'breederName' => $breederName,
                    'imageUrl' => route('serveImage',
                        [
                            'size' => 'medium',
                            'filename' => $product->primaryImage->name
                        ]
                    ),
                ];

                return $order;
            });

        return response()->json([
            'data' => [
                'history' => $history,
            ]
        ]);

    }

    public function reviewBreeder(Request $request, $breeder_id)
    {
        $customer = $this->user->userable;

        $breeder = Breeder::find($breeder_id);
        $reviews = $breeder->reviews();

        $item = $customer
            ->swineCartItems()
            ->with('product')
            ->where('id', $request->item_id)
            ->first();

        if ($item) {

            $product = $item->product;

            if ($product) {
                if ($item->if_rated == 0) {
                    // Create Review
                    $review = new Review;
                    $review->customer_id = $customer->id;
                    $review->comment = $request->comment;
                    $review->rating_delivery = $request->delivery;
                    $review->rating_transaction = $request->transaction;
                    $review->rating_productQuality = $request->productQuality;

                    $item->if_rated = 1;
                    $reviews->save($review);
                    $item->save();

                    $this->dispatchRatedNotif($item, $product, $review, $customer, $breeder);

                    return response()->json([
                        'message' => 'Review Breeder successful',
                    ], 200);
                }
                else return response()->json([
                    'error' => 'Item already rated!'
                ], 409);

            }
            else return response()->json([
                'error' => 'Product not Found!'
            ], 404);
        }
        else return response()->json([
            'error' => 'Item not Found!'
        ], 404);
    }

    public function getOrders(Request $request)
    {
        $customer = $this->user->userable;
        $status = $request->status;

        if ($status) {
            if ($status == 'requested') {
                $items = $customer
                    ->swineCartItems()
                    ->with(
                        'product.breeder.user',
                        'product.breed',
                        'product.primaryImage'
                    )
                    ->where('if_rated', 0)
                    ->where('if_requested', 1)
                    ->doesntHave('productReservation')
                    ->get()
                    ->map(function ($data) {
                        $order = [];

                        $product = $data->product;
                        $breeder = $product->breeder->user;
                        $breed_name = $product->breed->name;

                        $order['id'] = $data->id;
                        $order['status'] = 'requested';
                        $order['statusTime'] = $data
                                ->transactionLogs()
                                ->where('status', 'requested')
                                ->latest()->first()
                                ->created_at;

                        $order['product'] = [
                            'id' => $data->product_id,
                            'name' => $product->name,
                            'type' =>  $product->type,
                            'breed' =>  $this->transformBreedSyntax($breed_name),
                            'breederName' => $breeder->name,
                            'farmLocation' => $product->farmFrom->province,
                            'imageUrl' => route('serveImage',
                                [
                                    'size' => 'small',
                                    'filename' => $product->primaryImage->name
                                ]
                            ),
                        ];

                        return $order;
                    })
                    ->sortByDesc('statusTime')
                    ->forPage($request->page, $request->limit)
                    ->values()
                    ->all();

                return response()->json([
                    'data' => [
                        'items' => $items,
                    ]
                ]);
            }
            else if ($status == 'reserved' || $status == 'on_delivery' || $status == 'sold') {
                $items = $customer
                    ->swineCartItems()
                    ->with(
                        'product.primaryImage',
                        'product.breed',
                        'product.breeder'
                    )
                    ->where('if_rated', 0)
                    ->where('if_requested', 1)
                    ->whereHas('productReservation', function ($query) use ($status) {
                        $query->where('order_status', $status);
                    })
                    ->get()
                    ->map(function ($data) use ($status) {
                        $order = [];

                        $reservation = $data->productReservation;
                        $product = $data->product;
                        $breed = $product->breed;
                        $breeder = $product->breeder->user;

                        $order['id'] = $data->id;
                        $order['status'] = $reservation->order_status;
                        $order['statusTime'] = $data
                                ->transactionLogs()
                                ->where('status', $status)
                                ->latest()->first()
                                ->created_at;

                        $order['product'] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'type' => $product->type,
                            'breed' => $this->transformBreedSyntax($breed->name),
                            'breederId' => $product->breeder_id,
                            'breederName' => $breeder->name,
                            'farmLocation' => $product->farmFrom->province,
                            'imageUrl' => route('serveImage',
                                [
                                    'size' => 'small',
                                    'filename' => $product->primaryImage->name
                                ]
                            ),
                        ];

                        return $order;
                    })
                    ->sortByDesc('statusTime')
                    ->forPage($request->page, $request->limit)
                    ->values()
                    ->all();

                return response()->json([
                    'data' => [
                        'items' => $items,
                    ]
                ]);
            }
            else return response()->json([
                'error' => 'Invalid Status!'
            ], 400);
        }
        else return response()->json([
            'error' => 'Invalid Status!'
        ], 400);
    }

    public function getOrder(Request $request, $id)
    {
        $customer = $this->user->userable;

        $item = $customer
            ->swineCartItems()
            ->with(['transactionLogs' => function ($query) {
                $query->orderBy('created_at', 'DESC');
            }])
            ->with(
                'productReservation',
                'product.primaryImage',
                'product.breed',
                'product.breeder.user',
                'product.primaryImage',
                'product.farmFrom'
            )
            ->where('if_requested', 1)
            ->find($id);

        if ($item) {

            $order = [];

            $reservation = $item->productReservation;
            $product = $item->product;
            $breed = $product->breed;
            $breeder = $product->breeder->user;

            $order['id'] = $item->id;

            $order['product'] = [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'breed' => $this->transformBreedSyntax($breed->name),
                'farmLocation' => $product->farmFrom->province,
                'imageUrl' => route('serveImage',
                    [
                        'size' => 'small',
                        'filename' => $product->primaryImage->name
                    ]
                ),
            ];

            $order['logs'] = $element->transactionLogs->map(function ($item) {
                $log = [];
                $log['status'] = $item->status === 'on_delivery'
                    ? 'On Delivery'
                    : ucwords($item->status);
                $log['createdAt'] = $item->created_at;
                return $log;
            });

            $order['breederInfo'] = [
                'id' => $product->breeder_id,
                'name' => $breeder->name,
                'province' => $product->breeder->officeAddress_province,
                'landlineNumber' => $product->breeder->office_landline,
                'mobileNumber' => $product->breeder->office_mobile,
            ];

            $order['details'] = $this->formatOrderDetails(
                $reservation
                    ? $reservation 
                    : $item
            );

            return response()->json([
                'data' => [
                    // 'item' => $item,
                    'order' => $order,
                ]
            ]);
        }
        else return response()->json([
            'error' => 'Item not Found!'
        ], 404);
    }

    public function requestItem(Request $request, $item_id)
    {

        $customer = $this->user->userable;

        $cart_item = $customer
            ->swineCartItems()
            ->with(
                'product.breeder',
                'product.breed',
                'product.primaryImage'
            )
            ->find($item_id);


        if($cart_item) {

            if(!$cart_item->if_requested) {

                $cart_item->if_requested = 1;
                $cart_item->quantity = $request->requestQuantity;
                $cart_item->date_needed = ($request->dateNeeded) ? date_format(date_create($request->dateNeeded), 'Y-n-j') : '';
                $cart_item->special_request = $request->specialRequest;
                $cart_item->save();

                $product = $cart_item->product;
                $product->status = "requested";
                $product->save();

                $breeder = $product->breeder;

                $transactionDetails = [
                    'swineCart_id' => $cart_item->id,
                    'customer_id' => $cart_item->customer_id,
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

                $pubsubData = [
                    'body' => [
                        'uuid' => (string) Uuid::uuid4(),
                        'id' => $product->id,
                        'reservation_id' => 0,
                        'img_path' => route('serveImage', ['size' => 'small', 'filename' => $product->primaryImage->name]),
                        'breeder_id' => $product->breeder_id,
                        'farm_province' => $product->farmFrom->province,
                        'name' => $product->name,
                        'type' => $product->type,
                        'age' => $this->computeAge($product->birthdate),
                        'breed' => $this->transformBreedSyntax($product->breeder->name),
                        'quantity' => $product->quantity,
                        'adg' => $product->adg,
                        'fcr' => $product->fcr,
                        'bft' => $product->backfat_thickness,
                        'status' => $product->status,
                        'status_time' => '',
                        'customer_id' => 0,
                        'customer_name' => '',
                        'date_needed' => '',
                        'special_request' => '',
                        'delivery_date' => ''
                    ]
                ];

                $breederUser = $breeder->user;

                $this->addToTransactionLog($transactionDetails);

                dispatch(new NotifyUser('product-requested', $breederUser->id, $notificationDetails));
                dispatch(new SendToPubSubServer('notification', $breederUser->email));
                dispatch(new SendToPubSubServer('db-productRequest', $breederUser->email, $pubsubData));
                dispatch(new SendToPubSubServer('db-requested', $breederUser->email, ['product_type' => $product->type]));

                $item = $customer
                    ->swineCartItems()
                    ->with(
                        'product.breeder',
                        'product.breed',
                        'product.primaryImage'
                    )
                    ->where('if_rated', 0)
                    ->where('if_requested', 1)
                    ->doesntHave('productReservation')
                    ->find($item_id);
                
                $product = $item->product;
                $breeder = $product->breeder->user;
                $breed_name = $product->breed->name;

                $formattedItem = [];

                $formattedItem['id'] = $item->id;
                $formattedItem['status'] = 'requested';
                $formattedItem['statusTime'] = $item
                        ->transactionLogs()
                        ->where('status', 'requested')
                        ->latest()->first()
                        ->created_at;

                $formattedItem['product'] = [
                    'id' => $item->product_id,
                    'name' => $product->name,
                    'type' => $product->type,
                    'breed' => $this->transformBreedSyntax($breed_name),
                    'imageUrl' => route('serveImage',
                        [
                            'size' => 'small',
                            'filename' => $product->primaryImage->name
                        ]
                    ),
                    'breederName' => $breeder->name,
                    'farmLocation' => $product->farmFrom->province
                ];

                return response()->json([
                    'data' => [
                        'item' => $formattedItem
                    ]
                ], 200);
            }
            else return response()->json([
                'error' => 'Item already requested!' 
            ], 409);
        }
        else return response()->json([
            'error' => 'Item not Found!'
        ], 404);
    }
}
