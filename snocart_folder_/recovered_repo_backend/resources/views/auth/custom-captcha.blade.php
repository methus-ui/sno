<div class="captcha-row">
    <input
        type="text"
        class="form-input"
        name="custome_recaptcha"
        id="custome_recaptcha"
        required
        placeholder="{{translate('Enter captcha')}}"
        autocomplete="off"
        value="{{env('APP_MODE')=='dev'? session('six_captcha'):''}}"
    >
    <div class="captcha-image">
        <img src="<?php echo $custome_recaptcha->inline(); ?>" alt="captcha">
        <span class="refresh-captcha reloadCaptcha"><i class="tio-cached"></i></span>
    </div>
</div>
