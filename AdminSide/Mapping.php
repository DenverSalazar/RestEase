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
  <link rel="stylesheet" href="../css/map.css">
  <style>
    /* Add pick-niche-mode styles */
    body.pick-niche-mode .sidebar {
      display: none !important;
    }
    body.pick-niche-mode .main-content {
      margin-left: 0 !important;
      padding: 0 !important;
      width: 100vw !important;
      min-height: 100vh !important;
      background: #fff !important;
    }
    body.pick-niche-mode .search-filter-bar {
      margin: 18px 18px 0 18px !important;
      left: 0 !important;
      right: 0 !important;
      border-radius: 10px !important;
    }
    /* Only adjust legend position, not width */
    body.pick-niche-mode .custom-map-legend {
      left: 24px !important;
      right: auto !important;
      bottom: 18px !important;
      border-radius: 10px !important;
      max-width: 260px !important;
      min-width: 140px !important;
      width: auto !important;
    }
    body.pick-niche-mode #map {
      margin: 0 !important;
      width: 100vw !important;
      height: 100vh !important;
      border-radius: 0 !important;
      box-shadow: none !important;
      border: none !important;
    }
    body.pick-niche-mode .custom-popup,
    body.pick-niche-mode .popup-overlay {
      display: none !important;
    }
    #sectionToggleBar {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      padding: 8px 12px;
      align-items: center;
      min-width: 320px;
      max-width: 420px;
      font-family: 'Inter', sans-serif;
      /* Move to right side */
      right: 18px !important;
      left: auto !important;
      top: 18px !important;
      margin: 0 !important;
    }
    .section-btn {
      background: #f3f4f6;
      border: none;
      border-radius: 6px;
      padding: 7px 18px;
      font-size: 15px;
      color: #222;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.18s, color 0.18s;
      outline: none;
    }
    .section-btn.active, .section-btn:hover {
      background: #2d8cff;
      color: #fff;
    }
    @media (max-width: 600px) {
      #sectionToggleBar {
        min-width: 0;
        max-width: 100vw;
        flex-wrap: wrap;
        font-size: 13px;
        padding: 6px 4px;
        right: 4px !important;
        top: 4px !important;
      }
      .section-btn {
        padding: 6px 10px;
        font-size: 13px;
      }
    }
    /* Add these styles to your existing styles */
    .layer-control {
        position: absolute;
        top: 18px;
        right: 18px;
        z-index: 1001;
    }

    .layer-control-btn {
        background: #fff;
        border: none;
        border-radius: 10px;
        padding: 8px 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #333;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        transition: all 0.2s ease;
    }

    .layer-control-btn:hover {
        background: #f8f9fa;
    }

    .layer-control-btn i {
        font-size: 16px;
    }

    .layer-control-content {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 8px;
        background: #fff;
        border-radius: 10px;
        padding: 16px;
        min-width: 200px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: none;
    }

    .layer-control.active .layer-control-content {
        display: block;
    }

    .layer-section {
        margin-bottom: 12px;
    }

    .layer-section h4 {
        margin: 0 0 8px 0;
        font-size: 14px;
        color: #666;
        font-weight: 500;
    }

    .section-buttons {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .section-btn {
        background: #f3f4f6;
        border: none;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 13px;
        color: #222;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.18s, color 0.18s;
        text-align: left;
        width: 100%;
    }

    .section-btn.active, .section-btn:hover {
        background: #2d8cff;
        color: #fff;
    }

    @media (max-width: 600px) {
        .layer-control {
            top: 8px;
            right: 8px;
        }
        
        .layer-control-btn {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .layer-control-content {
            min-width: 180px;
            padding: 12px;
        }
    }
    /* Add to your existing styles */
    .show-all-btn {
        margin-top: 8px !important;
        background: #e9ecef !important;
        border-top: 1px solid #dee2e6 !important;
        padding-top: 12px !important;
    }

    .show-all-btn i {
        margin-right: 6px;
    }

    .show-all-btn.active {
        background: #2d8cff !important;
    }
  </style>
  <script>
    // Pass PHP deceased data to JS
    var deceasedData = <?php echo json_encode($deceasedData); ?>;
    // Add pick-niche-mode class to body if in pickNiche mode
    if (window.location.search.includes('pickNiche=1')) {
      document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('pick-niche-mode');
      });
    }

    // Highlight moved/edited niche if redirected from EditNiches.php
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const highlightNicheID = urlParams.get('nicheID');
      const oldNicheID = urlParams.get('oldNicheID');
      const highlight = urlParams.get('highlight');
      const moved = urlParams.get('moved');
      
      if (highlightNicheID && highlight === '1') {
        setTimeout(function() {
          // Function to highlight a niche on the map
          function highlightNicheOnMap(nicheID, color, openPopup, forceVacant) {
            [layer_Floor1, layer_Floor1_2, layer_Floor1_3, layer_Floor1_4].forEach(function(sectionLayer) {
              sectionLayer.eachLayer(function(layer) {
                if (
                  layer.feature &&
                  layer.feature.properties &&
                  layer.feature.properties['nicheID'] === nicheID
                ) {
                  // If forceVacant, show as vacant (green)
                  if (forceVacant) {
                    layer.setStyle({
                      fillColor: '#7dd591',
                      fillOpacity: 1,
                      color: '#7dd591',
                      weight: 3
                    });
                    // Update the layer's properties to show it's vacant
                    layer.feature.properties.occupied = false;
                    layer.feature.properties.deceased = null;
                    layer.feature.properties.Status = 'vacant';
                  } else {
                    // Otherwise use the specified color
                    layer.setStyle({
                      fillColor: color,
                      fillOpacity: 1,
                      color: color,
                      weight: 3
                    });
                    // Update the layer's properties to show it's occupied
                    layer.feature.properties.occupied = true;
                    layer.feature.properties.Status = 'sold';
                  }
                  if (openPopup) layer.fire('click');
                  // Reset style after 2 seconds
                  setTimeout(function() {
                    sectionLayer.resetStyle(layer);
                  }, 2000);
                }
              });
            });
          }

          // If this is a move operation
          if (moved === '1' && oldNicheID) {
            // First highlight the old niche in green (vacant)
            highlightNicheOnMap(oldNicheID, '#7dd591', false, true);
            
            // Then highlight the new niche in red (sold)
            setTimeout(function() {
              highlightNicheOnMap(highlightNicheID, '#fb9a99', true, false);
            }, 100);
          } else {
            // Just highlight the niche in red (sold)
            highlightNicheOnMap(highlightNicheID, '#fb9a99', true, false);
          }
        }, 600);
      }
    });
  </script>
