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

        var layer_OldMap_1 = new L.geoJson(json_oldmap_floor1, { attribution: '', interactive: true, dataVar: 'json_oldmap_floor1', layerName: 'layer_OldMap_1', pane: 'pane_Floor1', onEachFeature: pop_Floor1, style: style_Floor1_0 });
        var layer_OldMap_4 = new L.geoJson(json_oldmap_floor1_4, { attribution: '', interactive: true, dataVar: 'json_oldmap_floor1_4', layerName: 'layer_OldMap_4', pane: 'pane_Floor1', onEachFeature: pop_Floor1, style: style_Floor1_0 });

        // --- Floor Control Logic ---
        var currentFloor = 1; // 1: First, 2: Second, 3: Third, 4: Old
        function showFloor(floor) {
            // Remove all section layers for all floors
            [layer_Floor1, layer_Floor1_2, layer_Floor1_3, layer_Floor1_4,
             layer_Floor2, layer_Floor2_2, layer_Floor2_3, layer_Floor2_4,
             layer_Floor3, layer_Floor3_2, layer_Floor3_3, layer_Floor3_4,
             layer_OldMap_1, layer_OldMap_4].forEach(function(l) {
                if (map.hasLayer(l)) map.removeLayer(l);
            });

            // --- Border layer visibility logic ---
            if (floor == 4 || floor == "4") {
                // Remove border for Old Cemetery
                if (map.hasLayer(layer_border_1)) {
                    map.removeLayer(layer_border_1);
                }
                // Set larger max bounds for Old Cemetery
                var oldMapBounds = layer_OldMap_1.getBounds().extend(layer_OldMap_4.getBounds());
                var oldMapPaddedBounds = expandBounds(oldMapBounds, 2.0); // 100% larger for more panning
                map.setMaxBounds(oldMapPaddedBounds);
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
                map.addLayer(layer_OldMap_1);
                map.addLayer(layer_OldMap_4);
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
// --- SEARCH FUNCTIONALITY ---
// Same logic as OldMap.php, adapted for client view
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('mapSearchInput');
    var searchErrorPopup = document.getElementById('searchErrorPopup');
    var searchErrorOverlay = document.getElementById('searchErrorOverlay');
    var searchErrorCloseBtn = document.getElementById('searchErrorCloseBtn');
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
            // Only search visible layers (sections for current floor)
            if (map.hasLayer(layer_Floor1)) visibleLayers.push(layer_Floor1);
            if (map.hasLayer(layer_Floor1_2)) visibleLayers.push(layer_Floor1_2);
            if (map.hasLayer(layer_Floor1_3)) visibleLayers.push(layer_Floor1_3);
            if (map.hasLayer(layer_Floor1_4)) visibleLayers.push(layer_Floor1_4);
            if (map.hasLayer(layer_Floor2)) visibleLayers.push(layer_Floor2);
            if (map.hasLayer(layer_Floor2_2)) visibleLayers.push(layer_Floor2_2);
            if (map.hasLayer(layer_Floor2_3)) visibleLayers.push(layer_Floor2_3);
            if (map.hasLayer(layer_Floor2_4)) visibleLayers.push(layer_Floor2_4);
            if (map.hasLayer(layer_Floor3)) visibleLayers.push(layer_Floor3);
            if (map.hasLayer(layer_Floor3_2)) visibleLayers.push(layer_Floor3_2);
            if (map.hasLayer(layer_Floor3_3)) visibleLayers.push(layer_Floor3_3);
            if (map.hasLayer(layer_Floor3_4)) visibleLayers.push(layer_Floor3_4);
            if (map.hasLayer(layer_OldMap_1)) visibleLayers.push(layer_OldMap_1);
            if (map.hasLayer(layer_OldMap_4)) visibleLayers.push(layer_OldMap_4);

            visibleLayers.some(function(sectionLayer) {
                var matchLayer = null;
                sectionLayer.eachLayer(function(layer) {
                    var nicheID = layer.feature && layer.feature.properties['nicheID'];
                    var deceased = deceasedData[nicheID];
                    if (nicheID && nicheID.toLowerCase() === query) {
                        matchLayer = layer;
                        return;
                    }
                    if (deceased && normalizeName(deceased).includes(query)) {
                        matchLayer = layer;
                        return;
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
    /* ...existing code... */
   </style>
</body>
</html>

