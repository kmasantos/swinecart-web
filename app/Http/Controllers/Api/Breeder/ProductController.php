<?php

namespace App\Http\Controllers\Api\Breeder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\BreederPersonalProfileRequest;
use App\Http\Requests\BreederFarmProfileRequest;
use App\Http\Requests\ProductRequest;

use App\Models\Image;
use App\Models\Video;
use App\Models\User;
use App\Models\Breeder;
use App\Models\Breed;
use App\Models\Customer;
use App\Models\FarmAddress;
use App\Models\Product;

use App\Repositories\ProductRepository;
use App\Repositories\CustomHelpers;

use App\Jobs\ResizeUploadedImage;

use Auth;
use Response;
use Validator;
use JWTAuth;
use Mail;
use Storage;
use Config;

class ProductController extends Controller {

    use CustomHelpers {
        transformBreedSyntax as private;
        transformDateSyntax as private;
        transformOtherDetailsSyntax as private;
        computeAge as private;
    }

    /**
     * Image and Video constant variable paths
     */
    const IMG_PATH = '/images/';
    const VID_PATH = '/videos/';
    const BREEDER_IMG_PATH = '/images/breeder/';
    const PRODUCT_IMG_PATH = '/images/product/';
    const PRODUCT_VID_PATH = '/videos/product/';
    const PRODUCT_SIMG_PATH = '/images/product/resize/small/';
    const PRODUCT_MIMG_PATH = '/images/product/resize/medium/';
    const PRODUCT_LIMG_PATH = '/images/product/resize/large/';

    protected $guard = 'api';

    public function __construct()
    {
        $this->middleware('jwt:auth', ['except' => [
            'getProduct', 'getProductMedia'
        ]]);
        $this->middleware('jwt.role:breeder', ['except' => [
            'getProduct', 'getProductMedia'
        ]]);
        $this->middleware(function($request, $next) {
            $this->user = JWTAuth::user();
            return $next($request);
        }, ['except' => [
            'getProduct', 'getProductMedia'
        ]]);
    }
    /**
     * ---------------------------------------
     *  PRIVATE METHODS
     * ---------------------------------------
     */

    private function createMediaInfo($filename, $extension, $productId, $type, $breed)
    {
        $mediaInfo = [];

        $slug = md5(mt_rand());

        if (str_contains($breed,'+')) {
            $part = explode("+", $breed);
            $mediaInfo['filename'] = $productId . '_' . $type . '_' . $part[0] . ucfirst($part[1]) . '_' . $slug . '.' . $extension;
        }
        else {
            $mediaInfo['filename'] = $productId . '_' . $type . '_' . $breed . '_' . $slug . '.' . $extension;
        }

        if ($this->isImage($extension)) {
            $mediaInfo['directoryPath'] = self::PRODUCT_IMG_PATH;
            $mediaInfo['type'] = new Image;
        }

        else if($this->isVideo($extension)){
            $mediaInfo['directoryPath'] = self::PRODUCT_VID_PATH;
            $mediaInfo['type'] = new Video;
        }

        return $mediaInfo;

    }

    /**
     * Check if media is Image depending on extension
     *
     * @param  String   $extension
     * @return Boolean
     */
    private function isImage($extension)
    {
        return ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png') ? true : false;
    }

    /**
     * Check if media is Video depending on extension
     *
     * @param  String   $extension
     * @return Boolean
     */
    private function isVideo($extension)
    {
        return ($extension == 'mp4' || $extension == 'mkv' || $extension == 'avi' || $extension == 'flv') ? true : false;
    }


     /**
     * Find breed_id through breed name ($breed)
     * or create another breed if not found
     *
     * @param  String   $breed
     * @return Integer
     */
    private function findOrCreateBreed($breed)
    {
        $breedInstance = Breed::where('name','like', $breed)->get()->first();
        if($breedInstance) return $breedInstance->id;
        else{
            $newBreed = Breed::create(['name' => $breed]);
            return $newBreed->id;
        }
    }