</head>
<body>
   <!-- Sidebar -->
   <?php if (!isset($_GET['pickNiche'])) include '../Includes/sidebar.php'; ?>

   <main class="main-content">
     <div class="search-filter-bar">
        <div class="search-input-wrapper">
            <input class="search-input" id="mapSearchInput" type="text" placeholder="Tap to search">
            <span class="search-input-icon"><i class="fas fa-search"></i></span>
        </div>
        <select class="filter-select" id="mapFilterSelect">
            <option value="all">All</option>
            <option value="vacant">Vacant</option>
            <option value="sold">Sold</option>
            <option value="reserved">Reserved</option>
        </select>
     </div>
     <div id="map">
        <!-- Layer Control Button -->
        <div class="layer-control">
            <button class="layer-control-btn">
                <i class="fas fa-layer-group"></i>
                <span>Layers</span>
            </button>
            <div class="layer-control-content">
                <div class="layer-section">
                    <h4>Sections</h4>
                    <div class="section-buttons">
                        <button class="section-btn active" data-section="1">Section 1</button>
                        <button class="section-btn" data-section="2">Section 2</button>
                        <button class="section-btn" data-section="3">Section 3</button>
                        <button class="section-btn" data-section="4">Section 4</button>
                        <button class="section-btn show-all-btn" data-section="all">
                            <i class="fas fa-th-large"></i>
                            Show All Sections
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Custom Legend -->
        <div class="custom-map-legend" id="customMapLegend">
            <div class="legend-row">
                <span class="legend-dot vacant"></span>
                <span class="legend-label">Vacant</span>
            </div>
            <div class="legend-row">
                <span class="legend-dot sold"></span>
                <span class="legend-label">Sold</span>
            </div>
            <div class="legend-row">
                <span class="legend-dot reserved"></span>
                <span class="legend-label">Reserved</span>
            </div>
        </div>
     </div>
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
   
   <script src="../js/leaflet.js"></script>
   <script src="../js/L.Control.Layers.Tree.min.js"></script>
   <script src="../js/leaflet.rotatedMarker.js"></script>
   <script src="../js/leaflet.pattern.js"></script>
   <script src="../js/Autolinker.min.js"></script>
   <script src="../js/rbush.min.js"></script>
   <script src="../js/labelgun.min.js"></script>
   <script src="../js/labels.js"></script>
   <script src="../data/border_1.js"></script>
   <script src="../data/floor1.js"></script>
   <script src="../data/floor1_2.js"></script>
   <script src="../data/floor1_3.js"></script>
   <script src="../data/floor1_4.js"></script>
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
        // Remove OpenStreetMap and hash code
        // Set up map and restrict view to border
        var map = L.map('map', {
            zoomControl: false,
            maxBoundsViscosity: 1.0 // Prevent panning outside bounds
        });
        var borderLayer = new L.geoJson(json_border_1);
        var borderBounds = borderLayer.getBounds();
        map.fitBounds(borderBounds, {padding: [100, 100]});

        // Expand the max bounds a bit so you can pan around the border and not get stuck in the corner
        function expandBounds(bounds, factor) {
            var sw = bounds.getSouthWest();
            var ne = bounds.getNorthEast();
            var latDiff = (ne.lat - sw.lat) * (factor - 1) / 2;
            var lngDiff = (ne.lng - sw.lng) * (factor - 1) / 2;
            return L.latLngBounds(
                [sw.lat - latDiff, sw.lng - lngDiff],
                [ne.lat + latDiff, ne.lng + lngDiff]
            );
        }
        var paddedBounds = expandBounds(borderBounds, 1.2); // 20% larger
        map.setMaxBounds(paddedBounds);

        // Optionally, set min/max zoom based on border bounds
        var minZoom = map.getBoundsZoom(borderBounds, false);
        map.setMinZoom(minZoom - 1); // allow zooming out a bit more
        map.setMaxZoom(minZoom + 3); // allow zooming in more

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
        // After loading border_1.js, fit map to border bounds
        var borderLayer = new L.geoJson(json_border_1);
        map.fitBounds(borderLayer.getBounds());

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
        function pop_Floor1(feature, layer) {
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

        // --- Section Layer Creation ---
        function style_Floor1_0(feature) {
            // Check if this nicheID has a deceased record
            var nicheID = feature.properties && feature.properties['nicheID'];
            if (typeof deceasedData !== "undefined" && deceasedData[nicheID]) {
                // Use "sold" color if there is data
                return {
                    pane: 'pane_Floor1',
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
                    pane: 'pane_Floor1',
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
                pane: 'pane_Floor1',
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
                pane: 'pane_Floor1',
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
                pane: 'pane_Floor1',
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
        map.createPane('pane_Floor1');
        map.getPane('pane_Floor1').style.zIndex = 402;
        map.getPane('pane_Floor1').style['mix-blend-mode'] = 'normal';
        var layer_Floor1 = new L.geoJson(json_Floor1, {
            attribution: '',
            interactive: true,
            dataVar: 'json_Floor1',
            layerName: 'layer_Floor1',
            pane: 'pane_Floor1',
            onEachFeature: pop_Floor1,
            style: style_Floor1_0,
        });
        // Section 2
        var layer_Floor1_2 = new L.geoJson(json_Floor1_2, {
            attribution: '',
            interactive: true,
            dataVar: 'json_Floor1_2',
            layerName: 'layer_Floor1_2',
            pane: 'pane_Floor1',
            onEachFeature: pop_Floor1,
            style: style_Floor1_0,
        });
        // Section 3
        var layer_Floor1_3 = new L.geoJson(json_Floor1_3, {
            attribution: '',
            interactive: true,
            dataVar: 'json_Floor1_3',
            layerName: 'layer_Floor1_3',
            pane: 'pane_Floor1',
            onEachFeature: pop_Floor1,
            style: style_Floor1_0,
        });
        // Section 4
        var layer_Floor1_4 = new L.geoJson(json_Floor1_4, {
            attribution: '',
            interactive: true,
            dataVar: 'json_Floor1_4',
            layerName: 'layer_Floor1_4',
            pane: 'pane_Floor1',
            onEachFeature: pop_Floor1,
            style: style_Floor1_0,
        });

        bounds_group.addLayer(layer_Floor1);
        bounds_group.addLayer(layer_Floor1_2);
        bounds_group.addLayer(layer_Floor1_3);
        bounds_group.addLayer(layer_Floor1_4);

        // Only add Section 1 by default
        map.addLayer(layer_Floor1);

        // --- Section Toggle Button Logic ---
        function showSection(section) {
            // Remove all section layers
            [layer_Floor1, layer_Floor1_2, layer_Floor1_3, layer_Floor1_4].forEach(function(l) {
                if (map.hasLayer(l)) map.removeLayer(l);
            });
            
            if (section === 'all') {
                // Add all sections
                map.addLayer(layer_Floor1);
                map.addLayer(layer_Floor1_2);
                map.addLayer(layer_Floor1_3);
                map.addLayer(layer_Floor1_4);
                
                // Add labels for all sections
                addSectionLabels(layer_Floor1);
                addSectionLabels(layer_Floor1_2);
                addSectionLabels(layer_Floor1_3);
                addSectionLabels(layer_Floor1_4);
                resetLabels([layer_Floor1, layer_Floor1_2, layer_Floor1_3, layer_Floor1_4]);
            } else {
                // Add selected section
                switch(section) {
                    case 1: map.addLayer(layer_Floor1); break;
                    case 2: map.addLayer(layer_Floor1_2); break;
                    case 3: map.addLayer(layer_Floor1_3); break;
                    case 4: map.addLayer(layer_Floor1_4); break;
                }
            }
        }
        document.addEventListener("DOMContentLoaded", function() {
            const layerControlBtn = document.querySelector('.layer-control-btn');
            const layerControl = document.querySelector('.layer-control');
            
            // Toggle layer control
            layerControlBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                layerControl.classList.toggle('active');
            });

            // Close layer control when clicking outside
            document.addEventListener('click', function(e) {
                if (!layerControl.contains(e.target)) {
                    layerControl.classList.remove('active');
                }
            });

            // Section button click handlers
            const sectionBtns = document.querySelectorAll('.section-btn');
            sectionBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    sectionBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    
                    const section = btn.getAttribute('data-section');
                    showSection(section === 'all' ? 'all' : Number(section));
                    
                    // Optionally close the layer control after selection
                    layerControl.classList.remove('active');
                });
            });
        });
        // --- Tooltips and Labels for all sections ---
