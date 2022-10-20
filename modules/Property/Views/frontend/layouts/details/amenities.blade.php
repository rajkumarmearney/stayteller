@php
$terms_ids = $row->terms->pluck('term_id');
$attributes = \Modules\Core\Models\Terms::getTermsById($terms_ids);
@endphp
@if(!empty($terms_ids) and !empty($attributes))
    @foreach($attributes as $attribute )
        @php $translate_attribute = $attribute['parent']->translateOrOrigin(app()->getLocale()) @endphp
        @if(empty($attribute['parent']['hide_in_single']))
        @if($translate_attribute->name == 'Amenities')
        <h3 class="font-bold text-2xl ">{{ $translate_attribute->name }}</h3>
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    @php $terms = $attribute['child'] ;
                    $i=0;
                    
                    @endphp

                    @foreach($terms as $term )
                    @if($i <= 2)
                    @php $translate_term = $term->translateOrOrigin(app()->getLocale()) @endphp
                    <div class="col-sm-6">
                        @if($translate_term->icon)
                        <span class="{{ $translate_term->icon }}"></span>
                        @else
                        <span class="flaticon-tick"></span>
                        {{$translate_term->name}}
                        @endif
                    </div>
                    @php
                    $i++;
                    @endphp
                    @endif

                    @endforeach
                </div>
            </div>
        </div>
        @endif
        @endif
    @endforeach
@endif



@if(!empty($terms_ids) and !empty($attributes))
    @foreach($attributes as $attribute )
        @php $translate_attribute = $attribute['parent']->translateOrOrigin(app()->getLocale()) @endphp
        @if(empty($attribute['parent']['hide_in_single']))
        @if($translate_attribute->name == 'Features')
        <h3 class="font-bold text-2xl ">{{ $translate_attribute->name }}</h3>
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    @php $terms = $attribute['child'] @endphp
                    @foreach($terms as $term )
                    @php $translate_term = $term->translateOrOrigin(app()->getLocale()) @endphp
                    <div class="col-sm-6">
                        @if($translate_term->icon)
                        <span class="{{ $translate_term->icon }}"></span>
                        @else
                        <span class="flaticon-tick"></span>
                        {{$translate_term->name}}
                        @endif
                    </div>

                    @endforeach
                </div>
            </div>
        </div>
        @endif
        @endif
    @endforeach
@endif