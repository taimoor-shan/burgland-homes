/**
 * Communities Archive Page JavaScript
 * Handles filtering, map integration, and dynamic updates
 * 
 * @package Burgland_Homes
 */

(function($) {
  'use strict';

  let map = null;
  let markers = [];
  let infoWindows = [];
  let communityData = [];

  /**
   * Initialize the communities archive page
   */
  function init() {
    // Collect community data from DOM
    collectCommunityData();
    
    // Initialize Google Map
    initMap();
    
    // Setup filter handlers
    setupFilterHandlers();
    
    // Highlight card on marker click
    setupCardHighlight();
  }

  /**
   * Collect community data from the DOM
   */
  function collectCommunityData() {
    communityData = [];
    
    $('.community-card-wrapper').each(function() {
      const $card = $(this);
      const lat = parseFloat($card.attr('data-lat'));
      const lng = parseFloat($card.attr('data-lng'));
      const id = $card.attr('data-id');
      
      if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
        const $cardElement = $card.find('.community-card');
        const title = $cardElement.find('.card-title a').text().trim();
        const location = $cardElement.find('.bi-geo-alt-fill').parent().text().trim();
        const priceRange = $cardElement.find('.bh-card-price-badge').text().trim();
        const imageUrl = $cardElement.find('.card-img-top').attr('src') || '';
        const permalink = $cardElement.find('.card-title a').attr('href') || '#';
        const statusBadge = $cardElement.find('.badge').text().trim();
        const statusClass = $cardElement.find('.badge').attr('class') || '';
        
        communityData.push({
          id: id,
          lat: lat,
          lng: lng,
          title: title,
          location: location,
          priceRange: priceRange,
          imageUrl: imageUrl,
          permalink: permalink,
          statusBadge: statusBadge,
          statusClass: statusClass,
          element: $card
        });
      }
    });
  }

  /**
   * Initialize Google Map
   */
  function initMap() {
    // Check if Google Maps is loaded
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
      console.warn('Google Maps API not loaded. Please add your API key.');
      $('#communities-map').html(
        '<div class="d-flex align-items-center justify-content-center h-100 text-center p-4">' +
          '<div>' +
            '<i class="bi bi-exclamation-triangle fs-1 text-warning d-block mb-3"></i>' +
            '<h5>Google Maps API Key Required</h5>' +
            '<p class="text-muted">Please configure your Google Maps API key to display the map.</p>' +
          '</div>' +
        '</div>'
      );
      return;
    }

    // Calculate center of all communities
    let centerLat = 0;
    let centerLng = 0;
    let validCount = 0;

    communityData.forEach(function(community) {
      if (community.lat && community.lng) {
        centerLat += community.lat;
        centerLng += community.lng;
        validCount++;
      }
    });

    if (validCount === 0) {
      $('#communities-map').html(
        '<div class="d-flex align-items-center justify-content-center h-100 text-center p-4">' +
          '<div>' +
            '<i class="bi bi-map fs-1 text-muted d-block mb-3"></i>' +
            '<p class="text-muted">No communities with location data available.</p>' +
          '</div>' +
        '</div>'
      );
      return;
    }

    centerLat = centerLat / validCount;
    centerLng = centerLng / validCount;

    // Create map
    map = new google.maps.Map(document.getElementById('communities-map'), {
      center: { lat: centerLat, lng: centerLng },
      zoom: validCount === 1 ? 12 : 8,
      mapTypeControl: true,
      streetViewControl: false,
      fullscreenControl: true,
      zoomControl: true,
      styles: [
        {
          featureType: 'poi',
          elementType: 'labels',
          stylers: [{ visibility: 'off' }]
        }
      ]
    });

    // Add markers for all communities
    addMarkers();
  }

  /**
   * Add markers to the map
   */
  function addMarkers() {
    if (!map) return;

    // Clear existing markers
    clearMarkers();

    communityData.forEach(function(community) {
      if (!community.lat || !community.lng) return;

      // Create marker
      const marker = new google.maps.Marker({
        position: { lat: community.lat, lng: community.lng },
        map: map,
        title: community.title,
        animation: google.maps.Animation.DROP,
        icon: {
          url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
            '<svg width="32" height="42" xmlns="http://www.w3.org/2000/svg">' +
              '<path d="M16 0C7.2 0 0 7.2 0 16c0 8.8 16 26 16 26s16-17.2 16-26c0-8.8-7.2-16-16-16z" fill="#0d6efd"/>' +
              '<circle cx="16" cy="16" r="6" fill="white"/>' +
            '</svg>'
          ),
          scaledSize: new google.maps.Size(32, 42),
          anchor: new google.maps.Point(16, 42)
        }
      });

      // Create info window content
      let infoWindowContent = '<div class="map-infowindow">';
      
      if (community.imageUrl) {
        infoWindowContent += '<img src="' + community.imageUrl + '" alt="' + community.title + '" class="map-infowindow-img">';
      }
      
      infoWindowContent += '<div class="map-infowindow-content">';
      infoWindowContent += '<h3 class="map-infowindow-title">' + community.title + '</h3>';
      
      if (community.location) {
        infoWindowContent += '<p class="map-infowindow-location"><i class="bi bi-geo-alt-fill"></i> ' + community.location + '</p>';
      }
      
      if (community.priceRange) {
        infoWindowContent += '<p class="map-infowindow-price">' + community.priceRange + '</p>';
      }
      
      infoWindowContent += '<a href="' + community.permalink + '" class="map-infowindow-link">View Details <i class="bi bi-arrow-right"></i></a>';
      infoWindowContent += '</div></div>';

      // Create info window
      const infoWindow = new google.maps.InfoWindow({
        content: infoWindowContent,
        maxWidth: 300
      });

      // Add click listener to marker
      marker.addListener('click', function() {
        // Close all other info windows
        closeAllInfoWindows();
        
        // Open this info window
        infoWindow.open(map, marker);
        
        // Highlight corresponding card
        highlightCard(community.id);
        
        // Scroll to card
        scrollToCard(community.id);
      });

      // Store marker and info window
      markers.push(marker);
      infoWindows.push(infoWindow);
    });

    // Adjust map bounds to fit all markers
    if (markers.length > 1) {
      const bounds = new google.maps.LatLngBounds();
      markers.forEach(function(marker) {
        bounds.extend(marker.getPosition());
      });
      map.fitBounds(bounds);
      
      // Limit max zoom
      google.maps.event.addListenerOnce(map, 'bounds_changed', function() {
        if (map.getZoom() > 15) {
          map.setZoom(15);
        }
      });
    }
  }

  /**
   * Clear all markers from the map
   */
  function clearMarkers() {
    markers.forEach(function(marker) {
      marker.setMap(null);
    });
    markers = [];
    
    closeAllInfoWindows();
    infoWindows = [];
  }

  /**
   * Close all info windows
   */
  function closeAllInfoWindows() {
    infoWindows.forEach(function(infoWindow) {
      infoWindow.close();
    });
  }

  /**
   * Setup filter handlers
   */
  function setupFilterHandlers() {
    const $statusFilter = $('#status-filter');
    const $priceFilter = $('#price-filter');

    // Handle filter changes
    $statusFilter.on('change', function() {
      applyFilters();
    });

    $priceFilter.on('change', function() {
      applyFilters();
    });
  }

  /**
   * Apply filters
   */
  function applyFilters() {
    const selectedStatus = $('#status-filter').val();
    const selectedPrice = $('#price-filter').val();

    // Show loading spinner
    $('.loading-spinner').show();
    $('.communities-grid').addClass('loading');

    // Prepare data for AJAX request
    const data = {
      action: 'filter_communities',
      nonce: burglandHomesArchive.nonce,
      status: selectedStatus,
      price_range: selectedPrice
    };

    // Make AJAX request
    $.ajax({
      url: burglandHomesArchive.ajaxUrl,
      type: 'POST',
      data: data,
      success: function(response) {
        if (response.success) {
          // Update community list
          $('#communities-list').html(response.data.html);
          
          // Collect new community data
          collectCommunityData();
          
          // Update map markers
          if (map) {
            addMarkers();
          }
          
          // Hide loading spinner
          $('.loading-spinner').hide();
          $('.communities-grid').removeClass('loading');
        } else {
          console.error('Filter error:', response.data.message);
          $('.loading-spinner').hide();
          $('.communities-grid').removeClass('loading');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX error:', error);
        $('.loading-spinner').hide();
        $('.communities-grid').removeClass('loading');
        
        // Show error message
        $('#communities-list').html(
          '<div class="col-12">' +
            '<div class="alert alert-danger" role="alert">' +
              '<i class="bi bi-exclamation-triangle"></i> ' +
              'An error occurred while filtering communities. Please try again.' +
            '</div>' +
          '</div>'
        );
      }
    });
  }

  /**
   * Highlight card when marker is clicked
   */
  function highlightCard(communityId) {
    // Remove highlight from all cards
    $('.community-card').removeClass('border-primary shadow-lg');
    
    // Add highlight to selected card
    const $card = $('.community-card-wrapper[data-id="' + communityId + '"]').find('.community-card');
    $card.addClass('border-primary shadow-lg');
    
    // Remove highlight after 3 seconds
    setTimeout(function() {
      $card.removeClass('border-primary shadow-lg');
    }, 3000);
  }

  /**
   * Scroll to card
   */
  function scrollToCard(communityId) {
    const $cardWrapper = $('.community-card-wrapper[data-id="' + communityId + '"]');
    
    if ($cardWrapper.length) {
      $('html, body').animate({
        scrollTop: $cardWrapper.offset().top - 100
      }, 500);
    }
  }

  /**
   * Setup card highlight on hover
   */
  function setupCardHighlight() {
    $(document).on('mouseenter', '.community-card', function() {
      const $wrapper = $(this).closest('.community-card-wrapper');
      const communityId = $wrapper.attr('data-id');
      
      // Find corresponding marker and bounce it
      const markerIndex = communityData.findIndex(c => c.id === communityId);
      
      if (markerIndex !== -1 && markers[markerIndex]) {
        markers[markerIndex].setAnimation(google.maps.Animation.BOUNCE);
        
        // Stop bounce after 1 second
        setTimeout(function() {
          if (markers[markerIndex]) {
            markers[markerIndex].setAnimation(null);
          }
        }, 750);
      }
    });
  }

  /**
   * Initialize Swiper and GLightbox for single gallery
   */
  function initGallery() {
    // Initialize Swiper
    if ($('.plugin-slider__swiper').length && typeof Swiper !== 'undefined') {
      new Swiper('.plugin-slider__swiper', {
        loop: true,
        pagination: {
          el: '.swiper-pagination',
          clickable: true,
        },
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
        autoplay: {
          delay: 5000,
          disableOnInteraction: false,
        },
      });
    }

    // Initialize GLightbox
    if ($('.glightbox').length && typeof GLightbox !== 'undefined') {
      GLightbox({
        selector: '.glightbox',
        touchNavigation: true,
        loop: true,
      });
    }
  }

  // Initialize when document is ready
  $(document).ready(function() {
    // Initialize Gallery components
    initGallery();
    
    // Initialize Read More functionality
    initReadMore();

    // Check if we're on the communities archive page
    if ($('#communities-map').length) {
      // Wait for Google Maps to load
      if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
        init();
      } else {
        // If Google Maps isn't loaded yet, wait for it
        window.initCommunitiesMap = init;
      }
    }
    
    // Initialize lots grid filter if it exists
    if ($('#lots-filters').length > 0) {
      initLotsFilter();
    }
  });

  // Expose init function globally for callback
  window.initCommunitiesMap = init;

  /**
   * Initialize Read More Toggle Functionality
   * Handles expandable/collapsible content sections
   */
  function initReadMore() {
    $('.bh-read-more-wrapper').each(function() {
      var $wrapper = $(this);
      var $content = $wrapper.find('.bh-read-more-content');
      var $button = $wrapper.find('.bh-read-more-btn');
      var isExpanded = false;

      // Skip if no content or button
      if (!$content.length || !$button.length) {
        return;
      }

      // Get the full height of the content
      var fullHeight = $content[0].scrollHeight;
      var collapsedHeight = parseInt($content.css('max-height'));

      // If content is shorter than collapsed height, hide the button
      if (fullHeight <= collapsedHeight) {
        $button.hide();
        $content.addClass('bh-no-overflow');
        return;
      }

      // Button click handler
      $button.on('click', function(e) {
        e.preventDefault();
        
        if (isExpanded) {
          // Collapse
          $content.css('max-height', collapsedHeight + 'px');
          $button.text($button.attr('data-text-more'));
          $button.removeClass('expanded');
          isExpanded = false;
          
          // Scroll back to the card if it's out of view
          setTimeout(function() {
            var cardTop = $wrapper.closest('.card').offset().top - 20;
            var scrollTop = $(window).scrollTop();
            
            if (cardTop < scrollTop) {
              $('html, body').animate({
                scrollTop: cardTop
              }, 300);
            }
          }, 350);
        } else {
          // Expand
          $content.css('max-height', fullHeight + 'px');
          $button.text($button.attr('data-text-less'));
          $button.addClass('expanded');
          isExpanded = true;
        }
      });
    });
  }

  // Reinitialize read-more on window resize (to recalculate heights)
  var resizeTimer;
  $(window).on('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
      // Reset all read-more sections
      $('.bh-read-more-wrapper').each(function() {
        var $wrapper = $(this);
        var $content = $wrapper.find('.bh-read-more-content');
        var $button = $wrapper.find('.bh-read-more-btn');
        
        if (!$content.length || !$button.length) {
          return;
        }
        
        // If expanded, update to new full height
        if ($button.hasClass('expanded')) {
          $content.css('max-height', $content[0].scrollHeight + 'px');
        }
      });
    }, 250);
  });

  /**
   * Initialize Lots Grid Filter (for single community page)
   */
  function initLotsFilter() {
    const $form = $('#lots-filters');
    const $grid = $('#lots-grid');
    const $noResults = $('#no-lots-message');
    const $count = $('#lots-count');
    
    // Handle filter change
    $form.find('select').on('change', function() {
      filterAndSortLots();
    });
    
    /**
     * Filter and sort lots based on selected criteria
     */
    function filterAndSortLots() {
      const sqftRange = $('#sqft-filter').val();
      const bedrooms = $('#bedrooms-filter').val();
      const bathrooms = $('#bathrooms-filter').val();
      const sortOrder = $('#sort-order').val();
      
      // Get all lot cards
      let $lots = $('.lot-card-wrapper');
      let visibleCount = 0;
      
      // Filter lots
      $lots.each(function() {
        const $lot = $(this);
        let visible = true;
        
        // Filter by sqft range
        if (sqftRange) {
          const sqft = parseInt($lot.attr('data-sqft')) || 0;
          
          if (sqftRange === '0-1500' && sqft >= 1500) {
            visible = false;
          } else if (sqftRange === '1500-2000' && (sqft < 1500 || sqft >= 2000)) {
            visible = false;
          } else if (sqftRange === '2000-2500' && (sqft < 2000 || sqft >= 2500)) {
            visible = false;
          } else if (sqftRange === '2500-3000' && (sqft < 2500 || sqft >= 3000)) {
            visible = false;
          } else if (sqftRange === '3000+' && sqft < 3000) {
            visible = false;
          }
        }
        
        // Filter by bedrooms
        if (bedrooms && visible) {
          const lotBedrooms = $lot.attr('data-bedrooms');
          if (lotBedrooms !== bedrooms) {
            visible = false;
          }
        }
        
        // Filter by bathrooms
        if (bathrooms && visible) {
          const lotBathrooms = $lot.attr('data-bathrooms');
          if (lotBathrooms !== bathrooms) {
            visible = false;
          }
        }
        
        // Show/hide lot
        if (visible) {
          $lot.show();
          visibleCount++;
        } else {
          $lot.hide();
        }
      });
      
      // Sort visible lots
      if (visibleCount > 0) {
        let $visibleLots = $('.lot-card-wrapper:visible');
        
        $visibleLots.sort(function(a, b) {
          const $a = $(a);
          const $b = $(b);
          
          if (sortOrder === 'price-asc') {
            return parseInt($a.attr('data-price')) - parseInt($b.attr('data-price'));
          } else if (sortOrder === 'price-desc') {
            return parseInt($b.attr('data-price')) - parseInt($a.attr('data-price'));
          } else if (sortOrder === 'sqft-asc') {
            return parseInt($a.attr('data-sqft')) - parseInt($b.attr('data-sqft'));
          } else if (sortOrder === 'sqft-desc') {
            return parseInt($b.attr('data-sqft')) - parseInt($a.attr('data-sqft'));
          }
          
          return 0;
        });
        
        // Re-append sorted lots to grid
        $visibleLots.detach().appendTo($grid);
      }
      
      // Update count and show/hide no results message
      $count.text(visibleCount);
      
      if (visibleCount === 0) {
        $noResults.show();
      } else {
        $noResults.hide();
      }
    }
  }

})(jQuery);
