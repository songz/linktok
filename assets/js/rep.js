var dateDiff = 0;
var DAYSTRING = "dddMMMDDYYYY";
var ONE_HOUR = 3600000;

// Templates
var user_template = Handlebars.compile($("#user_value_template").html());
var no_appointment_template = Handlebars.compile($("#no_appointment_template").html());
var chatting_template = Handlebars.compile($("#chatting_template").html());

function setDateString(){
  var datestr = moment().add(dateDiff, 'days').format('ddd MMM DD, YYYY');
  var daystring = moment().add(dateDiff, 'days').format(DAYSTRING);
  $("#dateHeader").text(datestr);
  $.get("/index.php/availability/"+daystring, function(res){
    var bookings = JSON.parse(res);
    $(".time").removeClass('bookedTime');
    for(var i=0;i<bookings.length;i++){
      var hour = moment(parseInt(bookings[i])).format('H');
      $(".selectableTime.time[data-hour="+hour+"]").addClass('bookedTime');
    }
  });
}
function getUTCAppointment(hour){
  var utc = moment().hour(parseInt(hour)).add(dateDiff, 'days')
  utc.set('minute', 0);
  utc.set('second', 0);
  utc.set('millisecond', 0);
  return parseInt(utc.format('X'));
}
function getUTCString(utc, format){
  return moment(utc).format(format)
}

$(".dateNavigate").click(function(){
  dateDiff = $(this).data('dir') === "left" ? dateDiff - 1 : dateDiff + 1;
  setDateString();
});

$(".time").click(function(){
  if(!$(this).hasClass("selectableTime")) return;
  var utc = getUTCAppointment($(this).data('hour'))*1000;
  var timestring = getUTCString(utc, "ddd MMM DD, YYYY") + " at " + getUTCString(utc, "hA");

  $.get("/index.php/getinfo/"+utc, function(res){
    var info = JSON.parse(res)[0];
    if(info){
      $("#rightContainer").html(user_template(info));
      $("#startButton").click(function(){
        startAppointment(info);
      });
      $("#cancelButton").click(function(){
        if(window.confirm("are you sure you want to cancel this appointment?")){
          $.get("/index.php/cancel/"+utc, function(res){
            location.reload();
          });
        }
      });
    }else{
      $("#rightContainer").html(no_appointment_template({timestring: timestring}));
    }
  });
});
  console.log(location.protocol);
  console.log(location.host);

function startAppointment(info){
  info.url = location.protocol + "//" + location.host + "/index.php/chat/" + info.Sessionid;
  $("#rightContainer").html(chatting_template(info));
  $("#stopAppointment").click(function(){
    if(window.confirm("stop chatting?")){
      location.reload();
    }
  });
}

Handlebars.registerHelper("readyAppointment", function(val, options){
  return Math.abs(parseInt(val) - Date.now()) <= ONE_HOUR ? options.fn() : options.inverse();
});

Handlebars.registerHelper("oldAppointment", function(val, options){
  return parseInt(val) > Date.now() - ONE_HOUR ? options.fn() : options.inverse();
});

setDateString();
