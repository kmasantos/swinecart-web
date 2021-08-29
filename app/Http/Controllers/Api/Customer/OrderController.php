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
        getProductImage as private;
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

    private function formatDetails($item)
    {
        $special_request = trim($item->special_request);

        return [
            'quantity' => $item->quantity,
            'deliveryDate' => $item->delivery_date,
            'dateNeeded' => $item->date_needed === '0000-00-00'
                ? null
                : $item->date_needed,
            'specialRequest' => $special_request === ''
                ? null
                : $special_request,
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

    public function getHistory(Request $request)
    {
        $customer = $this->user->userable;

        $history = $customer
            ->swineCartItems()
            ->with(
                'product.breed',
                'product.farmFrom',
                'product.breeder.user',
                'product.primaryImage'
            )
            ->join('transaction_logs', function ($join) {
                $join
                    ->on(
                        'swine_cart_items.id',
                        '=',
                        'transaction_logs.swineCart_id'
                    )
                    ->where('status', 'rated');
            })
            ->where('if_rated', 1)
            ->orderBy('created_at', 'DESC')
            ->paginate($request->limit);

        $formatted = $history->map(function ($item) {

            $order = [];

            $product = $item->product;
            $breed_name = $product->breed->name;
            $breeder = $product->breeder->user;

            $order['id'] = $item->swineCart_id;

            $order['product'] = [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'breed' => $this->transformBreedSyntax($breed_name),
                'breederName' => $breeder->name,
                'farmLocation' => $product->farmFrom->province,
                'imageUrl' => $this->getProductImage($product, 'small'),
                'isDeleted' => $product->trashed(),
                'isUnique' => $product->is_unique === 1
            ];

            return $order;
        });

        return response()->json([
            'data' => [
                'hasNextPage' => $history->hasMorePages(),
                'history' => $formatted,
            ]
        ]);

    }

    public function getOrders(Request $request)
    {
        $customer = $this->user->userable;
        $status = $request->status;

        $statuses = [
            'requested' => true,
            'reserved' => true,
            'on_delivery' => true,
            'sold' => true,
        ];

        if ($status && array_key_exists($status, $statuses)) {

            $orders = $customer
                    ->swineCartItems()
                    ->with(
                        'product.breeder.user',
                        'product.breed',
                        'product.primaryImage'
                    )
                    ->join('transaction_logs', function ($join) use ($status) {
                        $join
                            ->on(
                                'swine_cart_items.id',
                                '=',
                                'transaction_logs.swineCart_id'
                            )
                            ->where('status', $status);
                    })
                    ->where('if_rated', 0)
                    ->where('if_requested', 1);

            $orders = $status == 'requested'
                ? $orders->doesntHave('productReservation')
                : $orders->whereHas('productReservation',
                    function ($query) use ($status) {
                        $query->where('order_status', $status);
                    });

            $orders = $orders->orderBy('created_at', 'DESC')
                    ->paginate($request->limit);

            $formatted = $orders->map(function ($item) use ($status) {

                $order = [];

                $product = $item->product;
                $breed = $product->breed;
                $breeder = $product->breeder->user;

                $order['id'] = $item->swineCart_id;
                $order['status'] = $status;
                $order['statusTime'] = $item->created_at;

                $order['product'] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'type' => $product->type,
                    'breed' => $this->transformBreedSyntax($breed->name),
                    'breederName' => $breeder->name,
                    'farmLocation' => $product->farmFrom->province,
                    'imageUrl' => $this->getProductImage($product, 'small'),
                    'isDeleted' => $product->trashed(),
                    'isUnique' => $product->is_unique === 1
                ];
                
                return $order;
            });

            return response()->json([
                'data' => [
                    'hasNextPage' => $orders->hasMorePages(),
                    'orders' => $formatted,
                ]
            ], 200);

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

            $product = $item->product;
            $breed = $product->breed;
            $breeder = $product->breeder->user;
            $reservation = $item->productReservation;
            $logs = $item->transactionLogs;

            $order['id'] = $item->id;

            $order['product'] = [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'breed' => $this->transformBreedSyntax($breed->name),
                'farmLocation' => $product->farmFrom->province,
                'imageUrl' => $this->getProductImage($product, 'small'),
                'isDeleted' => $product->trashed(),
                'isUnique' => $product->is_unique === 1
            ];

            $order['details'] = $this->formatDetails(
                $reservation
                    ? $reservation
                    : $item
            );

            $order['logs'] = $logs->map(function ($item) {
                return [
                    'status' => $item->status,
                    'createdAt' => $item->created_at,
                ];
            });

            $order['breeder'] = [
                'id' => $product->breeder_id,
                'name' => $breeder->name,
                'province' => $product->breeder->officeAddress_province,
                'landlineNumber' => $product->breeder->office_landline,
                'mobileNumber' => $product->breeder->office_mobile,
            ];

            $latestLog = $order['logs'][0];

            $order['status'] = $latestLog['status'];
            $order['statusTime'] = $latestLog['createdAt'];

            return response()->json([
                'data' => [
                    'order' => $order,
                ]
            ]);
        }
        else return response()->json([
            'error' => 'Item not Found!'
        ], 404);
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

                $product = $cart_item->product;
                $is_product_unique = $product->is_unique === 1;

                $cart_item->if_requested = 1;

                if ($product->type === 'semen') {
                    $cart_item->quantity = $request->quantity;
                }
                else {
                    if ($is_product_unique) {
                        $cart_item->quantity = 1;
                    }
                    else {
                        $cart_item->quantity = $request->quantity;
                    }
                }

                $cart_item->date_needed = ($request->dateNeeded) ? date_format(date_create($request->dateNeeded), 'Y-n-j') : '';
                $cart_item->special_request = trim($request->specialRequest);
                $cart_item->save();

                $product->status = 'requested';
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
                        'age' => !$product->birthdate || $product->birthdate === '0000-00-00'
                            ? null
                            : $this->computeAge($product->birthdate),
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

                $product = $cart_item->product;
                $breeder = $product->breeder->user;
                $breed = $product->breed;
                $is_product_deleted = $product->trashed();

                $formatted = [];

                $formatted['id'] = $cart_item->id;
                $formatted['status'] = 'requested';
                $formatted['statusTime'] = $cart_item
                        ->transactionLogs()
                        ->where('status', 'requested')
                        ->latest()->first()
                        ->created_at;

                $formatted['product'] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'type' => $product->type,
                    'breed' => $this->transformBreedSyntax($breed->name),
                    'breederName' => $breeder->name,
                    'farmLocation' => $product->farmFrom->province,
                    'imageUrl' => route('serveImage',
                        [
                            'size' => 'small',
                            'filename' => $is_product_deleted
                                ? $this->defaultImages[$product->type]
                                : $product->primaryImage->name
                        ]
                    ),
                    'isDeleted' => $is_product_deleted,
                    'isUnique' => $product->is_unique === 1
                ];

                return response()->json([
                    'success' => true,
                    'data' => [
                        'item' => $formatted
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
