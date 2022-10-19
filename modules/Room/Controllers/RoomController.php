<?php
namespace Modules\Room\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Room\Models\Room;
use Validator;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    protected $roomClass;

    public function __construct()
    {
        $this->roomClass      = Room :: class;
    }

    public function addReview(Request $request)
    {
       
        if (!Auth::id()) {
            return redirect()->to(url()->previous() . '#review-form')->with('error', __('Please login'));
        }
        $service_type = $request->input('review_service_type');
        $service_id = $request->input('review_service_id');
        $allServices = get_reviewable_services();
        $allServicesBooking = get_bookable_services();

        if (empty($allServices[$service_type])) {
            return redirect()->to(url()->previous() . '#review-form')->with('error', __('Service type not found'));
        }
        $module_class = $allServices[$service_type];
        $module = $module_class::find($service_id);

        if(empty($module)){
            return redirect()->to(url()->previous() . '#review-form')->with('error', __('Service not found'));
        }

        $reviewEnable = $module->getReviewEnable();
        if (!$reviewEnable) {
            return redirect()->to(url()->previous() . '#review-form')->with('error', __('Review not enable'));
        }
        $reviewEnableAfterBooking = $module->check_enable_review_after_booking();
        if (!empty($allServicesBooking[$service_type])) {
            if (!$reviewEnableAfterBooking) {
                return redirect()->to(url()->previous() . '#review-form')->with('error', __('You need booking before write a review'));
            } else {
                if (!$module->check_allow_review_after_making_completed_booking()) {
                    return redirect()->to(url()->previous() . '#review-form')->with('error', __('You can review after making completed booking'));
                }
            }
        }
        if ($module->create_user == Auth::id()) {
            return redirect()->to(url()->previous() . '#review-form')->with('error', __('You cannot review your service'));
        }
        
        if ($module_class == 'App\User' && $module->id == Auth::id()) {
            return redirect()->to(url()->previous() . '#review-form')->with('error', __('You cannot review your service'));            
        }

        $rules = [
            'review_title'   => 'required',
            'review_content' => 'required|min:10'
        ];
        $messages = [
            'review_title.required'   => __('Review Title is required field'),
            'review_content.required' => __('Review Content is required field'),
            'review_content.min'      => __('Review Content has at least 10 character'),
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return redirect()->to(url()->previous() . '#review-form')->withErrors($validator->errors());
        }

        $review_rate = $request->input('review_rate');
        $review = new Review([
            "object_id"    => $service_id,
            "object_model" => $service_type,
            "title"        => $request->input('review_title'),
            "content"      => $request->input('review_content'),
            "rate_number"  => $review_rate ?? 0,
            "author_ip"    => $request->ip(),
            "status"       => !$module->getReviewApproved() ? "approved" : "pending",
            'vendor_id'     =>$module->create_user
        ]);

        if ($review->save()) {
            $msg = __('Review success!');
            if ($module->getReviewApproved()) {
                $msg = __("Review success! Please wait for admin approved!");
            }
            return redirect()->to(url()->previous() . '#bravo-reviews')->with('success', $msg);
        }
        return redirect()->to(url()->previous() . '#review-form')->with('error', __('Review error!'));
    }

    
    public function create(Request $request){
       
         $this->checkPermission('rooms_create');
 
         $row = new $this->propertyClass();
         $data = [
             'row'           => $row,
             'translation' => new $this->propertyTranslationClass(),
             'property_category'    => $this->propertyCategoryClass::where('status', 'publish')->get()->toTree(),
             'property_location' => $this->locationClass::where("status","publish")->get()->toTree(),
             'attributes'    => $this->attributesClass::where('service', 'property')->get(),
             'breadcrumbs'        => [
                 [
                     'name' => __('Manage Properties'),
                     'url'  => route('property.vendor.index')
                 ],
                 [
                     'name'  => __('Create'),
                     'class' => 'active'
                 ],
             ],
             'page_title'         => __("Create Properties"),
         ];
         return view('Room::create', $data);
     }


     public function index(Request $request)
     {
     
         //$this->checkPermission("rooms_manage_others");
         //$this->checkPermission('property_view');
         $user_id = Auth::id();
         $rows = $this->roomClass::query()->select("bravo_rooms.*","bravo_properties.*","bravo_rooms.id as roomid")
                                                         ->leftJoin('bravo_properties', function ($join)  {
                                                       $join->on('bravo_properties.id', '=', 'bravo_rooms.property_id');
                                                      });
 
       
 
             $rows->orderBy('bravo_properties.id','desc');
       
        
 
         $data = [
             'rows' => $rows->paginate(5),
             'breadcrumbs'        => [
                 [
                     'name' => __('Manage Properties'),
                     'url'  => route('property.vendor.index')
                 ],
                 [
                     'name'  => __('All'),
                     'class' => 'active'
                 ],
             ],
             'page_title'         => __("Manage Properties"),
         ];
 
       
        
         return view('Room::front.index', $data);
     
         /*$model = Room::query();
         $model->orderBy('id', 'desc');
         if (!empty($author = $request->input('customer_id'))) {
             $model->where('create_user', $author);
         }
        
         $allServices = get_reviewable_services();
         $allServicesKeys = array_keys($allServices);
 
         if (!empty($search_name = $request->input('s'))) {
             $search_name = "%".$search_name."%";
             $model->whereRaw(" ( title LIKE ? OR author_ip LIKE ? OR content LIKE ? ) ",[$search_name,$search_name,$search_name]);
             $model->orderBy('title', 'asc');
         }
         if (!empty($status = $request->input('status'))) {
             $model->where('status', $status);
         }
         if (!empty($service_type = $request->input('service'))) {
             $model->where('object_model', $service_type);
         }
         if (!empty($service_id = $request->input('service_id'))) {
             $model->where('object_id', $service_id);
         }
         if (!empty($object_model = $request->input('object_model')) and in_array($object_model,$allServicesKeys)) {
             $model->where('object_model', $object_model );
         }
         $model->whereIn('object_model', $allServicesKeys );
         $data = [
             'rows'        => $model->paginate(10),
             'breadcrumbs' => [
                 ['name'  => __('Room'),
                  'class' => 'active'
                 ],
             ]
         ];
         return view('Room::admin.index', $data);*/
     }
 



   
}
