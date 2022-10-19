@extends('layouts.app')
@section('head')
@endsection
@section('content')
    <section class="listing-title-area p-0">
        @include('Property::frontend.layouts.details.gallery_property')
    </section>

    <!-- Agent Single Grid View -->
    <section class="our-agent-single bgc-f7 pb30-991">
        <div class="container">
       
            @include('Agencies::frontend.detail.search_mobile')
            <div class="row">
                <div class="col-sm-8">
                    <div class="listing_single_description style2">
                        @if(!empty($row['content']))
                                <div class="text-danger mb30 room_header">  {{ $translation->title ?? '' }}  
                                    <span class= "room_price">{{ $row['display_price'] ? $row['display_price'] : '' }}/{{__("Month")}}</span>
                                        @if($row->is_sold)
                                            <div><span class="badge badge-danger is_sold_out">{{__("Sold Out")}}</span></div>
                                        @endif

                               
                                   
                                </div>
                                <div class="gpara second_para white_goverlay mt10 mb10">{!! clean(Str::words($translation->content,50)) !!}</div>
                                <div class="collapse" id="collapseExample">
                                    <div class="card card-body">
                                        <div class="mt10 mb10">{!! clean($translation->content) !!}</div>
                                    </div>
                                </div>
                                <p class="overlay_close">
                                    <a class="text-thm fz14" data-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
                                        {{__('Show More')}} <span class="flaticon-download-1 fz12"></span>
                                    </a>
                                </p>
                        @endif
                    </div>
                </div>
                <div class="col-sm-4">
                    <h3 class="font-bold text-2xl mb-4">Amenities</h3>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="row">
                                @include('Property::frontend.layouts.details.amenities')   
                                </div>
                            </div>
                        </div>
                </div>
            </div>
            <div class=" mt30">
            <div class= "room_header">Book your stay</div>
                                <span class="">Select from a range of beautiful rooms</span>
            </div>

            <div class="row">
                <div class="col-sm-8 mt50">
                    <div class="listing_single_description style2">
                        @if(!empty($row['content']))
                        <div class="row">
                            

                            <?php 
                                    
                                    foreach($rooms as $roomdatainfo){
                                        $i =0;
                                        $j=0;
                                        $show=array();
                                        $selected=array();
                                       
                                        ?>
                                        <p>{{$roomdatainfo->name}}</p>
                                          @foreach ($attributes as $attribute)
                                          @if($attribute->room_Property ==1)
                                          @foreach($attribute->terms as $term)
                                          <?php
                                                $roominfoarr = json_decode($roomdatainfo->room_info);
                                              
                                                    foreach($roominfoarr as $roomdata =>$val){
                                                        $strdatareplace = str_replace("-", "_", $attribute->slug);
                                                        if(isset($roominfoarr[$i]->$strdatareplace) && ($roominfoarr[$i]->$strdatareplace == $term->id )){
                                                            $selected[] =$attribute->name.'-'.$term->name ;
                                                        }
                                                    }
                                                
                                                ?>
                                                @endforeach
                                          @php
                                            $i++;
                                            @endphp
                                          @endif

                                          @if($attribute->features_enable == 1)
                                         
                                          @foreach($attribute->terms as $term)
                                          <?php
                                       $roominfoarr = json_decode($roomdatainfo->amenities_details);
                                              foreach($roominfoarr as $roomdata =>$val){
                                                    $strdatareplace = str_replace("-", "_", $attribute->slug);
                                                   
                                                    if(isset($roominfoarr[$j]->$strdatareplace) && ($roominfoarr[$j]->$strdatareplace != '' )){
                                                        $checked = 'checked';
                                                        $show[] =$attribute->name.'-'.$roominfoarr[$j]->$strdatareplace;
                                                        $style = 'display: block;';
                                                    
                                                    }else{
                                                       
                                                        $show[] =$attribute->name.'-'.$j;
                                                    }
                                                
                                                }
                                            
                                            ?>




                                        @endforeach
                                        @php
                                            $j++;
                                            @endphp

                                          @endif

                                          @endforeach

                                          <?php
                                   $facilities = array_unique($show);
                                   $roomoption = array_unique($selected);
                                   ?>
                                   <div class="container">
                                        <div class="row">
                                            <div class="col-sm-3">
                                                @foreach($roomoption as $roomdata)
                                                <p>{{$roomdata}}</p>
                                                @endforeach
                                            </div>
                                            <div class="col-sm-3">
                                                @foreach($facilities as $fact)
                                                <p>{{$fact}}</p>
                                                @endforeach
                                            </div>
                                            <div class="col-sm-3">
                                                <ul class="list-inline-item">
                                                    <li><p>{{ __('Rent') }} :</p></li>
                                                    <li><p>{{ __('Deposit') }} :</p></li>
                                                </ul>
                                                <ul class="list-inline-item">
                                                    <li><p><span>{{ $roomdatainfo->price_per_month ? $roomdatainfo->price_per_month : __('None') }}</span></p></li>
                                                    <li><p><span>{{ $roomdatainfo->deposite ? $roomdatainfo->deposite : 0 }}</span></p></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="float-right">
            @if($roomdatainfo->no_of_room != 0)
                <a class="btn btn-primary bravo-button-book-mobile">{{__("Book Now")}}</a>
            @else
                <a class="btn btn-block btn-thm" data-toggle="modal" data-target="#enquiry_form_modal">{{__("Sold Out")}}</a>
            @endif
        </div>
                                    </div>
                                    
                                   <?php } ?>
                                    
                                    
                            
                            
                            
                        
                        </div>
                               
                        @endif
                    </div>
                </div>
                <div class="col-sm-4 mt50">
                    <h3 class="font-bold text-2xl">Summary</h3>
                    <div class="text-sm font-bold">1 night <span class="text-text">starting from</span> Wed 19 Oct, 2022</div>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="row">
                                <div class="flex flex-col items-center justify-center w-72">
                                    <img src="https://book.zostel.com/static/media/gray-zobu.018014d9.svg" alt="zobu" class="h-40">
                                    <span class="mt-4 font-medium text-lg text-subtitle">No room selected</span>
                                </div> 
                                </div>
                            </div>
                        </div>
                </div>
            </div>
           





            <div class="row">
                <div class="col-md-12 col-lg-8 mt50">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="listing_single_description2 mt30-767 mb30-767">
                                <div class="single_property_title">
                                    <h2>
                                        {{ $translation->title ?? '' }}
                                        
                                    </h2>
                                    <p>{{ $translation->address }}</p>
                                </div>
                                <div class="single_property_social_share style2">
                                    <div class="price">
                                        <h2>{{ $row['display_price'] ? $row['display_price'] : '' }}/{{__("Month")}}</h2>
                                        @if($row->is_sold)
                                            <div><span class="badge badge-danger is_sold_out">{{__("Sold Out")}}</span></div>
                                        @endif
                                    </div>
                                   
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-12">
                            <div class="listing_single_description style2">
                                <div class="lsd_list">
                                    <ul class="mb0">
                                        @if(!empty($row['Category']))
                                            <?php $translationCategory = $row['Category']->translateOrOrigin(app()->getLocale()); ?>
                                            <li class="list-inline-item"><a href="#">{{  __($translationCategory->name) }}</a></li> 
                                        @endif
                                        @if(!empty($row['bed']))
                                            <li class="list-inline-item"><a href="#">{{ __("Beds") }}: {{ !empty($row['bed']) ? $row['bed'] : ''}}</a></li>
                                        @endif
                                      
                                    </ul>
                                </div>
    
                                
                                @if(!empty($row['content']))
                                    <h4 class="mb30">{{ __("Description") }}</h4>
                                    <div class="gpara second_para white_goverlay mt10 mb10">{!! clean(Str::words($translation->content,50)) !!}</div>
                                    <div class="collapse" id="collapseExample">
                                        <div class="card card-body">
                                            <div class="mt10 mb10">{!! clean($translation->content) !!}</div>
                                        </div>
                                    </div>
                                    <p class="overlay_close">
                                        <a class="text-thm fz14" data-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
                                            {{__('Show More')}} <span class="flaticon-download-1 fz12"></span>
                                        </a>
                                    </p>
                                @endif
                            </div>
                        </div>
                            <nav>
                                <button><a href="#room_action" class="scrollTo active">Room Option</a></button>
                                <button><a href="#location" class="scrollTo">Location</a></button>
                                <button><a href="#review" class="scrollTo">Review</a></button>
                            </nav>
                        <div class="col-lg-12" id= "room_action">
                            <div class="additional_details">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <h4 class="mb15">{{ __("Property Details") }}</h4>
                                    </div>
                                    <div class="col-md-6 col-lg-6 ">
                                        <ul class="list-inline-item">
                                            <li><p>{{ __('Property Type') }} :</p></li>
                                            <li><p>{{ __('Features') }} :</p></li>
                                           
                                            
                                        </ul>

                                        <ul class="list-inline-item">
                                            <li><p><span>{{ $row->category ? $row->category->name : '' }}</span></p></li>
                                            <li><p><span>{{ $row->pool_size ? size_unit_format($row->pool_size) : 0 }}</span></p></li>
                                          
                                        </ul>
                                    </div>

                                    <div class="col-md-6 col-lg-6">
                                        <ul class="list-inline-item">
                                            <li><p>{{ __('Near By Facilities ') }} :</p></li>
                                            
                                        </ul>
                                        <ul class="list-inline-item">
                                            <li><p><span>{{ $row->remodal_year ? $row->remodal_year : __('None') }}</span></p></li>
                                            
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="additional_details">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <h2 class="mb15">{{__('Pricing details')}}</h2>
                                    </div>
                                    @php
                                    
                                  
                                   
                                    @endphp
                                   
                                  

                                  
                                   
                                  
                                  
                                </div>
                            </div>
                        </div>
                        @include('Property::frontend.layouts.details.property_feature')
                        @if(!empty($row->location->name))
                            @php $location =  $row->location->translateOrOrigin(app()->getLocale()) @endphp
                        @endif
                        <div class="col-lg-12" id = "location">
                            <div class="application_statics mt30">
                                <h4 class="mb30">{{ __("Location") }} <small class="application_statics_location float-right">{{ !empty($location->name) ? $location->name : '' }}</small></h4>
                                <div class="property_video p0">
                                    <div class="thumb">
                                        <div class="h400" id="map-canvas"></div>
                                        <div class="overlay_icon">
                                            <a href="#"><img class="map_img_icon" src="{{asset('findhouse/images/header-logo.png')}}" alt="header-logo.png"></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @include('Property::frontend.layouts.details.property_video')
                        <div class="col-lg-12" id = "review">
                            @include('Agencies::frontend.detail.review', ['review_service_id' => $row['id'], 'review_service_type' => 'property'])
                        </div>
                        @include('Property::frontend.layouts.details.property-related')
                    </div>
                </div>
                <div class="col-lg-4 col-xl-4 mt50">
                    
                    @include('Property::frontend.layouts.search.form-search')
                    @include('Property::frontend.sidebar.FeatureProperty')
                    @include('Property::frontend.sidebar.categoryProperty')
                    @include('Property::frontend.sidebar.recentViewdProperty')
                </div>
            </div>
        </div>
    </section>