function addSectionLabels(sectionLayer) {
    sectionLayer.eachLayer(function(layer) {
        if (layer.feature && layer.feature.properties['nicheID']) {
            // Remove tooltip display completely
            if (layer.getTooltip()) {
                layer.unbindTooltip();
            }
        }
        labels.push(layer);
        totalMarkers += 1;
        layer.added = true;
        addLabel(layer, totalMarkers);
    });
}

function removeSectionLabels(sectionLayer) {
    sectionLayer.eachLayer(function(layer) {
        if (layer.getTooltip()) {
            layer.unbindTooltip();
        }
        var idx = labels.indexOf(layer);
        if (idx !== -1) labels.splice(idx, 1);
        layer.added = false;
    });
}

// Add labels only for the default visible layer
addSectionLabels(layer_Floor1);
resetLabels([layer_Floor1]);

// Listen for layeradd/layerremove and update labels accordingly
map.on("layeradd", function(e){
    if (e.layer === layer_Floor1) {
        addSectionLabels(layer_Floor1);
        resetLabels([layer_Floor1]);
    }
    if (e.layer === layer_Floor1_2) {
        addSectionLabels(layer_Floor1_2);
        resetLabels([layer_Floor1_2]);
    }
    if (e.layer === layer_Floor1_3) {
        addSectionLabels(layer_Floor1_3);
        resetLabels([layer_Floor1_3]);
    }
    if (e.layer === layer_Floor1_4) {
        addSectionLabels(layer_Floor1_4);
        resetLabels([layer_Floor1_4]);
    }
});
map.on("layerremove", function(e){
    if (e.layer === layer_Floor1) {
        removeSectionLabels(layer_Floor1);
        resetLabels([]);
    }
    if (e.layer === layer_Floor1_2) {
        removeSectionLabels(layer_Floor1_2);
        resetLabels([]);
    }
    if (e.layer === layer_Floor1_3) {
        removeSectionLabels(layer_Floor1_3);
        resetLabels([]);
    }
    if (e.layer === layer_Floor1_4) {
        removeSectionLabels(layer_Floor1_4);
        resetLabels([]);
    }
});
map.on("zoomend", function(){
    // Only reset labels for visible layers
    var visibleLayers = [];
    if (map.hasLayer(layer_Floor1)) visibleLayers.push(layer_Floor1);
    if (map.hasLayer(layer_Floor1_2)) visibleLayers.push(layer_Floor1_2);
    if (map.hasLayer(layer_Floor1_3)) visibleLayers.push(layer_Floor1_3);
    if (map.hasLayer(layer_Floor1_4)) visibleLayers.push(layer_Floor1_4);
    resetLabels(visibleLayers);
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

            window.location.href = 'EditNiches.php?' + params.toString();
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
