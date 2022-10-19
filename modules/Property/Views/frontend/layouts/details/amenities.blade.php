@php
                                    $terms_ids = $row->terms->pluck('term_id');
                                    $attributes = \Modules\Core\Models\Terms::getTermsById($terms_ids);
                                @endphp
                                @if(!empty($terms_ids) and !empty($attributes))
                                    @foreach($attributes as $attribute )
                                        @php $translate_attribute = $attribute['parent']->translateOrOrigin(app()->getLocale()) @endphp
                                            @if(empty($attribute['parent']['hide_in_single']))
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
                                            @endif
                                    @endforeach 
                                @endif   