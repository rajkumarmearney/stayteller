<?php
namespace Modules\Booking\Models;

use App\BaseModel;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery\Exception;
use Modules\Booking\Emails\NewBookingEmail;
use Modules\Booking\Emails\StatusUpdatedEmail;
use Modules\Booking\Events\BookingUpdatedEvent;
use Modules\Hotel\Models\HotelRoomBooking;
use Modules\Space\Models\Space;
use Modules\Tour\Models\Tour;
use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Property\Models\Property;
use Modules\Review\Models\Review;
use Modules\User\Models\UserWishList;

class Booking extends BaseModel
{
    use SoftDeletes;
    protected $table      = 'bravo_bookings';
    protected $cachedMeta = [];
    //protected $cachedMetaArr = [];
    const DRAFT      = 'draft'; // New booking, before payment processing
    const UNPAID     = 'unpaid'; // Require payment
    const PROCESSING = 'processing'; // like offline - payment
    const CONFIRMED  = 'confirmed'; // after processing -> confirmed (for offline payment)
    const COMPLETED  = 'completed'; //
    const CANCELLED  = 'cancelled';
    const PAID       = 'paid'; //
    const PARTIAL_PAYMENT       = 'partial_payment'; //

    protected $casts = [
        'commission' => 'array',
    ];

    public static $notAcceptedStatus = [
        'draft','cancelled','unpaid'
    ];

    public function getGatewayObjAttribute()
    {
        return $this->gateway ? get_payment_gateway_obj($this->gateway) : false;
    }

    public function getStatusNameAttribute()
    {
        return booking_status_to_text($this->status);
    }

    public function getStatusClassAttribute()
    {
        switch ($this->status) {
            case "processing":
                return "primary";
                break;
            case "completed":
                return "success";
                break;
            case "confirmed":
                return "info";
                break;
            case "cancelled":
                return "danger";
                break;
            case "paid":
                return "info";
                break;
            case "partial_payment":
                return "warning";
                break;
        }
    }

