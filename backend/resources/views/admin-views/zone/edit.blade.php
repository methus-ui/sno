@extends('layouts.admin.app')

@section('title',translate('Update Zone'))

@push('css_or_js')
<style>
    /* Map Enhancement Styles */
    .map-tools {
        position: absolute;
        top: 60px;
        left: 10px;
        z-index: 1000;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        padding: 10px;
    }

    .map-tool-btn {
        display: block;
        width: 40px;
        height: 40px;
        margin: 5px 0;
        border: none;
        background: #fff;
        border-radius: 4px;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.3s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .map-tool-btn:hover {
        background: #FF6B35;
        color: white;
        transform: scale(1.1);
    }

    .drawing-progress {
        position: absolute;
        top: 10px;
        right: 10px;
        background: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        z-index: 1000;
        display: none;
    }

    .drawing-progress.active {
        display: block;
    }

    .progress-text {
        font-weight: 600;
        color: #FF6B35;
        margin-bottom: 8px;
    }

    .overlap-warning {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #dc3545;
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 1000;
        display: none;
    }

    .overlap-warning.active {
        display: block;
        animation: shake 0.5s;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(-50%) translateX(0); }
        25% { transform: translateX(-50%) translateX(-10px); }
        75% { transform: translateX(-50%) translateX(10px); }
    }

    .zone-info-panel {
        position: absolute;
        top: 10px;
        left: 70px;
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        z-index: 1000;
    }

    .zone-info-title {
        font-weight: 600;
        color: #050df2;
        margin-bottom: 5px;
    }

    .zone-info-detail {
        font-size: 12px;
        color: #666;
    }
</style>
@endpush

