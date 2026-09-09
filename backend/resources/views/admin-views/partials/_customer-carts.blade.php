<!-- Active Customer Carts - Real-Time AJAX with Animations -->
@if(isset($latest_carts) && isset($users) && isset($items) && $latest_carts->count() > 0)
<div class="mb-3" id="cart-section">
    <!-- Enhanced Stats Dashboard -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header border-0 pb-0">
            <div class="row align-items-center mb-3">
                <div class="col-md-6">
                    <h5 class="card-header-title mb-0">
                        <span class="card-header-icon">
                            <i class="tio-shopping-cart"></i>
                        </span>
                        <span>Active Shopping Carts</span>
                        <span class="badge badge-soft-success ml-2" id="live-status">
                            <i class="tio-checkmark-circle"></i> Live
                        </span>
                    </h5>
                </div>
                <div class="col-md-6 text-right">
                    <small class="text-muted" id="last-updated">
                        <i class="tio-time"></i> Updated just now
                    </small>
                </div>
            </div>
        </div>
        
        <div class="card-body pt-0">
            <!-- Primary Stats Row -->
            <div class="row mb-3">
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <div class="card card-sm card-hover-shadow h-100">
                        <div class="card-body">
                            <div class="media align-items-center">
                                <div class="avatar avatar-circle bg-soft-primary text-primary mr-3">
                                    <i class="tio-shopping-cart"></i>
                                </div>
                                <div class="media-body">
                                    <span class="d-block text-muted small">Active Carts</span>
                                    <h3 class="mb-0 animated-value" id="stat-count">{{$latest_carts->count()}}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <div class="card card-sm card-hover-shadow h-100">
                        <div class="card-body">
                            <div class="media align-items-center">
                                <div class="avatar avatar-circle bg-soft-success text-success mr-3">
                                    <i class="tio-money"></i>
                                </div>
                                <div class="media-body">
                                    @php
                                        $totalValue = 0;
                                        foreach($latest_carts as $carts) {
                                            $totalValue += $carts->sum(function($c) {
                                                return ($c->price ?? 0) * ($c->quantity ?? 0);
                                            });
                                        }
                                    @endphp
                                    <span class="d-block text-muted small">Total Value</span>
                                    <h3 class="mb-0 text-success animated-value" id="stat-total">{{\App\CentralLogics\Helpers::format_currency($totalValue)}}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <div class="card card-sm card-hover-shadow h-100">
                        <div class="card-body">
                            <div class="media align-items-center">
                                <div class="avatar avatar-circle bg-soft-info text-info mr-3">
                                    <i class="tio-chart-bar-4"></i>
                                </div>
                                <div class="media-body">
                                    @php
                                        $avgValue = $latest_carts->count() > 0 ? $totalValue / $latest_carts->count() : 0;
                                    @endphp
                                    <span class="d-block text-muted small">Avg Cart Value</span>
                                    <h3 class="mb-0 text-primary animated-value" id="stat-avg">{{\App\CentralLogics\Helpers::format_currency($avgValue)}}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card card-sm card-hover-shadow h-100">
                        <div class="card-body">
                            <div class="media align-items-center">
                                <div class="avatar avatar-circle bg-soft-warning text-warning mr-3">
                                    <i class="tio-shopping-basket"></i>
                                </div>
                                <div class="media-body">
                                    @php
                                        $totalItems = 0;
                                        foreach($latest_carts as $carts) {
                                            $totalItems += $carts->sum('quantity');
                                        }
                                    @endphp
                                    <span class="d-block text-muted small">Total Items</span>
                                    <h3 class="mb-0 text-warning animated-value" id="stat-items">{{$totalItems}}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Secondary Stats Row -->
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <div class="d-flex align-items-center">
                        <i class="tio-bolt text-danger mr-2"></i>
                        <div>
                            @php
                                $hotCarts = 0;
                                foreach($latest_carts as $carts) {
                                    $age = \Carbon\Carbon::parse($carts->first()->created_at)->diffInMinutes(now());
                                    if ($age < 5) $hotCarts++;
                                }
                            @endphp
                            <span class="text-muted small">Hot Carts (<5m)</span>
                            <h5 class="mb-0 text-danger animated-value" id="stat-hot">{{$hotCarts}}</h5>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <div class="d-flex align-items-center">
                        <i class="tio-user text-primary mr-2"></i>
                        <div>
                            <span class="text-muted small">Active Customers</span>
                            <h5 class="mb-0 animated-value" id="stat-customers">{{$latest_carts->count()}}</h5>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <div class="d-flex align-items-center">
                        <i class="tio-shop text-success mr-2"></i>
                        <div>
                            @php
                                $storeIds = collect();
                                foreach($latest_carts as $userCarts) {
                                    foreach($userCarts as $cart) {
                                        $item = $items->get($cart->item_id);
                                        if ($item && $item->store_id) {
                                            $storeIds->push($item->store_id);
                                        }
                                    }
                                }
                                $uniqueStores = $storeIds->unique()->count();
                            @endphp
                            <span class="text-muted small">Active Stores</span>
                            <h5 class="mb-0 text-success animated-value" id="stat-stores">{{$uniqueStores}}</h5>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="d-flex align-items-center">
                        <i class="tio-chart-pie text-info mr-2"></i>
                        <div>
                            @php
                                $avgItemsPerCart = $latest_carts->count() > 0 ? $totalItems / $latest_carts->count() : 0;
                            @endphp
                            <span class="text-muted small">Avg Items/Cart</span>
                            <h5 class="mb-0 text-info animated-value" id="stat-avg-items">{{number_format($avgItemsPerCart, 1)}}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Refresh Controls -->
