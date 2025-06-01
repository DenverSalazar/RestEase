<?php
// Database connection (adjust credentials as needed)
$conn = new mysqli("localhost", "root", "", "cemeterydb");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Fetch all deceased records indexed by nicheID
$deceasedData = [];
$result = $conn->query("SELECT nicheID, firstName, lastName, born, dateDied FROM deceased");
while ($row = $result->fetch_assoc()) {
    $deceasedData[$row['nicheID']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <link rel="stylesheet" href="../css/leaflet.css">
  <link rel="stylesheet" href="../css/L.Control.Layers.Tree.css">
  <link rel="stylesheet" href="../css/qgis2web.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <style>
      html, body {
          height: 100%;
          margin: 0;
          padding: 0;
      }
      .main-content {
          margin-left: var(--sidebar-width, 240px);
          padding-left: 32px; /* <-- This adds the gap between sidebar and map */
          height: 100vh;
          width: calc(100vw - var(--sidebar-width, 240px) - 32px);
          box-sizing: border-box;
      }
      #map {
          width: 100%;
          height: 100vh;
      }
      @media (max-width: 700px) {
        .main-content {
          margin-left: 0;
          padding-left: 0;
          width: 100vw;
        }
      }
      /* Custom Popup Styles */
      .custom-popup {
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: white;
          padding: 32px 32px 50px 32px;
          border-radius: 6px;
          box-shadow: 0 2px 16px rgba(0,0,0,0.12);
          z-index: 1000;
          width: 300px;
          display: none;
          font-family: 'Inter', sans-serif;
      }
      .custom-popup.active {
          display: block;
      }
      .popup-form-label {
          font-size: 15px;
          font-weight: 500;
          margin-bottom: 4px;
          color: #222;
      }
      .popup-form-id-label {
          font-size: 18px;
          font-weight: 700;
          margin-bottom: 0;
          color: #222;
      }
      .popup-form-id-value {
          font-size: 16px;
          color: #b0b0b0;
          margin-bottom: 18px;
          margin-top: 2px;
          font-weight: 500;
          letter-spacing: 1px;
      }
      .popup-form-group {
          margin-bottom: 18px;
      }
      .popup-form-input {
          width: 90%;
          padding: 8px 12px;
          border: 1px solid #e0e0e0;
          border-radius: 6px;
          background: #f7f7f7;
          font-size: 15px;
          color: #444;
          margin-top: 2px;
          margin-bottom: 0;
          outline: none;
      }
      .popup-form-input[readonly] {
          background: #f7f7f7;
          color: #888;
      }
      .popup-buttons {
          display: flex;
          justify-content: flex-end;
          gap: 10px;
          margin-top: 10px;
      }
      .popup-button {
          padding: 10px 28px;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          font-weight: 600;
          font-size: 16px;
          transition: background 0.2s;
      }
      .edit-button,
      .cancel-button {
          width: 120px;
      }
      .edit-button {
          background-color: #19d64c;
          color: white;
      }
      .edit-button:hover {
          background-color: #13b53e;
      }
      .cancel-button {
          background-color: #f44336;
          color: white;
      }
      .cancel-button:hover {
          background-color: #d32f2f;
      }
      .popup-overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0,0,0,0.18);
          z-index: 999;
          display: none;
      }
      .popup-overlay.active {
          display: block;
      }
  </style>
  <script>
    // Pass PHP deceased data to JS
    var deceasedData = <?php echo json_encode($deceasedData); ?>;
  </script>
