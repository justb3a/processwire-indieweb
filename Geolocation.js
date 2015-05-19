$(function(){
  var latitude = document.getElementById('Inputfield_iw_location_latitude');
  var longitude = document.getElementById('Inputfield_iw_location_longitude');

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function success(pos) {
        var crd = pos.coords;
        latitude.value = crd.latitude;
        longitude.value = crd.longitude;
      }
    );
  }
});
