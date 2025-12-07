(function() {
    'use strict';

    function initMaps() {
        if (typeof L === 'undefined') {
            // Retry after a short delay if Leaflet isn't loaded yet
            setTimeout(initMaps, 200);
            return;
        }

        var containers = document.querySelectorAll('.ssb-map-container');
        if (containers.length === 0) {
            return;
        }

        containers.forEach(function(container) {
            // Skip if already initialized
            if (container.dataset.initialized === 'true') {
                return;
            }

            var lat = parseFloat(container.dataset.lat);
            var lng = parseFloat(container.dataset.lng);
            var address = container.dataset.address || '';
            var needsGeocode = container.dataset.geocode === 'true';
            
            // If we need to geocode, do that first
            if (needsGeocode && address) {
                geocodeAndShowMap(container, address);
                return;
            }
            
            if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
                console.warn('Map container missing valid coordinates:', container.id);
                var loading = container.querySelector('.ssb-map-loading');
                if (loading) {
                    loading.textContent = 'Coordinates not available';
                }
                return;
            }

            // Ensure container is visible before initializing
            if (container.offsetHeight === 0 || container.offsetWidth === 0) {
                // Container might be hidden, try again later
                setTimeout(function() {
                    if (container.offsetHeight > 0 && container.offsetWidth > 0) {
                        initializeMap(container, lat, lng, address);
                    }
                }, 500);
                return;
            }

            try {
                initializeMap(container, lat, lng, address);
            } catch (e) {
                console.error('Error initializing map:', e);
                var loading = container.querySelector('.ssb-map-loading');
                if (loading) {
                    loading.textContent = 'Error loading map';
                    loading.style.color = '#ef4444';
                }
            }
        });
    }

    function initializeMap(container, lat, lng, address) {
        try {
            // Ensure container has dimensions
            if (container.offsetHeight === 0 || container.offsetWidth === 0) {
                console.warn('Map container has no dimensions');
                return;
            }

            // Initialize map
            var map = L.map(container, {
                zoomControl: true,
                scrollWheelZoom: true,
                doubleClickZoom: true,
                boxZoom: true,
                keyboard: true,
                dragging: true,
                touchZoom: true
            }).setView([lat, lng], 15);

            // Add OpenStreetMap tiles with custom attribution control
            var tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
                minZoom: 3
            }).addTo(map);
            
            // Get the attribution control after tile layer is added
            // Leaflet automatically creates an attribution control when you add a tile layer
            var attributionControl = map.attributionControl;
            if (attributionControl && attributionControl._container) {
                // Style it immediately if it exists
                var attrEl = attributionControl._container;
                attrEl.style.fontSize = '10px';
                attrEl.style.lineHeight = '1.2';
                attrEl.style.padding = '4px 6px';
                attrEl.style.fontWeight = 'normal';
                var links = attrEl.querySelectorAll('a');
                links.forEach(function(link) {
                    link.style.fontSize = '10px';
                    link.style.textDecoration = 'none';
                    link.style.fontWeight = 'normal';
                });
            }

            // Add custom marker icon (optional - using default for now)
            var marker = L.marker([lat, lng], {
                title: address || 'Location'
            }).addTo(map);
            
            // Bind popup with address if available
            if (address) {
                marker.bindPopup('<div style="font-weight: 600; margin-bottom: 0.25rem;">' + address + '</div>').openPopup();
            }

            // Style the attribution control to make it smaller
            // Use MutationObserver to catch it when Leaflet adds it to DOM
            function styleAttribution() {
                // Try multiple selectors to find the attribution
                var attribution = container.querySelector('.leaflet-control-attribution') || 
                                  map.getContainer().querySelector('.leaflet-control-attribution') ||
                                  document.querySelector('.leaflet-control-attribution');
                
                if (attribution) {
                    // Apply inline styles directly (highest priority - inline styles always win)
                    attribution.style.fontSize = '10px';
                    attribution.style.lineHeight = '1.2';
                    attribution.style.padding = '4px 6px';
                    attribution.style.fontWeight = 'normal';
                    attribution.style.cssText += '; font-size: 10px !important; line-height: 1.2 !important; padding: 4px 6px !important; font-weight: normal !important;';
                    
                    // Style all links and text inside
                    var links = attribution.querySelectorAll('a');
                    links.forEach(function(link) {
                        link.style.fontSize = '10px';
                        link.style.textDecoration = 'none';
                        link.style.fontWeight = 'normal';
                        link.style.cssText += '; font-size: 10px !important; text-decoration: none !important; font-weight: normal !important;';
                    });
                    
                    // Style all child elements
                    var allElements = attribution.querySelectorAll('*');
                    allElements.forEach(function(el) {
                        el.style.fontSize = '10px';
                        el.style.lineHeight = '1.2';
                        el.style.cssText += '; font-size: 10px !important; line-height: 1.2 !important;';
                    });
                    
                    return true; // Found and styled
                }
                return false; // Not found yet
            }
            
            // Use MutationObserver to watch for when Leaflet adds the attribution
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // Element node
                                if (node.classList && node.classList.contains('leaflet-control-attribution')) {
                                    styleAttribution();
                                }
                                // Also check children
                                var attribution = node.querySelector && node.querySelector('.leaflet-control-attribution');
                                if (attribution) {
                                    styleAttribution();
                                }
                            }
                        });
                    }
                });
            });
            
            // Start observing the map container
            observer.observe(map.getContainer(), {
                childList: true,
                subtree: true
            });
            
            // Also try immediately and with delays as fallback
            styleAttribution();
            setTimeout(function() {
                styleAttribution();
            }, 100);
            setTimeout(function() {
                styleAttribution();
            }, 300);
            setTimeout(function() {
                styleAttribution();
            }, 800);
            setTimeout(function() {
                styleAttribution();
                // Stop observing after 2 seconds
                observer.disconnect();
            }, 2000);

            // Invalidate size after a short delay to ensure proper rendering
            setTimeout(function() {
                map.invalidateSize();
            }, 100);

            // Mark as initialized
            container.dataset.initialized = 'true';
            
            // Remove loading indicator
            var loadingIndicator = container.querySelector('.ssb-map-loading');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
        } catch (e) {
            console.error('Error initializing map:', e);
        }
    }

    function geocodeAndShowMap(container, address) {
        // Use server-side geocoding via AJAX
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is required for geocoding');
            return;
        }

        jQuery.ajax({
            url: (typeof ssbMapsData !== 'undefined' && ssbMapsData.ajaxurl) ? ssbMapsData.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php'),
            type: 'POST',
            data: {
                action: 'ssb_geocode_address',
                address: address,
                nonce: (typeof ssbMapsData !== 'undefined' && ssbMapsData.nonce) ? ssbMapsData.nonce : ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    var lat = parseFloat(response.data.lat);
                    var lng = parseFloat(response.data.lon);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        initializeMap(container, lat, lng, address);
                    }
                }
            },
            error: function() {
                console.error('Failed to geocode address');
            }
        });
    }

    // Initialize on DOM ready and after Leaflet loads
    function startInit() {
        var attempts = 0;
        var maxAttempts = 50; // Try for 5 seconds max
        
        // Wait for both DOM and Leaflet to be ready
        function tryInit() {
            attempts++;
            
            if (typeof L !== 'undefined' && document.readyState !== 'loading') {
                // Leaflet is loaded, initialize maps
                setTimeout(function() {
                    initMaps();
                }, 200);
            } else if (attempts < maxAttempts) {
                // Keep trying
                setTimeout(tryInit, 100);
            } else {
                // Give up and show error
                console.error('Leaflet failed to load after ' + maxAttempts + ' attempts');
                document.querySelectorAll('.ssb-map-container').forEach(function(container) {
                    var loading = container.querySelector('.ssb-map-loading');
                    if (loading) {
                        loading.textContent = 'Map failed to load. Please refresh the page.';
                        loading.style.color = '#ef4444';
                    }
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(tryInit, 300);
            });
        } else {
            setTimeout(tryInit, 300);
        }
    }

    startInit();

    // Re-initialize if Leaflet loads later
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('ssb-maps-ready', initMaps);
        // Also try when window loads
        jQuery(window).on('load', function() {
            setTimeout(initMaps, 1000);
        });
    }
    
    // Fallback: try again after a longer delay
    setTimeout(function() {
        if (typeof L !== 'undefined') {
            initMaps();
        }
    }, 2000);
})();

