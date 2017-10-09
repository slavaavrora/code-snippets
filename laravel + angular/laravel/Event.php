<?php

namespace App;

use App\Traits\ActiveScopeTrait;
use App\Traits\WithArticleLangTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Event
 *
 * @package App
 *
 * @property integer                                                 $id
 * @property integer                                                 $city_id
 * @property integer                                                 $place_id
 * @property integer                                                 $photo_id
 * @property integer                                                 $latitude
 * @property integer                                                 $longitude
 * @property integer                                                 $like_counter
 * @property integer                                                 $go_counter
 * @property string                                                  $email
 * @property string                                                  $phone
 * @property string                                                  $site
 * @property boolean                                                 $active
 * @property boolean                                                 $popular
 * @property Carbon                                                  $start_at
 * @property Carbon                                                  $end_at
 * @property array                                                   $work_schedule
 * @property array                                                   $price_schedule
 * @property Carbon                                                  $created_at
 * @property Carbon                                                  $updated_at
 * @property Carbon                                                  $deleted_at
 * @property integer                                                 $kind
 * @property integer                                                 $people_needed
 *
 * -- Mutations
 * @property-read boolean                                            $liked
 * @property-read boolean                                            $going
 * @property-read boolean                                            $favoriting
 * @property-read string                                             $currency
 * @property-read float                                              $price
 *
 * -- Relations
 * @property \Illuminate\Database\Eloquent\Collection|EventType[]    $eventTypes
 * @property \Illuminate\Database\Eloquent\Collection|EventLang[]    $langs
 * @property EventLang                                               $lang
 * @property \Illuminate\Database\Eloquent\Collection|Photo[]        $photos
 * @property Photo                                                   $photo
 * @property \Illuminate\Database\Eloquent\Collection                $likes
 * @property \Illuminate\Database\Eloquent\Collection                $goes
 * @property \Illuminate\Database\Eloquent\Collection|EventComment[] $comments
 * @property City                                                    $city
 * @property Place                                                   $place
 * @property User[]                                                  $users
 *
 * -- Scopes
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder kind(integer $kind = 0)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder forCity(integer $city)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder fromDate(integer $date)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder isPopular()
 */
class Event extends AbstractWithLangModel
{
    use SoftDeletes, ActiveScopeTrait, WithArticleLangTrait;

    const KIND_EVENT = 0;
    const KIND_AND_LETS = 1;

    protected $table = 'events';

    protected $fillable = [
        'place_id',
        'photo_id',
        'latitude',
        'longitude',
        'email',
        'phone',
        'site',
        'popular',
        'start_at',
        'end_at',
        'work_schedule',
        'price_schedule',
        'people_needed',
    ];

    protected $casts = [
        'active' => 'boolean',
        'popular' => 'boolean',
        'work_schedule' => 'json',
        'price_schedule' => 'json',
        'start_at' => 'timestamp',
        'end_at' => 'timestamp',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'deleted_at' => 'timestamp',
    ];

    protected $dates = ['start_at', 'end_at', 'deleted_at'];

    protected $appends = [
        'title',
        'description',
        'article_title',
        'article_content',
        'address',
        'contacts',
    ];

    protected $with = ['lang', 'photo', 'eventTypes'];

    protected $attributes = [
        'active' => true, //false,
        'popular' => false,
    ];

    protected static $globalScopes = ['fromDate'];

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param integer                               $kind
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeKind($query, $kind = self::KIND_EVENT)
    {
        return $query->where('kind', $kind);
    }

    /**
     * Scope a query to get events for city.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param integer                               $city
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCity($query, $city)
    {
        return $query->where('city_id', $city);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param integer                               $date
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromDate($query, $date = null)
    {
        if (empty($date)) {
            $date = Carbon::now();
        }

        if ( ! ($date instanceof Carbon)) {
            $date = new Carbon($date);
        }

        return ($date instanceof Carbon) ? $query->where('start_at', '>=',
            $date->format('Y-m-d 00:00:00')) : $query;
    }

    /**
     * Scope a query to get events for city.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsPopular($query)
    {
        return $query->where('popular', true);
    }

    /**
     * @return bool
     */
    public function getLikedAttribute()
    {
        $auth = auth();

        return $auth->check() ? $this->likes()->find($auth->id()) != null
            : false;
    }

    /**
     * @return bool
     */
    public function getGoingAttribute()
    {
        $auth = auth();

        return $auth->check() ? $this->goes()->find($auth->id()) != null
            : false;
    }

    /**
     * @return bool
     */
    public function getFavoritingAttribute()
    {
        $auth = auth();

        return $auth->check() ? $this->favorites()->find($auth->id()) != null
            : false;
    }

    /**
     * @return string
     */
    public function getCurrencyAttribute()
    {
        if ($this->price_schedule
            && ! empty($this->price_schedule['currency'])
        ) {
            return Currency::find($this->price_schedule['currency']);
        }

        return null;
    }

    /**
     * @return float
     */
    public function getPriceAttribute()
    {
        return $this->price_schedule && ! empty($this->price_schedule['price'])
            ? (float)$this->price_schedule['price']
            : null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function eventTypes()
    {
        return $this->belongsToMany('App\EventType', 'event_event_types');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function photos()
    {
        return $this->belongsToMany('App\Photo', 'event_photos');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function photo()
    {
        return $this->belongsTo('App\Photo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function city()
    {
        return $this->belongsTo('App\City');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function place()
    {
        return $this->belongsTo('App\Place');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likes()
    {
        return $this->belongsToMany('App\User', 'event_likes');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany('App\User', 'user_events');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function goes()
    {
        return $this->belongsToMany('App\User', 'event_goes');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function favorites()
    {
        return $this->morphToMany('App\User', 'favoritable', 'favorites');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany('App\EventComment');
    }

    /**
     * Return Lang model name
     *
     * @return mixed
     */
    public function getLangRelated()
    {
        return 'App\EventLang';
    }
}