@endsection
@section('footer')
    <link href="{{ asset('libs/fotorama/fotorama.css') }}" rel="stylesheet">
    <script src="{{ asset('libs/fotorama/fotorama.js') }}"></script>
    {!! App\Helpers\MapEngine::scripts() !!}
    <script>
        jQuery(function ($) {
            new BravoMapEngine('map-canvas', {
                fitBounds: true,
                center: [{{$row->map_lat ?? "51.505"}}, {{$row->map_lng ?? "-0.09"}}],
                zoom:{{$row->map_zoom ?? "8"}},
                ready: function (engineMap) {
                    @if($row->map_lat && $row->map_lng)
                    engineMap.addMarker([{{$row->map_lat}}, {{$row->map_lng}}], {
                        icon_options: {}
                    });
                    @endif
                    engineMap.on('click', function (dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });
                    engineMap.on('zoom_changed', function (zoom) {
                        $("input[name=map_zoom]").attr("value", zoom);
                    });
                    engineMap.searchBox($('.bravo_searchbox'),function (dataLatLng) {
                        engineMap.clearMarkers();
                        engineMap.addMarker(dataLatLng, {
                            icon_options: {}
                        });
                        $("input[name=map_lat]").attr("value", dataLatLng[0]);
                        $("input[name=map_lng]").attr("value", dataLatLng[1]);
                    });
                }
            });
        })
        $('.scrollTo').click(function(){
    $('html, body').animate({
        scrollTop: $( $(this).attr('href') ).offset().top
    }, 500);
    return false;
});
    </script>
@endsection
