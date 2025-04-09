<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="initial-scale=1,user-scalable=no,maximum-scale=1,width=device-width">
  <meta name="mobile-web-app-capable" content="yes">
  <title>RestEase Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Mapping.css">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="stylesheet" href="../css/leaflet.css">
    <link rel="stylesheet" href="../css/L.Control.Layers.Tree.css">
    <link rel="stylesheet" href="../css/L.Control.Locate.min.css">
    <link rel="stylesheet" href="../css/qgis2web.css">
    <link rel="stylesheet" href="../css/fontawesome-all.min.css">
    <link rel="stylesheet" href="../css/leaflet-measure.css">
</head>
<style>
   #map {
            position: absolute;
            top: 80px; /* Adjusted to make space for controls */
            right: 0;
            bottom: 0;
            left: var(--sidebar-width);
            width: auto;
            height: calc(100vh - 80px);
        }
        .map-controls {
            position: absolute;
            top: 20px;
            left: calc(var(--sidebar-width) + 20px);
            right: 20px;
            display: flex;
            gap: 20px;
            z-index: 1000;
        }
        .search-container {
            flex: 1;
            max-width: 400px;
            position: relative;
        }
        .search-container input {
            width: 100%;
            padding: 12px 20px;
            padding-left: 45px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .search-container input:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 2px 8px rgba(74,144,226,0.1);
        }
        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
        .filter-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-btn.vacant {
            background-color: #a6e788;
            color: #1a1a1a;
        }
        .filter-btn.reserved {
            background-color: #eedf7a;
            color: #1a1a1a;
        }
        .filter-btn.sold {
            background-color: #b0ddfb;
            color: #1a1a1a;
        }
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .filter-btn.active {
            box-shadow: 0 0 0 2px #4a90e2;
        }
        .status-legend {
            position: fixed;
            bottom: 20px;
            left: calc(var(--sidebar-width) + 20px);
            background: white;
            padding: 15px;
            border-radius: 8px;
            font-size: 12px;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .floor-control {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            font-size: 12px;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .floor-btn {
            margin: 5px;
            padding: 8px 15px;
            cursor: pointer;
            border: none;
            border-radius: 6px;
            background-color: #f0f0f0;
            transition: all 0.3s ease;
        }
        .floor-btn:hover {
            background-color: #e0e0e0;
        }

        /* Add Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 50px auto;
            padding: 20px 30px;
            width: 90%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            color: #333;
            margin: 0;
        }

        .modal-header h2 i {
            font-size: 16px;
            color: #666;
        }

        .close-btn {
            font-size: 20px;
            color: #666;
            cursor: pointer;
            background: none;
            border: none;
            padding: 5px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #666;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background-color: #fff;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4a90e2;
            background-color: #f8f9fa;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }

        .btn-save {
            background-color: #28a745;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* Date input styling */
        input[type="date"] {
            position: relative;
            padding-right: 30px;
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
        }
</style>
<body>
   <!-- Sidebar -->
   <aside class="sidebar">
    <div class="logo">
      <img src="../assets/RE Logo New.png" alt="RestEase Logo">
    </div>
    <nav class="nav-links">
      <a href="Dashboard.php" class="nav-item active">
        <i class="fas fa-th-large"></i>
        Dashboard
      </a>
      <a href="Analytics.php" class="nav-item">
        <i class="fas fa-chart-line"></i>
        Analytics
      </a>
      <a href="Mapping.php" class="nav-item">
        <i class="fas fa-map-marker-alt"></i>
        Mapping
      </a>
      <a href="Transaction.php" class="nav-item">
        <i class="fas fa-exchange-alt"></i>
        Transaction
      </a>
      <a href="Renewals.php" class="nav-item">
        <i class="fas fa-sync"></i>
        Renewals
      </a>
      <a href="Reports.php" class="nav-item">
        <i class="fas fa-file-alt"></i>
        Reports
      </a>
    </nav>
    <div style="margin-top: auto;">
      <a href="Settings.php" class="nav-item">
        <i class="fas fa-cog"></i>
        Settings
      </a>
      <a href="#" class="nav-item">
        <i class="fas fa-question-circle"></i>
        Help Center
      </a>
      <a href="./../login.php" class="nav-item">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Header 
    <header class="header">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Tap to search">
      </div>
      <div class="user-profile">
        <div class="notification-icon">
          <i class="fas fa-bell"></i>
          <span class="notification-badge">1</span>
        </div>
        <div class="profile-info">
          <img src="../assets/Default Image.jpg" alt="Profile" class="profile-avatar">
          <div>
            <div class="profile-name">Sybau</div>
            <div class="profile-role">Admin</div>
          </div>
        </div>
      </div>
    </header>
    <h1 style="margin-left: 230px;">Analytics</h1>
-->
<div class="map-controls">
    <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search niches..." id="searchInput">
    </div>
    <div class="filter-buttons">
        <button class="filter-btn vacant" data-status="vacant">
            <i class="fas fa-circle"></i>
            Vacant
        </button>
        <button class="filter-btn reserved" data-status="reserved">
            <i class="fas fa-circle"></i>
            Reserved
        </button>
        <button class="filter-btn sold" data-status="sold">
            <i class="fas fa-circle"></i>
            Sold
        </button>
    </div>
</div>
<div id="map">
        </div>
        <div class="status-legend">
            <strong>Status Legend</strong>
            <table>
                <tr>
                    <td style="text-align: center;"><img src="../legend/floor1_2_Vacant0.png" /></td>
                    <td>Vacant</td>
                </tr>
                <tr>
                    <td style="text-align: center;"><img src="../legend/floor1_2_Reserved1.png" /></td>
                    <td>Reserved</td>
                </tr>
                <tr>
                    <td style="text-align: center;"><img src="../legend/floor1_2_Sold2.png" /></td>
                    <td>Sold</td>
                </tr>
            </table>
        </div>
        <script src="../js/qgis2web_expressions.js"></script>
        <script src="../js/leaflet.js"></script>
        <script src="../js/L.Control.Layers.Tree.min.js"></script>
        <script src="../js/L.Control.Locate.min.js"></script>
        <script src="../js/leaflet.rotatedMarker.js"></script>
        <script src="../js/leaflet.pattern.js"></script>
        <script src="../js/leaflet-hash.js"></script>
        <script src="../js/Autolinker.min.js"></script>
        <script src="../js/rbush.min.js"></script>
        <script src="../js/labelgun.min.js"></script>
        <script src="../js/labels.js"></script>
        <script src="../js/leaflet-measure.js"></script>
        <script src="../data/border_1.js"></script>
        <script src="../data/floor1_2.js"></script>
        <script src="../data/floor2.js"></script>
        <script src="../data/floor3.js"></script>
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
            highlightLayer.openPopup();
        }
        var map = L.map('map', {
            zoomControl:false, maxZoom:28, minZoom:1
        }).fitBounds([[13.88343162555525,121.22351412372174],[13.88355164256059,121.22377206271985]]);
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
        L.control.locate({locateOptions: {maxZoom: 19}}).addTo(map);
        var measureControl = new L.Control.Measure({
            position: 'topleft',
            primaryLengthUnit: 'meters',
            secondaryLengthUnit: 'kilometers',
            primaryAreaUnit: 'sqmeters',
            secondaryAreaUnit: 'hectares'
        });
        measureControl.addTo(map);
        document.getElementsByClassName('leaflet-control-measure-toggle')[0].innerHTML = '';
        document.getElementsByClassName('leaflet-control-measure-toggle')[0].className += ' fas fa-ruler';
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
                    if (typeof layer.closePopup == 'function') {
                        layer.closePopup();
                    } else {
                        layer.eachLayer(function(feature){
                            feature.closePopup()
                        });
                    }
                },
                mouseover: highlightFeature,
            });
            var popupContent = '<table>\
                    <tr>\
                        <td colspan="2">' + (feature.properties['border'] !== null ? autolinker.link(String(feature.properties['border']).replace(/'/g, '\'').toLocaleString()) : '') + '</td>\
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
                color: 'rgba(232,113,141,1.0)',
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
        function pop_floor1_2(feature, layer) {
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
                    // Get the feature properties
                    const properties = e.target.feature.properties;
                    
                    // Fill the form with the current data
                    document.getElementById('nicheNo').value = properties.niche_no || '';
                    document.getElementById('section').value = properties.section || '';
                    document.getElementById('status').value = properties.status || 'vacant';
                    document.getElementById('occupancy').value = properties.occupancy || '';
                    
                    // Split the dname into first, middle, and last name if it exists
                    if (properties.dname) {
                        const nameParts = properties.dname.split(' ');
                        document.getElementById('firstName').value = nameParts[0] || '';
                        document.getElementById('middleName').value = nameParts[1] || '';
                        document.getElementById('lastName').value = nameParts.slice(2).join(' ') || '';
                    } else {
                        document.getElementById('firstName').value = '';
                        document.getElementById('middleName').value = '';
                        document.getElementById('lastName').value = '';
                    }
                    
                    // Set death date if it exists
                    if (properties.death_date) {
                        document.getElementById('deathDate').value = formatDateForInput(properties.death_date);
                    }
                    
                    // Show the modal
                    document.getElementById('editModal').style.display = 'block';
                }
            });
            var popupContent = '<table>\
                    <tr>\
                        <td colspan="2"><strong>id</strong><br />' + (feature.properties['id'] !== null ? autolinker.link(String(feature.properties['id']).replace(/'/g, '\'').toLocaleString()) : '') + '</td>\
                    </tr>\
                    <tr>\
                        <td colspan="2"><strong>section</strong><br />' + (feature.properties['section'] !== null ? autolinker.link(String(feature.properties['section']).replace(/'/g, '\'').toLocaleString()) : '') + '</td>\
                    </tr>\
                    <tr>\
                        <th scope="row">status</th>\
                        <td>' + (feature.properties['status'] !== null ? autolinker.link(String(feature.properties['status']).replace(/'/g, '\'').toLocaleString()) : '') + '</td>\
                    </tr>\
                    <tr>\
                        <th scope="row">dname</th>\
                        <td>' + (feature.properties['dname'] !== null ? autolinker.link(String(feature.properties['dname']).replace(/'/g, '\'').toLocaleString()) : '') + '</td>\
                    </tr>\
                    <tr>\
                        <td class="visible-with-data" id="validity" colspan="2"><strong>validity</strong><br />' + (feature.properties['validity'] !== null ? autolinker.link(String(feature.properties['validity']).replace(/'/g, '\'').toLocaleString()) : '') + '</td>\
                    </tr>\
                </table>';
            var content = removeEmptyRowsFromPopupContent(popupContent, feature);
			layer.on('popupopen', function(e) {
				addClassToPopupIfMedia(content, e.popup);
			});
			layer.bindPopup(content, { maxHeight: 400 });
        }

        function style_floor1_2_0(feature) {
            switch(String(feature.properties['status'])) {
                case 'vacant':
                    return {
                pane: 'pane_floor1_2',
                opacity: 1,
                color: 'rgba(35,35,35,1.0)',
                dashArray: '',
                lineCap: 'butt',
                lineJoin: 'miter',
                weight: 1.0, 
                fill: true,
                fillOpacity: 1,
                fillColor: 'rgba(166,231,136,1.0)',
                interactive: true,
            }
                    break;
                case 'reserved':
                    return {
                pane: 'pane_floor1_2',
                opacity: 1,
                color: 'rgba(35,35,35,1.0)',
                dashArray: '',
                lineCap: 'butt',
                lineJoin: 'miter',
                weight: 1.0, 
                fill: true,
                fillOpacity: 1,
                fillColor: 'rgba(238,223,122,1.0)',
                interactive: true,
            }
                    break;
                case 'sold':
                    return {
                pane: 'pane_floor1_2',
                opacity: 1,
                color: 'rgba(35,35,35,1.0)',
                dashArray: '',
                lineCap: 'butt',
                lineJoin: 'miter',
                weight: 1.0, 
                fill: true,
                fillOpacity: 1,
                fillColor: 'rgba(176,221,251,1.0)',
                interactive: true,
            }
                    break;
            }
        }
        map.createPane('pane_floor1_2');
        map.getPane('pane_floor1_2').style.zIndex = 402;
        map.getPane('pane_floor1_2').style['mix-blend-mode'] = 'normal';
        var layer_floor1_2 = new L.geoJson(json_floor1_2, {
            attribution: '',
            interactive: true,
            dataVar: 'json_floor1_2',
            layerName: 'layer_floor1_2',
            pane: 'pane_floor1_2',
            onEachFeature: pop_floor1_2,
            style: style_floor1_2_0,
        });
        bounds_group.addLayer(layer_floor1_2);
        map.addLayer(layer_floor1_2);

        // Add buttons for Floor 2 and Floor 3
        var floorControl = L.control({ position: 'topright' });
        floorControl.onAdd = function () {
            var div = L.DomUtil.create('div', 'floor-control');
            div.innerHTML = `
                <button id="floor2-btn" class="floor-btn">Floor 2</button>
                <button id="floor3-btn" class="floor-btn">Floor 3</button>
            `;
            return div;
        };
        floorControl.addTo(map);

        // Add event listeners for the buttons
        document.getElementById('floor2-btn').addEventListener('click', function () {
            window.location.href = 'floor2.html';
            alert('Successfully navigated to Floor 2');
        });

        document.getElementById('floor3-btn').addEventListener('click', function () {
            window.location.href = 'floor3.html';
            alert('Successfully navigated to Floor 3');
        });

        // Function to fetch niches data from the server
        function fetchNiches() {
            fetch('api/fetch_niches.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error("Error fetching niches:", data.error);
                        return;
                    }
                    populateNichesTable(data);
                })
                .catch(error => console.error("Error:", error));
        }

        // Function to populate the table with niches data
        function populateNichesTable(niches) {
            const tbody = document.querySelector('#niches-table tbody');
            tbody.innerHTML = ''; // Clear existing rows

            niches.forEach(niche => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${niche.id}</td>
                    <td>${niche.niche_no}</td>
                    <td>${niche.section || ''}</td>
                    <td>${niche.status}</td>
                    <td>${niche.occupancy || ''}</td>
                    <td>${niche.informant || ''}</td>
                    <td>${niche.dname || ''}</td>
                    <td>${niche.validity || ''}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Call fetchNiches on page load
        document.addEventListener('DOMContentLoaded', fetchNiches);

        setBounds();
        var i = 0;
        layer_floor1_2.eachLayer(function(layer) {
            var context = {
                feature: layer.feature,
                variables: {}
            };
            layer.bindTooltip((layer.feature.properties['niche_no'] !== null?String('<div style="color: #323232; font-size: 10pt; font-family: \'Open Sans\', sans-serif;">' + layer.feature.properties['niche_no']) + '</div>':''), {permanent: true, offset: [-0, -16], className: 'css_floor1_2'});
            labels.push(layer);
            totalMarkers += 1;
              layer.added = true;
              addLabel(layer, i);
              i++;
        });
        resetLabels([layer_floor1_2]);
        map.on("zoomend", function(){
            resetLabels([layer_floor1_2]);
        });
        map.on("layeradd", function(){
            resetLabels([layer_floor1_2]);
        });
        map.on("layerremove", function(){
            resetLabels([layer_floor1_2]);
        });

        // Add search and filter functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            layer_floor1_2.eachLayer(function(layer) {
                const nicheNo = layer.feature.properties.niche_no?.toLowerCase() || '';
                const section = layer.feature.properties.section?.toLowerCase() || '';
                const dname = layer.feature.properties.dname?.toLowerCase() || '';
                
                if (nicheNo.includes(searchTerm) || 
                    section.includes(searchTerm) || 
                    dname.includes(searchTerm)) {
                    layer.setStyle({opacity: 1});
                } else {
                    layer.setStyle({opacity: 0.3});
                }
            });
        });

        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                const status = this.dataset.status;
                this.classList.toggle('active');
                
                layer_floor1_2.eachLayer(function(layer) {
                    const layerStatus = layer.feature.properties.status?.toLowerCase();
                    if (layerStatus === status) {
                        layer.setStyle({opacity: this.classList.contains('active') ? 1 : 0.3});
                    }
                }.bind(this));
            });
        });

        // Helper function to format date for input field
        function formatDateForInput(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toISOString().split('T')[0];
        }

        // Close modal when clicking the close button
        document.querySelector('.close-btn').onclick = closeModal;

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Handle form submission
        document.getElementById('nicheForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                niche_no: document.getElementById('nicheNo').value,
                section: document.getElementById('section').value,
                first_name: document.getElementById('firstName').value,
                middle_name: document.getElementById('middleName').value,
                last_name: document.getElementById('lastName').value,
                death_date: document.getElementById('deathDate').value,
                status: document.getElementById('status').value,
                occupancy: document.getElementById('occupancy').value
            };

            // Send the data to the server
            fetch('update_tomb.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Tomb information updated successfully!');
                    closeModal();
                    // Refresh the map to show updated data
                    location.reload();
                } else {
                    alert('Error updating tomb information: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating tomb information. Please try again.');
            });
        });
        </script>
  </main>

  <script>
    // Add any necessary JavaScript here
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize any components that need JavaScript
    });
  </script>

  <!-- Add Modal HTML -->
  <div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Tomb Details</h2>
            <button class="close-btn">&times;</button>
        </div>
        <form id="nicheForm">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nicheNo">Niche No:</label>
                    <input type="text" id="nicheNo" name="nicheNo" required>
                </div>
                <div class="form-group">
                    <label for="section">Section:</label>
                    <input type="text" id="section" name="section" required>
                </div>
                <div class="form-group">
                    <label for="firstName">First Name:</label>
                    <input type="text" id="firstName" name="firstName">
                </div>
                <div class="form-group">
                    <label for="middleName">Middle Name:</label>
                    <input type="text" id="middleName" name="middleName">
                </div>
                <div class="form-group">
                    <label for="lastName">Last Name:</label>
                    <input type="text" id="lastName" name="lastName">
                </div>
                <div class="form-group">
                    <label for="deathDate">Date of Death:</label>
                    <input type="date" id="deathDate" name="deathDate">
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="vacant">Vacant</option>
                        <option value="reserved">Reserved</option>
                        <option value="sold">Sold</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="occupancy">Occupancy:</label>
                    <input type="text" id="occupancy" name="occupancy">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>

