<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

use App\Http\Requests;
use App\Http\Requests\ProductRequest;
use App\Jobs\ResizeUploadedImage;
use App\Models\Breeder;
use App\Models\User;
use App\Models\FarmAddress;
use App\Models\Product;
use App\Models\Image;
use App\Models\Video;
use App\Models\Breed;
use App\Models\SwineCartItem;
use App\Repositories\ProductRepository;
use App\Repositories\CustomHelpers;
use App\Repositories\DashboardRepository;

use Auth;
use ImageManipulator;
use Storage;

class ProductController extends Controller
{
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

    protected $dashboard;
    protected $user;

	/**
     * Create new BreederController instance
     */
    public function __construct(DashboardRepository $dashboard)
    {
        $this->middleware('role:breeder',
        ['only' => ['showProducts',
            'breederViewProductDetail',
            'storeProduct',
            'updateProduct',
            'updateSelected',
            'deleteSelected',
            'uploadMedia',
            'deleteMedium',
            'productSummary',
            'setPrimaryPicture',
            'displayProduct']]);
        $this->middleware('updateProfile:breeder',
        ['only' => ['showProducts',
            'breederViewProductDetail',
            'storeProduct',
            'updateProduct',
            'updateSelected',
            'deleteSelected',
            'uploadMedia',
            'deleteMedium',
            'productSummary',
            'setPrimaryPicture',
            'displayProduct']]);
        $this->middleware('role:customer',['only' => ['customerViewProductDetail']]);
        $this->middleware('updateProfile:customer',['only' => ['customerViewProductDetail']]);
        $this->middleware(function($request, $next){
            $this->user = Auth::user();
            return $next($request);
        });
        $this->dashboard = $dashboard;
    }

    /**
     * ---------------------------------------
     *	BREEDER-SPECIFIC METHODS
     * ---------------------------------------
     */

    /**
     * Show the Breeder's products
     *
     * @param  Request $request
     * @return View
     */
    public function showProducts(Request $request)
    {
        // dd($request->all());
        $breeder = $this->user->userable;
        /*
          getting the breeder's products that are:
            quantity of non zero
            or
            quantity of zero but is a multiplier product
        */
        $products = $breeder->products()
                    ->whereIn('status', ['hidden','displayed','requested'])
                    ->where(function ($query) {
                      $query->where('quantity', '<>', 0)
                      ->orWhere([
                        ['is_unique', '=', 0],
                        ['quantity', '=', 0]
                      ]);
                    });

        // Check filters
        if($request->type && $request->type != 'all-type') {
          $products = $products->where('type', $request->type);
        }
        if($request->status && $request->status != 'all-status') {
          $products = $products->where('status',$request->status);
        }
        if($request->sort && $request->sort != 'none') {
            $part = explode('-',$request->sort);
            $products = $products->orderBy($part[0],$part[1])->paginate(15);
        }
        else $products = $products->orderBy('id', 'desc')->paginate(15);

        $farms = $breeder->farmAddresses;

        // For select elements
        $filters = [
            $request->type => 'selected',
            $request->status => 'selected',
            $request->sort => 'selected'
        ];

        // For pagination purposes
        $urlFilters = [
            'type' => $request->type,
            'status' => $request->status,
            'sort' => $request->sort,
            'page' => $products->currentPage()
        ];

        foreach ($products as $product) {
            $product->img_path = route('serveImage', ['size' => 'medium', 'filename' => Image::find($product->primary_img_id)->name]);
            $product->type = ucfirst($product->type);
            $product->birthdate = $this->transformDateSyntax($product->birthdate);
            $product->age = $this->computeAge($product->birthdate);
            $product->breed = $this->transformBreedSyntax(Breed::find($product->breed_id)->name);
            $product->other_details = $this->transformOtherDetailsSyntax($product->other_details);
        }

        return view('user.breeder.showProducts', compact('products', 'farms', 'filters', 'urlFilters'));
    }

