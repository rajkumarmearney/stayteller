@extends('layouts.app')
@section('head')
@endsection
@section('content')
    <style type="text/css">
        .cursor-pointer {
            cursor: pointer;
        }

        .bg-info.bg-lighten {
            background-color: #e5f4f7 !important;
        }

        .card-footer {
            background-color: #fff;
        }

        .btn-outline-secondary {
            color: #6c757d !important;
        }

        .btn-outline-secondary:hover {
            color: #fff !important;
        }

        .roomAvailabilityCalendar {
            position: relative !important;
        }

        .roomAvailabilityCalendar-actionContainer-left,
        .roomAvailabilityCalendar-actionContainer-right {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        .roomAvailabilityCalendar-actionContainer-left {
            left: -5px;
        }

        .roomAvailabilityCalendar-actionContainer-right {
            right: -5px;
        }

        .roomAvailabilityCalendar-date:hover {
            background-color: #80808026;
        }

        .col-1-7 {
            flex: 14%;
            max-width: 14%;
            position: relative;
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }

    

.topnav {
  overflow: hidden;
  background-color: #f1f1f1;
  border: 13px solid #ff5a5f;
}

.topnav a {
  float: left;
  display: block;
  color: black;
  text-align: center;
  padding: 14px 16px;
  text-decoration: none;
  font-size: 17px;
  border-bottom: 3px solid transparent;
}

.topnav a:hover {
  border-bottom: 3px solid red;
}

.topnav a.active {
  border-bottom: 3px solid red;
}

    </style>

    <section class="listing-title-area p-0">
        @include('Property::frontend.layouts.details.gallery_property')
    </section>

    <!-- Agent Single Grid View -->
    <section class="our-agent-single bgc-f7 pb30-991 bg-white">
        <div class="container">
       
            @include('Agencies::frontend.detail.search_mobile')
            <div class="row">
                <div class="col-sm-8">
                    <div class="d-flex flex-column">
                        @if(!empty($row['content']))
                                <div class="text-danger mb30 room_header mb-1">
                                    <div class="float-left">{{ $translation->title ?? '' }}</div>
                                    <div class="float-right">
                                        <span class= "room_price">{{ $row['display_price'] ? $row['display_price'] : '' }}/{{__("Month")}}</span>
                                        @if($row->is_sold)
                                            <span class="badge badge-danger is_sold_out">{{__("Sold Out")}}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="gpara second_para white_goverlay mt10 mb10">{!! clean(Str::words($translation->content,50)) !!}</div>
                                <div class="collapse" id="collapseExample">
                                    <div class="card card-body border-0 p-0">
                                        <div class="mb10">{!! clean($translation->content) !!}</div>
                                    </div>
                                </div>
                                <p class="overlay_close">
                                    <a class="text-thm fz14 " data-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample" onclick="showLess(this)">
                                    SHOW MORE
                                    </a>
                                </p>
                        @endif
                    </div>
                </div>
                <div class="col-sm-4">
                   
                                @include('Property::frontend.layouts.details.amenities')   
                               
            </div>

        </div>
    </section>

    <section class="our-agent-single bgc-f7 pb30-991 bg-info bg-lighten">
        <div class="container">
            <div >
           
            <nav class="topnav">
                                <button><a href="" class="scrollTo active">Room Details</a></button>
                                <button><a href="#location" class="scrollTo">Location</a></button>
                                <button><a href="#video" class="scrollTo">Property Video</a></button>
                                <button><a href="#policy" class="scrollTo">Property Policy</a></button>
                                <button><a href="#review" class="scrollTo">Reviews</a></button>
                            </nav>
            </div>

            <div class="row">
                <div class="col-sm-8 mt50">
                    <div class="listing_single_description style2 bg-transparent p-0 border-0">
                        @if(!empty($row['content']))
                            <?php
                            $roomsCount = 0;

                            foreach($rooms as $roomdatainfo) {
                                $i =0;
                                $j=0;
                                $show=array();
                                $selected=array();
                            ?>

                            <div class="row @if($roomsCount > 0) mt-3 @endif">
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm cursor-pointer roomContainer">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12">
                                                    <h4 class="font-weight-bold">{{$roomdatainfo->name}}</h4>
                                                </div>
                                            </div>

                                            <?php

                                            foreach ($attributes as $attribute) {
                                                if($attribute->room_Property ==1) {
                                                    foreach($attribute->terms as $term) {
                                                        $roominfoarr = json_decode($roomdatainfo->room_info);

                                                        foreach($roominfoarr as $roomdata =>$val) {
                                                            $strdatareplace = str_replace("-", "_", $attribute->slug);
                                                            if(isset($roominfoarr[$i]->$strdatareplace) && ($roominfoarr[$i]->$strdatareplace == $term->id )){
                                                                $selected[] =$attribute->name.'-'.$term->name ;
                                                            }
                                                        }
                                                    }

                                                    $i++;
                                                }

                                                if($attribute->features_enable == 1) {
                                                    foreach($attribute->terms as $term) {
                                                        $roominfoarr = json_decode($roomdatainfo->amenities_details);
                                                        foreach($roominfoarr as $roomdata =>$val){
                                                            $strdatareplace = str_replace("-", "_", $attribute->slug);
                                                           
                                                            if(isset($roominfoarr[$j]->$strdatareplace) && ($roominfoarr[$j]->$strdatareplace != '' )){
                                                                $checked = 'checked';
                                                                if($roominfoarr[$j]->$strdatareplace != []){
                                                                    $show[] =$attribute->name.'-'.$roominfoarr[$j]->$strdatareplace;
                                                                }else{
                                                                    $show[] =$attribute->name;
                                                                }
                                                               
                                                                $style = 'display: block;';
                                                            
                                                            }else{
                                                               
                                                                $show[] =$attribute->name.'-'.$j;
                                                            }
                                                        
                                                        }
                                                    }

                                                    $j++;
                                                }
                                            }

                                            $facilities = array_unique($show);
                                            $roomoption = array_unique($selected);

                                            ?>

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
                                                    <ul class="list-inline-item mb-0">
                                                        <li><p>{{ __('Rent') }} :</p></li>
                                                        <li><p>{{ __('Deposit') }} :</p></li>
                                                    </ul>
                                                    <ul class="list-inline-item mb-0">
                                                        <li><p><span>{{ $roomdatainfo->price_per_month ? $roomdatainfo->price_per_month : __('None') }}</span></p></li>
                                                        <li><p><span>{{ $roomdatainfo->deposite ? $roomdatainfo->deposite : 0 }}</span></p></li>
                                                    </ul>
                                                </div>
                                                <div class="col-sm-3">
                                                    <div class="float-right" style="transform: translateY(100%);">
                                                        @if($roomdatainfo->no_of_room != 0)
                                                            <a class="btn btn-primary bravo-button-book-mobile text-white btnBookRoom" data-roomid = "{{$roomdatainfo->id}}" data-propertyid = "{{$roomdatainfo->property_id}}">{{__("Book Now")}}</a>
                                                        @else
                                                            <a class="btn btn-block btn-thm" data-toggle="modal" data-target="#enquiry_form_modal">{{__("Sold Out")}}</a>
                                                        @endif
                                                    </div>
                                                </div>
                                                <span class="text-xs flex items-center font-semibold text-orange cursor-pointer btnBookRoom" data-roomid = "{{$roomdatainfo->id}}" data-propertyid = "{{$roomdatainfo->property_id}}">Availability calendar<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 ml-1"><path d="M18 15l-6-6-6 6"></path></svg></span>
                                            </div>

                                        </div><!-- card-body -->
                                        <div class="card-footer d-none">
                                            <div class="row">
                                                <div class="col-12">
                                                    <b class="text-danger">Availability</b>
                                                    <a href="javascript:void(0);" class="btn btn-link py-0 text-secondary float-right btnCancelRoomBooking">{{__("Cancel")}}</a>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 roomAvailabilityCalendar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                    
                            <?php

                                $roomsCount++;
                            }

                            ?>
                               
                        @endif
                    </div>
                </div>
                <div class="col-sm-4 mt50">
                   <!-- <h3 class="font-bold text-2xl">Summary</h3>
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
                </div>-->
            </div>
           
        </div>
    </section>

    <section class="our-agent-single bgc-f7 pb30-991">
        <div class="container">

            <div class="row">
                <div class="col-md-12 col-lg-8 mt50">
                    <div class="row">
                        
                        @if(!empty($row->location->name))
                            @php $location =  $row->location->translateOrOrigin(app()->getLocale()) @endphp
                        @endif
                        <div class="col-lg-12 hiperlink" id = "location"  style="display: none;">
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
                        <div class="col-lg-12 hiperlink" id = "review" style="display: none;">
                            @include('Agencies::frontend.detail.review', ['review_service_id' => $row['id'], 'review_service_type' => 'property'])
                        </div>

                        <div class="col-lg-12 hiperlink" id = "policy" style="display: none;">
                        <section class="" id="policy1"><section class="max-w-screen-xl w-full mx-auto p-4"><h2 class="font-bold text-2xl mb-4">Property Policy</h2><div class="whitespace-pre-line html-renderer-div"><p>- Guests are required to pay a 21% advance at the time of booking itself.</p><p>- Our standard check-in time is 1 PM and the standard check-out time is 11 AM.</p><p>- We only accept a government ID as valid identification proof. No local IDs shall be accepted at the time of check-in.</p><p>- Guests are not permitted to bring outsiders inside the home premises.</p><p>- We believe in self-help and do not provide luggage assistance or room services.</p><p>- Usage of alcohol and drugs is strictly banned inside and around the property.</p><p>- Quiet Hours are from 10 PM to 6 AM. Do not play loud music or cause nuisance, as the place is located in a village area. Please respect neighbours and culture around.</p><p>- Right to admission reserved.</p></div></section></section>
                        </div>
                        @include('Property::frontend.layouts.details.property-related')
                    </div>
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
        function showLess(event) {  
           
            console.log(event.innerText );
   if (event.innerText == 'SHOW MORE') {
    
        event.innerText = "SHOW LESS";
    } else if (event.innerText == "SHOW LESS") {
        event.innerText = "SHOW MORE";
    }
}

         var ajaxReady = 1;
        function addDaysToDateObj(dateObj, days) {
            return new Date(new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate()).setDate(new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate()).getDate() + days));
        }

        function dateStrToObj(dateStr) {
            let dateArray = dateStr.split('-');
            return new Date(dateArray[0], dateArray[1] - 1, dateArray[2]);
        }

        function dateObjToStr(dateObj, humanReadable = false) {
            if (humanReadable) {
                return `${dateObj.getDate()}-${dateObj.getMonth() + 1}-${dateObj.getFullYear()}`;
            }

            return `${dateObj.getFullYear()}-${dateObj.getMonth() + 1}-${dateObj.getDate()}`;
        }

        // Temporary function - remove when implementing actual data
        function generateRandomRoomAvailabilityData(btnb) {
            let n=90;
           let roomid = btnb.data('roomid');
            let propertyid = btnb.data('propertyid');
            
            $.ajax({
                    url: "{{route('property.availabilty')}}",
                    data: {
                        roomid: roomid,
                        propertyid: propertyid,
                        _token: "{{csrf_token()}}",
                    },
                    dataType: 'json',
                    type: 'post',
                    beforeSend: function (xhr) {
                        ajaxReady = 0;
                    },
                    success: function (res) {

                        renderRoomAvailabilityCalendar(btnb.closest('.roomContainer').find('.roomAvailabilityCalendar'),res);
                       
                        
    
                    },
                    error:function () {
                        ajaxReady = 1;
                    }
                })

           
        }

        /**
         * @param availableDates - should be Array of Objects( date: 'YYYY-MM-DD', fare: AMOUNT )
         * */
        function renderRoomAvailabilityCalendar(calendarContainer, availableDates, startDate = false) {

            const daysInWeek = [
                'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'
            ];
            const months = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ];
            let roomContainer = calendarContainer.closest('.roomContainer');

            if (availableDates && Array.isArray(availableDates) && availableDates.length > 0) {
                let availableDatesHTML = '';
                let prevWeekAvailable = false;
                let minDate = new Date();

                availableDates.forEach(function(availableDate, index) {
                    let dateObj = dateStrToObj(availableDate['date']);

                    if (dateObj < minDate) {
                        minDate = dateObj;
                    }

                    availableDate['dateObj'] = dateObj;

                    availableDates[index] = availableDate;
                });

                if (startDate) {
                    prevWeekAvailable = startDate > minDate;
                } else {
                    // Set start date - first time
                    startDate = minDate;

                    calendarContainer.prop('availableDates', availableDates);
                }

                let daysRendered = 0;
                let endDateStr = '';

                while(daysRendered < daysInWeek.length) {
                    let dateObj = addDaysToDateObj(startDate, daysRendered);
                    let dateStr = dateObjToStr(dateObj);
                    let availableDate = null;

                    availableDates.forEach(function(tempAvailableDate) {
                        if (tempAvailableDate['dateObj'].getTime() == dateObj.getTime()) {
                            availableDate = tempAvailableDate;
                            return;
                        }
                    });

                    let calendarHTML =  `<div class="col-1-7 roomAvailabilityCalendar-date text-center" data-date="${dateStr}">`+
                                            `<div class="row">`+
                                                `<div class="col-12">`+
                                                    daysInWeek[dateObj.getDay()]+
                                                `</div>`+
                                            `</div>`+
                                            `<div class="row">`+
                                                `<div class="col-12">`+
                                                    `<b>` + months[dateObj.getMonth()] + ` ` + dateObj.getDate() + `</b>`+
                                                `</div>`+
                                            `</div>`+
                                            `<div class="row">`+
                                                `<div class="col-12">`+
                                                    (availableDate ? `<span class="text-danger">` + availableDate['fare'] + `</span>` : `-`)+
                                                `</div>`+
                                            `</div>`+
                                        `</div>`;

                    if ((daysInWeek.length - 1) == daysRendered) {
                        let nextWeekStartDate = addDaysToDateObj(dateObj, 1);
                        endDateStr = dateObjToStr(nextWeekStartDate);
                    }

                    availableDatesHTML += calendarHTML;

                    daysRendered++;
                }

                if (prevWeekAvailable) {
                    let prevWeekStartDate = addDaysToDateObj(startDate, daysInWeek.length * -1);
                    let prevWeekStartDateStr = dateObjToStr(prevWeekStartDate);

                    availableDatesHTML +=   `<div class="roomAvailabilityCalendar-actionContainer-left">`+
                                                `<a href="javascript:void(0);" class="btn btn-danger btnShowPreviousWeek" data-date="${prevWeekStartDateStr}"><i class="fa fa-arrow-left"></i></a>`+
                                            `</div>`;
                }

                availableDatesHTML +=   `<div class="roomAvailabilityCalendar-actionContainer-right">`+
                                            `<a href="javascript:void(0);" class="btn btn-danger btnShowNextWeek" data-date="${endDateStr}"><i class="fa fa-arrow-right"></i></a>`+
                                        `</div>`;

                calendarContainer.html(`<div class="row justify-content-center mt-2">${availableDatesHTML}</div>`);
            } else {
                calendarContainer.html('<span class="text-danger"><em>Cannot find available dates</em></span>');
                roomContainer.find('.btnCancelRoomBooking').addClass('d-none');
            }
        }

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

            $(document).on('click', '.btnBookRoom', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let roomContainer = $(this).closest('.roomContainer');
               

                roomContainer.find('.card-footer').removeClass('d-none');
                roomContainer.find('.btnCancelRoomBooking').removeClass('d-none');
                $(this).addClass('d-none');


                generateRandomRoomAvailabilityData($(this));

                
            });

            $(document).on('click', '.btnCancelRoomBooking', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let roomContainer = $(this).closest('.roomContainer');
                roomContainer.find('.card-footer').addClass('d-none');
                roomContainer.find('.btnBookRoom').removeClass('d-none');
                $(this).addClass('d-none');
            });

            $(document).on('click', '.roomContainer .roomAvailabilityCalendar .btnShowPreviousWeek, .roomContainer .roomAvailabilityCalendar .btnShowNextWeek', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let calendarContainer = $(this).closest('.roomAvailabilityCalendar');
                let dateObj = dateStrToObj($(this).attr('data-date'));

                renderRoomAvailabilityCalendar(calendarContainer, calendarContainer.prop('availableDates'), dateObj);
            });

            $(document).on('click', '.roomContainer .roomAvailabilityCalendar .roomAvailabilityCalendar-date', function(e) {
                e.preventDefault();
                e.stopPropagation();

                let calendarContainer = $(this).closest('.roomAvailabilityCalendar');
                let dateObj = dateStrToObj($(this).attr('data-date'));
                let dateAvailability = null;
                let roomid = 1;
                 let propertyid = 1;

                (calendarContainer.prop('availableDates') || []).forEach(function(tempDateAvailability) {
                    if (tempDateAvailability['dateObj'].getTime() == dateObj.getTime()) {
                        dateAvailability = tempDateAvailability;
                        return;
                    }
                });

                if (dateAvailability) {
                    console.log(dateAvailability);
                    window.location.href = 'booked/1/1/1';
                  
                    
                } else {
                    alert(`Sorry, It is not available on ${dateObjToStr(dateObj, true)}`);
                }
            });
        });

        $('.scrollTo').click(function(){
            var hrefid = $(this).attr( 'href' );
            $('.hiperlink').css("display", "none");
            $(hrefid).css("display", "block");
            console.log(href);
            $('html, body').animate({
              

                scrollTop: $($(this).attr('href') ).offset().top
            }, 500);

            return false;
        });
       
    </script>

    
@endsection
