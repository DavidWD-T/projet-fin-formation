var map;
var service;
var infowindow;

function initMap() {
  const paris = new google.maps.LatLng(48.8534, 2.3488);
  infowindow = new google.maps.InfoWindow();
  map = new google.maps.Map(document.getElementById("map"), {
    center: paris,
    zoom: 18,
  });
  var destination = document.getElementById("companyAddress").innerHTML
  var name = document.getElementById("companyName").innerHTML
  var arrival
  const request = {
    query: destination,
    fields: ["name", "geometry"],
  };
  service = new google.maps.places.PlacesService(map);
  service.findPlaceFromQuery(request, (results, status) => {
    if (status === google.maps.places.PlacesServiceStatus.OK && results) {
      if(results.length > 1){
        results.forEach((result)=> {
          if (result.name == name) {
            createMarker(result);
            map.setCenter(result.geometry.location);
            arrival = new google.maps.LatLng(result.geometry.location.lat(), result.geometry.location.lng());
          };
          
        });
      }else {
        createMarker(results[0])
        map.setCenter(results[0].geometry.location);
        arrival = new google.maps.LatLng(results[0].geometry.location.lat(), results[0].geometry.location.lng());
      }      
    }
  });

  function createMarker(place) {
    if (!place.geometry || !place.geometry.location) return;
    const marker = new google.maps.Marker({
      map,
      position: place.geometry.location,
    });
    google.maps.event.addListener(marker, "click", () => {
      infowindow.setContent(place.name || "");
      infowindow.open(map);
    });
    // Try HTML5 geolocation.
    if (navigator.geolocation) {
      navigator.geolocation.watchPosition(function (position) {
      var pointA = new google.maps.LatLng(position.coords.latitude, position.coords.longitude),
      pointB = new google.maps.LatLng(marker.position.lat(), marker.position.lng()),
      myOptions = {
        zoom: 10,
        center: pointA
      },
      
      // Instantiate a directions service.
      directionsService = new google.maps.DirectionsService,
      directionsDisplay = new google.maps.DirectionsRenderer({
        map: map
      }),
      markerA = new google.maps.Marker({
        position: pointA,
        title: "point A",
        label: "A",
        map: map
      }),
      markerB = new google.maps.Marker({
        position: pointB,
        title: "point B",
        label: "B",
        map: map
      });

      // get route from A to B
      calculateAndDisplayRoute(directionsService, directionsDisplay, pointA, pointB);
      }, function () {
        handleLocationError(true, infoWindow, map.getCenter());
      });
      
    } else {
      // Browser doesn't support Geolocation
      handleLocationError(false, infoWindow, map.getCenter());
    }
  }

  function handleLocationError(browserHasGeolocation, infoWindow, pos) {
    infoWindow.setPosition(pos);
    infoWindow.setContent(browserHasGeolocation ?
      'Erreur: Accès position geographique non autorisé - vérifiez vos autorisation ou l\'activation de votre GPS.' :
      
      'Erreur: Vote navigateur ne supporte pas le service de geolocalisation.');
    infoWindow.open(map);
  }

  function calculateAndDisplayRoute(directionsService, directionsDisplay, pointA, pointB) {
    directionsService.route({
      origin: pointA,
      destination: pointB,
      avoidTolls: true,
      avoidHighways: false,
      travelMode: google.maps.TravelMode.DRIVING
    }, function (response, status) {
      if (status == google.maps.DirectionsStatus.OK) {
        directionsDisplay.setDirections(response);

      } else {
        window.alert('Directions request failed due to ' + status);
      }
    });
  }
}