</head>
<body>
   <!-- Sidebar -->
   <?php include '../Includes/sidebar.php'; ?>

   <main class="main-content">
     <div id="map"></div>
   </main>
   
   <!-- Custom Popup -->
   <div class="popup-overlay" id="popupOverlay"></div>
   <div class="custom-popup" id="customPopup">
       <div id="popupContent">
           <!-- Content will be dynamically inserted here -->
       </div>
       <div class="popup-buttons">
           <button class="popup-button edit-button" id="editButton">Edit</button>
           <button class="popup-button edit-button" id="insertButton" style="display:none;">Insert</button>
           <button class="popup-button cancel-button" id="cancelButton">Cancel</button>
       </div>
   </div>
   
        <script src="../js/qgis2web_expressions.js"></script>
        <script src="../js/leaflet.js"></script>
        <script src="../js/L.Control.Layers.Tree.min.js"></script>
        <script src="../js/leaflet.rotatedMarker.js"></script>
        <script src="../js/leaflet.pattern.js"></script>
        <script src="../js/leaflet-hash.js"></script>
        <script src="../js/Autolinker.min.js"></script>
        <script src="../js/rbush.min.js"></script>
        <script src="../js/labelgun.min.js"></script>
        <script src="../js/labels.js"></script>
        <script src="../data/border_1.js"></script>
        <script src="../data/Floor1_2.js"></script>
        <script>
        var highlightLayer;
        function highlightFeature(e) {
            highlightLayer = e.target;

            if (e.target.feature.geometry.type === 'LineString' || e.target.feature.geometry.type === 'MultiLineString') {
              highlightLayer.setStyle({
                color: '#ffff00',
              });
            } else {
              highlightLayer.setStyle({
                fillColor: '#ffff00',
                fillOpacity: 1
              });
            }
        }
        var map = L.map('map', {
            zoomControl:false, maxZoom:28, minZoom:1
        }).fitBounds([[13.883513513459492,121.2234865364029],[13.88355206056936,121.22356938136839]]);
        var hash = new L.Hash(map);
        map.attributionControl.setPrefix('<a href="https://github.com/tomchadwin/qgis2web" target="_blank">qgis2web</a> &middot; <a href="https://leafletjs.com" title="A JS library for interactive maps">Leaflet</a> &middot; <a href="https://qgis.org">QGIS</a>');
        var autolinker = new Autolinker({truncate: {length: 30, location: 'smart'}});
        // remove popup's row if "visible-with-data"
        function removeEmptyRowsFromPopupContent(content, feature) {
         var tempDiv = document.createElement('div');
         tempDiv.innerHTML = content;
         var rows = tempDiv.querySelectorAll('tr');
         for (var i = 0; i < rows.length; i++) {
             var td = rows[i].querySelector('td.visible-with-data');
             var key = td ? td.id : '';
             if (td && td.classList.contains('visible-with-data') && feature.properties[key] == null) {
                 rows[i].parentNode.removeChild(rows[i]);
             }
         }
         return tempDiv.innerHTML;
        }
        // add class to format popup if it contains media
		function addClassToPopupIfMedia(content, popup) {
			var tempDiv = document.createElement('div');
			tempDiv.innerHTML = content;
			if (tempDiv.querySelector('td img')) {
				popup._contentNode.classList.add('media');
					// Delay to force the redraw
					setTimeout(function() {
						popup.update();
					}, 10);
			} else {
				popup._contentNode.classList.remove('media');
			}
		}
        var zoomControl = L.control.zoom({
            position: 'topleft'
        }).addTo(map);
        var bounds_group = new L.featureGroup([]);
        function setBounds() {
        }
        map.createPane('pane_OpenStreetMap_0');
        map.getPane('pane_OpenStreetMap_0').style.zIndex = 400;
        var layer_OpenStreetMap_0 = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            pane: 'pane_OpenStreetMap_0',
            opacity: 1.0,
            attribution: '',
            minZoom: 1,
            maxZoom: 28,
            minNativeZoom: 0,
            maxNativeZoom: 19
        });
        layer_OpenStreetMap_0;
        map.addLayer(layer_OpenStreetMap_0);
        function pop_border_1(feature, layer) {
            layer.on({
                mouseout: function(e) {
                    for (var i in e.target._eventParents) {
                        if (typeof e.target._eventParents[i].resetStyle === 'function') {
                            e.target._eventParents[i].resetStyle(e.target);
                        }
                    }
                },
                mouseover: highlightFeature,
            });
            var popupContent = '<table>\
                    <tr>\
                        <td colspan="2">' + (feature.properties['borderID'] !== null ? autolinker.link(String(feature.properties['borderID']).replace(/'/g, '\'').toLocaleString()) : '') + '</td>\
                    </tr>\
                </table>';
            var content = removeEmptyRowsFromPopupContent(popupContent, feature);
			layer.on('popupopen', function(e) {
				addClassToPopupIfMedia(content, e.popup);
			});
			layer.bindPopup(content, { maxHeight: 400 });
        }

        function style_border_1_0() {
            return {
                pane: 'pane_border_1',
                opacity: 1,
                color: 'rgba(255,158,23,1.0)',
                dashArray: '',
                lineCap: 'square',
                lineJoin: 'bevel',
                weight: 1.0,
                fillOpacity: 0,
                interactive: false,
            }
        }
        map.createPane('pane_border_1');
        map.getPane('pane_border_1').style.zIndex = 401;
        map.getPane('pane_border_1').style['mix-blend-mode'] = 'normal';
        var layer_border_1 = new L.geoJson(json_border_1, {
            attribution: '',
            interactive: false,
            dataVar: 'json_border_1',
            layerName: 'layer_border_1',
            pane: 'pane_border_1',
            onEachFeature: pop_border_1,
            style: style_border_1_0,
        });
        bounds_group.addLayer(layer_border_1);
        map.addLayer(layer_border_1);
        function pop_Floor1_2(feature, layer) {
            layer.on({
                mouseout: function(e) {
                    for (var i in e.target._eventParents) {
                        if (typeof e.target._eventParents[i].resetStyle === 'function') {
                            e.target._eventParents[i].resetStyle(e.target);
                        }
                    }
                },
                mouseover: highlightFeature,
                click: function(e) {
                    // Add this block for niche picker mode
                    if (window.location.search.includes('pickNiche=1')) {
                        if (window.opener) {
                            window.opener.postMessage({ nicheID: feature.properties['nicheID'] }, '*');
                            window.close();
                        }
                        return;
                    }
                    var nicheID = feature.properties['nicheID'];
                    var deceased = deceasedData[nicheID];
                    var popupContent = '';
                    if (deceased) {
                        popupContent = `
        <div class="popup-form-group">
            <div class="popup-form-id-label">nicheID</div>
            <div class="popup-form-id-value">${nicheID}</div>
        </div>
        <div class="popup-form-group">
            <label class="popup-form-label">Name:</label>
            <input class="popup-form-input" type="text" value="${deceased.firstName} ${deceased.lastName}" readonly>
        </div>
        <div class="popup-form-group">
            <label class="popup-form-label">Born:</label>
            <input class="popup-form-input" type="text" value="${deceased.born}" readonly>
        </div>
        <div class="popup-form-group">
            <label class="popup-form-label">Date Died:</label>
            <input class="popup-form-input" type="text" value="${deceased.dateDied}" readonly>
        </div>
                  `;
                        setTimeout(function() {
                            document.getElementById('editButton').style.display = '';
                            document.getElementById('insertButton').style.display = 'none';
                        }, 0);
                    } else {
                        popupContent = `
        <div class="popup-form-group">
            <div class="popup-form-id-label">nicheID</div>
            <div class="popup-form-id-value">${nicheID}</div>
        </div>
        <div class="popup-form-group">
            <label class="popup-form-label">Status:</label>
            <input class="popup-form-input" type="text" value="Vacant" readonly>
        </div>
                        `;
                        setTimeout(function() {
                            document.getElementById('editButton').style.display = 'none';
                            document.getElementById('insertButton').style.display = '';
                        }, 0);
                    }
                    document.getElementById('popupContent').innerHTML = popupContent;
                    document.getElementById('popupOverlay').classList.add('active');
                    document.getElementById('customPopup').classList.add('active');
                }
            });
        }

        function style_Floor1_2_0(feature) {
            // Check if this nicheID has a deceased record
            var nicheID = feature.properties && feature.properties['nicheID'];
            if (typeof deceasedData !== "undefined" && deceasedData[nicheID]) {
                // Use "sold" color if there is data
                return {
                    pane: 'pane_Floor1_2',
                    opacity: 1,
                    color: 'rgba(35,35,35,1.0)',
                    dashArray: '',
                    lineCap: 'butt',
                    lineJoin: 'miter',
                    weight: 1.0, 
                    fill: true,
                    fillOpacity: 1,
                    fillColor: 'rgba(251,154,153,1.0)', // Sold color
                    interactive: true,
                };
            }
            if (feature.properties && feature.properties['borderID'] === 'separatorBand') {
                return {
                    pane: 'pane_Floor1_2',
                    color: 'rgba(96, 125, 139, 1.0)',
                    weight: 0,
                    fill: true,
                    fillOpacity: 1,
                    interactive: false
                };
            }
            switch(String(feature.properties['Status'])) {
                case 'vacant':
                    return {
                pane: 'pane_Floor1_2',
                opacity: 1,
                color: 'rgba(35,35,35,1.0)',
                dashArray: '',
                lineCap: 'butt',
                lineJoin: 'miter',
                weight: 1.0, 
                fill: true,
                fillOpacity: 1,
                fillColor: 'rgba(123,213,145,1.0)',
                interactive: true,
            }
                    break;
                case 'reserved':
                    return {
                pane: 'pane_Floor1_2',
                opacity: 1,
                color: 'rgba(35,35,35,1.0)',
                dashArray: '',
                lineCap: 'butt',
                lineJoin: 'miter',
                weight: 1.0, 
                fill: true,
                fillOpacity: 1,
                fillColor: 'rgba(166,206,227,1.0)',
                interactive: true,
            }
                    break;
                case 'sold':
                    return {
                pane: 'pane_Floor1_2',
                opacity: 1,
                color: 'rgba(35,35,35,1.0)',
                dashArray: '',
                lineCap: 'butt',
                lineJoin: 'miter',
                weight: 1.0, 
                fill: true,
                fillOpacity: 1,
                fillColor: 'rgba(251,154,153,1.0)',
                interactive: true,
            }
                    break;
            }
        }
        map.createPane('pane_Floor1_2');
        map.getPane('pane_Floor1_2').style.zIndex = 402;
        map.getPane('pane_Floor1_2').style['mix-blend-mode'] = 'normal';
        var layer_Floor1_2 = new L.geoJson(json_Floor1_2, {
            attribution: '',
            interactive: true,
            dataVar: 'json_Floor1_2',
            layerName: 'layer_Floor1_2',
            pane: 'pane_Floor1_2',
            onEachFeature: pop_Floor1_2,
            style: style_Floor1_2_0,
        });
        bounds_group.addLayer(layer_Floor1_2);
        map.addLayer(layer_Floor1_2);
        var overlaysTree = [
            {label: 'Floor 1<br /><table><tr><td style="text-align: center;"><img src="../legend/Floor1_2_Vacant0.png" /></td><td>Vacant</td></tr><tr><td style="text-align: center;"><img src="../legend/Floor1_2_Reserved1.png" /></td><td>Reserved</td></tr><tr><td style="text-align: center;"><img src="../legend/Floor1_2_Sold2.png" /></td><td>Sold</td></tr></table>', layer: layer_Floor1_2},
            {label: '<img src="../legend/border_1.png" /> border', layer: layer_border_1},
            {label: "OpenStreetMap", layer: layer_OpenStreetMap_0},]
        var lay = L.control.layers.tree(null, overlaysTree,{
            //namedToggle: true,
            //selectorBack: false,
            //closedSymbol: '&#8862; &#x1f5c0;',
            //openedSymbol: '&#8863; &#x1f5c1;',
            //collapseAll: 'Collapse all',
            //expandAll: 'Expand all',
            collapsed: false, 
        });
        lay.addTo(map);
		document.addEventListener("DOMContentLoaded", function() {
            // set new Layers List height which considers toggle icon
            function newLayersListHeight() {
                var layerScrollbarElement = document.querySelector('.leaflet-control-layers-scrollbar');
                if (layerScrollbarElement) {
                    var layersListElement = document.querySelector('.leaflet-control-layers-list');
                    var originalHeight = layersListElement.style.height 
                        || window.getComputedStyle(layersListElement).height;
                    var newHeight = parseFloat(originalHeight) - 50;
                    layersListElement.style.height = newHeight + 'px';
                }
            }
            var isLayersListExpanded = true;
            var controlLayersElement = document.querySelector('.leaflet-control-layers');
            var toggleLayerControl = document.querySelector('.leaflet-control-layers-toggle');
            // toggle Collapsed/Expanded and apply new Layers List height
            toggleLayerControl.addEventListener('click', function() {
                if (isLayersListExpanded) {
                    controlLayersElement.classList.remove('leaflet-control-layers-expanded');
                } else {
                    controlLayersElement.classList.add('leaflet-control-layers-expanded');
                }
                isLayersListExpanded = !isLayersListExpanded;
                newLayersListHeight()
            });	
			// apply new Layers List height if toggle layerstree
			if (controlLayersElement) {
				controlLayersElement.addEventListener('click', function(event) {
					var toggleLayerHeaderPointer = event.target.closest('.leaflet-layerstree-header-pointer span');
					if (toggleLayerHeaderPointer) {
						newLayersListHeight();
					}
				});
			}
            // Collapsed/Expanded at Start to apply new height
            setTimeout(function() {
                toggleLayerControl.click();
            }, 10);
            setTimeout(function() {
                toggleLayerControl.click();
            }, 10);
            // Collapsed touch/small screen
            var isSmallScreen = window.innerWidth < 650;
            if (isSmallScreen) {
                setTimeout(function() {
                    controlLayersElement.classList.remove('leaflet-control-layers-expanded');
                    isLayersListExpanded = !isLayersListExpanded;
                }, 500);
            }  
        });       
        setBounds();
        var i = 0;
        layer_Floor1_2.eachLayer(function(layer) {
            var context = {
                feature: layer.feature,
                variables: {}
            };
            if (layer.feature.properties['nicheID']) {
                layer.bindTooltip(
                    String('<div style="color: #323232; font-size: 10pt; font-family: \'Open Sans\', sans-serif;">' + layer.feature.properties['nicheID']) + '</div>',
                    {permanent: true, offset: [-0, -16], className: 'css_Floor1_2'}
                );
            }
            labels.push(layer);
            totalMarkers += 1;
              layer.added = true;
              addLabel(layer, i);
              i++;
        });
        resetLabels([layer_Floor1_2]);
        map.on("zoomend", function(){
            resetLabels([layer_Floor1_2]);
        });
        map.on("layeradd", function(){
            resetLabels([layer_Floor1_2]);
        });
        map.on("layerremove", function(){
            resetLabels([layer_Floor1_2]);
        });
        // Add event listeners for popup buttons
        document.getElementById('cancelButton').addEventListener('click', function() {
            document.getElementById('popupOverlay').classList.remove('active');
            document.getElementById('customPopup').classList.remove('active');
        });

        document.getElementById('editButton').addEventListener('click', function() {
            var nicheID = document.querySelector('.popup-form-id-value').textContent.trim();
            var name = document.querySelectorAll('.popup-form-input')[0].value.trim();
            var born = document.querySelectorAll('.popup-form-input')[1].value.trim();
            var died = document.querySelectorAll('.popup-form-input')[2].value.trim();

            var params = new URLSearchParams({
                nicheID: nicheID,
                name: name,
                born: born,
                died: died
            });

            window.location.href = 'Niches.php?' + params.toString();
        });

        document.getElementById('insertButton').addEventListener('click', function() {
            var nicheID = document.querySelector('.popup-form-id-value').textContent.trim();
            var params = new URLSearchParams({
                nicheID: nicheID
            });
            window.location.href = 'insert.php?' + params.toString();
        });

        // Close popup when clicking outside
        document.getElementById('popupOverlay').addEventListener('click', function() {
            document.getElementById('popupOverlay').classList.remove('active');
            document.getElementById('customPopup').classList.remove('active');
        });
        </script>
</body>
</html>