    public function service()
    {
        $all = get_bookable_services();
        if ($this->object_model and !empty($all[$this->object_model])) {
            return $this->hasOne($all[$this->object_model], 'id', 'object_id');
        }
        return null;
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'id', 'payment_id');
    }

    public function getCheckoutUrl()
    {
        return url(app_get_locale(false,false , "/").config('booking.booking_route_prefix') . '/' . $this->code . '/checkout');
    }

    public function getDetailUrl($full = true)
    {
        if (!$full) {
            return app_get_locale(false,false , "/").config('booking.booking_route_prefix') . '/' . $this->code;
        }
        return url(app_get_locale(false,false , "/").config('booking.booking_route_prefix') . '/' . $this->code);
    }

    public function getMeta($key, $default = '')
    {
        //if(isset($this->cachedMeta[$key])) return $this->cachedMeta[$key];
        $val = DB::table('bravo_booking_meta')->where([
            'booking_id' => $this->id,
            'name'       => $key
        ])->first();
        if (!empty($val)) {
            //$this->cachedMeta[$key]  = $val->val;
            return $val->val;
        }
        return $default;
    }

    public function getJsonMeta($key, $default = [])
    {
        $meta = $this->getMeta($key, $default);
        if(empty($meta)) return false;
        return json_decode($meta, true);
    }

    public function addMeta($key, $val, $multiple = false)
    {

        if (is_object($val) or is_array($val))
            $val = json_encode($val);
        if ($multiple) {
            return DB::table('bravo_booking_meta')->insert([
                'name'       => $key,
                'val'        => $val,
                'booking_id' => $this->id
            ]);
        } else {
            $old = DB::table('bravo_booking_meta')->where([
                'booking_id' => $this->id,
                'name'       => $key
            ])->first();
            if ($old) {

                return DB::table('bravo_booking_meta')->where('id', $old->id)->insert([
                    'val' => $val
                ]);

            } else {
                return DB::table('bravo_booking_meta')->insert([
                    'name'       => $key,
                    'val'        => $val,
                    'booking_id' => $this->id
                ]);
            }
        }
    }

    public function batchInsertMeta($metaArrs = [])
    {
        if (!empty($metaArrs)) {
            foreach ($metaArrs as $key => $val) {
                $this->addMeta($key, $val, true);
            }
        }
    }

    public function generateCode()
    {
        return md5(uniqid() . rand(0, 99999));
    }

    public function save(array $options = [])
    {
        if (empty($this->code))
            $this->code = $this->generateCode();
        return parent::save($options); // TODO: Change the autogenerated stub
    }

    public function markAsProcessing($payment, $service)
    {

        $this->status = static::PROCESSING;
        $this->save();
    }

    public function markAsPaid()
    {
        if($this->paid < $this->total){
            $this->status = static::PARTIAL_PAYMENT;
        }else{
            $this->status = static::PAID;
        }

        $this->save();

        $this->sendStatusUpdatedEmails();

        event(new BookingUpdatedEvent($this));
    }

    public function markAsPaymentFailed(){

        $this->status = static::UNPAID;
        $this->save();

        $this->sendStatusUpdatedEmails();

        event(new BookingUpdatedEvent($this));

    }

    public function sendNewBookingEmails()
    {
        try {
            // To Admin
            Mail::to(setting_item('admin_email'))->send(new NewBookingEmail($this, 'admin'));

            // to Vendor
            Mail::to(User::find($this->vendor_id))->send(new NewBookingEmail($this, 'owner'));

            // To Customer
            Mail::to($this->email)->send(new NewBookingEmail($this, 'customer'));

        }catch (\Exception | \Swift_TransportException $exception){

            Log::warning('sendNewBookingEmails: '.$exception->getMessage());
        }
    }

    public function sendStatusUpdatedEmails(){
        // Try to update locale
        $old = app()->getLocale();

        $bookingLocale = $this->getMeta('locale');
        if($bookingLocale){
            app()->setLocale($bookingLocale);
        }
        try{
            // To Admin
            Mail::to(setting_item('admin_email'))->send(new StatusUpdatedEmail($this,'admin'));

            // to Vendor
            Mail::to(User::find($this->vendor_id))->send(new StatusUpdatedEmail($this,'agent'));

            // To Customer
            Mail::to($this->email)->send(new StatusUpdatedEmail($this,'customer'));


            app()->setLocale($old);

        } catch(\Exception $e){

            Log::warning('sendStatusUpdatedEmails: '.$e->getMessage());

        }

        app()->setLocale($old);
    }

    /**
     * Get Location
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function vendor()
    {
        return $this->hasOne("App\User", "id", 'vendor_id');
    }

    public static function getRecentBookings($limit = 10)
    {

        $q = parent::where('status', '!=', 'draft');
        return $q->orderBy('id', 'desc')->limit($limit)->get();
    }

    public static function getTopCardsReport()
    {
        $total_properties = Property::count();
        $total_views     = Property::sum('view');
        $total_favorites     = UserWishList::count();
        $total_reviews   = Review::join("bravo_properties","bravo_properties.id","bravo_review.object_id")->where("bravo_review.object_model","'property'")->count();
        $res = [];
        $total_service = 0;
        $services = get_bookable_services();
        if(!empty($services))
        {
            foreach ($services as $service){
                $total_service += $service::where('status', 'publish')->count('id');
            }
        }
        $res[] = [
            'size'   => 6,
            'size_md'=>3,
            'title'  => __("Properties"),
            'amount' => $total_properties,
            'desc'   => __("All Properties"),
            'class'  => 'purple',
            'icon'   => 'icon ion-ios-cart'
        ];
        $res[] = [
            'size'   => 6,
            'size_md'=>3,
            'title'  => __("Views"),
        'amount' => $total_views,
            'desc'   => __("Total Views"),
            'class'  => 'pink',
            'icon'   => 'icon ion-ios-eye'
        ];
        $res[] = [

            'size'   => 6,
            'size_md'=>3,
            'title'  => __("Reviews"),
            'amount' => $total_reviews,
            'desc'   => __("Total Visitor Reviews"),
            'class'  => 'info',
            'icon'   => 'icon ion-ios-document'
        ];
        $res[] = [

            'size'   => 6,
            'size_md'=>3,
            'title'  => __("Favorites"),
            'amount' => $total_favorites,
            'desc'   => __("Total Favorites"),
            'class'  => 'success',
            'icon'   => 'icon ion-ios-heart'
        ];
        return $res;
    }

    public static function getDashboardChartData($from, $to)
    {
        $data = [
            'labels'   => [],
            'datasets' => [
                [
                    'label'           => __("Total Revenue"),
                    'data'            => [],
                    'backgroundColor' => '#8892d6',
                    'stack'           => 'group-total',
                ],
                [
                    'label'           => __("Total Earning"),
                    'data'            => [],
                    'backgroundColor' => '#F06292',
                    'stack'           => 'group-extra',
                ]
            ]
        ];
        $sql_raw[] = 'sum(`total`) as total_price';
        $sql_raw[] = 'sum( `total` - `total_before_fees` + `commission` ) AS total_earning';
        if (($to - $from) / DAY_IN_SECONDS > 90) {
            $year = date("Y", $from);
            // Report By Month
            for ($month = 1; $month <= 12; $month++) {
                $day_last_month = date("t", strtotime($year . "-" . $month . "-01"));
                $dataBooking = parent::selectRaw(implode(",", $sql_raw))->whereBetween('created_at', [
                    $year . '-' . $month . '-01 00:00:00',
                    $year . '-' . $month . '-' . $day_last_month . ' 23:59:59'
                ])->whereNotIn('status',static::$notAcceptedStatus);
                if (!empty($customer_id)) {
                    $dataBooking = $dataBooking->where('customer_id', $customer_id);
                }
                if (!empty($vendor_id)) {
                    $dataBooking = $dataBooking->where('vendor_id', $vendor_id);
                }
                $dataBooking = $dataBooking->first();
                $data['labels'][] = date("F", strtotime($year . "-" . $month . "-01"));
                $data['datasets'][0]['data'][] = $dataBooking->total_price ?? 0;
                $data['datasets'][1]['data'][] = $dataBooking->total_earning ?? 0;
            }
        } elseif (($to - $from) <= DAY_IN_SECONDS) {
            // Report By Hours
            for ($i = strtotime(date('Y-m-d', $from)); $i <= strtotime(date('Y-m-d 23:59:59', $to)); $i += HOUR_IN_SECONDS) {
                $dataBooking = parent::selectRaw(implode(",", $sql_raw))->whereBetween('created_at', [
                    date('Y-m-d H:i:s', $i),
                    date('Y-m-d H:i:s', $i + HOUR_IN_SECONDS - 1),
                ])->whereNotIn('status',static::$notAcceptedStatus);
                if (!empty($customer_id)) {
                    $dataBooking = $dataBooking->where('customer_id', $customer_id);
                }
                if (!empty($vendor_id)) {
                    $dataBooking = $dataBooking->where('vendor_id', $vendor_id);
                }
                $dataBooking = $dataBooking->first();
                $data['labels'][] = date('H:i', $i);
                $data['datasets'][0]['data'][] = $dataBooking->total_price ?? 0;
                $data['datasets'][1]['data'][] = $dataBooking->total_earning ?? 0;
            }
        } else {
            // Report By Day
            for ($i = strtotime(date('Y-m-d', $from)); $i <= strtotime(date('Y-m-d 23:59:59', $to)); $i += DAY_IN_SECONDS) {
                $dataBooking = parent::selectRaw(implode(",", $sql_raw))->whereBetween('created_at', [
                    date('Y-m-d 00:00:00', $i),
                    date('Y-m-d 23:59:59', $i),
                ])->whereNotIn('status',static::$notAcceptedStatus);
                if (!empty($customer_id)) {
                    $dataBooking = $dataBooking->where('customer_id', $customer_id);
                }
                if (!empty($vendor_id)) {
                    $dataBooking = $dataBooking->where('vendor_id', $vendor_id);
                }
                $dataBooking = $dataBooking->first();
                $data['labels'][] = display_date($i);
                $data['datasets'][0]['data'][] = $dataBooking->total_price ?? 0;
                $data['datasets'][1]['data'][] = $dataBooking->total_earning ?? 0;
            }
        }
        return $data;
    }

    public static function getBookingHistory($booking_status = false, $customer_id = false , $vendor_id = false , $service = false)
    {
        $list_booking = parent::query()->orderBy('id', 'desc');
        if (!empty($booking_status)) {
            $list_booking->where("status", $booking_status);
        }
        if (!empty($customer_id)) {
            $list_booking->where("customer_id", $customer_id);
        }
        if (!empty($vendor_id)) {
            $list_booking->where("vendor_id", $vendor_id);
        }
        if (!empty($service)) {
            $list_booking->where("object_model", $service);
        }
        $list_booking->where('status','!=','draft');
        $list_booking->whereIn('object_model', array_keys(get_bookable_services()));
        return $list_booking->paginate(10);
    }

    public static function getTopCardsReportForVendor($user_id)
    {

        $res = [];
        $total_money = parent::selectRaw('sum( `total_before_fees` - `commission` ) AS total_price , sum( CASE WHEN `status` = "completed" THEN `total_before_fees` - `commission` ELSE NULL END ) AS total_earning')->whereNotIn('status',static::$notAcceptedStatus)->where("vendor_id", $user_id)->first();
        $total_booking = parent::whereNotIn('status',static::$notAcceptedStatus)->where("vendor_id", $user_id)->count('id');
        $total_service = 0;
        $services = get_bookable_services();
        if(!empty($services))
        {
            foreach ($services as $service){
                $total_service += $service::where('status', 'publish')->where("create_user", $user_id)->count('id');
            }
        }
        $res[] = [
            'title'  => __("Pending"),
            'amount' => format_money_main($total_money->total_price - $total_money->total_earning),
            'desc'   => __("Total pending"),
        ];
        $res[] = [
            'title'  => __("Earnings"),
            'amount' => format_money_main($total_money->total_earning ?? 0),
            'desc'   => __("Total earnings"),
        ];
        $res[] = [
            'title'  => __("Bookings"),
            'amount' => $total_booking,
            'desc'   => __("Total bookings"),
        ];
        $res[] = [
            'title'  => __("Services"),
            'amount' => $total_service,
            'desc'   => __("Total bookable services"),
        ];
        return $res;
    }

    public static function getEarningChartDataForVendor($from, $to, $user_id)
    {
        $data = [
            'labels'   => [],
            'datasets' => [
                [
                    'label'           => __("Total Earning"),
                    'data'            => [],
                    'backgroundColor' => '#F06292'
                ],
                [
                    'label'           => __("Total Pending"),
                    'data'            => [],
                    'backgroundColor' => '#8892d6'
                ]
            ]
        ];
        $sql_raw[] = 'sum( `total_before_fees` - `commission` ) AS total_price';
        $sql_raw[] = 'sum( CASE WHEN `status` = "completed" THEN `total_before_fees` - `commission` ELSE NULL END ) AS total_earning';
        if (($to - $from) / DAY_IN_SECONDS > 90) {
            $year = date("Y", $from);
            // Report By Month
            for ($month = 1; $month <= 12; $month++) {
                $day_last_month = date("t", strtotime($year . "-" . $month . "-01"));
                $data['labels'][] = date("F", strtotime($year . "-" . $month . "-01"));
                $dataBooking = parent::selectRaw(implode(",", $sql_raw))->where("vendor_id", $user_id)->whereBetween('created_at', [
                    $year . '-' . $month . '-01 00:00:00',
                    $year . '-' . $month . '-' . $day_last_month . ' 23:59:59'
                ])->whereNotIn('status',static::$notAcceptedStatus);
                $dataBooking = $dataBooking->first();
                $data['datasets'][1]['data'][] = $dataBooking->total_price - $dataBooking->total_earning;
                $data['datasets'][0]['data'][] = $dataBooking->total_earning ?? 0;
            }
        } elseif (($to - $from) <= DAY_IN_SECONDS) {
            // Report By Hours
            for ($i = strtotime(date('Y-m-d', $from)); $i <= strtotime(date('Y-m-d 23:59:59', $to)); $i += HOUR_IN_SECONDS) {
                $data['labels'][] = date('H:i', $i);
                $dataBooking = parent::selectRaw(implode(",", $sql_raw))->where("vendor_id", $user_id)->whereBetween('created_at', [
                    date('Y-m-d H:i:s', $i),
                    date('Y-m-d H:i:s', $i + HOUR_IN_SECONDS - 1),
                ])->whereNotIn('status',static::$notAcceptedStatus);
                $dataBooking = $dataBooking->first();
                $data['datasets'][1]['data'][] = $dataBooking->total_price - $dataBooking->total_earning;
                $data['datasets'][0]['data'][] = $dataBooking->total_earning ?? 0;
            }
        } else {
            // Report By Day
            for ($i = strtotime(date('Y-m-d', $from)); $i <= strtotime(date('Y-m-d 23:59:59', $to)); $i += DAY_IN_SECONDS) {
                $data['labels'][] = display_date($i);
                $dataBooking = parent::selectRaw(implode(",", $sql_raw))->where("vendor_id", $user_id)->whereBetween('created_at', [
                    date('Y-m-d 00:00:00', $i),
                    date('Y-m-d 23:59:59', $i),
                ])->whereNotIn('status',static::$notAcceptedStatus);
                $dataBooking = $dataBooking->first();
                $data['datasets'][1]['data'][] = $dataBooking->total_price - $dataBooking->total_earning;
                $data['datasets'][0]['data'][] = $dataBooking->total_earning ?? 0;
            }
        }
        return $data;
    }

    public static function countBookingByServiceID($service_id = false, $user_id = false, $status = false)
    {
        if (empty($service_id))
            return false;
        $count = parent::query()->where("object_id", $service_id);

        if (!empty($status)) {
            $count->where("status", $status);
        }else{
            $count->whereNotIn('status',static::$notAcceptedStatus);
        }

        if (!empty($user_id)) {
            $count->where("customer_id", $user_id);
        }
        return $count->count("id");
    }

    public static function getAcceptedBookingQuery($service_id,$object_type){

        $q = static::query();

        return $q->where([
            ['object_id','=',$service_id],
            ['object_model','=',$object_type],
        ])->whereNotIn('status',static::$notAcceptedStatus);

    }

    public static function clearDraftBookings($day = 2)
    {
        $q = static::query();
        $q->where([
            ['created_at','<=',date('Y-m-d H:i:s', strtotime('-'.$day.' days'))],
            ['status','=','draft']
        ])->forceDelete();
    }

    public static function getStatisticChartData($from, $to, $statuses = false, $customer_id = false, $vendor_id = false)
    {
        // fix ver 1.5.1
        if ($statuses) {
            $list_statuses = [];
            foreach ($statuses as $status) {
                if(!in_array($status , static::$notAcceptedStatus) ){
                    $list_statuses[] = $status;
                }
            }
            $statuses = $list_statuses;
        }
        $data = [
            "chart"  => [
                'labels'   => [],
                'datasets' => [
                    [
                        'label'           => __("Total Revenue"),
                        'data'            => [],
                        'backgroundColor' => '#8892d6',
                        'stack'           => 'group-total',
                    ],
                    [
                        'label'           => __("Total Fees"),
                        'data'            => [],
                        'backgroundColor' => '#45bbe0',
                        'stack'           => 'group-extra',
                    ],
                    [
                        'label'           => __("Total Commission"),
                        'data'            => [],
                        'backgroundColor' => '#F06292',
                        'stack'           => 'group-extra',
                    ]
                ]
            ],
            "detail" => [
                "total_booking" => [
                    "title" => __("Total Booking"),
                    "val"   => 0,
                ],
                "total_price" => [
                    "title" => __("Total Revenue"),
                    "val"   => 0,
                ],
                "total_commission" => [
                    "title" => __("Total Commission"),
                    "val"   => 0,
                ],
                "total_fees" => [
                    "title" => __("Total Fees"),
                    "val"   => 0,
                ],
                "total_earning" => [
                    "title" => __("Total Earning"),
                    "val"   => 0,
                ],
            ]
        ];
        $sql_raw[] = 'sum(`total`) as total_price';
        $sql_raw[] = 'sum( CASE WHEN `total_before_fees` > 0 THEN  `total` - `total_before_fees` ELSE null END ) AS total_fees';
        $sql_raw[] = 'sum( `commission` ) AS total_commission';
        if ($statuses) {
            $sql_raw[] = "count( CASE WHEN `status` != 'draft' THEN id ELSE NULL END ) AS total_booking";
            foreach ($statuses as $status) {
                if(!in_array($status , static::$notAcceptedStatus) ){
                    $sql_raw[] = "count( CASE WHEN `status` = '{$status}' THEN id ELSE NULL END ) AS {$status}";
                }
            }
        }
        if (($to - $from) / DAY_IN_SECONDS > 90) {
            $year = date("Y", $from);
            // Report By Month
            for ($month = 1; $month <= 12; $month++) {
                $day_last_month = date("t", strtotime($year . "-" . $month . "-01"));
                $dataBooking = parent::selectRaw(implode(",", $sql_raw))->whereBetween('created_at', [
                    $year . '-' . $month . '-01 00:00:00',
                    $year . '-' . $month . '-' . $day_last_month . ' 23:59:59'
                ])->whereNotIn('status',static::$notAcceptedStatus);
                if (!empty($customer_id)) {
                    $dataBooking = $dataBooking->where('customer_id', $customer_id);
                }
                if (!empty($vendor_id)) {
                    $dataBooking = $dataBooking->where('vendor_id', $vendor_id);
                }
                $dataBooking = $dataBooking->first();
                $data['chart']['labels'][] = date("F", strtotime($year . "-" . $month . "-01"));
                $data['chart']['datasets'][0]['data'][] = $dataBooking->total_price ?? 0; // for total price
                $data['chart']['datasets'][1]['data'][] = $dataBooking->total_fees ?? 0; // for total fees
                $data['chart']['datasets'][2]['data'][] = $dataBooking->total_commission ?? 0; // for total commission
                $data['detail']['total_price']['val'] += ($dataBooking->total_price ?? 0);
                $data['detail']['total_booking']['val'] += $dataBooking->total_booking ?? 0;
                $data['detail']['total_commission']['val'] += $dataBooking->total_commission ?? 0;
                $data['detail']['total_fees']['val'] += $dataBooking->total_fees ?? 0;
                $data['detail']['total_earning']['val'] += ( $dataBooking->total_fees + $dataBooking->total_commission );
                if ($statuses) {
                    foreach ($statuses as $status) {
                        $data['detail'][$status]['title'] = booking_status_to_text($status);
                        $data['detail'][$status]['val'] = ($data['detail'][$status]['val'] ?? 0) + $dataBooking->$status ?? 0;
                    }
                }
            }
        } elseif (($to - $from) <= DAY_IN_SECONDS) {
            // Report By Hours
            for ($i = strtotime(date('Y-m-d', $from)); $i <= strtotime(date('Y-m-d 23:59:59', $to)); $i += HOUR_IN_SECONDS) {
                $dataBooking = parent::selectRaw(implode(",", $sql_raw))->whereBetween('created_at', [
                    date('Y-m-d H:i:s', $i),
                    date('Y-m-d H:i:s', $i + HOUR_IN_SECONDS - 1),
                ])->whereNotIn('status',static::$notAcceptedStatus);
                if (!empty($customer_id)) {
                    $dataBooking = $dataBooking->where('customer_id', $customer_id);
                }
                if (!empty($vendor_id)) {
                    $dataBooking = $dataBooking->where('vendor_id', $vendor_id);
                }
                $dataBooking = $dataBooking->first();
                $data['chart']['labels'][] = date('H:i', $i);
                $data['chart']['datasets'][0]['data'][] = $dataBooking->total_price ?? 0; // for total price
                $data['chart']['datasets'][1]['data'][] = $dataBooking->total_fees ?? 0; // for total fees
                $data['chart']['datasets'][2]['data'][] = $dataBooking->total_commission ?? 0; // for total commission
                $data['detail']['total_price']['val'] += ($dataBooking->total_price ?? 0);
                $data['detail']['total_booking']['val'] += $dataBooking->total_booking ?? 0;
                $data['detail']['total_commission']['val'] += $dataBooking->total_commission ?? 0;
                $data['detail']['total_fees']['val'] += $dataBooking->total_fees ?? 0;
                $data['detail']['total_earning']['val'] += ( $dataBooking->total_fees + $dataBooking->total_commission );
                if ($statuses) {
                    foreach ($statuses as $status) {
                        $data['detail'][$status]['title'] = booking_status_to_text($status);
                        $data['detail'][$status]['val'] = ($data['detail'][$status]['val'] ?? 0) + $dataBooking->$status ?? 0;
                    }
                }
            }
        } else {
            // Report By Day
            for ($i = strtotime(date('Y-m-d', $from)); $i <= strtotime(date('Y-m-d 23:59:59', $to)); $i += DAY_IN_SECONDS) {
                $dataBooking = parent::selectRaw(implode(",", $sql_raw))->whereBetween('created_at', [
                    date('Y-m-d 00:00:00', $i),
                    date('Y-m-d 23:59:59', $i),
                ])->whereNotIn('status',static::$notAcceptedStatus);
                if (!empty($customer_id)) {
                    $dataBooking = $dataBooking->where('customer_id', $customer_id);
                }
                if (!empty($vendor_id)) {
                    $dataBooking = $dataBooking->where('vendor_id', $vendor_id);
                }
                $dataBooking = $dataBooking->first();
                $data['chart']['labels'][] = display_date($i);
                $data['chart']['datasets'][0]['data'][] = $dataBooking->total_price ?? 0; // for total price
                $data['chart']['datasets'][1]['data'][] = $dataBooking->total_fees ?? 0; // for total fees
                $data['chart']['datasets'][2]['data'][] = $dataBooking->total_commission ?? 0; // for total commission
                $data['detail']['total_price']['val'] += ($dataBooking->total_price ?? 0);
                $data['detail']['total_booking']['val'] += $dataBooking->total_booking ?? 0;
                $data['detail']['total_commission']['val'] += $dataBooking->total_commission ?? 0;
                $data['detail']['total_fees']['val'] += $dataBooking->total_fees ?? 0;
                $data['detail']['total_earning']['val'] += ( $dataBooking->total_fees + $dataBooking->total_commission );
                if ($statuses) {
                    foreach ($statuses as $status) {
                        $data['detail'][$status]['title'] = booking_status_to_text($status);
                        $data['detail'][$status]['val'] = ($data['detail'][$status]['val'] ?? 0) + $dataBooking->$status ?? 0;
                    }
                }
            }
        }
        $data['detail']['total_price']['val'] = format_money_main($data['detail']['total_price']['val']);
        $data['detail']['total_commission']['val'] = format_money_main($data['detail']['total_commission']['val']);
        $data['detail']['total_fees']['val'] = format_money_main($data['detail']['total_fees']['val']);
        $data['detail']['total_earning']['val'] = format_money_main($data['detail']['total_earning']['val']);
        return $data;
    }

    public function getDurationNightsAttribute(){

        $days = max(1,floor((strtotime($this->end_date) - strtotime($this->start_date)) / DAY_IN_SECONDS));

        return $days;
    }
    public function getDurationDaysAttribute(){

        $days = max(1,floor((strtotime($this->end_date) - strtotime($this->start_date)) / DAY_IN_SECONDS) + 1 );
        return $days;
    }
    public function  checkMaximumBooking($date){

    }

    public static function getBookingInRanges($object_id,$object_model,$from,$to,$object_child_id = false){

        $query = parent::selectRaw(" * , SUM( total_guests ) as total_guests ")->where([
            'object_id'=>$object_id,
            'object_model'=>$object_model,
        ])->whereNotIn('status',static::$notAcceptedStatus)
            ->where('end_date','>=',$from)
            ->where('start_date','<=',$to)
            ->groupBy('start_date')
            ->take(200);

        if($object_child_id){
            $query->where('object_child_id',$object_child_id);
        }

        return $query->get();
    }
    public static function getAllBookingInRanges($object_id,$object_model,$from,$to){

        $query = parent::selectRaw("*")->where([
            'object_id'=>$object_id,
            'object_model'=>$object_model,
        ])->whereNotIn('status',static::$notAcceptedStatus)
            ->where('end_date','>=',$from)
            ->where('start_date','<=',$to)
            ->take(200);
        return $query->get();
    }
    public function getCommissionVendor(){
        $vendorId = $this->vendor_id;
        $total = $this->total_before_fees;
        $returnArray=[
            'commission'=>0,
            'commission_type'=>'',
        ];
        if (setting_item('vendor_enable') == 1) {
            $vendor = User::find($vendorId);
            if (!empty($vendor)) {
                $commission = [];
                $commission['amount'] = setting_item('vendor_commission_amount', 10);
                $commission['type'] = setting_item('vendor_commission_type', 'percent');

                if($vendor->vendor_commission_type){
                    $commission['type'] = $vendor->vendor_commission_type;
                }
                if($vendor->vendor_commission_amount){
                    $commission['amount'] = $vendor->vendor_commission_amount;
                }

                if ($commission['type'] == 'percent') {
                    $returnArray['commission'] = (float)($total / 100) * $commission['amount'];
                } else {
                    $returnArray['commission']= (float)min($total,$commission['amount']);
                }
                $returnArray['commission_type'] = json_encode($commission);
            }
        }
        return $returnArray;
    }

    public function calculateCommission(){
        $data = $this->getCommissionVendor();

        $this->commission = $data['commission'];
        $this->commission_type = $data['commission_type'];
    }

	public static function getContentCalendarIcal($service_type,$id,$module){
		$proid = config('app.name') . ' ' . $_SERVER['SERVER_NAME'];
		$calendar = new Calendar($proid);
		$data  = $module::find($id);
		if (!empty($data)) {
			$bookingData = self::where('object_id', $id)->where('object_model', $service_type)
				->whereNotIn('status', self::$notAcceptedStatus)
				->where('start_date','>=',now())
				->get();
			if($service_type=='room'){
				$bookingData = HotelRoomBooking::where('room_id',$id)->whereHas('booking',function (Builder $query){
					$query->whereNotIn('status', self::$notAcceptedStatus)
						->where('start_date','>=',now());
				})->get();
			}
			if (!empty($bookingData)) {
				foreach ($bookingData as $item => $value) {
					if($service_type=='room'){
						$customerName = $value->fist_name . ' ' . $value->last_name;
						$description = '<p>Name:' . $customerName . '</p>
                                <p>Email:' . $value->email . '</p>
                                <p>Phone:' . $value->phone . '</p>
                                <p>Address:' . $value->address . '</p>
                                <p>Customer notes:' . $value->customer_notes . '</p>
                                <p>Total guest:' . $value->number . '</p>';
						$eventCalendar = new Event();
						$eventCalendar
							->setUniqueId($value->id.time())
							->setCategories(ucfirst($service_type))
							->setDtStart(new \DateTime($value->start_date))
							->setDtEnd(new \DateTime($value->end_date))
							->setSummary($customerName . ' Booking ' . ucfirst($service_type) . ' ' . $data->title)
							->setNoTime(false)
							->setDescriptionHTML($description);
						$calendar->addComponent($eventCalendar);
					}else{


					$customerName = $value->fist_name . ' ' . $value->last_name;
					$description = '<p>Name:' . $customerName . '</p>
                                <p>Email:' . $value->email . '</p>
                                <p>Phone:' . $value->phone . '</p>
                                <p>Address:' . $value->address . '</p>
                                <p>Customer notes:' . $value->customer_notes . '</p>
                                <p>Total guest:' . $value->total_guests . '</p>';
					$eventCalendar = new Event();
					$eventCalendar
						->setUniqueId($value->code)
						->setCategories(ucfirst($service_type))
						->setDtStart(new \DateTime($value->start_date))
						->setDtEnd(new \DateTime($value->end_date))
						->setSummary($customerName . ' Booking ' . ucfirst($service_type) . ' ' . $data->title)
						->setNoTime(false)
						->setDescriptionHTML($description);
					$calendar->addComponent($eventCalendar);
					}

				}
			}

		}
		return $calendar->render();
	}

	public function getTotalBeforeExtraPriceAttribute(){
		$extra_price = $this->getJsonMeta('extra_price');

        if(empty($extra_price) or !is_array($extra_price)) return $this->total_before_fees;

        $extra_price_collection = collect($extra_price);

        return $this->total_before_fees - $extra_price_collection->sum('total');
	}

}