@section('content')

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/edit.png')}}" class="w--26" alt="">
                </span>
                <span>
                   {{ translate('edit_zone')}}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <form action="{{route('admin.business-settings.zone.update', $zone->id)}}" method="post" id="zone_form" class="shadow--card">
            @csrf
            <div class="row">
                <div class="col-md-5">
                    <div class="zone-setup-instructions">
                        <div class="zone-setup-top">
                            <h6 class="subtitle">{{ translate('Instructions') }}</h6>
                            <p>
                                {{ translate('Create_&_connect_dots_in_a_specific_area_on_the_map_to_add_a_new_business_zone.') }}
                            </p>
                        </div>
                        <div class="zone-setup-item">
                            <div class="zone-setup-icon">
                                <i class="tio-hand-draw"></i>
                            </div>
                            <div class="info">
                                {{ translate('Use_this_‘Hand_Tool’_to_find_your_target_zone.') }}
                            </div>
                        </div>
                        <div class="zone-setup-item">
                            <div class="zone-setup-icon">
                                <i class="tio-free-transform"></i>
                            </div>
                            <div class="info">
                                {{ translate('Use_this_‘Shape_Tool’_to_point_out_the_areas_and_connect_the_dots._Minimum_3_points/dots_are_required.') }}
                            </div>
                        </div>
                        <div class="instructions-image mt-4">
                            <img src="{{asset('public/assets/admin/img/instructions.gif')}}" alt="instructions">
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-7 zone-setup">
                    <div class="form-group">
                        @if($language)
                            <ul class="nav nav-tabs mb-4">
                                <li class="nav-item">
                                    <a class="nav-link lang_link active"
                                    href="#"
                                    id="default-link">{{translate('messages.default')}}</a>
                                </li>
                                @foreach ($language as $lang)
                                    <li class="nav-item">
                                        <a class="nav-link lang_link"
                                            href="#"
                                            id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="pl-xl-5 pl-xxl-0">
                        @if($language)
                            <div class="row lang_form" id="default-form">
                                <div class="form-group col-6">
                                    <label class="input-label" for="exampleFormControlInput1">{{translate('messages.name')}} ({{ translate('messages.default') }})</label>
                                    <input type="text" name="name[]" class="form-control" placeholder="{{translate('messages.new_zone')}}" maxlength="191" value="{{$zone?->getRawOriginal('name')}}"  >
                                </div>
                                <div class="form-group col-6">
                                    <label class="input-label" for="exampleFormControlInput1">{{translate('messages.display_name')}} ({{ translate('messages.default') }})</label>
                                    <input type="text" name="display_name[]" class="form-control" placeholder="{{translate('messages.display_name')}}" maxlength="191" value="{{$zone?->getRawOriginal('display_name')}}"  >
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                            </div>
                                @foreach($language as $lang)
                                    <?php
                                        if(count($zone['translations'])){
                                            $translate = [];
                                            foreach($zone['translations'] as $t)
                                            {
                                                if($t->locale == $lang && $t->key=="name"){
                                                    $translate[$lang]['name'] = $t->value;
                                                }
                                                if($t->locale == $lang && $t->key=="display_name"){
                                                    $translate[$lang]['display_name'] = $t->value;
                                                }
                                            }
                                        }
                                    ?>
                                <div class="row lang_form d-none" id="{{$lang}}-form">
                                    <div class="form-group col-6">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.name')}} ({{strtoupper($lang)}})</label>
                                        <input type="text" name="name[]" class="form-control" placeholder="{{translate('messages.new_zone')}}" maxlength="191" value="{{$translate[$lang]['name']??''}}"  >
                                    </div>
                                    <div class="form-group col-6">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.display_name')}} ({{strtoupper($lang)}})</label>
                                        <input type="text" name="display_name[]" class="form-control" placeholder="{{translate('messages.display_name')}}" maxlength="191" value="{{$translate[$lang]['display_name']??''}}"  >
                                    </div>
                                    <input type="hidden" name="lang[]" value="{{$lang}}">
                                </div>
                                @endforeach
                            @endif
                        <div class="form-group d-none">
                            <label class="input-label" for="exampleFormControlInput1">{{ translate('messages.Coordinates') }}
                                <span class="form-label-secondary" data-toggle="tooltip" data-placement="right" data-original-title="{{translate('messages.draw_your_zone_on_the_map')}}">
                                    {{translate('messages.draw_your_zone_on_the_map')}}
                                </span>
                            </label>
                                <textarea type="text" rows="8" name="coordinates" id="coordinates" class="form-control" readonly>@foreach($zone->coordinates[0]->toArray()['coordinates'] as $key=>$coords)<?php if(count($zone->coordinates[0]->toArray()['coordinates']) != $key+1) {if($key != 0) echo(','); ?>({{$coords[1]}}, {{$coords[0]}})<?php } ?>@endforeach</textarea>
                        </div>


                        <div class="map-warper rounded mt-0" style="position: relative; height: 500px;">
                            <!-- Zone Info Panel -->
                            <div class="zone-info-panel">
                                <div class="zone-info-title">✏️ Editing: {{$zone->name}}</div>
                                <div class="zone-info-detail">Zone ID: {{$zone->id}}</div>
                            </div>

                            <!-- Map Tools -->
                            <div class="map-tools">
                                <button type="button" class="map-tool-btn" id="clear-btn" title="Clear polygon">
                                    <i class="tio-delete"></i>
                                </button>
                                <button type="button" class="map-tool-btn" id="undo-btn" title="Undo last point">
                                    <i class="tio-undo"></i>
                                </button>
                                <button type="button" class="map-tool-btn" id="redraw-btn" title="Redraw from scratch">
                                    <i class="tio-refresh"></i>
                                </button>
                            </div>

                            <!-- Drawing Progress -->
                            <div class="drawing-progress" id="drawing-progress">
                                <div class="progress-text">
                                    <span id="points-count">0</span> points drawn (min. 3 required)
                                </div>
                            </div>

                            <!-- Overlap Warning -->
                            <div class="overlap-warning" id="overlap-warning">
                                <i class="tio-warning"></i>
                                <strong>Warning:</strong> <span id="overlap-message">Zone overlaps detected</span>
                            </div>

                            <input id="pac-input" class="controls rounded initial--33" title="{{translate('messages.search_your_location_here')}}" type="text" placeholder="{{translate('messages.search_here')}}"/>
                            <div id="map-canvas" class="initial--34" style="height: 100%; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="btn--container mt-3 justify-content-end">
                <button id="reset_btn" type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                <button type="submit" class="btn btn--primary">{{translate('messages.Save_changes')}}</button>
            </div>
        </form>
    </div>

@endsection