    /**
     * View Details of a Product
     *
     * @param  Product  $product
     * @return View
     */
    public function breederViewProductDetail(Product $product)
    {
        $product->img_path = route('serveImage', ['size' => 'large', 'filename' => Image::find($product->primary_img_id)->name]);
        $product->def_img_path = route('serveImage', ['size' => 'default', 'filename' => Image::find($product->primary_img_id)->name]);
        $product->breeder = Breeder::find($product->breeder_id)->users->first()->name;
        $product->type = ucfirst($product->type);
        $product->birthdate = $this->transformDateSyntax($product->birthdate);
        $product->age = $this->computeAge($product->birthdate);
        $product->breed = $this->transformBreedSyntax(Breed::find($product->breed_id)->name);
        $product->farm_province = FarmAddress::find($product->farm_from_id)->province;
        $product->other_details = $product->other_details === null ? null : $this->transformOtherDetailsSyntax($product->other_details);
        $product->imageCollection = $product->images()->where('id', '!=', $product->primary_img_id)->get();
        $product->videoCollection = $product->videos;

        $reviews = Breeder::find($product->breeder_id)->reviews;
        $breederRatings = [
            'deliveryRating' => ($reviews->avg('rating_delivery')) ? $reviews->avg('rating_delivery') : 0,
            'transactionRating' => ($reviews->avg('rating_transaction')) ? $reviews->avg('rating_transaction') : 0,
            'productQualityRating' => ($reviews->avg('rating_productQuality')) ? $reviews->avg('rating_productQuality') : 0
        ];

        return view('user.breeder.viewProductDetail', compact('product', 'breederRatings'));
    }

    /**
     * Go to Add Product form for storing Product
     * @return View
    */

    public function createProduct(Request $request)
    {
      $breeder = $this->user->userable;
      $farms = $breeder->farmAddresses;

      return view('user.breeder.addProduct', compact('farms'));
    }

    /**
     * Store the Breeder's product
     * AJAX
     *
     * @param  Request $request
     * @return JSON
     */
    public function storeProduct(ProductRequest $request)
    {
        $breeder = $this->user->userable;

        if($request->ajax()){
            $product = new Product;
            $productDetail= [];

            // Create default primary picture for product
            if($request->type == 'boar') $image = Image::firstOrCreate(['name' => 'boar_default.jpg']);
            else if($request->type == 'sow') $image = Image::firstOrCreate(['name' => 'sow_default.jpg']);
            else if($request->type == 'gilt') $image = Image::firstOrCreate(['name' => 'gilt_default.jpg']);
            else $image = Image::firstOrCreate(['name' => 'semen_default.jpg']);

            $product->farm_from_id = $request->farm_from_id;
            $product->primary_img_id = $image->id;
            $product->name = $request->name;
            $product->type = $request->type;
            $product->house_type = $request->house_type === '' ? null : $request->house_type;
            if ($request->birthdate === "") {
              $product->birthdate = '';
            }
            else {
              $product->birthdate = date_format(date_create($request->birthdate), 'Y-n-j');
            }
            $product->birthweight = $request->birthweight === '' ? null : $request->birthweight;
            $product->breed_id = $this->findOrCreateBreed(strtolower($request->breed));
            $product->min_price = $request->min_price === '' ? null : $request->min_price;
            $product->max_price = $request->max_price === '' ? null : $request->max_price;
            $product->quantity = ($request->type == 'semen') ? -1 : 1;
            $product->lsba = $request->lsba === '' ? null : $request->lsba;
            $product->adg = $request->adg === '' ? null : $request->adg;
            $product->fcr = $request->fcr === '' ? null : $request->fcr;
            $product->backfat_thickness = $request->backfat_thickness === '' ? null : $request->backfat_thickness;
            $product->left_teats = $request->left_teats === '' ? null : $request->left_teats;
            $product->right_teats = $request->right_teats === '' ? null : $request->right_teats;
            $product->other_details = $request->right_teats === '' ? null : $request->right_teats;
            $product->is_unique = $request->is_unique;
            $product->quantity = $request->quantity;

            $breeder->products()->save($product);

            $productDetail['product_id'] = $product->id;
            $productDetail['name'] = $product->name;
            $productDetail['type'] = ucfirst($request->type);
            $productDetail['breed'] = $request->breed;

            return collect($productDetail)->toJson();
            //return Redirect::to('user.breeder.showProducts');
        }
    }
    /**
     * Go to Edit Page of a Product
     *
     * @param Product $product
     * @return View
     *
     */

