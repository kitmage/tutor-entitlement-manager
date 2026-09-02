<?php
namespace Kitmage\TrainingEntitlements\Service;

final class TokenService {
	public function generate() { return rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' ); }
	public function hash( $token ) { return hash_hmac( 'sha256', (string) $token, wp_salt( 'auth' ) ); }
	public function encrypt( $token ) { $key=hash('sha256',wp_salt('secure_auth'),true);$iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($token,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);return rtrim(strtr(base64_encode($iv.$tag.$cipher),'+/','-_'),'='); }
	public function decrypt( $value ) { $raw=base64_decode(strtr($value,'-_','+/'),true);if(false===$raw||strlen($raw)<29)return ''; $key=hash('sha256',wp_salt('secure_auth'),true);return (string)openssl_decrypt(substr($raw,28),'aes-256-gcm',$key,OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16)); }
	public function url( $token ) { return apply_filters( 'kitmage_training_entitlements/redemption_url', home_url( '/training/redeem/' . rawurlencode( $token ) . '/' ), $token ); }
}
