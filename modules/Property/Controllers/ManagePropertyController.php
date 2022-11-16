<?php
namespace Modules\Property\Controllers;

use Modules\FrontendController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Property\Models\Property;
use Modules\Location\Models\Location;
use Modules\Core\Models\Attributes;
use Modules\Booking\Models\Booking;
use Modules\Property\Models\PropertyTerm;
use Modules\Property\Models\PropertyTranslation;
use Modules\Property\Models\PropertyCategory;
use Illuminate\Support\Facades\DB;

class ManagePropertyController extends FrontendController
{
    protected $propertyClass;
    protected $propertyTranslationClass; 
    protected $propertyTermClass;
    protected $attributesClass;
    protected $locationClass;
    protected $propertyCategoryClass;
    protected $bookingClass;

    public function __construct()
    {
        parent::__construct();
        $this->propertyClass = Property::class;
        $this->propertyTranslationClass = PropertyTranslation::class;
        $this->propertyTermClass = PropertyTerm::class;
        $this->attributesClass = Attributes::class;
        $this->locationClass = Location::class;
        $this->propertyCategoryClass = PropertyCategory::class;
        $this->bookingClass = Booking::class;
    }
    public function callAction($method, $parameters)
    {
        if(!Property::isEnable())
        {
            return redirect('/');
        }
        return parent::callAction($method, $parameters); // TODO: Change the autogenerated stub
    }

    public function manageProperty(Request $request)
    {
        $this->checkPermission('property_view');
        $user_id = Auth::id();
        $rows = $this->propertyClass::query()->select("bravo_properties.*")->where("bravo_properties.create_user", $user_id);

        if (!empty($search = $request->input("s"))) {
            $rows->where(function($query) use ($search) {
                $query->where('bravo_properties.title', 'LIKE', '%' . $search . '%');
                $query->orWhere('bravo_properties.content', 'LIKE', '%' . $search . '%');
            });

            if( setting_item('site_enable_multi_lang') && setting_item('site_locale') != app_get_locale() ){
                $rows->leftJoin('bravo_property_translations', function ($join) use ($search) {
                    $join->on('bravo_properties.id', '=', 'bravo_property_translations.origin_id');
                });
                $rows->orWhere(function($query) use ($search) {
                    $query->where('bravo_property_translations.title', 'LIKE', '%' . $search . '%');
                    $query->orWhere('bravo_property_translations.content', 'LIKE', '%' . $search . '%');
                });
            }
        }

        if (!empty($filterSelect = $request->input("select_filter"))) {
            if ($filterSelect == 'recent') {
                $rows->orderBy('bravo_properties.id','desc');
            }

            if ($filterSelect == 'old') {
                $rows->orderBy('bravo_properties.id','asc');
            }

            if ($filterSelect == 'featured') {
                $rows->where('bravo_properties.is_featured','=', 1);
            }
        } else {
            $rows->orderBy('bravo_properties.id','desc');
        }

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
        return view('Property::frontend.manageProperty.index', $data);
    }


    public function createProperty(Request $request)
    {
        $this->checkPermission('property_create');
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
        return view('Property::frontend.manageProperty.detail', $data);
    }


    public function store( Request $request, $id ){



        $rules = [
            'title'     => 'required',
            'content'  => 'required',
            'price'     => 'required',
            'location_id'=>'required',
            'address'     => 'required',
            'map_lat'       =>'required',
            'map_lng'       =>'required',
            'category_id'       =>'required',
            
        ];
        $messages = [
            'title.required'         => __('Property title is required field'),
            'content.required'       => __('property content is required field'),
            'price.required'         => __('property price is required field'),
            'location_id'             => __('property location is required field'),
            'address'                  => __('property address is required field'),
            'address'                  => __('property address is required field'),
            'map_lat'                  => __('property lat is required field'),
            'map_lng'                  => __('property lng is required field'),
            'category_id'                  => __('property Category is required field'),


           
        ];
        $validator = \Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }




        if($id>0){
            $this->checkPermission('property_update');
            $row = $this->propertyClass::find($id);
            if (empty($row)) {
                return redirect(route('property.vendor.index'));
            }

            if($row->create_user != Auth::id() and !$this->hasPermission('property_manage_others'))
            {
                return redirect(route('property.vendor.index'));
            }
        }else{
            $this->checkPermission('property_create');
            $row = new $this->propertyClass();
            $row->status = "publish";
            if(setting_item("property_vendor_create_service_must_approved_by_admin", 0)){
                $row->status = "pending";
            }
        }
        $dataKeys = [
            'title',
            'content',
            'price',
            'is_instant',
            'video',
            'faqs',
            'image_id',
            'banner_image_id',
            'gallery',
            'bed',
            'bathroom',
            'square',
            'garages',
            'year_built',
            'area',
            'area_unit',
            'location_id',
            'category_id',
            'address',
            'map_lat',
            'map_lng',
            'map_zoom',
            'default_state',
            'price',
            'sale_price',
            'max_guests',
            'enable_extra_price',
            'extra_price',
            'is_featured',
            'default_state',
            'deposit',
            'pool_size',
            'additional_zoom',
            'remodal_year',
            'amenities',
            'equipment',
            'property_type',
            'is_sold'
        ];
        if($this->hasPermission('property_manage_others')){
            $dataKeys[] = 'create_user';
        }
        $row->fillByAttr($dataKeys,$request->input());
	    //$row->ical_import_url  = $request->ical_import_url;

        $res = $row->saveOriginOrTranslation($request->input('lang'),true);