    public function editProduct(Product $product)
    {
      $breeder = $this->user->userable;
      $farms = $breeder->farmAddresses;

      $product->img_path = route('serveImage', ['size' => 'large', 'filename' => Image::find($product->primary_img_id)->name]);
      $product->def_img_path = route('serveImage', ['size' => 'default', 'filename' => Image::find($product->primary_img_id)->name]);
      $product->breeder = Breeder::find($product->breeder_id)->users->first();
      $product->breeder = $product->breeder['name'];
      $product->type = ucfirst($product->type);
      if ($product->birthdate != "0000-00-00") $product->birthdate = $this->transformDateSyntax($product->birthdate);
      $product->age = $this->computeAge($product->birthdate);
      $product->breed = $this->transformBreedSyntax(Breed::find($product->breed_id)->name);
      $product->farm_province = FarmAddress::find($product->farm_from_id)->province;
      $product->imageCollection = $product->images()->where('id', '!=', $product->primary_img_id)->get();
      $product->videoCollection = $product->videos;

      //dd($product);

      return view('user.breeder.editProduct', compact('product', 'farms'));
    }

    /**
     * Update details of a Product
     * AJAX
     *
     * @param  Request $request
     * @return String
     */
    public function updateProduct(ProductRequest $request)
    {
        if($request->ajax()){
          $product = Product::find($request->id);
          $product->farm_from_id = $request->farm_from_id;
          $product->name = $request->name;
          $product->type = $request->type;
          if ($request->birthdate === '') {
            $product->birthdate = "0000-00-00";
          }
          else {
            $product->birthdate = date_format(date_create($request->birthdate), 'Y-n-j');
          }

          $product->birthweight = $request->birthweight === '' ? null : $request->birthweight;

          $product->breed_id = $this->findOrCreateBreed(strtolower($request->breed));

          $product->house_type = $request->house_type === 'none' ? null : $request->house_type;

          // $product->price = $request->price;
          $product->min_price = $request->min_price === '' ? null : $request->min_price;
          $product->max_price = $request->max_price === '' ? null : $request->max_price;


          $product->adg = $request->adg === '' ? null : $request->adg;
          $product->fcr = $request->fcr === '' ? null : $request->fcr;
          $product->backfat_thickness = $request->backfat_thickness === '' ? null : $request->backfat_thickness;
          $product->lsba = $request->lsba === '' ? null : $request->lsba;

          $product->left_teats = $request->left_teats === '' ? null : $request->left_teats;
          $product->right_teats = $request->right_teats === '' ? null : $request->right_teats;

          $product->quantity = $request->quantity;
          $product->is_unique = $request->is_unique;

          $product->other_details = $request->other_details === '' ? null : $request->other_details;
          $product->save();

          return "OK";
        }
    }

    /**
     * Update selected products
     * AJAX
     *
     * @param  Request $request
     * @return String
     */
    public function updateSelected(Request $request)
    {
        if($request->ajax() && $request->updateTo_status == 'display'){
            foreach ($request->product_ids as $id) {
                $product = Product::find($id);
                $product->status = 'displayed';
                $product->save();
            }
            return "OK";
        }
        else if($request->ajax() && $request->updateTo_status == 'hide'){
            foreach ($request->product_ids as $id) {
                $product = Product::find($id);
                $product->status = 'hidden';
                $product->save();
            }
            return "OK";
        }

    }

    /**
     * Delete selected products
     * AJAX
     *
     * @param  Request $request
     * @return String
     */
    public function deleteSelected(Request $request)
    {
        if($request->ajax()){
            foreach ($request->product_ids as $id) {
                $product = Product::find($id);

                // Delete images associated to product
                foreach ($product->images as $image) {
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
                }

                // Delete videos associated to product
                foreach ($product->videos as $video) {
                    $fullFilePath = self::PRODUCT_VID_PATH.$video->name;

                    // Check if file exists in the storage
                    if(Storage::disk('public')->exists($fullFilePath)) Storage::disk('public')->delete($fullFilePath);

                    $video->delete();
                }

                $breedId = $product->breed_id;
                $product->delete();

                // Delete breed from Breed database record if there are
                // no products of the same breed found
                // after product deletion
                $breedInstance = Product::where('breed_id',$breedId)->get()->first();
                if(!$breedInstance){
                    $breed = Breed::find($breedId);
                    $breed->delete();
                }
            }
            return "OK";
        }
    }

