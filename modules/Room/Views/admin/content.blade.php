
<?php  $propertycollection = \Modules\Property\Models\Property::all(); 

?>

<div class="panel">
    <div class="panel-title"><strong>{{__("Room Content")}}</strong></div>
    <div class="panel-body">
        <div class="form-group">
            <label>{{__("Select Property")}}</label>
            <div class="">
                                        <select name="category_id" class="form-control">
                                            <option value="">{{__("-- Please Select --")}}</option>
                                            <?php
                                          
                                                foreach ($propertycollection as $propertydata) {
                                                    $selected = '';
                                                  
                                                        $selected = 'selected';
                                                    printf("<option value='%s' %s>%s</option>", $propertydata->id, $selected,  ' ' . $propertydata->title);
                                                  
                                                }
                                           
                                            ?>
                                        </select>
                                    </div>
        </div>
    </div>
</div>
@if(is_default_lang())
<div class="panel">
    <div class="panel-title"><strong>{{__("Room Info")}}</strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{__("Room Name")}}</label>
                    <input type="text" value="" placeholder="{{__("Example: Room Name")}}" name="room_name" class="form-control" min="0">
                </div>
            </div>
            @foreach ($attributes as $attribute)
            @if($attribute->room_Property ==1)
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{$attribute->name}}</label>
                  
                    
                     <select name="{{str_replace("-", "_", $attribute->slug)}}" class="form-control">
                        <option value="">{{__("-- Please Select --")}}</option>
                        @foreach($attribute->terms as $term)
                        <?php
                        $selected = '';
                        printf("<option value='%s' %s>%s</option>", $term->id, $selected,  ' ' . $term->name);
                        ?>
                         @endforeach
                    </select>
                    
                     
                </div>
            </div>
            @endif
            @endforeach
           
        </div>
    </div>
</div>
@endif
@if(is_default_lang())
<div class="panel">
    <div class="panel-title"><strong>{{__("Amenities details")}}</strong></div>
    <div class="panel-body">
        <div class="row">
        @foreach ($attributes as $attribute)
            @if($attribute->features_enable == 1)
            <div class="col-md-4">
                <div class="form-group">
                    <label>
                    
                    <input type="checkbox" value = "{{$attribute->name}}"  name="{{str_replace("-", "_", $attribute->slug)}}" data-id = "{{$attribute->id}}" data-attributes = "{{$attribute->name}}"data-show = "{{$attribute->features_choice}}" class="form-control amenities_details">{{$attribute->name}}
                                        </label>
                </div>
                    <div class = "form-group show_choice {{$attribute->name}}_{{$attribute->id}}"  style="display: none;">
                    @foreach($attribute->terms as $term)
                        <label><input type = "radio" name= "{{$attribute->name}}_choice" class ="amenities {{$attribute->name}}_{{$attribute->id}}" value = "Yes" >{{$term->name}}</label>
                    @endforeach    
                    </div>


            </div>
            @endif
            @endforeach 
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-title"><strong>{{__("Priceing details")}}</strong></div>
    <div class="panel-body">
        <div class="row">
      
            <div class="col-md-4">
                <div class="form-group">
                    <label>No Of Rooms</label>
                    <input type="text" value = ""   name="no_of_rooms" class="form-control">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Price Per Month</label>
                    <input type="text" value = ""   name="per_month" class="form-control">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Deposit</label>
                    <input type="text" value = ""   name="deposit" class="form-control">
                </div>
            </div>


            
           
        </div>
    </div>
    <div class="panel">
                        
                        <div class="panel-body">
                            <div class="my_profile_setting_input text-center">
                                <button type="submit" class="btn btn2 btn-success">{{ __('Save') }}</button>
                            </div>
                        </div>
                    </div>
</div>
@endif

@section('script.body')
<script>
$( document ).ready(function() {
    $('.amenities_details').click(function() {
        var showdata = $(this).data('show');
        var dataname = $(this).val();
        var dataid   = $(this).data('id');
        if(showdata == '1'){
            $('.'+dataname+'_'+dataid).css("display","block");
            //alert(dataname+'_'+dataid);
        }
      
    });
});
</script>
@endsection

