<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Breed;
use App\Models\Image;
use App\Models\SwineCartItem;
use App\Repositories\DashboardRepository;
use App\Repositories\CustomHelpers;

use JWTAuth;

class OrderController extends Controller
{
    use CustomHelpers {
        transformBreedSyntax as private;
        transformDateSyntax as private;
        computeAge as private;
        getProductImage as private;
    }

    private $statuses = [
        'requested' => true,
        'reserved' => true,
        'on_delivery' => true,
        'sold' => true,
    ];

    public function __construct(DashboardRepository $dashboard)
    {
        $this->middleware('jwt:auth');
        $this->middleware(function($request, $next) {
            $this->user = JWTAuth::user();
            $this->account_type = explode('\\', $this->user->userable_type)[2];
            return $next($request);
        });

        $this->dashboard = $dashboard;
    }

    private function formatProduct($item)
    {
        $product = [
            'id' => $item->id,
            'name' => $item->name,
            'type' => $item->type,
            'breed' => $this->transformBreedSyntax($item->breed->name),
            'imageUrl' => $this->getProductImage($item, 'small'),
            'isDeleted' => $item->trashed(),
            'isUnique' => $item->is_unique === 1
        ];

        return $product;
    }

    private function formatOrder($item)
    {
        $order = [];

        $product = $item->product;
        $customer = $item->customer;
        $breeder = $item->product->breeder;

        $order['id'] = $item->id;
        $order['swineCartId'] = $item->swinecart_id;
        $order['status'] = $item->order_status;
        $order['statusTime'] = $item->created_at;

        $order['product'] = [
            'id' => $product->id,
            'name' => $product->name,
            'type' => $product->type,
            'breed' => $this->transformBreedSyntax($product->breed->name),
            'farmLocation' => $product->farmFrom->province,
            'imageUrl' => $this->getProductImage($product, 'small'),
            'isDeleted' => $product->trashed(),
            'isUnique' => $product->is_unique === 1
        ];

        $order['customer'] = [
            'id' => $customer->user->id,
            'name' => $customer->user->name,
        ];

        $order['breeder'] = [
            'id' => $breeder->user->id,
            'name' => $breeder->user->name,
        ];

        return $order;
    }

    public function getOrders(Request $request)
    {
        $account_user = $this->user->userable;
        $status = $request->status;

        if ($status && array_key_exists($status, $this->statuses)) {

            if ($status === 'requested') {

                $orders = $account_user
                    ->swineCartItems()
                    ->with(
                        'productReservation',
                        // 'product.breed',
                        // 'product.primaryImage',
                        // 'product.farmFrom',
                        // 'product.breeder.user',
                        // 'customer.user'
                    )
                    // ->join('transaction_logs', function ($join) use ($status) {
                    //     $join
                    //         ->on(
                    //             'swine_cart_items.id',
                    //             '=',
                    //             'transaction_logs.swineCart_id'
                    //         )
                    //         ->where('transaction_logs.status', $status);
                    // })
                    ->get()
                    ->map(function ($item) {
                        // $item->product = $item->product->name;

                        $order = [];

                        // $order['productName'] = $item->product->name;
                        // $order['if_requested'] = $item->if_requested;
                        $order['item'] = $item;

                        return $order;
                    });

                return response()->json([
                    'success' => true,
                    'data' => [
                        // 'hasNextPage' => $orders->hasMorePages(),
                        'orders' => $orders,
                    ]
                ], 200);
            }
            else {

                $orders = $account_user
                    ->reservations()
                    ->with(
                        'product.breed',
                        'product.primaryImage',
                        'product.farmFrom',
                        'product.breeder.user',
                        'customer.user'
                    )
                    ->join('swine_cart_items', function ($join) {
                        $join
                            ->on(
                                'product_reservations.id',
                                '=',
                                'swine_cart_items.reservation_id'
                            );
                    })
                    ->join('transaction_logs', function ($join) use ($status) {
                        $join
                            ->on(
                                'swine_cart_items.id',
                                '=',
                                'transaction_logs.swineCart_id'
                            )
                            ->where('transaction_logs.status', $status);
                    })
                    ->select(
                        'product_reservations.*',
                        'swine_cart_items.id as swinecart_id',
                        'transaction_logs.created_at as created_at'
                    )
                    ->where('order_status', $status)
                    ->orderBy('created_at', 'DESC')
                    ->paginate($request->limit);

                $formatted = $orders->map(function ($item) {
                    return $this->formatOrder($item);
                });

                return response()->json([
                    'success' => true,
                    'data' => [
                        'hasNextPage' => $orders->hasMorePages(),
                        'orders' => $formatted,
                    ]
                ], 200);
            }
        }
        else return response()->json([
            'success' => false,
            'error' => 'Invalid Status!'
        ], 400);
    }

    public function getOrderDetails(Request $request, $order_id)
    {
        $account_user = $this->user->userable;

        $item = $account_user
            ->reservations()
            ->with(
                'product.breed',
                'product.primaryImage',
                'product.farmFrom',
                'product.breeder.user',
                'customer.user'
            )
            ->with(['transactionLogs' => function ($query) {
                $query->orderBy('created_at', 'DESC');
            }])
            ->find($order_id);

        if ($item) {

            $order = [];

            $product = $item->product;
            $customer = $item->customer;
            $breeder = $item->product->breeder;
            $logs = $item->transactionLogs;
            $special_request = trim($item->special_request);

            $order['id'] = $item->id;

            $order['product'] = [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'breed' => $this->transformBreedSyntax($product->breed->name),
                'farmLocation' => $product->farmFrom->province,
                'imageUrl' => $this->getProductImage($product, 'small'),
                'isDeleted' => $product->trashed(),
                'isUnique' => $product->is_unique === 1
            ];

            $order['details'] = [
                'quantity' => $item->quantity,
                'deliveryDate' => $item->delivery_date,
                'dateNeeded' => $item->date_needed === '0000-00-00'
                    ? null
                    : $item->date_needed,
                'specialRequest' => $special_request === ''
                    ? null
                    : $special_request,
            ];

            $order['customer'] = [
                'id' => $customer->user->id,
                'name' => $customer->user->name,
                'province' => $customer->address_province,
            ];

            $order['breeder'] = [
                'id' => $breeder->user->id,
                'name' => $breeder->user->name,
                'province' => $breeder->officeAddress_province,
            ];

            $order['logs'] = $logs->map(function ($item) {
                return [
                    'status' => $item->status,
                    'createdAt' => $item->created_at,
                ];
            });

            $latestLog = $order['logs'][0];

            $order['status'] = $latestLog['status'];
            $order['statusTime'] = $latestLog['createdAt'];

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order
                ]
            ], 200);

        }
        else return response()->json([
            'error' => 'Order does not exist!'
        ], 404);
    }
}