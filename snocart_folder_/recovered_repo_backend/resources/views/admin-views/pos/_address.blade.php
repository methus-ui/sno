@if (session()->has('address'))
    @php
        $address = session()->get('address')
    @endphp
    
    <div class="delivery-address-card">
        <div class="delivery-address-header">
            <div class="delivery-icon-wrapper">
                <i class="tio-user-outlined"></i>
            </div>
            <div class="delivery-address-info">
                <h6 class="customer-name mb-1">{{ $address['contact_person_name'] }}</h6>
                <a href="tel:{{ $address['contact_person_number'] }}" class="contact-number">
                    <i class="tio-call-outlined"></i>
                    {{ $address['contact_person_number'] }}
                </a>
            </div>
        </div>
        
        @if(isset($address['address']) && $address['address'])
        <div class="delivery-address-location">
            <div class="location-icon">
                <i class="tio-poi"></i>
            </div>
            <div class="location-text">
                {{ $address['address'] }}
                @if(isset($address['road']) || isset($address['house']) || isset($address['floor']))
                    <div class="location-details mt-1">
                        @if(isset($address['house']))
                            <span class="location-detail-item">
                                <i class="tio-home-outlined"></i> {{ $address['house'] }}
                            </span>
                        @endif
                        @if(isset($address['road']))
                            <span class="location-detail-item">
                                <i class="tio-directions-outlined"></i> {{ $address['road'] }}
                            </span>
                        @endif
                        @if(isset($address['floor']))
                            <span class="location-detail-item">
                                <i class="tio-building-outlined"></i> Floor {{ $address['floor'] }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        @endif
        
        @if(isset($address['delivery_fee']))
        <div class="delivery-fee-badge">
            <span class="delivery-fee-label">{{ translate('Delivery Fee') }}</span>
            <span class="delivery-fee-amount">{{ \App\CentralLogics\Helpers::format_currency($address['delivery_fee']) }}</span>
        </div>
        @endif
    </div>
@else
    <div class="no-delivery-address">
        <div class="no-address-icon">
            <i class="tio-poi"></i>
        </div>
        <p class="no-address-text">{{ translate('No delivery address set') }}</p>
        <small class="no-address-hint">{{ translate('Click the edit icon to add delivery address') }}</small>
    </div>
@endif

<style>
.delivery-address-card {
    background: var(--color-surface, #FFFFFF);
    border-radius: 8px;
    padding: 16px;
    border: 1px solid #E5E7EB;
    transition: all 0.2s ease;
}

.delivery-address-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.delivery-icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.delivery-icon-wrapper i {
    color: white;
    font-size: 20px;
}

.delivery-address-info {
    flex: 1;
}

.customer-name {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.contact-number {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: #6B7280;
    text-decoration: none;
    transition: color 0.2s ease;
}

.contact-number:hover {
    color: #2563EB;
}

.contact-number i {
    font-size: 14px;
}

.delivery-address-location {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #F9FAFB;
    border-radius: 6px;
    margin-bottom: 12px;
}

.location-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.location-icon i {
    color: #EF4444;
    font-size: 18px;
}

.location-text {
    flex: 1;
    font-size: 14px;
    color: #374151;
    line-height: 1.5;
}

.location-details {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.location-detail-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #6B7280;
    padding: 4px 8px;
    background: white;
    border-radius: 4px;
}

.location-detail-item i {
    font-size: 12px;
    color: #9CA3AF;
}

.delivery-fee-badge {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    border-radius: 6px;
    color: white;
}

.delivery-fee-label {
    font-size: 13px;
    font-weight: 500;
}

.delivery-fee-amount {
    font-size: 16px;
    font-weight: 700;
    font-family: 'Roboto Mono', monospace;
}

.no-delivery-address {
    text-align: center;
    padding: 32px 16px;
    background: #F9FAFB;
    border-radius: 8px;
    border: 2px dashed #E5E7EB;
}

.no-address-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 16px;
    border-radius: 50%;
    background: #FEE2E2;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-address-icon i {
    font-size: 28px;
    color: #EF4444;
}

.no-address-text {
    font-size: 15px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 4px;
}

.no-address-hint {
    font-size: 13px;
    color: #6B7280;
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .delivery-address-card {
        padding: 12px;
    }
    
    .delivery-icon-wrapper {
        width: 36px;
        height: 36px;
    }
    
    .delivery-icon-wrapper i {
        font-size: 18px;
    }
    
    .customer-name {
        font-size: 15px;
    }
    
    .location-details {
        flex-direction: column;
        gap: 6px;
    }
}
</style>