    private function formatProduct($product)
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'type' => $product->type,
            'breed' => $this->transformBreedSyntax($product->breed->name),
            'status' => $product->status,
            'age' => !$product->birthdate || $product->birthdate === '0000-00-00'
                ? null
                : $this->computeAge($product->birthdate),
            'quantity' => $product->quantity,
            'isUnique' => $product->is_unique === 1,
            'imageCount' => $product->images->count(),
            'videoCount' => $product->videos->count(),
            'imageUrl' => route('serveImage', [
                'size' => 'small',
                'filename' => $product->primaryImage->name
            ]),
        ];
    }

    private function getBreederProduct($breeder, $product_id)
    {
        $breeder_id = $breeder->id;

        $product = Product::where([
            ['breeder_id', '=', $breeder_id],
            ['id', '=', $product_id]
        ])->first();

        return $product;
    }

    /**
     * ---------------------------------------
     *  PUBLIC METHODS
     * ---------------------------------------
     */

    public function getProducts(Request $request)
    {
        $breeder = $this->user->userable;

        $products = $breeder->products()
            ->with('breed', 'primaryImage', 'images', 'videos')
            ->whereIn('status', ['hidden', 'displayed', 'requested'])
            ->where('quantity','<>', 0)
            ->orWhere([
                ['is_unique', '=', '0'],
                ['quantity', '=', '0']
            ]);

        // Check for Filtering and Sorting
        if($request->input('type')) {
            $products = $products->where('type', $request->input('type'));
        }

        if($request->input('status')) {
            $products = $products->where('status', $request->input('status'));
        }

        if($request->input('sort')) {
            $part = explode('-', $request->input('sort'));
            $products = $products->orderBy($part[0], $part[1]);
        }
        else {
            $products = $products->orderBy('id', 'DESC');
        }

        // Paginate and Transform Product
        $products = $products
            ->paginate($request->limit);

        $formatted = $products->map(function ($item) {
            return $this->formatProduct($item);
        });

        return response()->json([
            'data' => [
                'hasNextPage' => $products->hasMorePages(),
                'products' => $formatted
            ]
        ], 200);
    }

    public function getProduct(Request $request, $product_id)
    {
        $product = Product::with(
                'breed', 'primaryImage', 'farmFrom', 'breeder.users',
                'images','videos'
            )
            ->find($product_id);

        if ($product) {

            $breeder = $product->breeder;
            $farmFrom = $product->farmFrom;
            $user = $breeder->users->first();

            $productInfo = [
                'id' => $product->id,
                'name' => $product->name,
                'type' => $product->type,
                'breed' => $this->transformBreedSyntax($product->breed->name),
                'age' => !$product->birthdate || $product->birthdate === '0000-00-00'
                    ? null
                    : $this->computeAge($product->birthdate),
                'birthDate' => $product->birthdate === '0000-00-00'
                    ? null
                    : $product->birthdate,
                'quantity' => $product->quantity,
                'isUnique' => $product->is_unique === 1,
                'primaryImage' => [
                    'id' => $product->primaryImage->id,
                    'link' => route('serveImage', [
                        'size' => 'large',
                        'filename' => $product->primaryImage->name
                    ]),
                ]
            ];

            $swineInfo = [
                'adg' => $product->adg,
                'fcr' => $product->fcr,
                'bft' => $product->backfat_thickness,
                'lsba' => $product->lsba,
                'houseType' => $product->house_type,
                'minPrice' => $product->min_price,
                'maxPrice' => $product->max_price,
                'leftTeats' => $product->left_teats,
                'rightTeats' => $product->right_teats,
                'birthWeight' => $product->birthweight,
            ];

            $breeder = [
                'id' => $product->breeder_id,
                'name' => $user->name,
            ];

            $farm = [
                'id' => $farmFrom->id,
                'name' => $farmFrom->name,
                'province' => $farmFrom->province,
            ];

            $images = $product->images()
                ->where('id', '<>', $product->primaryImage->id)
                ->orderBy('id', 'DESC')
                ->get()
                ->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'link' => route('serveImage', [
                            'size' => 'large',
                            'filename' => $image->name
                        ])
                    ];
                });

            $images = $images->prepend($productInfo['primaryImage']);

            $videos = $product->videos->map(function ($video) {
                return [
                    'id'=> $video->id,
                    'link'=> route('serveImage', [
                        'size' => 'large',
                        'filename' => $video->name
                    ])
                ];
            });

            $otherDetails = trim(strip_tags($product->other_details));
            $otherDetails = $otherDetails === '' ? null : $otherDetails;

            return response()->json([
                'data' => [
                    'product' => [
                        'productInfo' => $productInfo,
                        'swineInfo' => $swineInfo,
                        'otherDetails' => $otherDetails,
                        'breeder' => $breeder,
                        'farm' => $farm,
                        'images' => $images,
                        'videos' => $videos
                    ]
                ]
            ], 200);
        }
        else return response()->json([
            'error' => 'Product does not exist'
        ], 404);
    }

    public function addProduct(Request $request)
    {
        $breeder = $this->user->userable;
        $farms = $breeder->farmAddresses;

        $farm = $farms->find($request->farm_from_id);

        $data = $request->only([
            'name',
            'type',
            'min_price',
            'max_price',
            'is_unique',
            'quantity',
            'breed',
            'birthdate',
            'farm_from_id',
            'house_type',
            'birth_weight',
            'adg',
            'fcr',
            'bft',
            'lsba',
            'left_teats',
            'right_teats',
            'other_details'
        ]);

        $validator = Validator::make($data, [
            'name' => 'required',
            'type' => 'required',
            'is_unique' => 'required',
            'farm_from_id' => 'required',
            'breed' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }
        else {
            if ($farm) {

                $product = new Product;

                if($request->type == 'boar') $image = Image::firstOrCreate(['name' => 'boar_default.jpg']);
                else if($request->type == 'sow') $image = Image::firstOrCreate(['name' => 'sow_default.jpg']);
                else if($request->type == 'gilt') $image = Image::firstOrCreate(['name' => 'gilt_default.jpg']);
                else $image = Image::firstOrCreate(['name' => 'semen_default.jpg']);

                $product->primary_img_id = $image->id;

                $product->name = $data['name'];
                $product->type = strtolower($data['type']);
                $product->min_price = $data['min_price'];
                $product->max_price = $data['max_price'];
                $product->is_unique = $data['is_unique'];

                if ($product->type === 'semen') {
                    $product->quantity = -1;
                }
                else {
                    if ($data['is_unique'] == 1) {
                        $product->quantity = 1;
                    }
                    else {
                        $product->quantity = $data['quantity'];
                    }
                }

                $product->breed_id = $this->findOrCreateBreed(strtolower($data['breed']));
                $product->birthdate = !$data['birthdate'] || $data['birthdate'] == ''
                    ? ''
                    : date_format(date_create($data['birthdate']), 'Y-n-j');

                $product->farm_from_id = $data['farm_from_id'];
                $product->house_type = $data['house_type'];
                $product->birthweight = $data['birth_weight'];
                $product->adg = $data['adg'];
                $product->fcr = $data['fcr'];
                $product->backfat_thickness = $data['bft'];
                $product->lsba = $data['lsba'];
                $product->left_teats = $data['left_teats'];
                $product->right_teats = $data['right_teats'];
                $product->other_details = $data['other_details'];

                $breeder->products()->save($product);

                $product = $this->getBreederProduct($breeder, $product->id);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'product' => $this->formatProduct($product),
                    ]
                ], 200);
            }
            else return response()->json([
                'success' => false,
                'error' => 'Farm does not exist!'
            ], 404);
        }
    }

    public function updateProduct(Request $request, $product_id)
    {
        $breeder = $this->user->userable;
        $product = $this->getBreederProduct($breeder, $product_id);

        $farms = $breeder->farmAddresses;

        $data = $request->only([
            'name',
            'type',
            'min_price',
            'max_price',
            'is_unique',
            'quantity',
            'breed',
            'birthdate',
            'farm_from_id',
            'house_type',
            'birth_weight',
            'adg',
            'fcr',
            'bft',
            'lsba',
            'left_teats',
            'right_teats',
            'other_details'
        ]);

        $validator = Validator::make($data, [
            'name' => 'required',
            'type' => 'required',
            'is_unique' => 'required',
            'farm_from_id' => 'required',
            'breed' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors(),
            ], 422);
        }
        else {
            if ($product) {

                $farm = $farms->find($data['farm_from_id']);

                if ($farm) {

                    // Product Info
                    $product->name = $data['name'];
                    $product->type = strtolower($data['type']);
                    $product->min_price = $data['min_price'];
                    $product->max_price = $data['max_price'];
                    $product->is_unique = $data['is_unique'];

                    if ($product->type === 'semen') {
                        $product->quantity = -1;
                    }
                    else {
                        if ($data['is_unique'] == 1) {
                            $product->quantity = 1;
                        }
                        else {
                            $product->quantity = $data['quantity'];
                        }
                    }

                    $product->breed_id = $this->findOrCreateBreed(strtolower($data['breed']));
                    $product->birthdate = !$data['birthdate'] || $data['birthdate'] == ''
                        ? ''
                        : date_format(date_create($data['birthdate']), 'Y-n-j');

                    $product->farm_from_id = $data['farm_from_id'];
                    $product->house_type = $data['house_type'];
                    $product->birthweight = $data['birth_weight'];
                    $product->adg = $data['adg'];
                    $product->fcr = $data['fcr'];
                    $product->backfat_thickness = $data['bft'];
                    $product->lsba = $data['lsba'];
                    $product->left_teats = $data['left_teats'];
                    $product->right_teats = $data['right_teats'];
                    $product->other_details = $data['other_details'];

                    $product->save();

                    $product = $this->getBreederProduct($breeder, $product->id);

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'product' => $this->formatProduct($product),
                        ]
                    ], 200);
                }
                else return response()->json([
                    'success' => false,
                    'error' => 'Farm does not exist!'
                ], 404);
            }
            else return response()->json([
                'success' => false,
                'error' => 'Product does not exist!'
            ], 404);
        }

    }

    public function deleteProduct(Request $request, $product_id)
    {
        $breeder = $this->user->userable;

        $product = $this->getBreederProduct($breeder, $product_id);

        if($product) {

            foreach ($product->images as $image) {
                $fullFilePath = self::PRODUCT_IMG_PATH.$image->name;
                $sFullFilePath = self::PRODUCT_SIMG_PATH.$image->name;
                $mFullFilePath = self::PRODUCT_MIMG_PATH.$image->name;
                $lFullFilePath = self::PRODUCT_LIMG_PATH.$image->name;

                if(Storage::disk('public')->exists($fullFilePath)) Storage::disk('public')->delete($fullFilePath);
                if(Storage::disk('public')->exists($sFullFilePath)) Storage::disk('public')->delete($sFullFilePath);
                if(Storage::disk('public')->exists($mFullFilePath)) Storage::disk('public')->delete($mFullFilePath);
                if(Storage::disk('public')->exists($lFullFilePath)) Storage::disk('public')->delete($lFullFilePath);

                $image->delete();
            }

            foreach ($product->videos as $video) {
                $fullFilePath = self::PRODUCT_VID_PATH.$video->name;

                if(Storage::disk('public')->exists($fullFilePath)) Storage::disk('public')->delete($fullFilePath);

                $video->delete();
            }

            $breedId = $product->breed_id;
            $product->delete();

            $breedInstance = Product::where('breed_id', $breedId)
                ->get()
                ->first();

            if (!$breedInstance) {
                $breed = Breed::find($breedId);
                $breed->delete();
            }

            return response()->json([
                'success' => true
            ], 200);

        }
        else return response()->json([
            'success' => false,
            'error' => 'Product does not exist!'
        ], 404);
    }

    public function toggleProductVisibility(Request $request, $product_id)
    {
        $breeder = $this->user->userable;
        $product = $this->getBreederProduct($breeder, $product_id);

        if ($product) {
            if($product->status === 'hidden') {
                $product->status = 'displayed';
            }
            else if($product->status === 'displayed') {
                $product->status = 'hidden';
            }

            $product->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'status' => $product->status
                ]
            ], 200);
        }
        else return response()->json([
            'error' => 'Product does not exist!'
        ], 404);
    }

    public function setPrimaryPicture(Request $request, $product_id)
    {
        $breeder = $this->user->userable;
        $product = $this->getBreederProduct($breeder, $product_id);

        if($product) {
            $product->primary_img_id = $request->imageId;
            $product->save();

            return response()->json([
                'success' => true,
            ]);
        }
        else return response()->json([
            'error' => 'Product does not exist!'
        ], 404);

    }

    public function getMedia(Request $request, $product_id)
    {
        $product = Product::with('primaryImage', 'images', 'videos')
            ->find($product_id);

        if ($product) {

            $images = $product->images()
                ->orderBy('id', 'DESC')
                ->get()
                ->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'link' => route('serveImage', [
                            'size' => 'large',
                            'filename' => $image->name
                        ])
                    ];
                });

            $videos = $product->videos->map(function ($video) {
                return [
                    'id'=> $video->id,
                    'link'=> route('serveImage', [
                        'size' => 'large',
                        'filename' => $video->name
                    ])
                ];
            });

            return response()->json([
                'data' => [
                    'primaryImageId' => $product->primaryImage->id,
                    'images' => $images,
                    'videos' => $videos
                ]
            ], 200);
        }
        else return response()->json([
            'error' => 'Product does not exist'
        ], 404);
    }

    public function addMedia(Request $request, $product_id)
    {
        $file = $request->file('file');

        $product = Product::find($product_id);

        if (!$request->hasFile('file')) {
            return response()->json([
                'error' => 'Upload Failed'
            ], 500);
        }

        if ($file->isValid()) {

            $fileExtension = $file->getClientOriginalExtension();
            $fileName = $file->getClientOriginalName();

            if ($this->isImage($fileExtension) || $this->isVideo($fileExtension)) {
                $mediaInfo = $this->createMediaInfo(
                    $fileName, $fileExtension, $product_id, $product->type, $product->breed->name
                );
            }
            else return response()->json([
                'error' => 'Invalid File Extension'
            ], 500);

            Storage::disk('public')->put(
                $mediaInfo['directoryPath'].$mediaInfo['filename'],
                file_get_contents($file)
            );

            if ($file) {

                $media = $mediaInfo['type'];
                $media->name = $mediaInfo['filename'];

                if ($this->isImage($fileExtension)) {

                    $product->images()->save($media);
                    dispatch(new ResizeUploadedImage($media->name));

                    return response()->json([
                        'success'=> true,
                        'data' => [
                            'id' => $media->id,
                            'link' => route('serveImage', [
                                'size' => 'medium',
                                'filename' => $media->name
                            ]),
                        ]
                    ], 200);
                }
                else if ($this->isVideo($fileExtension)) {

                    $product->videos()->save($media);

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'id' => $media->id,
                            'mediaFileName' => $media->name
                        ]
                    ], 200);
                }
            }
            else return response()->json([
                'error' => 'Upload Failed'
            ], 500);
        }
        else return response()->json([
            'error' => 'Upload Failed'
        ], 500);
    }

    public function deleteMedia(Request $request, $product_id)
    {
        $image = Image::find($request->mediaId);

        if ($image) {
            $fullFilePath = self::PRODUCT_IMG_PATH.$image->name;
            $sFullFilePath = self::PRODUCT_SIMG_PATH.$image->name;
            $mFullFilePath = self::PRODUCT_MIMG_PATH.$image->name;
            $lFullFilePath = self::PRODUCT_LIMG_PATH.$image->name;

            // Check if file exists in the storage
            if(Storage::disk('public')->exists($fullFilePath)) Storage::disk('public')->delete($fullFilePath);
            if(Storage::disk('public')->exists($sFullFilePath)) Storage::disk('public')->delete($sFullFilePath);
            if(Storage::disk('public')->exists($mFullFilePath)) Storage::disk('public')->delete($mFullFilePath);
            if(Storage::disk('public')->exists($lFullFilePath)) Storage::disk('public')->delete($lFullFilePath);

            $image->delete();

            return response()->json([
                'success' => true,
            ], 200);
        }
        else return response()->json([
            'error' => 'Image does not exist'
        ], 404);
    }
}
