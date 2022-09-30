@php
//dd($rows);
@endphp
<!-- Feature Properties -->
<section id="feature-property" class="feature-property-home6">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="main-title mb40">

                    @if($title)
                        <h2>{{clean($title)}}</h2>
                    @endif
                    <p>
                        @if($desc)
                            {{clean($desc)}}.
                    @endif
                            <a class="float-right" href="{{route('property.search')}}">{{__('View All')}} <span
                                    class="flaticon-next"></span></a></p>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="feature_property_home6_slider">
                    @foreach($rows as $row)
                    @if($row->Category->name == 'Family House')
                        @include('Property::frontend.layouts.search.loop-gird-overlay')
                        @endif
                    @endforeach

                </div>
            </div>
        </div>
    </div>
</section>


<section id="feature-property" class="feature-property-home6">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="main-title mb40">

                    @if($title)
                        <h2>{{clean($title)}}</h2>
                    @endif
                    <p>
                        @if($desc)
                            {{clean($desc)}}.
                    @endif
                            <a class="float-right" href="{{route('property.search')}}">{{__('View All')}} <span
                                    class="flaticon-next"></span></a></p>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="feature_property_home6_slider">
                    @foreach($rows as $row)
                    @if($row->Category->name == 'Town House')
                        @include('Property::frontend.layouts.search.loop-gird-overlay')
                        @endif
                    @endforeach

                </div>
            </div>
        </div>
    </div>
</section>