    /**
     * Upload media for a product
     *
     * @param  Request $request
     * @return JSON
     */
    public function uploadMedia(Request $request)
    {
        // Check if request contains media file input
        if($request->hasFile('media')) {
            $files = $request->file('media.*');
            $fileDetails = [];

            foreach ($files as $file) {

                // Check if file has no problems in uploading
                if($file->isValid()){
                    $fileExtension = $file->getClientOriginalExtension();
                    $fileName = $file->getClientOriginalName();

                    // Get media (Image/Video) info according to extension
                    //if($this->isImage($fileExtension)) $mediaInfo = $this->createMediaInfo($file);
                    //else if($this->isVideo($fileExtension)) $mediaInfo = $this->createMediaInfo($file);
                    if($this->isImage($fileExtension)) $mediaInfo = $this->createMediaInfo($fileName, $fileExtension, $request->productId, $request->type, $request->breed);
                    else if($this->isVideo($fileExtension)) $mediaInfo = $this->createMediaInfo($fileName, $fileExtension, $request->productId, $request->type, $request->breed);
                    else return response()->json('Invalid file extension', 500);

                    Storage::disk('public')->put($mediaInfo['directoryPath'].$mediaInfo['filename'], file_get_contents($file));

                    // Check if file is successfully moved to desired path
                    if($file){
                        $product = Product::find($request->productId);

                        // Make Image/Video instance
                        $media = $mediaInfo['type'];
                        $media->name = $mediaInfo['filename'];

                        if($this->isImage($fileExtension)){
                            $product->images()->save($media);

                            // Resize images
                            dispatch(new ResizeUploadedImage($media->name));
                        }
                        else if($this->isVideo($fileExtension)) $product->videos()->save($media);

                        array_push($fileDetails, ['id' => $media->id, 'name' => $media->name]);
                    }
                    else return response()->json('Move file failed', 500);
                }
                else {
                  return response()->json('Upload failed', 500);
                }
            }

            return response()->json(collect($fileDetails)->toJson(), 200);
        }
        else return response()->json('No files detected', 500);
    }

    /**
     * Delete a media of a Product
     * AJAX
     *
     * @param  Request $request
     * @return JSON
     */
    public function deleteMedium(Request $request)
    {
        if($request->ajax()){
            if($request->mediaType == 'image'){
                $image = Image::find($request->mediaId);
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
            }
            else if($request->mediaType = 'video'){
                $video = Video::find($request->mediaId);
                $fullFilePath = self::PRODUCT_VID_PATH.$video->name;

                // Check if file exists in the storage
                if(Storage::disk('public')->exists($fullFilePath)) Storage::disk('public')->delete($fullFilePath);

                $video->delete();
            }

            return response()->json('File deleted', 200);
        }
    }

    /**
     * Get summary of Product
     *
     * @param  Request $request
     * @return JSON
     */
    public function productSummary(Request $request)
    {
        if($request->ajax()){
            $product = Product::find($request->product_id);
            $product->type = ucfirst($product->type);
            $product->breed = $this->transformBreedSyntax(Breed::find($product->breed_id)->name);
            $product->farm_province = FarmAddress::find($product->farm_from_id)->province;
            $product->birthdate = $this->transformDateSyntax($product->birthdate);
            $product->imageCollection = $product->images;
            $product->videoCollection = $product->videos;
            return $product->toJson();
        }
    }

    /**
     * Set the primary picture of a Product
     *
     * @param  Request $request
     * @return String
     */
    public function setPrimaryPicture(Request $request)
    {
        if($request->ajax()){
            $product = Product::find($request->product_id);
            $product->primary_img_id = $request->img_id;
            $product->save();

            return "OK";
        }
    }

    /**
     * Display Product
     *
     * @param  Request $request
     * @return String
     */
    public function displayProduct(Request $request)
    {
        if($request->ajax()){
            $product = Product::find($request->product_id);
            $product->status = 'displayed';
            $product->save();

            return "OK";
        }
    }