<div class="d-flex justify-content-end align-items-center mb-2">
    <button class="btn btn-sm btn-soft-success mr-2" onclick="toggleAutoRefresh()" id="auto-refresh-toggle">
        <i class="tio-play"></i>
        <span>Auto-Refresh ON</span>
    </button>
    <button class="btn btn-sm btn-white" onclick="refreshCarts(false)" id="refresh-btn">
        <i class="tio-refresh" id="refresh-icon"></i> Refresh Now
    </button>
</div>


    <!-- Cart Grid Container -->
    <div class="row" id="cart-grid-container">
        @include('admin-views.partials._customer-carts-grid', [
            'latest_carts' => $latest_carts,
            'users' => $users,
            'items' => $items
        ])
    </div>

    <!-- Show More Button -->
    <div class="text-center mt-3" id="show-more-section" style="{{$latest_carts->count() <= 8 ? 'display: none;' : ''}}">
        <button class="btn btn-white" onclick="toggleAllCarts()">
            <i class="tio-chevron-down" id="toggle-icon"></i>
            <span id="toggle-text">Show More</span>
        </button>
    </div>
</div>

<!-- Modal (Same as before) -->
<div class="modal fade" id="cartModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center w-100">
                    <div class="avatar avatar-lg avatar-circle mr-3" id="modal-avatar">
                        <img class="avatar-img" src="" alt="">
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <h5 class="modal-title mb-0 text-truncate">
                            <span id="modal-name"></span>'s Cart
                        </h5>
                        <span class="d-block text-muted small text-truncate" id="modal-contact"></span>
                    </div>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary ml-2" data-dismiss="modal">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>
            </div>
            
            <div class="modal-body border-bottom py-3">
                <div class="row text-center">
                    <div class="col-3">
                        <h5 class="mb-1" id="stats-items">0</h5>
                        <small class="text-muted">Items</small>
                    </div>
                    <div class="col-3">
                        <h5 class="mb-1" id="stats-stores">0</h5>
                        <small class="text-muted">Stores</small>
                    </div>
                    <div class="col-3">
                        <h5 class="mb-1" id="stats-age">0m</h5>
                        <small class="text-muted">Age</small>
                    </div>
                    <div class="col-3">
                        <h5 class="mb-1 text-primary" id="stats-avg">₹0</h5>
                        <small class="text-muted">Avg</small>
                    </div>
                </div>
            </div>
            
            <div class="modal-body">
                <h6 class="mb-3">Cart Items</h6>
                <div class="list-group list-group-flush" id="modal-items"></div>
            </div>
            
            <div class="modal-footer">
                <div class="row w-100 align-items-center gx-3">
                    <div class="col-sm-6 mb-2 mb-sm-0">
                        <div class="d-flex justify-content-between">
                            <span>Total:</span>
                            <h5 class="mb-0 text-primary" id="footer-total">₹0.00</h5>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <a href="#" id="view-customer-link" class="btn btn-primary btn-block">
                            <i class="tio-user"></i> View Customer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data -->
<script>
    let cartData = {!! json_encode($latest_carts) !!};
    let usersData = {!! json_encode($users) !!};
    let itemsData = {!! json_encode($items) !!};
</script>

<!-- Animated Styles -->
<style>
    .card-hover-shadow {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .card-hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-2px);
    }
    
    /* Animation for value changes */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .animated-value.updating {
        animation: pulse 0.5s ease;
        color: #00c9a7 !important;
    }
    
    /* Fade in animation for cards */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .cart-card-animated {
        animation: fadeInUp 0.5s ease;
    }
    
    /* Shimmer loading effect */
    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }
    
    .loading-shimmer {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 1000px 100%;
        animation: shimmer 2s infinite;
    }
    
    .product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 4px;
        height: 100px;
    }
    
    .product-img-box {
        position: relative;
        overflow: hidden;
        border-radius: 0.5rem;
    }
    
    .product-img-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .qty-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        font-size: 9px;
        padding: 2px 4px;
    }
    
    .more-items {
        background: #377dff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .more-text {
        color: white;
        font-size: 18px;
        font-weight: 600;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .spinning {
        animation: spin 1s linear infinite;
    }
    
    .min-width-0 {
        min-width: 0;
    }
    
    .text-truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .product-grid {
            height: 80px;
        }
        .more-text {
            font-size: 14px;
        }
    }
