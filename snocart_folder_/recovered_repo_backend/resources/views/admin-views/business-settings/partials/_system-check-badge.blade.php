@if($status === 'ok')
    <span class="badge badge-success">{{ translate('OK') }}</span>
@elseif($status === 'warning')
    <span class="badge badge-warning">{{ translate('Warning') }}</span>
@else
    <span class="badge badge-danger">{{ translate('Error') }}</span>
@endif