    /**
     * ---------------------------------------
     *  CUSTOMER-SPECIFIC METHODS
     * ---------------------------------------
     */

    /**
     * View Products of all Breeders
     *
     * @param  Request              $request
     * @param  ProductRepository    $repository
     * @return View
     */
    public function viewProducts(Request $request, ProductRepository $repository)
    {
        // Check if from a search query
        /* $products = ($request->q)
                      ?
                      $repository->search($request->q)
                      :
                      Product::whereIn('status', ['displayed', 'requested'])->where('quantity', '!=', 0); */

        $products = ($request->q)
                      ?
                      $repository->search($request->q)
                      :
                      Product::whereIn('status', ['displayed','requested'])->where(function ($query) {
                        $query->where('quantity', '<>', 0)
                        ->orWhere([
                          ['is_unique', '=', 0],
                          ['quantity', '=', 0]
                        ]);
                      });

        $scores = ($request->q && isset($products->scores)) ? $products->scores : [];

        // return breeders with user name and the breeder_id table
        $breeders = $this->getBreeders();

        $separatedBreeders = explode(' ', $request->breeder);

        // only get the requested breeders in all the breeders based on the breeder_id
        $parsedBreederIds = [];
        foreach($separatedBreeders as $requestBreeder) {
          foreach($breeders as $breeder) {
            $breederName = str_replace('-', ' ', $requestBreeder);
            if ($breederName === $breeder['user']->name) array_push($parsedBreederIds, $breeder['breeder']->id);
          }
        }

        $parsedTypes = ($request->type) ? explode(' ',$request->type) : '';
        $parsedBreedIds = ($request->breed) ? $this->getBreedIds($request->breed) : '';

        $parsedSort = ($request->sort && $request->sort != 'none') ? explode('-',$request->sort) : ['id', 'desc'];

        if($parsedTypes) $products = $products->whereIn('type', $parsedTypes);
        if($parsedBreedIds) $products = $products->whereIn('breed_id', $parsedBreedIds);
        if($parsedBreederIds) $products = $products->whereIn('breeder_id', $parsedBreederIds);

        $products;
        if ($request->q) {
          $products = $products->get();
        } else if ($request->sort && $request->sort === 'breederrating-asc') {

          // get the products and its breeder, then the breeder rating overall
          $products = Product::with('breeder')->get()->map(function($product) {
            $breederOfProduct = Breeder::where('id', $product->breeder_id)->first();
            $breederSummaryReviewsAndRatings = $this->dashboard->getSummaryReviewsAndRatings($breederOfProduct);
            $product->breeder_rating = $breederSummaryReviewsAndRatings['overall'];
            return $product;
          });

          $products = $products->sortByDesc('breeder_rating');
          $products->values()->all(); // laravel thingy

        } else {
          $products = $products->orderBy($parsedSort[0], $parsedSort[1])->get();
        }

        foreach ($products as $key => $product) {
            // Check if the farm where the product is is still accredited
            // Exclude it from the showcase of products if farm's
            // accreditation status is not active
            Log::debug("Looping now in $key. Product is " . json_encode($product));
            if($product->farmFrom->accreditation_status != 'active'){
                unset($products[$key]);
                continue;
            }
            $product->img_path = route('serveImage', ['size' => 'medium', 'filename' => Image::find($product->primary_img_id)->name]);
            $product->type = ucfirst($product->type);
            $product->birthdate = $this->transformDateSyntax($product->birthdate);
            $product->age = $this->computeAge($product->birthdate);
            $product->breed = $this->transformBreedSyntax(Breed::find($product->breed_id)->name);
            $product->breeder = Breeder::find($product->breeder_id)->users()->first();
            $product->breeder = $product->breeder['name'];
            $product->farm_province = FarmAddress::find($product->farm_from_id)->province;
            $product->score = ($request->q && count($scores) > 0) ? $scores[$product->id] : 0;
        }

        // Sort according to score if from a search query
        if($request->q) $products = $products->sortByDesc('score');

        // Manual pagination
        $page = ($request->page) ? $request->page : 1;
        $perPage = 10;
        $offset = ($page * $perPage) - $perPage;
        $products = new LengthAwarePaginator(
                array_slice($products->all(), $offset, $perPage, true),
                count($products),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

        $filters = $this->parseThenJoinFilters($request->type, $request->breed, $request->breeder, $request->sort);
        $breedFilters = Breed::where('name','not like', '%+%')->where('name','not like', '')->orderBy('name','asc')->get();

        $urlFilters = [
          'q' => $request->q,
          'type' => $request->type,
          'breed' => $request->breed,
          'breeder' => $request->breeder,
          'sort' => $request->sort,
          'page' => $products->currentPage()
        ];

        /* return view(
          'user.customer.viewProducts',
          compact('products', 'breeders', 'filters', 'breedFilters', 'urlFilters')
        ); */

        return view(
          'products',
          compact('products', 'breeders', 'filters', 'breedFilters', 'urlFilters')
        );
    }

    /**
     * View Breeder's Profile
     *
     * @param  Breeder  $breeder
     * @return View
     */
    public function viewBreederProfile(Breeder $breeder, $breeder_handle)
    {
        $breeder = Breeder::where('breeder_handle', $breeder_handle)->first();

        $breeder->name = $breeder->users()->first()->name;
        $breeder->farms = $breeder->farmAddresses;
        $breeder->logoImage =
          ($breeder->logo_img_id) ?
            self::BREEDER_IMG_PATH.Image::find($breeder->logo_img_id)->name :
            self::IMG_PATH.'default_logo.png' ;

        $products = $breeder->products()->get();

        foreach ($products as $key => $product) {
            // Check if the farm where the product is is still accredited
            // Exclude it from the showcase of products if farm's
            // accreditation status is not active
            if($product->farmFrom->accreditation_status != 'active'){
                unset($products[$key]);
                continue;
            }
            $product->img_path = route('serveImage', ['size' => 'medium', 'filename' => Image::find($product->primary_img_id)->name]);
            $product->type = ucfirst($product->type);
            $product->birthdate = $this->transformDateSyntax($product->birthdate);
            $product->age = $this->computeAge($product->birthdate);
            $product->breed = $this->transformBreedSyntax(Breed::find($product->breed_id)->name);
            $product->farm_province = FarmAddress::find($product->farm_from_id)->province;
        }

        return view('user.customer.viewBreederProfile', compact('breeder', 'products'));
    }

    /**
     * View Details of a Product
     *
     * @param  Product  $product
     * @return View
     */
    public function customerViewProductDetail(Product $product)
    {
        if($product->status == 'hidden') return back();
        $product->img_path = route('serveImage', ['size' => 'large', 'filename' => Image::find($product->primary_img_id)->name]);
        $product->def_img_path = route('serveImage', ['size' => 'default', 'filename' => Image::find($product->primary_img_id)->name]);
        $product->breeder = Breeder::find($product->breeder_id)->users->first()->name;
        $product->birthdate = $this->transformDateSyntax($product->birthdate);
        $product->age = $this->computeAge($product->birthdate);
        $product->type = ucfirst($product->type);
        $product->breed = $this->transformBreedSyntax(Breed::find($product->breed_id)->name);
        $product->farm_province = FarmAddress::find($product->farm_from_id)->province;
        $product->other_details = $product->other_details === null ? null : $this->transformOtherDetailsSyntax($product->other_details);
        $product->imageCollection = $product->images()->where('id', '!=', $product->primary_img_id)->get();
        $product->videoCollection = $product->videos;
        $product->userid = Breeder::find($product->breeder_id)->users->first()->id;

        $reviews = Breeder::find($product->breeder_id)->reviews;
        $breederRatings = [
            'deliveryRating' => ($reviews->avg('rating_delivery')) ? $reviews->avg('rating_delivery') : 0,
            'transactionRating' => ($reviews->avg('rating_transaction')) ? $reviews->avg('rating_transaction') : 0,
            'productQualityRating' => ($reviews->avg('rating_productQuality')) ? $reviews->avg('rating_productQuality') : 0
        ];

        return view('user.customer.viewProductDetail', compact('product', 'breederRatings'));
    }

    /**
     * ---------------------------------------
     *  PRIVATE METHODS
     * ---------------------------------------
     */

    /**
     * Find breed_id through breed name ($breed)
     * or create another breed if not found
     *
     * @param  String   $breed
     * @return Integer
     */
    private function findOrCreateBreed($breed)
    {
        $breedInstance = Breed::where('name','like',$breed)->get()->first();
        if($breedInstance) return $breedInstance->id;
        else{
            $breed = str_replace(' ', '-', $breed);
            $newBreed = Breed::create(['name' => $breed]);
            return $newBreed->id;
        }
    }

    /**
     * Get appropriate media (Image/Video) info depending on extension
     *
     * @param  String           $filename
     * @param  String           $extension
     * @param  Integer          $productId
     * @param  String           $type
     * @param  String           $breed
     * @return AssociativeArray $mediaInfo
     */
    //private function createMediaInfo($file)
    private function createMediaInfo($filename, $extension, $productId, $type, $breed)
    {
        $mediaInfo = [];

        $slug = md5(mt_rand());

        if(str_contains($breed,'+')){
            $part = explode("+", $breed);
            $mediaInfo['filename'] = $productId . '_' . $type . '_' . $part[0] . ucfirst($part[1]) . '_' . $slug . '.' . $extension;
        }
        else {
            $mediaInfo['filename'] = $productId . '_' . $type . '_' . $breed . '_' . $slug . '.' . $extension;
        }
        /* $filename = $file->getClientOriginalName();
        $pathname = $file->getPathName();
        dd($pathname);
        $fileExtension = $file->getClientOriginalExtension();
        $mediaInfo['filename'] = md5_file($pathname . '/' . $filename) . '.' . $fileExtension; */

        //$mediaInfo['fileName'] = str_replace('/', '_', $mediaInfo['fileName']);

        if($this->isImage($extension)){
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
     * Parse the Filters according to Type, Breed, and Sort By
     *
     * @param   String          $typeParameter
     * @param   String          $breedParameter
     * @param   String          $sortParameter
     * @return  AssocativeArray
     */
    private function parseThenJoinFilters($typeParameter, $breedParameter, $breederParameter, $sortParameter)
    {
        $tempFilters = [];

        if($typeParameter){
            // Parse if there is more than one type filter value
            $types = explode(' ',$typeParameter);
            foreach ($types as $type) {
                $tempFilters[$type] = 'checked';
            }
        }

        if($breedParameter){
            // Parse if there is more than one breed filter value
            $breeds = explode(' ',$breedParameter);
            foreach ($breeds as $breed) {
                $tempFilters[$breed] = 'checked';
            }
        }

        if($breederParameter){
          // Parse if there is more than one breeder filter value
          $breeders = explode(' ', $breederParameter);
          foreach ($breeders as $breeder) {
            $breeder = str_replace('-', ' ', $breeder);
            $tempFilters[$breeder] = 'checked';
          }
        }

        $tempFilters[$sortParameter] = 'selected';

        return $tempFilters;
    }


    /**
     * Get breed ids of products based from breed filter value
     *
     * @param   String  $breedParameter
     * @return  Array
     */
    private function getBreedIds($breedParameter)
    {
        $tempBreedIds = [];
        foreach (explode(' ', $breedParameter) as $breedName) {
            if($breedName == 'crossbreed') {
                // Get all breed ids that contain '+' in their breed name
                $crossbreeds = Breed::where('name','like','%+%')->get();
                foreach ($crossbreeds as $crossbreed) {
                    array_push($tempBreedIds, $crossbreed->id);
                }
                continue;
            }
            else $breedInstance = Breed::where('name',$breedName)->get()->first()->id;
            array_push($tempBreedIds, $breedInstance);
        }

        return $tempBreedIds;
    }

    /**
     * Get breeders
     *
     * @param   NULL
     * @return  Array of Breeders
     */
    private function getBreeders() {
      // return breeders with user name and the breeder_id table
      $breeders = Breeder::with('users')->get()->map(function($breeder) {
        $user = $breeder->users->first();
        return [
          'user' => $user,
          'breeder' => $breeder,
        ];
      });

      return $breeders;
    }

}
