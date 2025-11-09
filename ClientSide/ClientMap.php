<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}
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
    <title>RestEase</title>
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/clientleaflet.css">
    <link rel="stylesheet" href="../css/clientL.Control.Layers.Tree.css">
    <link rel="stylesheet" href="../css/clientqgis2web.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/clientmap.css">
    <style>
          body {
        font-family: 'Poppins', sans-serif;
        background: #fafbfc;
        color: #222;
        margin: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }
      .main-content {
        flex: 1 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        min-height: 60vh;
      }
      
      .footer {
        flex-shrink: 0;
      }
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        overflow-y: auto; /* Ensure vertical scroll is enabled */
      }
      #map-wrapper {
        min-height: 87vh; /* Fill the viewport */
        /* Remove align-items: stretch if present */
        display: flex;
        justify-content: center;
      }
      #map-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        min-height: 87vh; /* Fill the viewport */
        display: flex;
        flex-direction: column;
      }
      #map {
        flex: 1 1 auto;
        min-height: 80vh;
        /* Or use: height: calc(100vh - 120px); */
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
    /* Search suggestions - ensure input keeps original full width on large screens,
       and the dropdown matches the input width exactly */
    .search-input-wrapper {
      position: relative;
      display: block;
      width: 100%;
      max-width: none; /* don't constrain on large screens */
      box-sizing: border-box;
    }
    /* Ensure the input fills the wrapper (if your existing .search-input sets width, this is safe) */
    .search-input {
      width: 100%;
      box-sizing: border-box;
    }
    .search-suggestions {
      position: absolute;
      top: calc(100% + 8px);
      left: 0;
      right: 0;
      width: 100%;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 8px 30px rgba(2,6,23,0.12);
      z-index: 13000;
      overflow: auto;
      max-height: 320px;
      padding: 6px;
      box-sizing: border-box;
    }
    .search-suggestion-item {
      padding: 10px 12px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      color: #111827;
      display: flex;
      justify-content: space-between;
      gap: 12px;
      align-items: center;
    }
    .search-suggestion-item:hover,
    .search-suggestion-item.active {
      background: #f3f4f6;
    }
    .search-suggestion-name { flex:1; text-align:left; font-weight:600; }
    .search-suggestion-meta { color:#6b7280; font-size:0.95rem; }
    @media (max-width:720px) {
  /* keep some side padding on small screens so input isn't edge-to-edge */
  .search-input-wrapper {
    padding-left: 12px;
    padding-right: 12px;
    box-sizing: border-box;
    position: relative; /* for absolute icon positioning */
    display: block;
    width: 100%;
    margin: 8px 0;
  }

  /* Make the input a touch-friendly pill */
  .search-input {
    width: 100%;
    box-sizing: border-box;
    height: 44px;
    padding: 10px 16px 10px 44px; /* left padding to accommodate icon */
    border-radius: 999px;
    border: 1px solid rgba(229,231,235,1); /* subtle border */
    background: #ffffff;
    box-shadow: 0 8px 24px rgba(2,6,23,0.06);
    font-size: 16px;
    outline: none;
  }
  .search-input:focus {
    border-color: rgba(59,130,246,0.9);
    box-shadow: 0 8px 30px rgba(59,130,246,0.08);
  }

  /* Position the search icon inside the pill on the left */
  .search-input-icon {
    position: absolute;
    left: 22px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 18px;
    pointer-events: none; /* doesn't block clicks/typing */
    z-index: 13001;
  }

  /* Suggestions dropdown should align with the pill and be easy to tap */
  .search-suggestions {
    position: absolute;
    top: calc(100% + 10px);
    left: 12px;   /* align with wrapper padding */
    right: 12px;  /* make it full-width with small margins */
    width: auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 12px 40px rgba(2,6,23,0.12);
    z-index: 13000;
    overflow: auto;
    max-height: 320px;
    padding: 6px;
    box-sizing: border-box;
  }

  /* Slightly larger tappable suggestion items on mobile */
  .search-suggestion-item {
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 15px;
  }
  .search-suggestion-item .search-suggestion-meta {
    font-size: 0.9rem;
  }

  /* Ensure suggestions hidden until needed */
  .search-suggestions[style*="display:none"] {
    display: none !important;
  }
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
  <?php if (!isset($_GET['embed'])): ?>
   
  <?php endif; ?>

    <div id="map-wrapper" style="display: flex; justify-content: center;">
        <div id="map-container">
            <!-- Search Bar -->
            <div class="search-filter-bar" style="margin-top:0 !important; margin-bottom:0 !important;">
                <div class="search-input-wrapper">
                    <input class="search-input" id="mapSearchInput" type="text" placeholder="Tap to search">
                    <span class="search-input-icon"><i class="fas fa-search"></i></span>
                    <!-- Search suggestions dropdown (moved inside wrapper so it aligns with the input) -->
                    <div id="searchSuggestions" class="search-suggestions" role="listbox" aria-label="Search suggestions" style="display:none;"></div>
                </div>
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
                            <div class="section-buttons" id="sectionButtons">
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
                <!-- Floor Control Button (added for client map) -->
                <div class="layer-control floor-control" style="margin-top:40px;">
                    <button class="layer-control-btn" id="floorControlBtn">
                        <i class="fas fa-building"></i>
                        <span>Select Floor</span>
                    </button>
                    <div class="layer-control-content" id="floorControlContent">
                        <div class="layer-section">
                            <h4>Floors</h4>
                            <div class="section-buttons" id="floorButtons">
                                <button class="section-btn active" data-floor="1">First Floor</button>
                                <button class="section-btn" data-floor="2">Second Floor</button>
                                <button class="section-btn" data-floor="3">Third Floor</button>
                                <button class="section-btn" data-floor="4">Old Cemetery</button>
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
        </div>
    </div>

    <!-- Custom Popup (view-only, no admin buttons) -->
    <div class="popup-overlay" id="popupOverlay"></div>
    <div class="custom-popup" id="customPopup">
        <div id="popupContent">
            <!-- Content will be dynamically inserted here -->
        </div>
        <!-- Admin popup-buttons removed for client view-only -->
    </div>
    <!-- Search Error Popup (replaced for better layout / responsiveness) -->
    <div class="popup-overlay" id="searchErrorOverlay" aria-hidden="true"></div>

    <div class="custom-popup" id="searchErrorPopup" role="dialog" aria-modal="true" aria-labelledby="searchErrorTitle" aria-describedby="searchErrorContent">
        <div id="searchErrorContent">
            <div id="searchErrorTitle" style="display:none;">No Match Found</div>
            No Niche ID or Name on the database, please check your entry and try again
        </div>
        <div style="width:100%;text-align:center;">
            <button class="popup-button cancel-button search-error-btn" id="searchErrorCloseBtn" aria-label="Close search error">Close</button>
        </div>
    </div>

  <?php if (!isset($_GET['embed'])): ?>
    
  <?php endif; ?>
   <script src="../js/leaflet.js"></script>
   <script src="../js/L.Control.Layers.Tree.min.js"></script>
   <script src="../js/leaflet.rotatedMarker.js"></script>
   <script src="../js/leaflet.pattern.js"></script>
   <script src="../js/Autolinker.min.js"></script>
   <script src="../js/rbush.min.js"></script>
   <script src="../js/labelgun.min.js"></script>
   <script src="../js/labels.js"></script>
   <script src="../data/OldMap/border_1.js"></script>
   <script src="../data/border_1.js"></script>
   <script src="../data/floor1.js"></script>
   <script src="../data/floor1_2.js"></script>
   <script src="../data/floor1_3.js"></script>
   <script src="../data/floor1_4.js"></script>
   <script src="../data/floor2.js"></script>
   <script src="../data/floor2_2.js"></script>
   <script src="../data/floor2_3.js"></script>
   <script src="../data/floor2_4.js"></script>
   <script src="../data/floor3.js"></script>
   <script src="../data/floor3_2.js"></script>
   <script src="../data/floor3_3.js"></script>
   <script src="../data/floor3_4.js"></script>
   <script src="../data/oldmap/floor1.js"></script>
   <script src="../data/oldmap/floor1_4.js"></script>
   <!-- Fallback for case-sensitive hosts: try alternate folder casing -->
   <script src="../data/OldMap/floor1.js" onerror="this.remove();"></script>
   <script src="../data/OldMap/floor1_4.js" onerror="this.remove();"></script>
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
        map.setZoom(minZoom); // set initial zoom level to fit bounds

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
                        } else {
                            // Vacant niche popup
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
                        }
                        document.getElementById('popupContent').innerHTML = popupContent;
                        document.getElementById('popupOverlay').classList.add('active');
                        document.getElementById('customPopup').classList.add('active');
                        // Add navigation event listeners if needed
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

        // Add similar for Floor2, Floor3, OldMap
        var layer_Floor2 = new L.geoJson(json_Floor2, { attribution: '', interactive: true, dataVar: 'json_Floor2', layerName: 'layer_Floor2', pane: 'pane_Floor1', onEachFeature: pop_Floor1, style: style_Floor1_0 });
        var layer_Floor2_2 = new L.geoJson(json_Floor2_2, { attribution: '', interactive: true, dataVar: 'json_Floor2_2', layerName: 'layer_Floor2_2', pane: 'pane_Floor1', onEachFeature: pop_Floor1, style: style_Floor1_0 });
        var layer_Floor2_3 = new L.geoJson(json_Floor2_3, { attribution: '', interactive: true, dataVar: 'json_Floor2_3', layerName: 'layer_Floor2_3', pane: 'pane_Floor1', onEachFeature: pop_Floor1, style: style_Floor1_0 });
        var layer_Floor2_4 = new L.geoJson(json_Floor2_4, { attribution: '', interactive: true, dataVar: 'json_Floor2_4', layerName: 'layer_Floor2_4', pane: 'pane_Floor1', onEachFeature: pop_Floor1, style: style_Floor1_0 });

        var layer_Floor3 = new L.geoJson(json_Floor3, { attribution: '', interactive: true, dataVar: 'json_Floor3', layerName: 'layer_Floor3', pane: 'pane_Floor1', onEachFeature: pop_Floor1, style: style_Floor1_0 });
        var layer_Floor3_2 = new L.geoJson(json_Floor3_2, { attribution: '', interactive: true, dataVar: 'json_Floor3_2', layerName: 'layer_Floor3_2', pane: 'pane_Floor1', onEachFeature: pop_Floor1, style: style_Floor1_0 });
        var layer_Floor3_3 = new L.geoJson(json_Floor3_3, { attribution: '', interactive: true, dataVar: 'json_Floor3_3', layerName: 'layer_Floor3_3', pane: 'pane_Floor1', onEachFeature: pop_Floor1, style: style_Floor1_0 });
        var layer_Floor3_4 = new L.geoJson(json_Floor3_4, { attribution: '', interactive: true, dataVar: 'json_Floor3_4', layerName: 'layer_Floor3_4', pane: 'pane_Floor1', onEachFeature: pop_Floor1, style: style_Floor1_0 });

        // --- Robust OldMap JSON handling (fixes case-sensitive hosting issues) ---
        (function() {
            function getJsonGlobal(name) {
                var variants = [
                    name,
                    name.replace('oldmap','oldMap'),
                    name.replace('oldmap','OldMap'),
                    name.replace('oldmap','Oldmap'),
                    name.replace(/([A-Z])/g, '_$1').toLowerCase()
                ];
                for (var i = 0; i < variants.length; i++) {
                    var v = variants[i];
                    try {
                        if (typeof window[v] !== 'undefined') return window[v];
                    } catch (e) {}
                }
                var lname = name.toLowerCase();
                for (var key in window) {
                    try {
                        if (key.toLowerCase() === lname) return window[key];
                    } catch (e) {}
                }
                return undefined;
            }

            function createOldMapLayers() {
                var json_oldmap_floor1_data = getJsonGlobal('json_oldmap_floor1');
                var json_oldmap_floor1_4_data = getJsonGlobal('json_oldmap_floor1_4');

                if (json_oldmap_floor1_data) {
                    try {
                        window.layer_OldMap_1 = new L.geoJson(json_oldmap_floor1_data, {
                            attribution: '',
                            interactive: true,
                            dataVar: 'json_oldmap_floor1',
                            layerName: 'layer_OldMap_1',
                            pane: 'pane_Floor1',
                            onEachFeature: pop_Floor1,
                            style: style_Floor1_0,
                        });
                        // also expose plain global
                        layer_OldMap_1 = window.layer_OldMap_1;
                    } catch (e) {
                        window.layer_OldMap_1 = null;
                        layer_OldMap_1 = null;
                    }
                } else {
                    window.layer_OldMap_1 = window.layer_OldMap_1 || null;
                    layer_OldMap_1 = layer_OldMap_1 || null;
                }

                if (json_oldmap_floor1_4_data) {
                    try {
                        window.layer_OldMap_4 = new L.geoJson(json_oldmap_floor1_4_data, {
                            attribution: '',
                            interactive: true,
                            dataVar: 'json_oldmap_floor1_4',
                            layerName: 'layer_OldMap_4',
                            pane: 'pane_Floor1',
                            onEachFeature: pop_Floor1,
                            style: style_Floor1_0,
                        });
                        layer_OldMap_4 = window.layer_OldMap_4;
                    } catch (e) {
                        window.layer_OldMap_4 = null;
                        layer_OldMap_4 = null;
                    }
                } else {
                    window.layer_OldMap_4 = window.layer_OldMap_4 || null;
                    layer_OldMap_4 = layer_OldMap_4 || null;
                }

                if (typeof bounds_group !== 'undefined') {
                    if (window.layer_OldMap_1 && !bounds_group.hasLayer(window.layer_OldMap_1)) bounds_group.addLayer(window.layer_OldMap_1);
                    if (window.layer_OldMap_4 && !bounds_group.hasLayer(window.layer_OldMap_4)) bounds_group.addLayer(window.layer_OldMap_4);
                }
            }

            function tryLoadVariantsIfNeeded() {
                if (window.layer_OldMap_1 || window.layer_OldMap_4) return;

                var candidates = [
                    '../data/oldmap/floor1.js',
                    '../data/OldMap/floor1.js',
                    '../data/oldMap/floor1.js',
                    '../data/oldmap/Floor1.js',
                    '../data/oldmap/floor1_4.js',
                    '../data/OldMap/floor1_4.js',
                    '../data/oldMap/floor1_4.js',
                    '../data/oldmap/Floor1_4.js'
                ];
                var tried = {};
                var i = 0;

                function loadNext() {
                    if (i >= candidates.length) {
                        createOldMapLayers();
                        return;
                    }
                    var url = candidates[i++];
                    if (tried[url]) return loadNext();
                    tried[url] = true;

                    // Prefer a quick HEAD check, but still inject script if check fails
                    fetch(url, { method: 'HEAD' }).then(function(resp) {
                        if (resp.ok) {
                            var s = document.createElement('script');
                            s.src = url;
                            s.async = false;
                            s.onload = function() {
                                try { createOldMapLayers(); } catch (e) {}
                                if (!window.layer_OldMap_1 && !window.layer_OldMap_4) setTimeout(loadNext, 60);
                            };
                            s.onerror = function() { setTimeout(loadNext, 60); };
                            document.head.appendChild(s);
                        } else {
                            setTimeout(loadNext, 10);
                        }
                    }).catch(function() {
                        var s2 = document.createElement('script');
                        s2.src = url;
                        s2.async = false;
                        s2.onload = function() { createOldMapLayers(); if (!window.layer_OldMap_1 && !window.layer_OldMap_4) setTimeout(loadNext, 60); };
                        s2.onerror = function() { setTimeout(loadNext, 60); };
                        document.head.appendChild(s2);
                    });
                }

                loadNext();
            }

            // initial attempt then fallback
            setTimeout(function() {
                createOldMapLayers();
                tryLoadVariantsIfNeeded();
            }, 40);
        })();

        // --- Floor Control Logic ---
        var currentFloor = 1; // 1: First, 2: Second, 3: Third, 4: Old
        function showFloor(floor) {
            // Remove all section layers for all floors (only include existing layers)
            var allLayers = [];
            [layer_Floor1, layer_Floor1_2, layer_Floor1_3, layer_Floor1_4,
             layer_Floor2, layer_Floor2_2, layer_Floor2_3, layer_Floor2_4,
             layer_Floor3, layer_Floor3_2, layer_Floor3_3, layer_Floor3_4].forEach(function(l){ if (l) allLayers.push(l); });
            if (window.layer_OldMap_1) allLayers.push(window.layer_OldMap_1);
            if (window.layer_OldMap_4) allLayers.push(window.layer_OldMap_4);

            allLayers.forEach(function(l) {
                if (map.hasLayer(l)) map.removeLayer(l);
            });

            // --- Border layer visibility logic ---
            if (floor == 4 || floor == "4") {
                // Remove border for Old Cemetery
                if (map.hasLayer(layer_border_1)) {
                    map.removeLayer(layer_border_1);
                }
                // Set larger max bounds for Old Cemetery if OldMap layers exist
                if (window.layer_OldMap_1 && window.layer_OldMap_4) {
                    var oldMapBounds = window.layer_OldMap_1.getBounds().extend(window.layer_OldMap_4.getBounds());
                    var oldMapPaddedBounds = expandBounds(oldMapBounds, 2.0); // 100% larger for more panning
                    map.setMaxBounds(oldMapPaddedBounds);
                } else {
                    // fallback: keep default padded bounds if OldMap data missing
                    map.setMaxBounds(paddedBounds);
                }
            } else {
                // Add border for other floors
                if (!map.hasLayer(layer_border_1)) {
                    map.addLayer(layer_border_1);
                }
                // Reset to default padded bounds for other floors
                map.setMaxBounds(paddedBounds);
            }

            // Show section buttons for selected floor
            var sectionButtons = document.getElementById('sectionButtons');
            sectionButtons.innerHTML = '';
            if (floor == 1 || floor == "1") {
                currentFloor = 1;
                sectionButtons.innerHTML = `
                    <button class="section-btn active" data-section="1">Section 1</button>
                    <button class="section-btn" data-section="2">Section 2</button>
                    <button class="section-btn" data-section="3">Section 3</button>
                    <button class="section-btn" data-section="4">Section 4</button>
                    <button class="section-btn show-all-btn" data-section="all">
                        <i class="fas fa-th-large"></i>
                        Show All Sections
                    </button>
                `;
                map.addLayer(layer_Floor1);
                map.addLayer(layer_Floor1_2);
                map.addLayer(layer_Floor1_3);
                map.addLayer(layer_Floor1_4);
            } else if (floor == 2 || floor == "2") {
                currentFloor = 2;
                sectionButtons.innerHTML = `
                    <button class="section-btn active" data-section="1">Section 1</button>
                    <button class="section-btn" data-section="2">Section 2</button>
                    <button class="section-btn" data-section="3">Section 3</button>
                    <button class="section-btn" data-section="4">Section 4</button>
                    <button class="section-btn show-all-btn" data-section="all">
                        <i class="fas fa-th-large"></i>
                        Show All Sections
                    </button>
                `;
                map.addLayer(layer_Floor2);
                map.addLayer(layer_Floor2_2);
                map.addLayer(layer_Floor2_3);
                map.addLayer(layer_Floor2_4);
            } else if (floor == 3 || floor == "3") {
                currentFloor = 3;
                sectionButtons.innerHTML = `
                    <button class="section-btn active" data-section="1">Section 1</button>
                    <button class="section-btn" data-section="2">Section 2</button>
                    <button class="section-btn" data-section="3">Section 3</button>
                    <button class="section-btn" data-section="4">Section 4</button>
                    <button class="section-btn show-all-btn" data-section="all">
                        <i class="fas fa-th-large"></i>
                        Show All Sections
                    </button>
                `;
                map.addLayer(layer_Floor3);
                map.addLayer(layer_Floor3_2);
                map.addLayer(layer_Floor3_3);
                map.addLayer(layer_Floor3_4);
            } else if (floor == 4 || floor == "4") {
                currentFloor = 4;
                sectionButtons.innerHTML = `
                    <button class="section-btn active" data-section="1">Section 1</button>
                    <button class="section-btn" data-section="4">Section 4</button>
                    <button class="section-btn show-all-btn" data-section="all">
                        <i class="fas fa-th-large"></i>
                        Show All Sections
                    </button>
                `;
                if (window.layer_OldMap_1) map.addLayer(window.layer_OldMap_1);
                if (window.layer_OldMap_4) map.addLayer(window.layer_OldMap_4);
            }
            // Re-bind section button events
            bindSectionButtonEvents();
        }

        function showSection(section) {
            // Remove all section layers for current floor
            var layers = [];
            if (currentFloor == 1) layers = [layer_Floor1, layer_Floor1_2, layer_Floor1_3, layer_Floor1_4];
            else if (currentFloor == 2) layers = [layer_Floor2, layer_Floor2_2, layer_Floor2_3, layer_Floor2_4];
            else if (currentFloor == 3) layers = [layer_Floor3, layer_Floor3_2, layer_Floor3_3, layer_Floor3_4];
            else if (currentFloor == 4) layers = [layer_OldMap_1, layer_OldMap_4];
            layers.forEach(function(l) { if (map.hasLayer(l)) map.removeLayer(l); });
            // Add selected section(s)
            if (section === 'all') {
                layers.forEach(function(l) { map.addLayer(l); });
            } else {
                var idx = Number(section) - 1;
                if (layers[idx]) map.addLayer(layers[idx]);
            }
        }

        function bindSectionButtonEvents() {
            var sectionBtns = document.querySelectorAll('#sectionButtons .section-btn');
            sectionBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    sectionBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    var section = btn.getAttribute('data-section');
                    showSection(section === 'all' ? 'all' : section);
                });
            });
        }

        // Initial floor and section setup
        document.addEventListener("DOMContentLoaded", function() {
            showFloor(1); // Default to first floor
            // Floor control toggle
            var floorControl = document.querySelector('.floor-control');
            var floorControlBtn = document.getElementById('floorControlBtn');
            var floorControlContent = document.getElementById('floorControlContent');
            floorControlBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                floorControl.classList.toggle('active');
                // Close Layers control if open
                var layersControl = document.querySelector('.layer-control:not(.floor-control)');
                if (layersControl) layersControl.classList.remove('active');
            });
            document.addEventListener('click', function(e) {
                if (!floorControl.contains(e.target)) {
                    floorControl.classList.remove('active');
                }
            });
            // Floor button click handlers
            var floorBtns = document.querySelectorAll('#floorButtons .section-btn');
            floorBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    floorBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    var floor = btn.getAttribute('data-floor');
                    showFloor(floor);
                    floorControl.classList.remove('active');
                });
            });

            // --- Layers control toggle logic ---
            var layersControl = document.querySelector('.layer-control:not(.floor-control)');
            var layersControlBtn = layersControl.querySelector('.layer-control-btn');
            layersControlBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                layersControl.classList.toggle('active');
                // Close Floor control if open
                if (floorControl) floorControl.classList.remove('active');
            });
            document.addEventListener('click', function(e) {
                if (!layersControl.contains(e.target)) {
                    layersControl.classList.remove('active');
                }
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

// Remove all admin popup button event listeners for client view
// Only keep close popup on overlay click
document.getElementById('popupOverlay').addEventListener('click', function() {
    document.getElementById('popupOverlay').classList.remove('active');
    document.getElementById('customPopup').classList.remove('active');
});
        </script>

<script>
window.focusNiche = function(nicheID) {
  // Your map logic here (same as in your message handler)
  var found = null;
  var foundSection = null;
  var sectionLayers = [
    {layer: window.layer_Floor1, section: 1},
    {layer: window.layer_Floor1_2, section: 2},
    {layer: window.layer_Floor1_3, section: 3},
    {layer: window.layer_Floor1_4, section: 4}
  ];

  sectionLayers.forEach(function(sectionObj) {
    sectionObj.layer.eachLayer(function(layer) {
      if (
        layer.feature &&
        layer.feature.properties &&
        layer.feature.properties['nicheID'] === nicheID
      ) {
        found = layer;
        foundSection = sectionObj.section;
      }
    });
  });

  if (found && foundSection) {
    showSection(foundSection);
    found.fire('click');
    if (found.setStyle) {
      found.setStyle({
        fillColor: '#ffff00',
        fillOpacity: 1,
        color: '#ffff00',
        weight: 3
      });
    //   setTimeout(function() {
    //     var parentLayer = found._eventParents ? Object.values(found._eventParents)[0] : null;
    //     if (parentLayer && typeof parentLayer.resetStyle === 'function') {
    //       parentLayer.resetStyle(found);
    //     }
    //   }, 2000);
    }
  } else {
    alert('Niche not found: ' + nicheID);
  }
};
</script>
<script>
// --- SEARCH FUNCTIONALITY FOR CLIENT SIDE ---
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('mapSearchInput');
    var searchErrorPopup = document.getElementById('searchErrorPopup');
    var searchErrorOverlay = document.getElementById('searchErrorOverlay');
    var searchErrorCloseBtn = document.getElementById('searchErrorCloseBtn');

   // --- Autocomplete / Suggestions ---
   var suggestionsBox = document.getElementById('searchSuggestions');
   var searchIndex = []; // { nicheID, name, nameLower, display }
   function buildSearchIndex() {
       searchIndex = [];
       for (var nicheID in deceasedData) {
           var arr = deceasedData[nicheID];
           if (!arr) {
               searchIndex.push({ nicheID: nicheID, name: '', nameLower: '', display: nicheID });
               continue;
           }
           var list = Array.isArray(arr) ? arr : [arr];
           list.forEach(function(d) {
               var firstName = d.firstName || '';
               var middleName = d.middleName || '';
               var lastName = d.lastName || '';
               var suffix = d.suffix || '';
               var middleInitial = middleName ? (middleName.trim().charAt(0).toUpperCase() + '.') : '';
               var fullName = firstName;
               if (middleInitial) fullName += ' ' + middleInitial;
               if (lastName) fullName += ' ' + lastName;
               if (suffix) fullName += ', ' + suffix;
               fullName = fullName.trim();
               var display = fullName ? (fullName + ' — ' + nicheID) : nicheID;
               searchIndex.push({ nicheID: nicheID, name: fullName, nameLower: fullName.toLowerCase(), display: display });
           });
       }
   }
   buildSearchIndex();

   var activeIndex = -1;
   function renderSuggestions(results) {
       suggestionsBox.innerHTML = '';
       if (!results || results.length === 0) {
           suggestionsBox.style.display = 'none';
           return;
       }
       results.forEach(function(r, i) {
           var div = document.createElement('div');
           div.className = 'search-suggestion-item' + (i === activeIndex ? ' active' : '');
           div.setAttribute('role','option');
           div.setAttribute('data-niche', r.nicheID);
           div.innerHTML = '<div class="search-suggestion-name">' + escapeHtml(r.display) + '</div>' +
                           '<div class="search-suggestion-meta">' + (r.name ? 'Name' : 'Niche') + '</div>';
           div.addEventListener('click', function() {
               chooseSuggestion(r);
           });
           suggestionsBox.appendChild(div);
       });
       suggestionsBox.style.display = 'block';
   }

   function escapeHtml(s) { return String(s).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; }); }

   function findMatches(q) {
       if (!q) return [];
       q = q.toLowerCase();
       var out = [];
       for (var i = 0; i < searchIndex.length; i++) {
           var it = searchIndex[i];
           if (it.nameLower && it.nameLower.indexOf(q) !== -1) {
               out.push(it);
           } else if (it.nicheID && String(it.nicheID).toLowerCase().indexOf(q) !== -1) {
               out.push(it);
           }
           if (out.length >= 8) break;
       }
       return out;
   }

   function chooseSuggestion(item) {
       clearSuggestions();
       searchInput.value = item.name || item.nicheID;
       goToNiche(item.nicheID);
   }

   function clearSuggestions() {
       suggestionsBox.style.display = 'none';
       suggestionsBox.innerHTML = '';
       activeIndex = -1;
   }

   searchInput.addEventListener('input', function(e) {
       var q = searchInput.value.trim();
       if (!q) { clearSuggestions(); return; }
       var results = findMatches(q);
       renderSuggestions(results);
   });

   searchInput.addEventListener('keydown', function(e) {
       var items = suggestionsBox.querySelectorAll('.search-suggestion-item');
       if (e.key === 'ArrowDown') {
           e.preventDefault();
           if (items.length === 0) return;
           activeIndex = (activeIndex + 1) % items.length;
           updateActive(items);
       } else if (e.key === 'ArrowUp') {
           e.preventDefault();
           if (items.length === 0) return;
           activeIndex = (activeIndex - 1 + items.length) % items.length;
           updateActive(items);
       } else if (e.key === 'Enter') {
           if (items.length > 0 && activeIndex >= 0) {
               e.preventDefault();
               var niche = items[activeIndex].getAttribute('data-niche');
               chooseSuggestion({ nicheID: niche, name: '' });
               return;
           }
           // allow existing Enter search behavior to continue if no suggestion selected
       } else if (e.key === 'Escape') {
           clearSuggestions();
       }
   });

   function updateActive(items) {
       items.forEach(function(it, idx) { it.classList.toggle('active', idx === activeIndex); });
       if (items[activeIndex]) {
           // ensure visible scroll
           var el = items[activeIndex];
           el.scrollIntoView({ block: 'nearest' });
       }
   }

   document.addEventListener('click', function(ev) {
       if (!ev.target.closest('#searchSuggestions') && ev.target !== searchInput) {
           clearSuggestions();
       }
   });

   // --- Navigate map to niche (search across all floors/sections) ---
   function goToNiche(nicheID) {
       // Define mapping of layers to floor/section (only include layer vars that exist)
       var sectionList = [];
       if (window.layer_Floor1) sectionList.push({ layer: window.layer_Floor1, floor:1, section:1 });
       if (window.layer_Floor1_2) sectionList.push({ layer: window.layer_Floor1_2, floor:1, section:2 });
       if (window.layer_Floor1_3) sectionList.push({ layer: window.layer_Floor1_3, floor:1, section:3 });
       if (window.layer_Floor1_4) sectionList.push({ layer: window.layer_Floor1_4, floor:1, section:4 });
       if (window.layer_Floor2) sectionList.push({ layer: window.layer_Floor2, floor:2, section:1 });
       if (window.layer_Floor2_2) sectionList.push({ layer: window.layer_Floor2_2, floor:2, section:2 });
       if (window.layer_Floor2_3) sectionList.push({ layer: window.layer_Floor2_3, floor:2, section:3 });
       if (window.layer_Floor2_4) sectionList.push({ layer: window.layer_Floor2_4, floor:2, section:4 });
       if (window.layer_Floor3) sectionList.push({ layer: window.layer_Floor3, floor:3, section:1 });
       if (window.layer_Floor3_2) sectionList.push({ layer: window.layer_Floor3_2, floor:3, section:2 });
       if (window.layer_Floor3_3) sectionList.push({ layer: window.layer_Floor3_3, floor:3, section:3 });
       if (window.layer_Floor3_4) sectionList.push({ layer: window.layer_Floor3_4, floor:3, section:4 });
       if (window.layer_OldMap_1) sectionList.push({ layer: window.layer_OldMap_1, floor:4, section:1 });
       if (window.layer_OldMap_4) sectionList.push({ layer: window.layer_OldMap_4, floor:4, section:4 });

           var found = null;
           var foundFloor = null;
           var foundSection = null;
           for (var i = 0; i < sectionList.length; i++) {
               var s = sectionList[i];
               if (!s.layer) continue;
               s.layer.eachLayer(function(layer) {
                   try {
                       if (layer.feature && layer.feature.properties && String(layer.feature.properties['nicheID']) === String(nicheID)) {
                           found = layer;
                           foundFloor = s.floor;
                           foundSection = s.section;
                       }
                   } catch (err) {}
               });
               if (found) break;
           }
           if (!found) {
               // fallback to visible-layer search (shouldn't usually happen)
               alert('Niche not found: ' + nicheID);
               return;
           }
           // Ensure correct floor & section visible, then center and open
           showFloor(foundFloor);
           // small delay to let layers be added
           setTimeout(function() {
               // show only the section
               showSection(String(foundSection));
               // center and open
               var center;
               if (found.getBounds) center = found.getBounds().getCenter();
               else if (found.getLatLng) center = found.getLatLng();
               if (center) map.setView(center, Math.max(map.getZoom(), map.getMaxZoom() - 1), { animate: true });
               // highlight then open
               highlightFeature({ target: found });
               setTimeout(function() { found.fire('click'); }, 250);
           }, 250);
       }

    // Close handler helper (placed inside DOMContentLoaded so elements exist)
    function closeSearchError() {
        if (searchErrorPopup) {
            searchErrorPopup.classList.remove('active');
            searchErrorPopup.style.display = 'none';
        }
        if (searchErrorOverlay) {
            searchErrorOverlay.classList.remove('active');
            searchErrorOverlay.style.display = 'none';
        }
        if (searchInput) {
            searchInput.style.borderColor = '';
            searchInput.focus();
        }
    }

    if (searchErrorCloseBtn) {
        searchErrorCloseBtn.addEventListener('click', function() {
            closeSearchError();
        });
    }
    if (searchErrorOverlay) {
        searchErrorOverlay.addEventListener('click', function() {
            closeSearchError();
        });
    }
});
</script>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
<style>
    /* ...existing code... */
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
      font-family: 'Poppins', cursive, 'Times New Roman', serif;
      font-size: 1.5rem;
      font-weight: 700;
      color: #222;
      margin-bottom: 8px;
      letter-spacing: 1px;
    }
    .plaque-dates {
      font-family: 'Poppins', cursive, 'Times New Roman', serif;
      font-size: 1.15rem;
      color: #222;
      margin-bottom: 8px;
    }
    .plaque-verse {
      font-family: 'Poppins', cursive, 'Times New Roman', serif;
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
    #searchErrorOverlay {
      display: none; /* toggled by JS */
        position: fixed;
        inset: 0;

        background: rgba(17,24,39,0.45);

        z-index: 12050;
        align-items: center;
        justify-content: center;
      }
      #searchErrorPopup {
        display: none; /* toggled by JS */
        z-index: 12060;
        max-width: 420px;
        width: calc(100% - 40px);
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 12px 40px rgba(2,6,23,0.16);
        padding: 22px;
        box-sizing: border-box;
        margin: 0 16px;
        flex-direction: column;
        align-items: center;
        justify-content: center;
      }
      #searchErrorContent {
        color: #fb7185; /* soft red */
        text-align: center;
        font-weight: 600;
        line-height: 1.35;
        font-size: 1rem;
        margin-bottom: 18px;
        white-space: normal;
      }
      .search-error-btn {
        background: #ef4444; /* red */
        color: #fff;
        border: none;
        padding: 10px 18px;
        border-radius: 10px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 6px 20px rgba(239,68,68,0.12);
      }
      .search-error-btn:active { transform: translateY(1px); }

      /* make button full width on small screens for easier tapping */
      @media (max-width:420px) {
        #searchErrorPopup { padding:16px; max-width: 92%; }
        .search-error-btn { width:100%; padding:12px; font-size:1rem; border-radius:10px; }
        #searchErrorContent { font-size:0.98rem; }
      }
    </style>
</body>
</html>