</style>

<!-- Enhanced JavaScript with Real-Time AJAX -->
<!-- Data -->
<script>
    let cartData = {!! json_encode($latest_carts) !!};
    let usersData = {!! json_encode($users) !!};
    let itemsData = {!! json_encode($items) !!};
</script>

<!-- Enhanced JavaScript with Auto-Refresh -->
<script>
    let isExpanded = false;
    let isRefreshing = false;
    let autoRefreshInterval = null;
    let autoRefreshEnabled = true; // Auto-refresh ON by default
    let autoRefreshSeconds = 40; // Refresh every 30 seconds
    
    // Animate number changes
    function animateValue(element, newValue) {
        if (!element) return;
        element.classList.add('updating');
        setTimeout(() => {
            element.textContent = newValue;
            setTimeout(() => {
                element.classList.remove('updating');
            }, 500);
        }, 100);
    }
    
    // Real-Time AJAX Refresh
    function refreshCarts(isAuto = false) {
        if (isRefreshing) return;
        
        console.log(isAuto ? '🔄 Auto-refresh triggered...' : '🔄 Manual refresh...');
        
        const btn = document.getElementById('refresh-btn');
        const icon = document.getElementById('refresh-icon');
        const liveStatus = document.getElementById('live-status');
        const gridContainer = document.getElementById('cart-grid-container');
        
        isRefreshing = true;
        if (!isAuto) {
            btn.disabled = true;
        }
        icon.classList.add('spinning');
        
        // Update live status
        liveStatus.innerHTML = '<i class="tio-refresh"></i> Updating...';
        liveStatus.classList.remove('badge-soft-success');
        liveStatus.classList.add('badge-soft-warning');
        
        fetch('/admin/order/cart-data', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.html) {
                // Update global data
                cartData = data.cartData || {};
                usersData = data.usersData || {};
                itemsData = data.itemsData || {};
                
                // Fade out old content
                gridContainer.style.opacity = '0.3';
                
                setTimeout(() => {
                    // Replace grid HTML
                    gridContainer.innerHTML = data.html;
                    
                    // Fade in new content
                    gridContainer.style.opacity = '1';
                    gridContainer.style.transition = 'opacity 0.5s ease';
                    
                    // Calculate and animate stats
                    let newTotal = 0;
                    Object.values(cartData).forEach(carts => {
                        carts.forEach(cart => {
                            newTotal += (cart.price || 0) * (cart.quantity || 0);
                        });
                    });
                    
                    const count = Object.keys(cartData).length;
                    const newAvg = count > 0 ? newTotal / count : 0;
                    
                    // Animate values
                    animateValue(document.getElementById('stat-count'), count);
                    animateValue(document.getElementById('stat-avg'), '₹' + newAvg.toFixed(2));
                    animateValue(document.getElementById('stat-total'), '₹' + newTotal.toFixed(2));
                    
                    // Update show more button
                    const showMoreSection = document.getElementById('show-more-section');
                    if (showMoreSection) {
                        showMoreSection.style.display = count > 8 ? 'block' : 'none';
                    }
                    
                    console.log('✅ Cart refreshed successfully');
                }, 300);
                
                // Success status
                setTimeout(() => {
                    liveStatus.innerHTML = '<i class="tio-checkmark-circle"></i> ' + (isAuto ? 'Auto-Updated' : 'Updated');
                    liveStatus.classList.remove('badge-soft-warning');
                    liveStatus.classList.add('badge-soft-success');
                    
                    setTimeout(() => {
                        liveStatus.innerHTML = '<i class="tio-checkmark-circle"></i> Live';
                    }, 2000);
                }, 800);
            }
        })
        .catch(error => {
            console.error('❌ Error refreshing carts:', error);
            liveStatus.innerHTML = '<i class="tio-error"></i> Error';
            liveStatus.classList.remove('badge-soft-warning');
            liveStatus.classList.add('badge-soft-danger');
            
            setTimeout(() => {
                liveStatus.innerHTML = '<i class="tio-checkmark-circle"></i> Live';
                liveStatus.classList.remove('badge-soft-danger');
                liveStatus.classList.add('badge-soft-success');
            }, 3000);
        })
        .finally(() => {
            isRefreshing = false;
            if (!isAuto) {
                btn.disabled = false;
            }
            icon.classList.remove('spinning');
        });
    }
    
    // Toggle Auto-Refresh
    function toggleAutoRefresh() {
        autoRefreshEnabled = !autoRefreshEnabled;
        const toggleBtn = document.getElementById('auto-refresh-toggle');
        const toggleIcon = toggleBtn.querySelector('i');
        const toggleText = toggleBtn.querySelector('span');
        
        if (autoRefreshEnabled) {
            startAutoRefresh();
            toggleBtn.classList.remove('btn-soft-secondary');
            toggleBtn.classList.add('btn-soft-success');
            toggleIcon.classList.remove('tio-pause');
            toggleIcon.classList.add('tio-play');
            toggleText.textContent = 'Auto-Refresh ON';
            console.log('✅ Auto-refresh enabled (every ' + autoRefreshSeconds + 's)');
        } else {
            stopAutoRefresh();
            toggleBtn.classList.remove('btn-soft-success');
            toggleBtn.classList.add('btn-soft-secondary');
            toggleIcon.classList.remove('tio-play');
            toggleIcon.classList.add('tio-pause');
            toggleText.textContent = 'Auto-Refresh OFF';
            console.log('⏸️ Auto-refresh disabled');
        }
    }
    
    // Start Auto-Refresh
    function startAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        
        autoRefreshInterval = setInterval(function() {
            if (autoRefreshEnabled) {
                refreshCarts(true);
            }
        }, autoRefreshSeconds * 1000);
    }
    
    // Stop Auto-Refresh
    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }
    
    // Modal functions
    function showCartDetails(userId, customerId) {
        const customer = usersData[userId];
        const carts = cartData[userId];
        
        if (!customer || !carts) return;
        
        document.getElementById('modal-name').textContent = customer.f_name + ' ' + customer.l_name;
        document.getElementById('modal-contact').textContent = customer.phone || 'N/A';
        document.getElementById('modal-avatar').querySelector('img').src =
            customer.image_full_url || '{{asset('public/assets/admin/img/160x160/img1.jpg')}}';
        document.getElementById('view-customer-link').href = `/admin/customer/view/${customerId}`;
        
        let total = 0;
        let items = 0;
        let stores = new Set();
        let itemsHtml = '';
        
        carts.forEach(cart => {
            const item = itemsData[cart.item_id];
            if (item) {
                const itemTotal = cart.price * cart.quantity;
                total += itemTotal;
                items += cart.quantity;
                if (item.store_id) stores.add(item.store_id);
                
                itemsHtml += `
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <img class="avatar rounded"
                                     style="width: 60px; height: 60px; object-fit: cover;"
                                     onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                                     src="${item.image_full_url || ''}"
                                     alt="${item.name}">
                            </div>
                            <div class="col">
                                <h6 class="mb-1 text-truncate">${item.name}</h6>
                                <small class="text-muted">₹${cart.price} each</small>
                            </div>
                            <div class="col-auto text-right">
                                <span class="badge badge-primary mb-1">${cart.quantity}x</span>
                                <div class="text-primary font-weight-bold">₹${itemTotal.toFixed(2)}</div>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
        
        const minutesAgo = Math.floor((new Date() - new Date(carts[0].created_at)) / 60000);
        const avgPrice = items > 0 ? total / items : 0;
        
        document.getElementById('stats-items').textContent = items;
        document.getElementById('stats-stores').textContent = stores.size;
        document.getElementById('stats-age').textContent = minutesAgo < 60 ? minutesAgo + 'm' : Math.floor(minutesAgo/60) + 'h';
        document.getElementById('stats-avg').textContent = '₹' + avgPrice.toFixed(2);
        document.getElementById('modal-items').innerHTML = itemsHtml;
        document.getElementById('footer-total').textContent = '₹' + total.toFixed(2);
        
        $('#cartModal').modal('show');
    }
    
    function toggleAllCarts() {
        const cards = document.querySelectorAll('.cart-card-item');
        const icon = document.getElementById('toggle-icon');
        const text = document.getElementById('toggle-text');
        
        if (!isExpanded) {
            cards.forEach(card => card.style.display = 'block');
            icon.classList.remove('tio-chevron-down');
            icon.classList.add('tio-chevron-up');
            text.textContent = 'Show Less';
            isExpanded = true;
        } else {
            cards.forEach((card, index) => {
                if (index >= 8) card.style.display = 'none';
            });
            icon.classList.remove('tio-chevron-up');
            icon.classList.add('tio-chevron-down');
            text.textContent = 'Show More';
            isExpanded = false;
        }
    }
    
    // Initialize auto-refresh on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (autoRefreshEnabled) {
            startAutoRefresh();
            console.log('✅ Auto-refresh started (every ' + autoRefreshSeconds + ' seconds)');
        }
    });
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        stopAutoRefresh();
    });
</script>

@endif
