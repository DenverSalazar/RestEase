<?php
// Database connection (adjust credentials as needed)
include_once '../Includes/db.php';
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Fetch all deceased records indexed by nicheID
$deceasedData = [];
$result = $conn->query("SELECT nicheID, firstName, middleName, lastName, suffix, born, dateDied FROM deceased");
while ($row = $result->fetch_assoc()) {
    $nicheID = $row['nicheID'];
    if (!isset($deceasedData[$nicheID])) {
        $deceasedData[$nicheID] = [];
    }
    $deceasedData[$nicheID][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../assets/re logo blue.png">
  <title>Old Map</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
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
      right: 24px !important;
      left: auto !important;
      bottom: 18px !important;
      border-radius: 10px !important;
      max-width: 260px !important;
      min-width: 140px !important;
      width: auto !important;
    }
    /* Position legend at lower right in all modes */
    .custom-map-legend {
      right: 18px !important;
      left: auto !important;
      bottom: 18px !important;
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
    .main-content {
      position: relative;
      padding-top: 0 !important;
    }
    .search-filter-bar {
      margin-top: 0 !important;
      margin-bottom: 0 !important;
      position: relative;
      top: 0;
      z-index: 14000; /* ensure search bar and dropdown sit above map elements */
      box-shadow: none !important;
      border-radius: 0 !important;
    }
    #map {
      margin-top: 0 !important;
      box-shadow: none !important;
      border-top-left-radius: 0 !important;
      border-top-right-radius: 0 !important;
    }
    /* Custom styled select for filter */
    .filter-select {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background: #fff url('data:image/svg+xml;utf8,<svg fill="%232d8cff" height="20" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 12px center/18px 18px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      padding: 8px 36px 8px 14px;
      font-size: 15px;
      color: #222;
      font-family: 'Inter', 'Poppins', sans-serif;
      font-weight: 500;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      transition: border 0.18s, box-shadow 0.18s;
      outline: none;
      cursor: pointer;
      min-width: 120px;
      margin-left: 12px;
    }
    .filter-select:focus {
      border: 1.5px solid #2d8cff;
      box-shadow: 0 0 0 2px rgba(45,140,255,0.08);
    }
    .filter-select option {
      background: #fff;
      color: #222;
      font-weight: 500;
      font-family: 'Inter', 'Poppins', sans-serif;
    }
    @media (max-width: 600px) {
      .filter-select {
        font-size: 13px;
        padding: 6px 28px 6px 10px;
        min-width: 90px;
        margin-left: 6px;
      }
    }
    .custom-popup.active {
      display: block !important;
    }
    .popup-overlay.active {
      display: block !important;
    }
    /* Add search validation popup styles from Mapping.php */
    #searchErrorOverlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.15);
      z-index: 9999;
    }
    #searchErrorPopup {
      position: fixed;
      left: 50%; top: 50%;
      transform: translate(-50%, -50%);
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.13);
      z-index: 10000;
      padding: 0;
      min-width: 260px;
      max-width: 340px;
      display: none;
    }
    #searchErrorPopup.active, #searchErrorOverlay.active {
      display: block !important;
    }
    #searchErrorPopup .popup-button {
      background: #fb9a99;
      color: #fff;
      border-radius: 6px;
      border: none;
      padding: 8px 22px;
      font-size: 15px;
      font-family: 'Inter','Poppins',sans-serif;
      cursor: pointer;
      margin-top: 10px;
      transition: background 0.18s;
    }
    #searchErrorPopup .popup-button:hover {
      background: #e57373;
    }
    .custom-niche-tooltip {
      background: #fff;
      color: #222;
      border-radius: 0.5rem;
      box-shadow: 0 2px 8px rgba(60,60,60,0.12);
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
      padding: 0.5rem 1rem;
      border: 1px solid #ddd;
      font-weight: 500;
      z-index: 9999;
    }
    .plaque-popup {
      background: #f7f7f7;
      border: 2px solid #222;
      border-radius: 18px;
      box-shadow: 0 4px 24px rgba(60,60,60,0.13);
      padding: 28px 18px 18px 18px;
      text-align: center;
      font-family: 'Poppins', 'Times New Roman', serif;
      position: relative;
      margin-bottom: 12px;
      max-width: 340px;
      margin-left: auto;
      margin-right: auto;
    }
    .plaque-header {
      font-size: 1.08rem;
      font-weight: 600;
      letter-spacing: 1px;
      margin-bottom: 8px;
      color: #222;
      font-family: 'Poppins', serif;
    }
    .plaque-icon {
      font-size: 2.2rem;
      color: #222;
      margin-bottom: 8px;
    }
    .plaque-name {
      font-family: 'Dancing Script', cursive, 'Times New Roman', serif;
      font-size: 1.5rem;
      font-weight: 700;
      color: #222;
      margin-bottom: 8px;
      letter-spacing: 1px;
    }
    .plaque-dates {
      font-family: 'Dancing Script', cursive, 'Times New Roman', serif;
      font-size: 1.15rem;
      color: #222;
      margin-bottom: 8px;
    }
    .plaque-verse {
      font-family: 'Dancing Script', cursive, 'Times New Roman', serif;
      font-size: 1rem;
      color: #444;
      margin-bottom: 4px;
      font-style: italic;
    }
    .plaque-ref {
      font-size: 0.95rem;
      color: #888;
      font-family: 'Poppins', serif;
      margin-bottom: 0;
    }
      .popup-buttons {
      display: flex;
      justify-content: center;
      gap: 18px;
      margin-top: 18px;
      margin-bottom: 0;
      padding: 0;
    }
    /* Autocomplete suggestions (OldMap) */
    .search-input-wrapper { position: relative; display: block; width: 100%; box-sizing: border-box; }
    .search-suggestions {
      position: absolute;
      top: calc(100% + 8px);
      left: 0;
      right: 0;
      width: 100%;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 8px 30px rgba(2,6,23,0.08);
      z-index: 14001;
      overflow: auto;
      max-height: 300px;
      padding: 6px;
      box-sizing: border-box;
      display: none;
    }
    .search-suggestion-item { padding:10px 12px; border-radius:8px; cursor:pointer; display:flex; justify-content:space-between; gap:12px; align-items:center; font-weight:600; color:#111; }
    .search-suggestion-item.active, .search-suggestion-item:hover { background:#f3f4f6; }
    .search-suggestion-name { flex:1; text-align:left; }
    .search-suggestion-meta { color:#6b7280; font-size:.95rem; }
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
            [layer_Floor1, layer_Floor1, layer_Floor1, layer_Floor1_4].forEach(function(sectionLayer) {
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
                    // Update the layer's properties to show it's leased
                    layer.feature.properties.occupied = true;
                    layer.feature.properties.Status = 'leased';
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
            
            // Then highlight the new niche in red (leased)
            setTimeout(function() {
              highlightNicheOnMap(highlightNicheID, '#fb9a99', true, false);
            }, 100);
          } else {
            // Just highlight the niche in red (leased)
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
       <div class="search-filter-bar" style="margin-top:0 !important; margin-bottom:0 !important;">
        <div class="search-input-wrapper">
            <input class="search-input" id="mapSearchInput" type="text" placeholder="Tap to search">
            <span class="search-input-icon"><i class="fas fa-search"></i></span>
            <div id="searchSuggestions" class="search-suggestions" role="listbox" aria-label="Search suggestions"></div>
        </div>
        <!-- Removed filter select dropdown -->
     </div>
      <div id="map" style="margin-top:0 !important;">
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
                <span class="legend-label">Leased</span>
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
   <!-- Search Error Popup -->
   <div class="popup-overlay" id="searchErrorOverlay" style="display:none;"></div>
   <div class="custom-popup" id="searchErrorPopup" style="display:none; max-width:340px;">
       <div id="searchErrorContent" style="padding:24px 18px; font-size:16px; color:#fb9a99; text-align:center;">
           No Niche ID or Name on the database, please check your entry and try again
       </div>
       <div style="text-align:center; margin-bottom:12px;">
           <button class="popup-button cancel-button" id="searchErrorCloseBtn" style="margin-top:10px;">Close</button>
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
   <script src="../data/OldMap/border_1.js"></script>
   <script src="../data/OldMap/floor1.js"></script>
   <script src="../data/OldMap/floor1_4.js"></script>
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
        map.setMaxZoom(minZoom + 5); // allow more zoom in capability
        map.setZoom(minZoom + 1); // set initial zoom level lower

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
                    // Remove tooltip on mouseout
                    layer.unbindTooltip();
                    for (var i in e.target._eventParents) {
                        if (typeof e.target._eventParents[i].resetStyle === 'function') {
                            e.target._eventParents[i].resetStyle(e.target);
                        }
                    }
                },
                mouseover: function(e) {
                    var nicheID = feature.properties['nicheID'];
                    var deceasedEntry = deceasedData[nicheID];
                    var tooltipContent = '';
                    if (deceasedEntry && deceasedEntry.length > 0) {
                        var names = deceasedEntry.map(function(d) {
                            var firstName = d.firstName || '';
                            var middleName = d.middleName || '';
                            var lastName = d.lastName || '';
                            var suffix = d.suffix || '';
                            var middleInitial = middleName ? (middleName.trim().charAt(0).toUpperCase() + '.') : '';
                            var fullName = firstName;
                            if (middleInitial) fullName += ' ' + middleInitial;
                            if (lastName) fullName += ' ' + lastName;
                            if (suffix) fullName += ', ' + suffix;
                            return `Name: ${fullName.trim()}`;
                        });
                        tooltipContent = `<strong>Niche ID:</strong> ${nicheID}<br>${names.join('<br>')}`;
                    } else {
                        tooltipContent = `<strong>Niche ID:</strong> ${nicheID}`;
                    }
                    layer.bindTooltip(tooltipContent, {
                        direction: 'top',
                        className: 'custom-niche-tooltip',
                        sticky: true
                    }).openTooltip();
                    highlightFeature(e);
                },
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
                    var deceasedEntry = deceasedData[nicheID];
                    var deceasedList = Array.isArray(deceasedEntry) ? deceasedEntry : (deceasedEntry ? [deceasedEntry] : []);
                    var deceasedIndex = 0;
                    var popupContent = '';

                    function renderPlaque(index) {
                        var deceased = deceasedList[index];
                        if (deceased) {
                            var firstName = deceased.firstName || '';
                            var middleName = deceased.middleName || '';
                            var lastName = deceased.lastName || '';
                            var suffix = deceased.suffix || '';
                            var middleInitial = middleName ? (middleName.trim().charAt(0).toUpperCase() + '.') : '';
                            var fullName = firstName;
                            if (middleInitial) fullName += ' ' + middleInitial;
                            if (lastName) fullName += ' ' + lastName;
                            if (suffix) fullName += ', ' + suffix;
                            popupContent = `
<div style="display:flex; align-items:center; justify-content:space-between;">
   <button id="prevDeceasedBtn" 
  style="background:none; border:none; cursor:pointer; ${deceasedList.length > 1 ? '' : 'visibility:hidden'}">
  <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" 
    fill="none" stroke="rgba(0,0,0,0.4)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="15 18 9 12 15 6"></polyline>
  </svg>
</button>
    <div style="flex:1;">
        <div class="plaque-popup">
            <div class="plaque-header">IN LOVING MEMORY OF</div>
            <div class="plaque-icon"><i class="fas fa-dove"></i></div>
            <div class="plaque-name">${fullName}</div>
            <div class="plaque-dates">
                ${deceased.born ? new Date(deceased.born).toLocaleDateString() : ''} - 
                ${deceased.dateDied ? new Date(deceased.dateDied).toLocaleDateString() : ''}
            </div>
        </div>
    </div>
   <button id="nextDeceasedBtn" style="background:none; border:none; cursor:pointer; ${deceasedList.length > 1 ? '' : 'visibility:hidden'}">
  <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" 
    fill="none" stroke="rgba(0,0,0,0.4)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="9 18 15 12 9 6"></polyline>
  </svg>
</button>
</div>
`;
                            setTimeout(function() {
                                document.getElementById('editButton').style.display = '';
                                document.getElementById('insertButton').style.display = 'none';
                            }, 0);
                        } else {
                            popupContent = `
<div class="plaque-popup">
    <div class="plaque-header">VACANT NICHE</div>
    <div class="plaque-icon"><i class="fas fa-cube"></i></div>
    <div class="plaque-name">${nicheID}</div>
    <div class="plaque-dates"></div>
    <div class="plaque-verse">This niche is available for lease.</div>
    <div class="plaque-ref" style="margin-bottom:8px;">Contact admin for details.</div>
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
                        if (deceasedList.length > 1) {
                            var prevBtn = document.getElementById('prevDeceasedBtn');
                            var nextBtn = document.getElementById('nextDeceasedBtn');
                            if (prevBtn) prevBtn.onclick = function() {
                                deceasedIndex = (deceasedIndex - 1 + deceasedList.length) % deceasedList.length;
                                renderPlaque(deceasedIndex);
                            };
                            if (nextBtn) nextBtn.onclick = function() {
                                deceasedIndex = (deceasedIndex + 1) % deceasedList.length;
                                renderPlaque(deceasedIndex);
                            };
                        }
                    }
                    renderPlaque(deceasedIndex);
                }
            });
        }

        // --- Section Layer Creation ---
        function style_Floor1(feature) {
            // Check if this nicheID has a deceased record
            var nicheID = feature.properties && feature.properties['nicheID'];
            if (typeof deceasedData !== "undefined" && deceasedData[nicheID]) {
                // Use "leased" color if there is data
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
                    fillColor: 'rgba(251,154,153,1.0)', // Leased color
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
                case 'leased':
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
        var layer_Floor1 = new L.geoJson(json_oldmap_floor1, {
            attribution: '',
            interactive: true,
            dataVar: 'json_oldmap_floor1',
            layerName: 'layer_Floor1',
            pane: 'pane_Floor1',
            onEachFeature: pop_Floor1,
            style: style_Floor1,
        });
        // Section 2
        var layer_Floor1 = new L.geoJson(json_oldmap_floor1, {
            attribution: '',
            interactive: true,
            dataVar: 'json_oldmap_floor1',
            layerName: 'layer_Floor1',
            pane: 'pane_Floor1',
            onEachFeature: pop_Floor1,
            style: style_Floor1,
        });
        // Section 3
        var layer_Floor1 = new L.geoJson(json_oldmap_floor1, {
            attribution: '',
            interactive: true,
            dataVar: 'json_oldmap_floor1',
            layerName: 'layer_Floor1',
            pane: 'pane_Floor1',
            onEachFeature: pop_Floor1,
            style: style_Floor1,
        });
        // Section 4
        var layer_Floor1_4 = new L.geoJson(json_oldmap_floor1_4, {
            attribution: '',
            interactive: true,
            dataVar: 'json_oldmap_floor1_4',
            layerName: 'layer_Floor1_4',
            pane: 'pane_Floor1',
            onEachFeature: pop_Floor1,
            style: style_Floor1,
        });

        bounds_group.addLayer(layer_Floor1);
        bounds_group.addLayer(layer_Floor1);
        bounds_group.addLayer(layer_Floor1);
        bounds_group.addLayer(layer_Floor1_4);

        // Only add Section 1 by default
        map.addLayer(layer_Floor1);
        map.addLayer(layer_Floor1);
        map.addLayer(layer_Floor1);
        map.addLayer(layer_Floor1_4);
        addSectionLabels(layer_Floor1);
        addSectionLabels(layer_Floor1);
        addSectionLabels(layer_Floor1);
        addSectionLabels(layer_Floor1_4);
        resetLabels([layer_Floor1, layer_Floor1, layer_Floor1, layer_Floor1_4]);

        // --- Section Toggle Button Logic ---
        function showSection(section) {
            // Remove all section layers
            [layer_Floor1, layer_Floor1, layer_Floor1, layer_Floor1_4].forEach(function(l) {
                if (map.hasLayer(l)) map.removeLayer(l);
            });
            
            if (section === 'all') {
                // Add all sections
                map.addLayer(layer_Floor1);
                map.addLayer(layer_Floor1);
                map.addLayer(layer_Floor1);
                map.addLayer(layer_Floor1_4);
                
                // Add labels for all sections
                addSectionLabels(layer_Floor1);
                addSectionLabels(layer_Floor1);
                addSectionLabels(layer_Floor1);
                addSectionLabels(layer_Floor1_4);
                resetLabels([layer_Floor1, layer_Floor1, layer_Floor1, layer_Floor1_4]);
            } else {
                // Add selected section
                switch(section) {
                    case 1: map.addLayer(layer_Floor1); break;
                    case 2: map.addLayer(layer_Floor1); break;
                    case 3: map.addLayer(layer_Floor1); break;
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
    if (e.layer === layer_Floor1) {
        addSectionLabels(layer_Floor1);
        resetLabels([layer_Floor1]);
    }
    if (e.layer === layer_Floor1) {
        addSectionLabels(layer_Floor1);
        resetLabels([layer_Floor1]);
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
    if (e.layer === layer_Floor1) {
        removeSectionLabels(layer_Floor1);
        resetLabels([]);
    }
    if (e.layer === layer_Floor1) {
        removeSectionLabels(layer_Floor1);
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
    if (map.hasLayer(layer_Floor1)) visibleLayers.push(layer_Floor1);
    if (map.hasLayer(layer_Floor1)) visibleLayers.push(layer_Floor1);
    if (map.hasLayer(layer_Floor1_4)) visibleLayers.push(layer_Floor1_4);
    resetLabels(visibleLayers);
});

// Add event listeners for popup buttons
document.getElementById('cancelButton').addEventListener('click', function() {
    document.getElementById('popupOverlay').classList.remove('active');
    document.getElementById('customPopup').classList.remove('active');
});

document.getElementById('editButton').addEventListener('click', function() {
    // Get deceased data from the currently displayed popup
    var plaqueName = document.querySelector('.plaque-name');
    var nicheID = '';
    var deceasedFields = {};
    // Find the deceased entry currently shown in the popup
    var deceasedList = [];
    for (var key in deceasedData) {
        var entry = deceasedData[key];
        if (Array.isArray(entry)) {
            deceasedList = deceasedList.concat(entry.map(function(d) {
                d.nicheID = key;
                return d;
            }));
        } else if (entry) {
            entry.nicheID = key;
            deceasedList.push(entry);
        }
    }
    var popupName = plaqueName ? plaqueName.textContent.trim() : '';
    var foundDeceased = deceasedList.find(function(d) {
        var firstName = d.firstName || '';
        var middleName = d.middleName || '';
        var lastName = d.lastName || '';
        var suffix = d.suffix || '';
        var middleInitial = middleName ? (middleName.trim().charAt(0).toUpperCase() + '.') : '';
        var fullName = firstName;
        if (middleInitial) fullName += ' ' + middleInitial;
        if (lastName) fullName += ' ' + lastName;
        if (suffix) fullName += ', ' + suffix;
        return fullName.trim() === popupName;
    });
    if (foundDeceased) {
        deceasedFields = {
            nicheID: foundDeceased.nicheID || '',
            firstName: foundDeceased.firstName || '',
            middleName: foundDeceased.middleName || '',
            lastName: foundDeceased.lastName || '',
            suffix: foundDeceased.suffix || '',
            born: foundDeceased.born || '',
            dateDied: foundDeceased.dateDied || '',
            age: foundDeceased.age || '',
            residency: foundDeceased.residency || '',
            dateInternment: foundDeceased.dateInternment || '',
            informantName: foundDeceased.informantName || ''
        };
    } else {
        // fallback: just nicheID
        deceasedFields = { nicheID: plaqueName ? plaqueName.textContent.trim() : '' };
    }
    deceasedFields.from = 'mapping';
    var params = new URLSearchParams(deceasedFields);
    window.location.href = 'EditNiches.php?' + params.toString();
});

document.getElementById('insertButton').addEventListener('click', function() {
    // Extract nicheID from the popup content
    var nicheID = '';
    var plaqueName = document.querySelector('.plaque-name');
    if (plaqueName) {
        nicheID = plaqueName.textContent.trim();
    }
    if (!nicheID) return;
    var params = new URLSearchParams({
        nicheID: nicheID
    });
    window.location.href = 'Insert.php?' + params.toString();
});

// Close popup when clicking outside
document.getElementById('popupOverlay').addEventListener('click', function() {
    document.getElementById('popupOverlay').classList.remove('active');
    document.getElementById('customPopup').classList.remove('active');
});
</script>
<script>
// --- SEARCH FUNCTIONALITY ---
// Replace previous search logic with validation popup logic
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('mapSearchInput');
    var searchErrorPopup = document.getElementById('searchErrorPopup');
    var searchErrorOverlay = document.getElementById('searchErrorOverlay');
    var searchErrorCloseBtn = document.getElementById('searchErrorCloseBtn');
   // --- Autocomplete for OldMap (oldmap layers only) ---
   var suggestionsBox = document.getElementById('searchSuggestions');
   var searchIndex = [];
   function buildOldMapIndex() {
       searchIndex = [];
       var layers = [window.layer_Floor1, window.layer_Floor1_4];
       layers.forEach(function(layer) {
           if (!layer) return;
           layer.eachLayer(function(fl) {
               var niche = fl.feature && fl.feature.properties && fl.feature.properties['nicheID'];
               if (!niche) return;
               var deceasedArr = deceasedData[niche];
               if (!deceasedArr) return;
               var arr = Array.isArray(deceasedArr) ? deceasedArr : [deceasedArr];
               arr.forEach(function(d) {
                   var firstName = d.firstName || '';
                   var middleName = d.middleName || '';
                   var lastName = d.lastName || '';
                   var suffix = d.suffix || '';
                   var midInit = middleName ? (middleName.trim().charAt(0).toUpperCase() + '.') : '';
                   var fullName = firstName;
                   if (midInit) fullName += ' ' + midInit;
                   if (lastName) fullName += ' ' + lastName;
                   if (suffix) fullName += ', ' + suffix;
                   fullName = fullName.trim();
                   var display = fullName ? (fullName + ' — ' + niche) : niche;
                   searchIndex.push({ nicheID: niche, name: fullName, nameLower: fullName.toLowerCase(), display: display });
               });
           });
       });
   }
   buildOldMapIndex();

   var activeIndex = -1;
   function escapeHtml(s){ return String(s).replace(/[&<>"']/g,function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];}); }
   function renderSuggestions(results){ suggestionsBox.innerHTML=''; if (!results || results.length===0){ suggestionsBox.style.display='none'; activeIndex=-1; return; } results.forEach(function(r,i){ var div=document.createElement('div'); div.className='search-suggestion-item'+(i===activeIndex?' active':''); div.setAttribute('data-niche', r.nicheID); div.innerHTML = '<div class="search-suggestion-name">'+escapeHtml(r.display)+'</div><div class="search-suggestion-meta">'+(r.name?'Name':'Niche')+'</div>'; div.addEventListener('click', function(){ chooseSuggestion(r); }); suggestionsBox.appendChild(div); }); suggestionsBox.style.display='block'; }
   function findMatches(q){ if(!q) return []; q=q.toLowerCase(); var out=[]; for(var i=0;i<searchIndex.length;i++){ var it=searchIndex[i]; if(it.nameLower && it.nameLower.indexOf(q)!==-1) out.push(it); else if(it.nicheID && String(it.nicheID).toLowerCase().indexOf(q)!==-1) out.push(it); if(out.length>=8) break; } return out; }
   function clearSuggestions(){ suggestionsBox.style.display='none'; suggestionsBox.innerHTML=''; activeIndex=-1; }
   function chooseSuggestion(item){ clearSuggestions(); searchInput.value = item.name || item.nicheID; var targets = [window.layer_Floor1, window.layer_Floor1_4]; var found=null; targets.forEach(function(l){ if (l) l.eachLayer(function(a){ if(!found && a.feature && a.feature.properties && String(a.feature.properties['nicheID'])===String(item.nicheID)) found=a; }); }); if (found){ [layer_Floor1, layer_Floor1_4].forEach(function(l){ if(l && !map.hasLayer(l)) map.addLayer(l); }); var center = found.getBounds ? found.getBounds().getCenter() : (found.getLatLng ? found.getLatLng() : null); if (center) map.setView(center, Math.max(map.getZoom(), map.getMaxZoom()-1), { animate:true }); highlightFeature({ target: found }); setTimeout(function(){ found.fire('click'); }, 220); } }
   searchInput.addEventListener('input', function(){ var q = searchInput.value.trim(); if(!q){ clearSuggestions(); return; } renderSuggestions(findMatches(q)); });
   searchInput.addEventListener('keydown', function(e){ var items = suggestionsBox.querySelectorAll('.search-suggestion-item'); if (e.key === 'ArrowDown'){ if(!items.length) return; e.preventDefault(); activeIndex=(activeIndex+1)%items.length; items.forEach(function(it,idx){ it.classList.toggle('active', idx===activeIndex); }); if(items[activeIndex]) items[activeIndex].scrollIntoView({block:'nearest'}); } else if (e.key==='ArrowUp'){ if(!items.length) return; e.preventDefault(); activeIndex=(activeIndex-1+items.length)%items.length; items.forEach(function(it,idx){ it.classList.toggle('active', idx===activeIndex); }); if(items[activeIndex]) items[activeIndex].scrollIntoView({block:'nearest'}); } else if (e.key==='Enter'){ if(items.length>0 && activeIndex>=0){ e.preventDefault(); var niche = items[activeIndex].getAttribute('data-niche'); chooseSuggestion({ nicheID: niche }); return; } /* else allow existing Enter handler below */ } else if (e.key==='Escape'){ clearSuggestions(); } });
   document.addEventListener('click', function(ev){ if (!ev.target.closest('#searchSuggestions') && ev.target !== searchInput) clearSuggestions(); });
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            var query = searchInput.value.trim().toLowerCase();
            if (!query) return;

            function normalizeName(deceased) {
                if (!deceased) return '';
                var firstName = deceased.firstName || '';
                var lastName = deceased.lastName || '';
                return (firstName + ' ' + lastName).trim().toLowerCase();
            }

            var found = false;
            var visibleLayers = [];
            if (map.hasLayer(layer_Floor1)) visibleLayers.push(layer_Floor1);
            if (map.hasLayer(layer_Floor1_4)) visibleLayers.push(layer_Floor1_4);

            visibleLayers.some(function(sectionLayer) {
                var matchLayer = null;
                sectionLayer.eachLayer(function(layer) {
                    var nicheID = layer.feature && layer.feature.properties['nicheID'];
                     var deceasedArr = deceasedData[nicheID];
                    if (nicheID && nicheID.toLowerCase() === query) {
                        matchLayer = layer;
                        return;
                    }
                    if (deceasedArr) {
                        var arr = Array.isArray(deceasedArr) ? deceasedArr : [deceasedArr];
                        if (arr.some(function(deceased) {
                            return normalizeName(deceased).includes(query);
                        })) {
                        matchLayer = layer;
                        return;
                          }
                    }
                });
                if (matchLayer) {
                    found = true;
                    // Zoom in to the center of the niche at max zoom
                    var center;
                    if (matchLayer.getBounds) {
                        center = matchLayer.getBounds().getCenter();
                    } else if (matchLayer.getLatLng) {
                        center = matchLayer.getLatLng();
                    }
                    if (center) {
                        map.setView(center, map.getMaxZoom(), { animate: true });
                    }
                    highlightFeature({ target: matchLayer });
                    setTimeout(function() {
                        matchLayer.fire('click');
                    }, 300);
                    return true;
                }
                return false;
            });

            if (!found) {
                searchInput.style.borderColor = '#fb9a99';
                if (searchErrorPopup && searchErrorOverlay) {
                    searchErrorPopup.classList.add('active');
                    searchErrorOverlay.classList.add('active');
                }
            } else {
                if (searchErrorPopup && searchErrorOverlay) {
                    searchErrorPopup.classList.remove('active');
                    searchErrorOverlay.classList.remove('active');
                }
            }
        }
    });
    if (searchErrorCloseBtn && searchErrorOverlay && searchErrorPopup) {
        searchErrorCloseBtn.addEventListener('click', function() {
            searchErrorPopup.classList.remove('active');
            searchErrorOverlay.classList.remove('active');
            searchInput.style.borderColor = '';
        });
        searchErrorOverlay.addEventListener('click', function() {
            searchErrorPopup.classList.remove('active');
            searchErrorOverlay.classList.remove('active');
            searchInput.style.borderColor = '';
        });
    }
});
</script>
</body>
</html>
