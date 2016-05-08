$(function(){
  var locationEnabled = document.getElementById('Inputfield_iw_location');
  var latitude = document.getElementById('Inputfield_iw_location_latitude');
  var longitude = document.getElementById('Inputfield_iw_location_longitude');

  $(locationEnabled).click(function(){
    if ($(this).attr('checked') === 'checked') {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          function success(pos) {
            var crd = pos.coords;
            latitude.value = Math.round(crd.latitude * 100000) / 100000;
            longitude.value = Math.round(crd.longitude * 100000) / 100000;
          }
        );
      }
    }
  });
});