        if ($res) {
            if(!$request->input('lang') or is_default_lang($request->input('lang'))) {
                $this->saveTerms($row, $request);
            }

            if($id > 0 ){
                return back()->with('success',  __('Property updated') );
            }else{
                return redirect(route('property.vendor.edit',['id'=>$row->id]))->with('success', __('Property created') );
            }
        }
    }

    public function saveTerms($row, $request)
    {
        if (empty($request->input('terms'))) {
            $this->propertyTermClass::where('target_id', $row->id)->delete();
        } else {
            $term_ids = $request->input('terms');
            foreach ($term_ids as $term_id) {
                $this->propertyTermClass::firstOrCreate([
                    'term_id' => $term_id,
                    'target_id' => $row->id
                ]);
            }
            $this->propertyTermClass::where('target_id', $row->id)->whereNotIn('term_id', $term_ids)->delete();
        }
    }

    public function editProperty(Request $request, $id)
    {
        $this->checkPermission('property_update');
        $user_id = Auth::id();
        $row = $this->propertyClass::where("create_user", $user_id);
        $row = $row->find($id);
        if (empty($row)) {
            return redirect(route('property.vendor.index'))->with('warning', __('Property not found!'));
        }
        $translation = $row->translateOrOrigin($request->query('lang'));
        $data = [
            'translation'    => $translation,
            'row'           => $row,
            'property_category'    => $this->propertyCategoryClass::where('status', 'publish')->get()->toTree(),
            'property_location' => $this->locationClass::where("status","publish")->get()->toTree(),
            'attributes'    => $this->attributesClass::where('service', 'property')->get(),
            "selected_terms" => $row->terms->pluck('term_id'),
            'breadcrumbs'        => [
                [
                    'name' => __('Manage Properties'),
                    'url'  => route('property.vendor.index')
                ],
                [
                    'name'  => __('Edit'),
                    'class' => 'active'
                ],
            ],
            'page_title'         => __("Edit Properties"),
        ];
        return view('Property::frontend.manageProperty.detail', $data);
    }

    public function deleteProperty($id)
    {
        $this->checkPermission('property_delete');
        $user_id = Auth::id();
        $query = $this->propertyClass::where("create_user", $user_id)->where("id", $id)->first();
        if(!empty($query)){
            $query->delete();
        }
        return redirect(route('property.vendor.index'))->with('success', __('Delete property success!'));
    }

    public function bulkEditProperty($id , Request $request){
        $this->checkPermission('property_update');
        $action = $request->input('action');
        $user_id = Auth::id();
        $query = $this->propertyClass::where("create_user", $user_id)->where("id", $id)->first();
        if (empty($id)) {
            return redirect()->back()->with('error', __('No item!'));
        }
        if (empty($action)) {
            return redirect()->back()->with('error', __('Please select an action!'));
        }
        if(empty($query)){
            return redirect()->back()->with('error', __('Not Found'));
        }
        switch ($action){
            case "make-hide":
                $query->status = "draft";
                break;
            case "make-publish":
                $query->status = "publish";
                break;
        }
        $query->save();
        return redirect()->back()->with('success', __('Update success!'));
    }

    public function bookingReport(Request $request)
    {
        $data = [
            'bookings' => $this->bookingClass::getBookingHistory($request->input('status'), false , Auth::id() , 'property'),
            'statues'  => config('booking.statuses'),
            'breadcrumbs'        => [
                [
                    'name' => __('Manage Property'),
                    'url'  => route('property.vendor.index')
                ],
                [
                    'name' => __('Booking Report'),
                    'class'  => 'active'
                ]
            ],
            'page_title'         => __("Booking Report"),
        ];
        return view('Property::frontend.manageProperty.bookingReport', $data);
    }

    public function bookingReportBulkEdit($booking_id , Request $request){
        $status = $request->input('status');
        if (!empty(setting_item("property_allow_vendor_can_change_their_booking_status")) and !empty($status) and !empty($booking_id)) {
            $query = $this->bookingClass::where("id", $booking_id);
            $query->where("vendor_id", Auth::id());
            $item = $query->first();
            if(!empty($item)){
                $item->status = $status;
                $item->save();
                $item->sendStatusUpdatedEmails();
                return redirect()->back()->with('success', __('Update success'));
            }
            return redirect()->back()->with('error', __('Booking not found!'));
        }
        return redirect()->back()->with('error', __('Update fail!'));
    }

	public function cloneProperty(Request $request,$id){
		$this->checkPermission('property_update');
		$user_id = Auth::id();
		$row = $this->propertyClass::where("create_user", $user_id);
		$row = $row->find($id);
		if (empty($row)) {
			return redirect(route('property.vendor.index'))->with('warning', __('Property not found!'));
		}
		try{
			$clone = $row->replicate();
			$clone->status  = 'draft';
			$clone->push();
			if(!empty($row->terms)){
				foreach ($row->terms as $term){
					$e= $term->replicate();
					if($e->push()){
						$clone->terms()->save($e);

					}
				}
			}
			if(!empty($row->meta)){
				$e= $row->meta->replicate();
				if($e->push()){
					$clone->meta()->save($e);

				}
			}
			if(!empty($row->translations)){
				foreach ($row->translations as $translation){
					$e = $translation->replicate();
					$e->origin_id = $clone->id;
					if($e->push()){
						$clone->translations()->save($e);
					}
				}
			}

			return redirect()->back()->with('success',__('Property clone was successful'));
		}catch (\Exception $exception){
			$clone->delete();
			return redirect()->back()->with('warning',__($exception->getMessage()));
		}
	}
}
