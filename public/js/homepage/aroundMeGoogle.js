var map, infoWindow;
var baseURI = APP_ENV='dev'? 'http://localhost:8000':'https://owine.shop';

function initMap() {
    var options = {
      center: { lat: 48.8534, lng: 2.3488 },
      zoom: 4
  };
  
  map = new google.maps.Map(document.getElementById("map"), options);
  
  infoWindow = new google.maps.InfoWindow();
  
  // Try HTML5 geolocation.
  if (navigator.geolocation) {
    navigator.geolocation.watchPosition(function(position) {
      var pos = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
      };
      
      infoWindow.setPosition(pos);
      infoWindow.setContent('Vous êtes ici');
      infoWindow.open(map);
      map.setCenter(pos);
      map.setZoom(8);
    }, function() {
      handleLocationError(true, infoWindow, map.getCenter());
    });
  } else {
    // Browser doesn't support Geolocation
    handleLocationError(false, infoWindow, map.getCenter());
  }
  
  
  function handleLocationError(browserHasGeolocation, infoWindow, pos) {
    infoWindow.setPosition(pos);
    infoWindow.setContent(browserHasGeolocation ?
      'Error: The Geolocation service failed.' :
      'Error: Your browser doesn\'t support geolocation.');
    infoWindow.open(map);
    // let company =loadCompanies();
    // console.log(company);
  }

}


  