$("#roomNameInput").focus();
$("#roomNameInput").keypress(function(e){
  if(e.keyCode===13) {
    window.location = "/index.php/"+$("#roomNameInput").val();
  }
});
$("#createRoomButton").click(function(){
  window.location = "/index.php/"+$("#roomNameInput").val();
});