@push('script_2')
<script src="https://maps.googleapis.com/maps/api/js?v=3.45.8&key={{\App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value}}&libraries=drawing,places"></script>
<!-- <script src="https://maps.googleapis.com/maps/api/js?v=3.45.8&key={{\App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value}}&libraries=drawing,places"></script> -->
<script>
    "use strict";
    auto_grow();
    function auto_grow() {
        let element = document.getElementById("coordinates");
        element.style.height = "5px";
        element.style.height = (element.scrollHeight)+"px";
    }

    let map;
    let drawingManager;
    let lastpolygon = null;
    let bounds = new google.maps.LatLngBounds();
    let allZonePolygons = [];
    let zonesData = @json($zonesData);
    let pointsDrawn = 0;
    let currentZoneId = {{$zone->id}};
    let originalPolygon = null;

    function initialize() {
        let myLatlng = new google.maps.LatLng(zonesData.find(z => z.color === '#050df2').center.lat, 
                                              zonesData.find(z => z.color === '#050df2').center.lng);
        let myOptions = {
            zoom: 13,
            center: myLatlng,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);

        // Draw all zones with different colors
        zonesData.forEach(zone => {
            const zoneCoords = zone.coordinates.map(coord => {
                return { lat: coord[1], lng: coord[0] };
            });
            
            let zonePolygon = new google.maps.Polygon({
                paths: zoneCoords,
                strokeColor: zone.color,
                strokeOpacity: 0.8,
                strokeWeight: 3,
                fillColor: zone.color,
                fillOpacity: 0.2,
            });
            
            zonePolygon.setMap(map);
            allZonePolygons.push(zonePolygon);
            
            // Add center marker with zone ID and name
            new google.maps.Marker({
                position: { lat: parseFloat(zone.center.lat), lng: parseFloat(zone.center.lng) },
                map: map,
                label: {
                    text: `Zone ${zone.id}: ${zone.name}`, // Display both ID and name
                    color: "#ffffffff",
                    fontWeight: "bold",
                    fontSize: "10px", // Adjusted for better readability
                    className: "zone-marker-label"
                },
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: zone.color,
                    fillOpacity: 1,
                    strokeColor: '#000000',
                    strokeWeight: 2,
                    scale: 15 // Increased size to accommodate longer text
                }
            });
            
            // Add info window with enhanced zone details
            const infoWindow = new google.maps.InfoWindow({
                content: `<div style="line-height:1.4;overflow:hidden;min-width:180px;padding:5px;">
                            <strong style="color:#2c3e50;">Zone ${zone.id}: ${zone.name}</strong><br>
                            <span style="color:#7f8c8d;font-size:12px;">Display Name: ${zone.display_name || 'N/A'}</span><br>
                            <span style="color:#7f8c8d;font-size:12px;">Coordinates: ${zone.coordinates.length} points</span><br>
                            <span style="color:#27ae60;font-size:12px;font-weight:bold;">Status: Active</span>
                          </div>`
            });
            
            // Add click listener to polygon for info window
            google.maps.event.addListener(zonePolygon, 'click', function(event) {
                infoWindow.setPosition(event.latLng);
                infoWindow.open(map);
            });
            
            // Add click listener to marker for info window
            google.maps.event.addListener(zonePolygon, 'mouseover', function() {
                zonePolygon.setOptions({
                    strokeWeight: 5,
                    fillOpacity: 0.4
                });
            });
            
            google.maps.event.addListener(zonePolygon, 'mouseout', function() {
                zonePolygon.setOptions({
                    strokeWeight: 3,
                    fillOpacity: 0.2
                });
            });
            
            // Extend bounds to include this zone
            zoneCoords.forEach(coord => {
                bounds.extend(new google.maps.LatLng(coord.lat, coord.lng));
            });
        });

        // Fit map to show all zones
        map.fitBounds(bounds);

        // Initialize drawing manager for editing
        drawingManager = new google.maps.drawing.DrawingManager({
            drawingMode: google.maps.drawing.OverlayType.POLYGON,
            drawingControl: true,
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: [google.maps.drawing.OverlayType.POLYGON]
            },
            polygonOptions: {
                editable: true,
                strokeColor: '#050df2',
                strokeWeight: 3,
                fillColor: '#050df2',
                fillOpacity: 0.3
            }
        });
        drawingManager.setMap(map);

        google.maps.event.addListener(drawingManager, "overlaycomplete", function(event) {
            if(lastpolygon) {
                lastpolygon.setMap(null);
            }

            $('#coordinates').val(event.overlay.getPath().getArray());
            lastpolygon = event.overlay;
            pointsDrawn = event.overlay.getPath().getLength();
            updateDrawingProgress();
            auto_grow();

            // Check for overlaps with all other zones (excluding current zone)
            checkForOverlaps(event.overlay);

            // Add listeners for path changes
            google.maps.event.addListener(event.overlay.getPath(), 'set_at', function() {
                pointsDrawn = event.overlay.getPath().getLength();
                updateDrawingProgress();
                $('#coordinates').val(lastpolygon.getPath().getArray());
                checkForOverlaps(lastpolygon);
            });

            google.maps.event.addListener(event.overlay.getPath(), 'insert_at', function() {
                pointsDrawn = event.overlay.getPath().getLength();
                updateDrawingProgress();
                $('#coordinates').val(lastpolygon.getPath().getArray());
                checkForOverlaps(lastpolygon);
            });

            google.maps.event.addListener(event.overlay.getPath(), 'remove_at', function() {
                pointsDrawn = event.overlay.getPath().getLength();
                updateDrawingProgress();
                $('#coordinates').val(lastpolygon.getPath().getArray());
                checkForOverlaps(lastpolygon);
            });
        });

        // Create search box
        const input = document.getElementById("pac-input");
        const searchBox = new google.maps.places.SearchBox(input);
        map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
        
        map.addListener("bounds_changed", () => {
            searchBox.setBounds(map.getBounds());
        });
        
        let markers = [];
        searchBox.addListener("places_changed", () => {
            const places = searchBox.getPlaces();
            if (places.length == 0) return;
            
            markers.forEach((marker) => {
                marker.setMap(null);
            });
            markers = [];
            
            const bounds = new google.maps.LatLngBounds();
            places.forEach((place) => {
                if (!place.geometry || !place.geometry.location) return;
                
                markers.push(
                    new google.maps.Marker({
                        map,
                        title: place.name,
                        position: place.geometry.location,
                    })
                );

                bounds.extend(place.geometry.location);
            });
            map.fitBounds(bounds);
        });
    }

    function checkForOverlaps(newPolygon) {
        let overlapDetected = false;
        let overlappingZones = [];

        allZonePolygons.forEach((existingPolygon, index) => {
            // Skip the current zone being edited (blue color)
            if (zonesData[index].color === '#050df2') return;

            // Check each vertex of new polygon against existing zones
            const newPath = newPolygon.getPath().getArray();
            let hasOverlap = false;

            for (let i = 0; i < newPath.length; i++) {
                if (google.maps.geometry.poly.containsLocation(newPath[i], existingPolygon)) {
                    hasOverlap = true;
                    break;
                }
            }

            // Also check if existing polygon vertices are inside new polygon
            if (!hasOverlap) {
                const existingPath = existingPolygon.getPath().getArray();
                for (let i = 0; i < existingPath.length; i++) {
                    if (google.maps.geometry.poly.containsLocation(existingPath[i], newPolygon)) {
                        hasOverlap = true;
                        break;
                    }
                }
            }

            if (hasOverlap) {
                overlappingZones.push({
                    id: zonesData[index].id,
                    name: zonesData[index].name
                });
                overlapDetected = true;
            }
        });

        if (overlapDetected) {
            let message = overlappingZones.map(z => `Zone ${z.id}: ${z.name}`).join(', ');
            $('#overlap-message').text(`Overlaps with ${message}`);
            $('#overlap-warning').addClass('active');

            setTimeout(() => {
                $('#overlap-warning').removeClass('active');
            }, 5000);
        } else {
            $('#overlap-warning').removeClass('active');
        }
    }

    // Update Drawing Progress
    function updateDrawingProgress() {
        $('#points-count').text(pointsDrawn);
        if (pointsDrawn > 0) {
            $('#drawing-progress').addClass('active');
        } else {
            $('#drawing-progress').removeClass('active');
        }
    }

    // Initialize map when DOM is ready
    google.maps.event.addDomListener(window, 'load', initialize);

    $(document).on('ready', function(){
        $("#zone_form").on('keydown', function(e){
            if (e.keyCode === 13) {
                e.preventDefault();
            }
        });
    });

    $('#reset_btn').click(function(){
        location.reload(true);
    });

    // Map Tool Buttons
    $('#clear-btn').click(function() {
        if (lastpolygon) {
            lastpolygon.setMap(null);
            lastpolygon = null;
            $('#coordinates').val('');
            pointsDrawn = 0;
            updateDrawingProgress();
            $('#overlap-warning').removeClass('active');
        }
    });

    $('#undo-btn').click(function() {
        if (lastpolygon && pointsDrawn > 0) {
            const path = lastpolygon.getPath();
            path.removeAt(path.getLength() - 1);
            pointsDrawn--;
            updateDrawingProgress();
            $('#coordinates').val(lastpolygon.getPath().getArray());
        }
    });

    $('#redraw-btn').click(function() {
        if (confirm('Are you sure you want to redraw the zone from scratch? This will clear your current drawing.')) {
            if (lastpolygon) {
                lastpolygon.setMap(null);
                lastpolygon = null;
            }
            $('#coordinates').val('');
            pointsDrawn = 0;
            updateDrawingProgress();
            $('#overlap-warning').removeClass('active');
            toastr.info('You can now draw a new zone boundary');
        }
    });

    // Additional helper functions for better UX
    function highlightZone(zoneId) {
        const zoneIndex = zonesData.findIndex(z => z.id === zoneId);
        if (zoneIndex !== -1 && allZonePolygons[zoneIndex]) {
            const polygon = allZonePolygons[zoneIndex];
            const originalColor = zonesData[zoneIndex].color;
            
            // Highlight effect
            polygon.setOptions({
                strokeColor: '#ff0000',
                strokeWeight: 5,
                fillOpacity: 0.6
            });
            
            // Reset after 2 seconds
            setTimeout(() => {
                polygon.setOptions({
                    strokeColor: originalColor,
                    strokeWeight: 3,
                    fillOpacity: 0.2
                });
            }, 2000);
        }
    }

    function focusOnZone(zoneId) {
        const zone = zonesData.find(z => z.id === zoneId);
        if (zone) {
            map.setCenter(new google.maps.LatLng(zone.center.lat, zone.center.lng));
            map.setZoom(15);
            highlightZone(zoneId);
        }
    }
</script>
@endpush
