<?php $container = 1 ?>
@extends('admin.layouts.app')
@section('head')


@endsection
<style>
    div#calendar{
  margin:0px auto;
  padding:0px;
  width: 602px;
  font-family:Helvetica, "Times New Roman", Times, serif;
}
 
div#calendar div.box{
    position:relative;
    top:0px;
    left:0px;
    width:100%;
    height:40px;
    background-color:   #787878 ;      
}
 
div#calendar div.header{
    line-height:40px;  
    vertical-align:middle;
    position:absolute;
    left:11px;
    top:0px;
    width:582px;
    height:40px;   
    text-align:center;
}
 
div#calendar div.header a.prev,div#calendar div.header a.next{ 
    position:absolute;
    top:0px;   
    height: 17px;
    display:block;
    cursor:pointer;
    text-decoration:none;
    color:#FFF;
}
 
div#calendar div.header span.title{
    color:#FFF;
    font-size:18px;
}
 
 
div#calendar div.header a.prev{
    left:0px;
}
 
div#calendar div.header a.next{
    right:0px;
}
 
 
 
 
/*******************************Calendar Content Cells*********************************/
div#calendar div.box-content{
    border:1px solid #787878 ;
    border-top:none;
}
 
 
 
div#calendar ul.label{
    float:left;
    margin: 0px;
    padding: 0px;
    margin-top:5px;
    margin-left: 5px;
}
 
div#calendar ul.label li{
    margin:0px;
    padding:0px;
    margin-right:5px;  
    float:left;
    list-style-type:none;
    width:80px;
    height:40px;
    line-height:40px;
    vertical-align:middle;
    text-align:center;
    color:#000;
    font-size: 15px;
    background-color: transparent;
}
 
 
div#calendar ul.dates{
    float:left;
    margin: 0px;
    padding: 0px;
    margin-left: 5px;
    margin-bottom: 5px;
}
 
/** overall width = width+padding-right**/
div#calendar ul.dates li{
    margin:0px;
    padding:0px;
    margin-right:5px;
    margin-top: 5px;
    line-height:80px;
    vertical-align:middle;
    float:left;
    list-style-type:none;
    width:80px;
    height:80px;
    font-size:25px;
    background-color: #DDD;
    color:#000;
    text-align:center; 
}
 
:focus{
    outline:none;
}
 
div.clear{
    clear:both;
}   
</style>
@section('content')
    <div class="col-lg-12 mb10">
    {!!$html!!}
    </div>
    
    
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Update Room Availabilty</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
       <div class = "col-sm-6">
        <input type = "hidden" class="room_id">
        <label>Date </label> <span class = "availabilty_date">{{date('y-M-d')}}</span>
       </div>
       <div class = "col-sm-6">
        <label>Availability </label> <span class = "availabiltycount">6</span>
       </div>
       <div class = "col-sm-6">
        <label>Available </label> <input type="number" name="available" class = "roomcount">
       </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary availabiltystore">Save changes</button>
      </div>
    </div>
  </div>
</div>
@endsection
@section('script.body')
<script>
     $(document).on("click",".availabiltystore",function() {
        
        var room_id =  $('.room_id').val();
        var  date   = $('.availabilty_date').text();
        var roomcount =$('.roomcount').val();

        var ajaxReady = 1;
        $.ajax({
                    url: "{{route('room.availabiltyupdate')}}",
                    data: {
                        roomid: room_id,
                        date: date,
                        room_count:roomcount,
                        _token: "{{csrf_token()}}",
                    },
                    dataType: 'json',
                    type: 'post',
                    beforeSend: function (xhr) {
                        ajaxReady = 0;
                    },
                    success: function (res) {
                        if(res.status==1){
                            alert(res.message);
                            $('.close').trigger( "click" )
                        }
                      console.log(res);
                       
                        
    
                    },
                    error:function () {
                        ajaxReady = 1;
                    }
                })
        
   


     })
    $(document).on("click",".availabiltyupdate",function() {
       

        $('.availabilty_date').text($(this).data('date'));
        $('.availabiltycount').text($(this).data('availability'));
        $('.room_id').val($(this).data('room_id'));

        
   
});
    
</script>
   
@endsection
