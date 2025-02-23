<?php

namespace App\Models;

use App\Models\Breeder;
use App\Models\Breed;
use App\Models\FarmAddress;
use App\Models\Image;
use App\Models\Video;
use App\Models\TransactionLog;
use App\Models\SwineCartItem;
use App\Models\ProductReservation;
use App\Observers\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, Searchable;

    public $timestamps = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['farm_from_id',
        'primary_img_id',
        'name',
        'type',
        'age',
        'breed_id',
        // 'price',
        'house_type',
        'min_price',
        'max_price',
        'birthweight',
        'lsba',
        'left_teats',
        'right_teats',
        'quantity',
        'adg',
        'fcr',
        'backfat_thickness',
        'other_details',
        'is_unique',
        'status'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['deleted_at'];


    public function breed()
    {
        return $this->belongsTo(Breed::class);
    }

    /**
     * Get the breeder that owns this product
     */
    public function breeder()
    {
        return $this->belongsTo(Breeder::class);
    }

    /**
     * Get the farm to where this product belongs
     */
    public function farmFrom()
    {
        return $this->belongsTo(FarmAddress::class, 'farm_from_id');
    }

    /**
     * Get all of the Product's images
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Get all of the Product's videos
     */
    public function videos()
    {
        return $this->morphMany(Video::class, 'videoable');
    }

    /**
     * Get all of the Product's reservations
     */
    public function reservations()
    {
        return $this->hasMany(ProductReservation::class);
    }

    /**
     * Get the respective Transaction Log of the Product
     */
    public function transactionLog()
    {
        return $this->hasOne(TransactionLog::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(Image::class, 'id', 'primary_img_id');
    }

    public function swineCartItem()
    {
        return $this->hasMany(SwineCartItem::class, 'product_id');
    }

}
